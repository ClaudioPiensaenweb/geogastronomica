<?php
/**
 * Pagina de ajustes del plugin.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra pagina de ajustes bajo el menu GeoGastronomica.
 */
class Settings {

	/**
	 * Option name para los ajustes.
	 */
	private const OPTION_NAME = 'geogastronomica_settings';

	/**
	 * Option name para los packs.
	 */
	public const PACKS_OPTION = 'geogastronomica_packs';

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'geo_save_settings';

	/**
	 * Zonas disponibles por pagina.
	 */
	public const AVAILABLE_ZONES = array(
		'home'          => array( 'vertical_1', 'horizontal_1', 'horizontal_2' ),
		'categoria'     => array( 'vertical_1', 'horizontal_1', 'horizontal_2' ),
		'subcategoria'  => array( 'vertical_1', 'vertical_2', 'vertical_3', 'horizontal_1' ),
	);

	/**
	 * Labels de paginas.
	 */
	private const PAGE_LABELS = array(
		'home'         => 'Pagina de inicio',
		'categoria'    => 'Pagina de categoria',
		'subcategoria' => 'Subcategoria / Articulo / Autor',
	);

	/**
	 * Labels de zonas.
	 */
	private const ZONE_LABELS = array(
		'vertical_1'   => 'Vertical 1',
		'vertical_2'   => 'Vertical 2',
		'vertical_3'   => 'Vertical 3',
		'horizontal_1' => 'Horizontal 1',
		'horizontal_2' => 'Horizontal 2',
	);

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Anadir submenu bajo GeoGastronomica.
	 */
	public function add_submenu(): void {
		add_submenu_page(
			'edit.php?post_type=' . CPT_Anuncio::POST_TYPE,
			esc_html__( 'Ajustes', 'geogastronomica' ),
			esc_html__( 'Ajustes', 'geogastronomica' ),
			'manage_options',
			'geogastronomica-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Obtener packs configurados.
	 *
	 * @return array Packs con slug => { name, price, zones }.
	 */
	public static function get_packs(): array {
		$packs = get_option( self::PACKS_OPTION, array() );
		if ( empty( $packs ) ) {
			return self::get_default_packs();
		}
		return $packs;
	}

	/**
	 * Packs por defecto.
	 *
	 * @return array
	 */
	private static function get_default_packs(): array {
		return array(
			'basico' => array(
				'name'  => 'Basico',
				'price' => '200',
				'zones' => array(
					'home'         => array( 'vertical_1' ),
					'categoria'    => array( 'vertical_1' ),
					'subcategoria' => array( 'vertical_1' ),
				),
			),
			'avanzado' => array(
				'name'  => 'Avanzado',
				'price' => '350',
				'zones' => array(
					'home'         => array( 'vertical_1', 'horizontal_1' ),
					'categoria'    => array( 'vertical_1', 'horizontal_1' ),
					'subcategoria' => array( 'vertical_1', 'vertical_2', 'horizontal_1' ),
				),
			),
			'premium' => array(
				'name'  => 'Premium',
				'price' => '500',
				'zones' => array(
					'home'         => array( 'vertical_1', 'horizontal_1', 'horizontal_2' ),
					'categoria'    => array( 'vertical_1', 'horizontal_1', 'horizontal_2' ),
					'subcategoria' => array( 'vertical_1', 'vertical_2', 'vertical_3', 'horizontal_1' ),
				),
			),
		);
	}

	/**
	 * Guardar ajustes via POST.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['geo_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['geo_settings_nonce'], self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$packs = array();

		if ( isset( $_POST['geo_packs'] ) && is_array( $_POST['geo_packs'] ) ) {
			foreach ( $_POST['geo_packs'] as $raw_pack ) {
				$slug = sanitize_key( $raw_pack['slug'] ?? '' );
				if ( empty( $slug ) ) {
					continue;
				}

				$zones = array();
				foreach ( self::AVAILABLE_ZONES as $page => $page_zones ) {
					$selected = array();
					if ( isset( $raw_pack['zones'][ $page ] ) && is_array( $raw_pack['zones'][ $page ] ) ) {
						$selected = array_intersect(
							array_map( 'sanitize_key', $raw_pack['zones'][ $page ] ),
							$page_zones
						);
					}
					$zones[ $page ] = array_values( $selected );
				}

				$packs[ $slug ] = array(
					'name'  => sanitize_text_field( $raw_pack['name'] ?? '' ),
					'price' => sanitize_text_field( $raw_pack['price'] ?? '' ),
					'zones' => $zones,
				);
			}
		}

		update_option( self::PACKS_OPTION, $packs );

		add_settings_error(
			'geogastronomica',
			'settings_updated',
			esc_html__( 'Ajustes guardados.', 'geogastronomica' ),
			'updated'
		);
	}

	/**
	 * Renderizar pagina de ajustes.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$packs = self::get_packs();
		settings_errors( 'geogastronomica' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GeoGastronomica — Ajustes', 'geogastronomica' ); ?></h1>

			<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px 24px; margin:20px 0 12px;">
				<strong><?php esc_html_e( 'Version actual:', 'geogastronomica' ); ?></strong>
				<code><?php echo esc_html( GeoGastronomica::VERSION ); ?></code>
			</div>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, 'geo_settings_nonce' ); ?>

				<h2><?php esc_html_e( 'Packs de visibilidad', 'geogastronomica' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Define los packs que aparecen en el editor de anuncios. Cada pack agrupa las zonas donde se muestra un anuncio.', 'geogastronomica' ); ?>
				</p>

				<div id="geo-packs-container">
					<?php
					$index = 0;
					foreach ( $packs as $slug => $pack ) :
						$this->render_pack_card( $index, $slug, $pack );
						$index++;
					endforeach;
					?>
				</div>

				<p>
					<button type="button" id="geo-add-pack" class="button">
						+ <?php esc_html_e( 'Anadir pack', 'geogastronomica' ); ?>
					</button>
				</p>

				<?php submit_button( esc_html__( 'Guardar ajustes', 'geogastronomica' ) ); ?>
			</form>
		</div>

		<template id="geo-pack-template">
			<?php $this->render_pack_card( '__INDEX__', '', array( 'name' => '', 'price' => '', 'zones' => array() ) ); ?>
		</template>

		<style>
			.geo-pack-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 6px;
				padding: 20px 24px;
				margin: 16px 0;
				position: relative;
			}
			.geo-pack-card:hover { border-color: #2271b1; }
			.geo-pack-header {
				display: flex;
				gap: 16px;
				align-items: flex-end;
				margin-bottom: 16px;
				flex-wrap: wrap;
			}
			.geo-pack-header label {
				display: block;
				font-weight: 600;
				margin-bottom: 4px;
				font-size: 13px;
			}
			.geo-pack-header input { max-width: 200px; }
			.geo-pack-remove {
				position: absolute;
				top: 12px;
				right: 12px;
				color: #b32d2e;
				cursor: pointer;
				background: none;
				border: none;
				font-size: 13px;
			}
			.geo-pack-remove:hover { text-decoration: underline; }
			.geo-pack-zones {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
				gap: 16px;
			}
			.geo-zone-column {
				background: #f9f9f9;
				border: 1px solid #e5e5e5;
				border-radius: 4px;
				padding: 12px;
			}
			.geo-zone-column-title {
				font-weight: 600;
				font-size: 12px;
				text-transform: uppercase;
				color: #50575e;
				margin-bottom: 8px;
			}
			.geo-zone-column label {
				display: block;
				padding: 4px 0;
				font-size: 13px;
			}
			.geo-zone-column input[type="checkbox"] { margin-right: 6px; }
		</style>

		<script>
		(function() {
			const container = document.getElementById('geo-packs-container');
			const addBtn = document.getElementById('geo-add-pack');
			const template = document.getElementById('geo-pack-template');
			let packCount = <?php echo count( $packs ); ?>;

			addBtn.addEventListener('click', function() {
				const html = template.innerHTML.replace(/__INDEX__/g, packCount);
				const div = document.createElement('div');
				div.innerHTML = html;
				container.appendChild(div.firstElementChild);
				packCount++;
			});

			container.addEventListener('click', function(e) {
				if (e.target.classList.contains('geo-pack-remove')) {
					e.target.closest('.geo-pack-card').remove();
				}
			});

			// Auto-generar slug desde nombre.
			container.addEventListener('input', function(e) {
				if (e.target.classList.contains('geo-pack-name')) {
					const card = e.target.closest('.geo-pack-card');
					const slugInput = card.querySelector('.geo-pack-slug');
					if (slugInput && !slugInput.dataset.manual) {
						slugInput.value = e.target.value
							.toLowerCase()
							.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
							.replace(/[^a-z0-9]+/g, '_')
							.replace(/^_|_$/g, '');
					}
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Renderizar una card de pack.
	 *
	 * @param int|string $index Indice del pack.
	 * @param string     $slug  Slug del pack.
	 * @param array      $pack  Datos del pack.
	 */
	private function render_pack_card( $index, string $slug, array $pack ): void {
		$prefix = "geo_packs[{$index}]";
		?>
		<div class="geo-pack-card">
			<button type="button" class="geo-pack-remove">&times; <?php esc_html_e( 'Eliminar', 'geogastronomica' ); ?></button>

			<div class="geo-pack-header">
				<div>
					<label><?php esc_html_e( 'Nombre del pack', 'geogastronomica' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $prefix ); ?>[name]"
					       value="<?php echo esc_attr( $pack['name'] ); ?>"
					       class="regular-text geo-pack-name"
					       placeholder="<?php esc_attr_e( 'Ej: Premium', 'geogastronomica' ); ?>">
				</div>
				<div>
					<label><?php esc_html_e( 'Precio', 'geogastronomica' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $prefix ); ?>[price]"
					       value="<?php echo esc_attr( $pack['price'] ?? '' ); ?>"
					       class="small-text"
					       placeholder="0" style="max-width:80px;">
					<span>&euro;</span>
				</div>
				<div>
					<label><?php esc_html_e( 'ID', 'geogastronomica' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $prefix ); ?>[slug]"
					       value="<?php echo esc_attr( $slug ); ?>"
					       class="geo-pack-slug"
					       style="max-width:120px; font-family:monospace;"
					       <?php echo $slug ? 'data-manual="1"' : ''; ?>>
				</div>
			</div>

			<div class="geo-pack-zones">
				<?php foreach ( self::AVAILABLE_ZONES as $page => $page_zones ) : ?>
					<div class="geo-zone-column">
						<div class="geo-zone-column-title">
							<?php echo esc_html( self::PAGE_LABELS[ $page ] ?? $page ); ?>
						</div>
						<?php foreach ( $page_zones as $zone ) :
							$checked = isset( $pack['zones'][ $page ] ) && in_array( $zone, $pack['zones'][ $page ], true );
							?>
							<label>
								<input type="checkbox"
								       name="<?php echo esc_attr( $prefix ); ?>[zones][<?php echo esc_attr( $page ); ?>][]"
								       value="<?php echo esc_attr( $zone ); ?>"
								       <?php checked( $checked ); ?>>
								<?php echo esc_html( self::ZONE_LABELS[ $zone ] ?? $zone ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
