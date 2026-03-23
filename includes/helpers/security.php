<?php
/**
 * Helpers de seguridad.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verificar nonce y capability en una sola llamada.
 *
 * @param string $nonce_action Accion del nonce.
 * @param string $nonce_field  Nombre del campo nonce en $_POST.
 * @param string $capability   Capability requerida.
 * @param int    $post_id      ID del post (para capabilities con contexto).
 * @return bool True si pasa ambas verificaciones.
 */
function geo_verify_request( string $nonce_action, string $nonce_field, string $capability = 'edit_post', int $post_id = 0 ): bool {
	// Verificar nonce.
	if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( $_POST[ $nonce_field ], $nonce_action ) ) {
		return false;
	}

	// Verificar capability.
	if ( $post_id > 0 ) {
		return current_user_can( $capability, $post_id );
	}

	return current_user_can( $capability );
}
