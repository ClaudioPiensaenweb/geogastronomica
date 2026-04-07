<?php
/**
 * Cron Manager — Caducidad automatica de anuncios.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona el cron event para marcar anuncios caducados como draft.
 */
class Cron_Manager {

	/**
	 * Hook name del cron event.
	 */
	public const CRON_HOOK = 'geo_check_expired';

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( self::CRON_HOOK, array( $this, 'check_expired_ads' ) );
		add_action( 'save_post_' . CPT_Anuncio::POST_TYPE, array( $this, 'validate_dates' ), 10, 2 );
	}

	/**
	 * Programar cron event al activar el plugin.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Desprogramar cron event al desactivar el plugin.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Verificar anuncios caducados y marcar como draft.
	 */
	public function check_expired_ads(): void {
		$today = current_time( 'Y-m-d' );

		$expired = new \WP_Query(
			array(
				'post_type'      => CPT_Anuncio::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_geo_fecha_fin',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => '_geo_fecha_fin',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			)
		);

		foreach ( $expired->posts as $post_id ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * Validar que fecha_fin no sea anterior a fecha_inicio.
	 *
	 * @param int      $post_id ID del post.
	 * @param \WP_Post $post    Post actual.
	 */
	public function validate_dates( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$fecha_inicio = get_post_meta( $post_id, '_geo_fecha_comienzo', true );
		$fecha_fin    = get_post_meta( $post_id, '_geo_fecha_fin', true );

		if ( $fecha_inicio && $fecha_fin && $fecha_fin < $fecha_inicio ) {
			set_transient(
				'geo_date_warning_' . $post_id,
				esc_html__( 'Advertencia: La fecha fin es anterior a la fecha de inicio.', 'geogastronomica' ),
				30
			);
			add_action( 'admin_notices', function () use ( $post_id ) {
				$msg = get_transient( 'geo_date_warning_' . $post_id );
				if ( $msg ) {
					echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
					delete_transient( 'geo_date_warning_' . $post_id );
				}
			} );
		}
	}
}
