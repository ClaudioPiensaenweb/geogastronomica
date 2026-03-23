<?php
/**
 * Meta boxes con tabs para el CPT geo_anuncio.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra meta boxes organizados en 3 tabs.
 */
class Meta_Boxes {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'geo_save_anuncio';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = '_geo_nonce';

	/**
	 * Definicion de campos por tab.
	 *
	 * @return array<string, array>
	 */
	private function get_tabs(): array {
		return array(
			'empresa' => array(
				'label'  => esc_html__( 'Info empresa', 'geogastronomica' ),
				'fields' => array(
					'_geo_empresa_nombre'   => array(
						'label'    => esc_html__( 'Nombre empresa', 'geogastronomica' ),
						'type'     => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					'_geo_empresa_email'    => array(
						'label'    => esc_html__( 'Email empresa', 'geogastronomica' ),
						'type'     => 'email',
						'sanitize' => 'sanitize_email',
					),
					'_geo_empresa_telefono' => array(
						'label'    => esc_html__( 'Telefono empresa', 'geogastronomica' ),
						'type'     => 'text',
						'sanitize' => 'sanitize_text_field',
					),
				),
			),
			'anuncio' => array(
				'label'  => esc_html__( 'Anuncio', 'geogastronomica' ),
				'fields' => array(
					'_geo_descripcion' => array(
						'label'    => esc_html__( 'Descripcion anuncio', 'geogastronomica' ),
						'type'     => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					'_geo_enlace'      => array(
						'label'    => esc_html__( 'Enlace', 'geogastronomica' ),
						'type'     => 'url',
						'sanitize' => 'esc_url_raw',
					),
					'_geo_imagen_vertical'   => array(
						'label'    => esc_html__( 'Anuncio vertical (285x627)', 'geogastronomica' ),
						'type'     => 'image',
						'sanitize' => 'absint',
					),
					'_geo_imagen_cuadrado'   => array(
						'label'    => esc_html__( 'Anuncio cuadrado (285x285)', 'geogastronomica' ),
						'type'     => 'image',
						'sanitize' => 'absint',
					),
					'_geo_imagen_horizontal' => array(
						'label'    => esc_html__( 'Anuncio horizontal (1230x350)', 'geogastronomica' ),
						'type'     => 'image',
						'sanitize' => 'absint',
					),
					'_geo_imagen_movil'      => array(
						'label'    => esc_html__( 'Anuncio para movil (1000x400)', 'geogastronomica' ),
						'type'     => 'image',
						'sanitize' => 'absint',
					),
				),
			),
			'config'  => array(
				'label'  => esc_html__( 'Configuracion del anuncio', 'geogastronomica' ),
				'fields' => array(
					'_geo_fecha_comienzo' => array(
						'label'    => esc_html__( 'Fecha comienzo', 'geogastronomica' ),
						'type'     => 'date',
						'sanitize' => 'sanitize_text_field',
					),
					'_geo_fecha_fin'      => array(
						'label'    => esc_html__( 'Fecha fin', 'geogastronomica' ),
						'type'     => 'date',
						'sanitize' => 'sanitize_text_field',
					),
					'_geo_home'           => array(
						'label'    => esc_html__( 'Pagina de inicio', 'geogastronomica' ),
						'type'     => 'checkbox',
						'sanitize' => 'absint',
					),
					'_geo_todas_categorias' => array(
						'label'    => esc_html__( 'Todas las categorias', 'geogastronomica' ),
						'type'     => 'checkbox',
						'sanitize' => 'absint',
					),
					'_geo_anuncio_home'   => array(
						'label'    => esc_html__( 'Zonas en pagina de inicio', 'geogastronomica' ),
						'type'     => 'zone_checkboxes',
						'options'  => array( 'vertical_1', 'horizontal_1', 'cuadrado_1', 'horizontal_2' ),
						'sanitize' => 'array_map_absint',
					),
					'_geo_anuncio_categoria' => array(
						'label'    => esc_html__( 'Zonas en pagina de categoria', 'geogastronomica' ),
						'type'     => 'zone_checkboxes',
						'options'  => array( 'vertical_1', 'horizontal_1', 'cuadrado_1', 'horizontal_2' ),
						'sanitize' => 'array_map_absint',
					),
					'_geo_anuncio_subcategoria' => array(
						'label'    => esc_html__( 'Zonas en subcategoria, articulo o autor', 'geogastronomica' ),
						'type'     => 'zone_checkboxes',
						'options'  => array( 'vertical_1', 'vertical_2', 'vertical_3', 'horizontal_1' ),
						'sanitize' => 'array_map_absint',
					),
					'_geo_prioridad'      => array(
						'label'    => esc_html__( 'Prioridad', 'geogastronomica' ),
						'type'     => 'number',
						'sanitize' => 'absint',
					),
				),
			),
		);
	}

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_' . CPT_Anuncio::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registrar meta box.
	 */
	public function register_meta_box(): void {
		add_meta_box(
			'geo_anuncio_details',
			esc_html__( 'Detalles del anuncio', 'geogastronomica' ),
			array( $this, 'render_meta_box' ),
			CPT_Anuncio::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Renderizar meta box con tabs.
	 *
	 * @param \WP_Post $post Post actual.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$tabs = $this->get_tabs();
		?>
		<div class="geo-meta-tabs">
			<ul class="geo-tabs-nav">
				<?php $first = true; ?>
				<?php foreach ( $tabs as $tab_id => $tab ) : ?>
					<li>
						<a href="#geo-tab-<?php echo esc_attr( $tab_id ); ?>"
						   class="geo-tab-link <?php echo $first ? 'active' : ''; ?>"
						   data-tab="<?php echo esc_attr( $tab_id ); ?>">
							<?php echo esc_html( $tab['label'] ); ?>
						</a>
					</li>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</ul>

			<?php $first = true; ?>
			<?php foreach ( $tabs as $tab_id => $tab ) : ?>
				<div id="geo-tab-<?php echo esc_attr( $tab_id ); ?>"
				     class="geo-tab-content <?php echo $first ? 'active' : ''; ?>">
					<table class="form-table">
						<?php foreach ( $tab['fields'] as $meta_key => $field ) : ?>
							<tr>
								<th>
									<label for="<?php echo esc_attr( $meta_key ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
									</label>
								</th>
								<td>
									<?php $this->render_field( $meta_key, $field, $post->ID ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Renderizar un campo individual.
	 *
	 * @param string $meta_key Key del meta.
	 * @param array  $field    Definicion del campo.
	 * @param int    $post_id  ID del post.
	 */
	private function render_field( string $meta_key, array $field, int $post_id ): void {
		$value = get_post_meta( $post_id, $meta_key, true );

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
				printf(
					'<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( $field['type'] ),
					esc_attr( $meta_key ),
					esc_attr( $meta_key ),
					esc_attr( $value )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( $meta_key ),
					esc_attr( $meta_key ),
					esc_url( $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0">',
					esc_attr( $meta_key ),
					esc_attr( $meta_key ),
					esc_attr( $value )
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( $meta_key ),
					esc_attr( $meta_key ),
					esc_attr( $value )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
					esc_attr( $meta_key ),
					esc_attr( $meta_key ),
					checked( $value, '1', false ),
					esc_html__( 'Activado', 'geogastronomica' )
				);
				break;

			case 'image':
				$this->render_image_field( $meta_key, $value );
				break;

			case 'zone_checkboxes':
				$this->render_zone_checkboxes( $meta_key, $field, $value );
				break;
		}
	}

	/**
	 * Renderizar campo de imagen con Media Library picker.
	 *
	 * @param string $meta_key     Key del meta.
	 * @param mixed  $attachment_id ID del attachment.
	 */
	private function render_image_field( string $meta_key, mixed $attachment_id ): void {
		$image_url = $attachment_id ? wp_get_attachment_image_url( (int) $attachment_id, 'medium' ) : '';
		?>
		<div class="geo-image-field" data-field="<?php echo esc_attr( $meta_key ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $meta_key ); ?>"
			       value="<?php echo esc_attr( $attachment_id ); ?>" class="geo-image-id">
			<div class="geo-image-preview">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="">
				<?php endif; ?>
			</div>
			<button type="button" class="button geo-upload-btn">
				<?php esc_html_e( 'Seleccionar imagen', 'geogastronomica' ); ?>
			</button>
			<button type="button" class="button geo-remove-btn" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
				<?php esc_html_e( 'Eliminar', 'geogastronomica' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Renderizar checkboxes de zonas.
	 *
	 * @param string $meta_key Key del meta.
	 * @param array  $field    Definicion del campo.
	 * @param mixed  $value    Valor guardado (array serializado).
	 */
	private function render_zone_checkboxes( string $meta_key, array $field, mixed $value ): void {
		$saved = is_array( $value ) ? $value : array();
		$zone_labels = array(
			'vertical_1'   => esc_html__( 'Anuncio Vertical 1', 'geogastronomica' ),
			'vertical_2'   => esc_html__( 'Anuncio Vertical 2', 'geogastronomica' ),
			'vertical_3'   => esc_html__( 'Anuncio Vertical 3', 'geogastronomica' ),
			'horizontal_1' => esc_html__( 'Anuncio Horizontal 1', 'geogastronomica' ),
			'horizontal_2' => esc_html__( 'Anuncio Horizontal 2', 'geogastronomica' ),
			'cuadrado_1'   => esc_html__( 'Anuncio Cuadrado 1', 'geogastronomica' ),
		);

		echo '<div class="geo-zone-checkboxes">';
		foreach ( $field['options'] as $option ) {
			$checked = in_array( $option, $saved, true ) ? 'checked' : '';
			$label   = $zone_labels[ $option ] ?? $option;
			printf(
				'<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label> ',
				esc_attr( $meta_key ),
				esc_attr( $option ),
				$checked,
				esc_html( $label )
			);
		}
		echo '</div>';
	}

	/**
	 * Guardar meta datos con sanitizacion.
	 *
	 * @param int      $post_id ID del post.
	 * @param \WP_Post $post    Post actual.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verificar nonce + capabilities.
		if ( ! geo_verify_request( self::NONCE_ACTION, self::NONCE_FIELD, 'edit_post', $post_id ) ) {
			return;
		}

		// No guardar en autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Guardar cada campo con su sanitizacion.
		$tabs = $this->get_tabs();
		foreach ( $tabs as $tab ) {
			foreach ( $tab['fields'] as $meta_key => $field ) {
				if ( 'zone_checkboxes' === $field['type'] ) {
					$raw   = isset( $_POST[ $meta_key ] ) && is_array( $_POST[ $meta_key ] )
						? $_POST[ $meta_key ]
						: array();
					$value = array_map( 'sanitize_text_field', $raw );
					update_post_meta( $post_id, $meta_key, $value );
				} elseif ( 'checkbox' === $field['type'] ) {
					$value = isset( $_POST[ $meta_key ] ) ? 1 : 0;
					update_post_meta( $post_id, $meta_key, $value );
				} else {
					$raw   = isset( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
					$value = call_user_func( $field['sanitize'], $raw );
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}

		// Auto-generar titulo desde empresa + descripcion.
		$empresa     = sanitize_text_field( $_POST['_geo_empresa_nombre'] ?? '' );
		$descripcion = sanitize_text_field( $_POST['_geo_descripcion'] ?? '' );
		$auto_title  = trim( $empresa . ( $descripcion ? ' — ' . $descripcion : '' ) );

		if ( $auto_title && $auto_title !== $post->post_title ) {
			remove_action( 'save_post_' . CPT_Anuncio::POST_TYPE, array( $this, 'save_meta' ) );
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $auto_title,
			) );
			add_action( 'save_post_' . CPT_Anuncio::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		}
	}

	/**
	 * Encolar assets solo en la pantalla de edicion del CPT.
	 *
	 * @param string $hook_suffix Hook de la pagina actual.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || CPT_Anuncio::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'geo-admin-meta-boxes',
			GEO_PLUGIN_URL . 'assets/css/admin-meta-boxes.css',
			array(),
			GeoGastronomica::VERSION
		);

		wp_enqueue_script(
			'geo-admin-meta-boxes',
			GEO_PLUGIN_URL . 'assets/js/admin-meta-boxes.js',
			array( 'jquery' ),
			GeoGastronomica::VERSION,
			true
		);
	}
}
