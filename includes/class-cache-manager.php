<?php
/**
 * Cache Manager — Transient API para anuncios activos.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona cache de IDs de anuncios por zona con Transient API.
 */
class Cache_Manager {

	/**
	 * Prefijo de transients.
	 */
	private const TRANSIENT_PREFIX = 'geoad_zone_';

	/**
	 * TTL del cache en segundos (1 hora).
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Inicializar hooks de invalidacion.
	 */
	public function init(): void {
		add_action( 'save_post_' . CPT_Anuncio::POST_TYPE, array( $this, 'invalidate_on_save' ), 20 );
		add_action( 'trashed_post', array( $this, 'invalidate_on_trash' ) );
		add_action( 'untrashed_post', array( $this, 'invalidate_on_trash' ) );
	}

	/**
	 * Obtener IDs de anuncios desde cache o query.
	 *
	 * @param string $zone     Nombre de la zona.
	 * @param callable $query_fn Funcion que ejecuta la query si no hay cache.
	 * @return array Array de post IDs.
	 */
	public function get_or_query( string $zone, callable $query_fn ): array {
		$key    = self::TRANSIENT_PREFIX . $zone;
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return (array) $cached;
		}

		$ids = $query_fn();
		set_transient( $key, $ids, self::CACHE_TTL );

		return $ids;
	}

	/**
	 * Invalidar transients al guardar un anuncio.
	 *
	 * @param int $post_id ID del post guardado.
	 */
	public function invalidate_on_save( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$this->flush_all_zone_transients();
	}

	/**
	 * Invalidar transients al enviar a papelera.
	 *
	 * @param int $post_id ID del post.
	 */
	public function invalidate_on_trash( int $post_id ): void {
		if ( get_post_type( $post_id ) !== CPT_Anuncio::POST_TYPE ) {
			return;
		}
		$this->flush_all_zone_transients();
	}

	/**
	 * Eliminar todos los transients de zonas.
	 */
	public function flush_all_zone_transients(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::TRANSIENT_PREFIX . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);
	}
}
