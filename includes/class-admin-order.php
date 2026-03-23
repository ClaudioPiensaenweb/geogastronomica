<?php
/**
 * Pagina de ordenacion drag & drop para anuncios activos.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submenu "Orden" con drag & drop para reordenar anuncios.
 */
class Admin_Order {

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'wp_ajax_geo_save_order', array( $this, 'ajax_save_order' ) );
	}

	/**
	 * Anadir submenu.
	 */
	public function add_submenu(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=' . CPT_Anuncio::POST_TYPE,
			esc_html__( 'Orden de anuncios', 'geogastronomica' ),
			esc_html__( 'Orden', 'geogastronomica' ),
			'edit_posts',
			'geogastronomica-order',
			array( $this, 'render_page' )
		);

		add_action( 'admin_print_scripts-' . $hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Encolar assets.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'geo-admin-order',
			GEO_PLUGIN_URL . 'assets/js/admin-order.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			GeoGastronomica::VERSION,
			true
		);

		wp_localize_script( 'geo-admin-order', 'geoOrderData', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'geo_save_order' ),
		) );

		wp_enqueue_style(
			'geo-admin-order',
			GEO_PLUGIN_URL . 'assets/css/admin-order.css',
			array(),
			GeoGastronomica::VERSION
		);
	}

	/**
	 * Renderizar pagina de orden.
	 */
	public function render_page(): void {
		$active_ads = new \WP_Query( array(
			'post_type'      => CPT_Anuncio::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Orden de anuncios', 'geogastronomica' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Arrastra y suelta para cambiar el orden. El primero de la lista tiene mas prioridad.', 'geogastronomica' ); ?>
			</p>

			<?php if ( $active_ads->have_posts() ) : ?>
				<div id="geo-order-notice" class="notice notice-success" style="display:none;">
					<p><?php esc_html_e( 'Orden guardado correctamente.', 'geogastronomica' ); ?></p>
				</div>

				<ul id="geo-sortable-list" class="geo-order-list">
					<?php
					$position = 1;
					while ( $active_ads->have_posts() ) :
						$active_ads->the_post();
						$post_id  = get_the_ID();
						$empresa  = get_post_meta( $post_id, '_geo_empresa_nombre', true );
						$desc     = get_post_meta( $post_id, '_geo_descripcion', true );
						$fecha_fin = get_post_meta( $post_id, '_geo_fecha_fin', true );
						$today    = current_time( 'Y-m-d' );
						$status   = ( $fecha_fin && $fecha_fin < $today ) ? 'caducado' : 'activo';

						// Zonas asignadas.
						$zonas = array();
						foreach ( array( '_geo_anuncio_home', '_geo_anuncio_categoria', '_geo_anuncio_subcategoria' ) as $zk ) {
							$z = get_post_meta( $post_id, $zk, true );
							if ( is_array( $z ) && ! empty( $z ) ) {
								$page_label = str_replace( '_geo_anuncio_', '', $zk );
								foreach ( $z as $slot ) {
									$zonas[] = $page_label . '_' . $slot;
								}
							}
						}
						?>
						<li class="geo-order-item <?php echo esc_attr( $status ); ?>"
						    data-id="<?php echo esc_attr( $post_id ); ?>">
							<span class="geo-order-handle dashicons dashicons-menu"></span>
							<span class="geo-order-position"><?php echo esc_html( $position ); ?></span>
							<div class="geo-order-info">
								<strong><?php echo esc_html( $empresa ?: __( 'Sin empresa', 'geogastronomica' ) ); ?></strong>
								<?php if ( $desc ) : ?>
									<span class="geo-order-desc"><?php echo esc_html( $desc ); ?></span>
								<?php endif; ?>
							</div>
							<div class="geo-order-zones">
								<?php foreach ( $zonas as $z ) : ?>
									<span class="geo-order-zone-tag"><?php echo esc_html( $z ); ?></span>
								<?php endforeach; ?>
							</div>
							<?php if ( 'caducado' === $status ) : ?>
								<span class="geo-order-badge caducado"><?php esc_html_e( 'Caducado', 'geogastronomica' ); ?></span>
							<?php endif; ?>
						</li>
						<?php
						$position++;
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No hay anuncios activos para ordenar.', 'geogastronomica' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX: Guardar nuevo orden.
	 */
	public function ajax_save_order(): void {
		check_ajax_referer( 'geo_save_order', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'No tienes permisos.' );
		}

		$order = isset( $_POST['order'] ) && is_array( $_POST['order'] )
			? array_map( 'absint', $_POST['order'] )
			: array();

		if ( empty( $order ) ) {
			wp_send_json_error( 'Orden vacio.' );
		}

		global $wpdb;
		foreach ( $order as $position => $post_id ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => (int) $position ),
				array( 'ID' => (int) $post_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		// Invalidar cache.
		$cache = new Cache_Manager();
		$cache->flush_all_zone_transients();

		wp_send_json_success( 'Orden guardado.' );
	}
}
