<?php
/**
 * Uninstall GeoGastronomica.
 *
 * Se ejecuta cuando el plugin se desinstala desde wp-admin.
 * Limpia todos los datos del plugin de la base de datos.
 * Orden: posts del CPT → tabla custom stats → transients → opciones.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Eliminar todos los posts del CPT geo_anuncio y su meta.
$post_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
		'geo_anuncio'
	)
);

if ( ! empty( $post_ids ) ) {
	$ids_placeholder = implode( ',', array_map( 'absint', $post_ids ) );

	// Eliminar post meta.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$ids_placeholder})" );

	// Eliminar posts.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$ids_placeholder})" );
}

// 2. Eliminar tabla custom de estadisticas.
$stats_table = $wpdb->prefix . 'geoad_stats';
$wpdb->query( "DROP TABLE IF EXISTS {$stats_table}" );

// 3. Eliminar transients del plugin.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_geoad_%',
		'_transient_timeout_geoad_%'
	)
);

// 4. Eliminar transients de warnings.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_geo_date_warning_%',
		'_transient_timeout_geo_date_warning_%'
	)
);

// 5. Limpiar cron events.
wp_clear_scheduled_hook( 'geo_check_expired' );
wp_clear_scheduled_hook( 'geo_aggregate_stats' );
