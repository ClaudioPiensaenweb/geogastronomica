<?php
/**
 * Registra el Custom Post Type geo_anuncio.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para registrar y configurar el CPT de anuncios.
 */
class CPT_Anuncio {

	/**
	 * Slug del CPT.
	 */
	public const POST_TYPE = 'geo_anuncio';

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Registrar el Custom Post Type.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'                  => esc_html__( 'Anuncios', 'geogastronomica' ),
			'singular_name'         => esc_html__( 'Anuncio', 'geogastronomica' ),
			'menu_name'             => esc_html__( 'GeoGastronomica', 'geogastronomica' ),
			'add_new'               => esc_html__( 'Nuevo anuncio', 'geogastronomica' ),
			'add_new_item'          => esc_html__( 'Anadir nuevo anuncio', 'geogastronomica' ),
			'edit_item'             => esc_html__( 'Editar anuncio', 'geogastronomica' ),
			'new_item'              => esc_html__( 'Nuevo anuncio', 'geogastronomica' ),
			'view_item'             => esc_html__( 'Ver anuncio', 'geogastronomica' ),
			'search_items'          => esc_html__( 'Buscar anuncios', 'geogastronomica' ),
			'not_found'             => esc_html__( 'No se encontraron anuncios', 'geogastronomica' ),
			'not_found_in_trash'    => esc_html__( 'No hay anuncios en la papelera', 'geogastronomica' ),
			'all_items'             => esc_html__( 'Todos los anuncios', 'geogastronomica' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		);

		$result = register_post_type( self::POST_TYPE, $args );

		if ( is_wp_error( $result ) ) {
			error_log( 'GeoGastronomica: Error registrando CPT — ' . $result->get_error_message() );
		}
	}
}
