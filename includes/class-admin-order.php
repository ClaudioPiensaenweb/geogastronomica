<?php
/**
 * Pagina de ordenacion drag & drop para anuncios, agrupados por zona.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submenu "Orden" con drag & drop para reordenar anuncios por zona.
 */
class Admin_Order {

	/**
	 * Todas las zonas con labels legibles.
	 */
	private const ZONE_MAP = array(
		'home_vertical_1'         => 'Inicio — Vertical 1',
		'home_horizontal_1'       => 'Inicio — Horizontal 1',
		'home_horizontal_2'       => 'Inicio — Horizontal 2',
		'categoria_horizontal_1'  => 'Categoria — Horizontal 1',
		'categoria_horizontal_2'  => 'Categoria — Horizontal 2',
		'subcategoria_vertical_1' => 'Subcategoria — Vertical 1',
		'subcategoria_vertical_2' => 'Subcategoria — Vertical 2',
		'subcategoria_vertical_3' => 'Subcategoria — Vertical 3',
		'subcategoria_horizontal_1' => 'Subcategoria — Horizontal 1',
	);

	/**
	 * Meta keys por pagina.
	 */
	private const PAGE_META = array(
		'home'         => '_geo_anuncio_home',
		'categoria'    => '_geo_anuncio_categoria',
		'subcategoria' => '_geo_anuncio_subcategoria',
	);

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
	 * Obtener anuncios publicados indexados por zona.
	 *
	 * @return array { zona_completa => [ post_id, ... ] }
	 */
	private function get_ads_by_zone(): array {
		$query = new \WP_Query( array(
			'post_type'      => CPT_Anuncio::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );

		$by_zone = array();
		foreach ( self::ZONE_MAP as $zone_key => $label ) {
			$by_zone[ $zone_key ] = array();
		}

		foreach ( $query->posts as $post_id ) {
			foreach ( self::PAGE_META as $page => $meta_key ) {
				$slots = get_post_meta( $post_id, $meta_key, true );
				if ( ! is_array( $slots ) ) {
					continue;
				}
				foreach ( $slots as $slot ) {
					$zone_key = $page . '_' . $slot;
					if ( isset( $by_zone[ $zone_key ] ) ) {
						$by_zone[ $zone_key ][] = $post_id;
					}
				}
			}
		}

		return $by_zone;
	}

	/**
	 * Renderizar pagina de orden con tabs por zona.
	 */
	public function render_page(): void {
		$by_zone    = $this->get_ads_by_zone();
		$active_tab = isset( $_GET['zone'] ) ? sanitize_key( $_GET['zone'] ) : '';
		$today      = current_time( 'Y-m-d' );

		// Si no hay tab activo, usar la primera zona que tenga anuncios.
		if ( empty( $active_tab ) || ! isset( self::ZONE_MAP[ $active_tab ] ) ) {
			foreach ( $by_zone as $zone_key => $ids ) {
				if ( ! empty( $ids ) ) {
					$active_tab = $zone_key;
					break;
				}
			}
			// Si ninguna tiene anuncios, usar la primera.
			if ( empty( $active_tab ) ) {
				$active_tab = array_key_first( self::ZONE_MAP );
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Orden de anuncios por zona', 'geogastronomica' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Selecciona una zona y arrastra para cambiar el orden. El primero tiene mas prioridad.', 'geogastronomica' ); ?>
			</p>

			<div id="geo-order-notice" class="notice notice-success" style="display:none;">
				<p><?php esc_html_e( 'Orden guardado correctamente.', 'geogastronomica' ); ?></p>
			</div>

			<nav class="nav-tab-wrapper geo-order-tabs">
				<?php foreach ( self::ZONE_MAP as $zone_key => $label ) :
					$count    = count( $by_zone[ $zone_key ] );
					$is_vert  = str_contains( $zone_key, 'vertical' );
					$type_cls = $is_vert ? 'geo-tab-vertical' : 'geo-tab-horizontal';
					$base_url = admin_url( 'edit.php?post_type=' . CPT_Anuncio::POST_TYPE . '&page=geogastronomica-order&zone=' . $zone_key );
					?>
					<a href="<?php echo esc_url( $base_url ); ?>"
					   class="nav-tab <?php echo $active_tab === $zone_key ? 'nav-tab-active' : ''; ?> <?php echo esc_attr( $type_cls ); ?>">
						<?php echo esc_html( $label ); ?>
						<span class="geo-tab-count"><?php echo esc_html( $count ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="geo-order-panel">
				<?php
				$zone_ids = $by_zone[ $active_tab ] ?? array();
				if ( ! empty( $zone_ids ) ) :
					?>
					<ul id="geo-sortable-list" class="geo-order-list" data-zone="<?php echo esc_attr( $active_tab ); ?>">
						<?php
						$position = 1;
						foreach ( $zone_ids as $post_id ) :
							$empresa   = get_post_meta( $post_id, '_geo_empresa_nombre', true );
							$desc      = get_post_meta( $post_id, '_geo_descripcion', true );
							$fecha_fin = get_post_meta( $post_id, '_geo_fecha_fin', true );
							$pack      = get_post_meta( $post_id, '_geo_pack', true );
							$status    = ( $fecha_fin && $fecha_fin < $today ) ? 'caducado' : 'activo';
							$packs     = Settings::get_packs();
							$pack_name = isset( $packs[ $pack ] ) ? $packs[ $pack ]['name'] : '';
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
								<?php if ( $pack_name ) : ?>
									<span class="geo-order-pack-tag"><?php echo esc_html( $pack_name ); ?></span>
								<?php endif; ?>
								<?php if ( $fecha_fin ) : ?>
									<span class="geo-order-date <?php echo 'caducado' === $status ? 'geo-order-date-expired' : ''; ?>">
										<?php echo esc_html( $fecha_fin ); ?>
									</span>
								<?php endif; ?>
								<?php if ( 'caducado' === $status ) : ?>
									<span class="geo-order-badge caducado"><?php esc_html_e( 'Caducado', 'geogastronomica' ); ?></span>
								<?php endif; ?>
								<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="geo-order-edit" title="<?php esc_attr_e( 'Editar', 'geogastronomica' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</a>
							</li>
							<?php
							$position++;
						endforeach;
						?>
					</ul>
				<?php else : ?>
					<div class="geo-order-empty">
						<span class="dashicons dashicons-format-image"></span>
						<p><?php esc_html_e( 'No hay anuncios asignados a esta zona.', 'geogastronomica' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
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
