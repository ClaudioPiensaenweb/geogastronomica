<?php
/**
 * Columnas admin personalizadas para el listado de anuncios.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Personaliza columnas del listado y acciones en lote.
 */
class Admin_Columns {

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_filter( 'manage_' . CPT_Anuncio::POST_TYPE . '_posts_columns', array( $this, 'set_columns' ) );
		add_action( 'manage_' . CPT_Anuncio::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . CPT_Anuncio::POST_TYPE . '_sortable_columns', array( $this, 'set_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_filter( 'bulk_actions-edit-' . CPT_Anuncio::POST_TYPE, array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-' . CPT_Anuncio::POST_TYPE, array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
	}

	/**
	 * Definir columnas del listado.
	 *
	 * @param array $columns Columnas existentes.
	 * @return array Columnas modificadas.
	 */
	public function set_columns( array $columns ): array {
		$new = array();
		$new['cb']              = $columns['cb'];
		$new['title']           = $columns['title'];
		$new['geo_empresa']     = esc_html__( 'Empresa', 'geogastronomica' );
		$new['geo_descripcion'] = esc_html__( 'Descripcion anuncio', 'geogastronomica' );
		$new['geo_fecha_ini']   = esc_html__( 'Fecha comienzo', 'geogastronomica' );
		$new['geo_fecha_fin']   = esc_html__( 'Fecha fin', 'geogastronomica' );
		$new['geo_estado']      = esc_html__( 'Estado', 'geogastronomica' );
		$new['date']            = $columns['date'];

		return $new;
	}

	/**
	 * Renderizar contenido de columna personalizada.
	 *
	 * @param string $column  Nombre de la columna.
	 * @param int    $post_id ID del post.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'geo_empresa':
				$val = get_post_meta( $post_id, '_geo_empresa_nombre', true );
				echo $val ? esc_html( $val ) : '&mdash;';
				break;

			case 'geo_descripcion':
				$val = get_post_meta( $post_id, '_geo_descripcion', true );
				echo $val ? esc_html( $val ) : '&mdash;';
				break;

			case 'geo_fecha_ini':
				$val = get_post_meta( $post_id, '_geo_fecha_comienzo', true );
				echo $val ? esc_html( $val ) : '&mdash;';
				break;

			case 'geo_fecha_fin':
				$val = get_post_meta( $post_id, '_geo_fecha_fin', true );
				echo $val ? esc_html( $val ) : esc_html__( 'Permanente', 'geogastronomica' );
				break;

			case 'geo_estado':
				echo $this->get_status_badge( $post_id );
				break;
		}
	}

	/**
	 * Calcular y renderizar badge de estado.
	 *
	 * @param int $post_id ID del post.
	 * @return string HTML del badge.
	 */
	private function get_status_badge( int $post_id ): string {
		$today        = current_time( 'Y-m-d' );
		$fecha_inicio = get_post_meta( $post_id, '_geo_fecha_comienzo', true );
		$fecha_fin    = get_post_meta( $post_id, '_geo_fecha_fin', true );
		$post_status  = get_post_status( $post_id );

		if ( 'draft' === $post_status ) {
			return '<span style="color:#999">' . esc_html__( 'Borrador', 'geogastronomica' ) . '</span>';
		}

		if ( $fecha_fin && $fecha_fin < $today ) {
			return '<span style="color:#dc3232;font-weight:600">' . esc_html__( 'Caducado', 'geogastronomica' ) . '</span>';
		}

		if ( $fecha_inicio && $fecha_inicio > $today ) {
			return '<span style="color:#f0b849">' . esc_html__( 'Programado', 'geogastronomica' ) . '</span>';
		}

		return '<span style="color:#46b450;font-weight:600">' . esc_html__( 'Activo', 'geogastronomica' ) . '</span>';
	}

	/**
	 * Definir columnas ordenables.
	 *
	 * @param array $columns Columnas ordenables.
	 * @return array Columnas modificadas.
	 */
	public function set_sortable( array $columns ): array {
		$columns['geo_fecha_ini'] = 'geo_fecha_ini';
		$columns['geo_fecha_fin'] = 'geo_fecha_fin';
		$columns['geo_empresa']   = 'geo_empresa';
		return $columns;
	}

	/**
	 * Manejar ordenamiento por meta value.
	 *
	 * @param \WP_Query $query Query actual.
	 */
	public function handle_sorting( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . CPT_Anuncio::POST_TYPE !== $screen->id ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		$meta_map = array(
			'geo_fecha_ini' => '_geo_fecha_comienzo',
			'geo_fecha_fin' => '_geo_fecha_fin',
			'geo_empresa'   => '_geo_empresa_nombre',
		);

		if ( isset( $meta_map[ $orderby ] ) ) {
			$query->set( 'meta_key', $meta_map[ $orderby ] );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Registrar acciones en lote.
	 *
	 * @param array $actions Acciones existentes.
	 * @return array Acciones modificadas.
	 */
	public function register_bulk_actions( array $actions ): array {
		$actions['geo_activate']   = esc_html__( 'Activar (publicar)', 'geogastronomica' );
		$actions['geo_deactivate'] = esc_html__( 'Desactivar (borrador)', 'geogastronomica' );
		return $actions;
	}

	/**
	 * Manejar acciones en lote.
	 *
	 * @param string $redirect_to URL de redireccion.
	 * @param string $action      Accion ejecutada.
	 * @param array  $post_ids    IDs seleccionados.
	 * @return string URL de redireccion.
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( ! in_array( $action, array( 'geo_activate', 'geo_deactivate' ), true ) ) {
			return $redirect_to;
		}

		$new_status = 'geo_activate' === $action ? 'publish' : 'draft';
		$count      = 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_status' => $new_status,
				)
			);
			$count++;
		}

		return add_query_arg( 'geo_bulk_updated', $count, $redirect_to );
	}

	/**
	 * Mostrar notice despues de accion en lote.
	 */
	public function bulk_action_notices(): void {
		if ( ! isset( $_GET['geo_bulk_updated'] ) ) {
			return;
		}

		$count = absint( $_GET['geo_bulk_updated'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: numero de anuncios actualizados */
					_n( '%d anuncio actualizado.', '%d anuncios actualizados.', $count, 'geogastronomica' ),
					$count
				)
			)
		);
	}
}
