<?php
/**
 * Stats Tracker — Tabla custom y logica de estadisticas.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona la tabla custom de estadisticas y el cron de agregacion.
 */
class Stats_Tracker {

	/**
	 * Hook del cron de agregacion.
	 */
	public const CRON_HOOK = 'geo_aggregate_stats';

	/**
	 * Dias de retencion de eventos raw.
	 */
	private const RAW_RETENTION_DAYS = 30;

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( self::CRON_HOOK, array( $this, 'aggregate_and_purge' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_stats_meta_box' ) );
	}

	/**
	 * Obtener nombre de la tabla.
	 *
	 * @return string Nombre completo con prefijo.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'geoad_stats';
	}

	/**
	 * Crear tabla al activar el plugin.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			event_type varchar(20) NOT NULL DEFAULT 'impression',
			event_date date NOT NULL,
			count int(11) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_post_event_date (post_id, event_type, event_date)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Eliminar tabla al desinstalar.
	 */
	public static function drop_table(): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	/**
	 * Registrar un evento (impression o click).
	 *
	 * @param int    $post_id    ID del anuncio.
	 * @param string $event_type Tipo: 'impression' o 'click'.
	 * @return bool True si se registro correctamente.
	 */
	public function record_event( int $post_id, string $event_type ): bool {
		global $wpdb;

		if ( ! in_array( $event_type, array( 'impression', 'click' ), true ) ) {
			return false;
		}

		if ( get_post_type( $post_id ) !== CPT_Anuncio::POST_TYPE ) {
			return false;
		}

		$table = self::table_name();
		$today = current_time( 'Y-m-d' );

		// Upsert: incrementar si ya existe registro para hoy.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE post_id = %d AND event_type = %s AND event_date = %s",
				$post_id,
				$event_type,
				$today
			)
		);

		if ( $existing ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET count = count + 1 WHERE id = %d",
					$existing
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'post_id'    => $post_id,
					'event_type' => $event_type,
					'event_date' => $today,
					'count'      => 1,
				),
				array( '%d', '%s', '%s', '%d' )
			);
		}

		return true;
	}

	/**
	 * Obtener totales de un anuncio.
	 *
	 * @param int $post_id ID del anuncio.
	 * @return array { impressions: int, clicks: int, ctr: float }
	 */
	public function get_totals( int $post_id ): array {
		global $wpdb;
		$table = self::table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, SUM(count) as total FROM {$table} WHERE post_id = %d GROUP BY event_type",
				$post_id
			)
		);

		$totals = array( 'impressions' => 0, 'clicks' => 0, 'ctr' => 0.0 );
		foreach ( $results as $row ) {
			if ( 'impression' === $row->event_type ) {
				$totals['impressions'] = (int) $row->total;
			} elseif ( 'click' === $row->event_type ) {
				$totals['clicks'] = (int) $row->total;
			}
		}

		if ( $totals['impressions'] > 0 ) {
			$totals['ctr'] = round( ( $totals['clicks'] / $totals['impressions'] ) * 100, 2 );
		}

		return $totals;
	}

	/**
	 * Agregar datos y purgar registros antiguos.
	 */
	public function aggregate_and_purge(): void {
		global $wpdb;
		$table    = self::table_name();
		$cutoff   = gmdate( 'Y-m-d', strtotime( '-' . self::RAW_RETENTION_DAYS . ' days' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE event_date < %s",
				$cutoff
			)
		);
	}

	/**
	 * Programar cron de agregacion.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Desprogramar cron.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Registrar meta box de estadisticas.
	 */
	public function register_stats_meta_box(): void {
		add_meta_box(
			'geo_anuncio_stats',
			esc_html__( 'Estadisticas', 'geogastronomica' ),
			array( $this, 'render_stats_meta_box' ),
			CPT_Anuncio::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Renderizar meta box de estadisticas.
	 *
	 * @param \WP_Post $post Post actual.
	 */
	public function render_stats_meta_box( \WP_Post $post ): void {
		$totals = $this->get_totals( $post->ID );

		if ( 0 === $totals['impressions'] && 0 === $totals['clicks'] ) {
			echo '<p>' . esc_html__( 'Sin datos aun', 'geogastronomica' ) . '</p>';
			return;
		}

		?>
		<table class="widefat" style="border:0">
			<tr>
				<td><strong><?php esc_html_e( 'Impresiones', 'geogastronomica' ); ?></strong></td>
				<td><?php echo esc_html( number_format_i18n( $totals['impressions'] ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Clicks', 'geogastronomica' ); ?></strong></td>
				<td><?php echo esc_html( number_format_i18n( $totals['clicks'] ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'CTR', 'geogastronomica' ); ?></strong></td>
				<td><?php echo esc_html( $totals['ctr'] . '%' ); ?></td>
			</tr>
		</table>
		<?php
	}
}
