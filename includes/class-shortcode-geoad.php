<?php
/**
 * Shortcode [geoad] para renderizar zonas de anuncios.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra y renderiza el shortcode [geoad zone="..."].
 */
class Shortcode_GeoAd {

	/**
	 * Shortcode tag.
	 */
	public const TAG = 'geoad';

	/**
	 * Flag para encolar assets solo una vez.
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Cache manager.
	 *
	 * @var Cache_Manager|null
	 */
	private ?Cache_Manager $cache = null;

	/**
	 * Breakpoints para picture/source.
	 */
	private const BREAKPOINTS = array(
		'movil'      => '(max-width: 767px)',
		'cuadrado'   => '(max-width: 1023px)',
		'horizontal' => '(min-width: 1024px)',
		'vertical'   => '(min-width: 1024px)',
	);

	/**
	 * Aspect ratios por formato.
	 */
	private const ASPECT_RATIOS = array(
		'vertical'   => '285 / 627',
		'cuadrado'   => '285 / 285',
		'horizontal' => '1230 / 350',
		'movil'      => '1000 / 400',
	);

	/**
	 * Inicializar.
	 *
	 * @param Cache_Manager|null $cache Cache manager (inyeccion opcional).
	 */
	public function init( ?Cache_Manager $cache = null ): void {
		$this->cache = $cache;
		add_shortcode( self::TAG, array( $this, 'render' ) );
		wp_register_style(
			'geoad-frontend',
			GEO_PLUGIN_URL . 'assets/css/geoad-frontend.css',
			array(),
			GeoGastronomica::VERSION
		);
		wp_register_script(
			'geoad-rotation',
			GEO_PLUGIN_URL . 'assets/js/geoad-rotation.js',
			array(),
			GeoGastronomica::VERSION,
			true
		);
	}

	/**
	 * Callback del shortcode.
	 *
	 * @param array|string $atts Atributos del shortcode.
	 * @return string HTML del banner.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array( 'zone' => '' ),
			$atts,
			self::TAG
		);

		$zone = sanitize_key( $atts['zone'] );
		if ( empty( $zone ) ) {
			return defined( 'WP_DEBUG' ) && WP_DEBUG
				? '<!-- geoad: atributo zone requerido -->'
				: '';
		}

		$this->maybe_enqueue_assets();

		$ads = $this->get_active_ads_cached( $zone );
		if ( empty( $ads ) ) {
			return '';
		}

		if ( count( $ads ) > 1 ) {
			wp_enqueue_script( 'geoad-rotation' );
		}

		return $this->build_html( $ads, $zone );
	}

	/**
	 * Obtener anuncios activos con cache.
	 *
	 * @param string $zone Nombre de la zona.
	 * @return array Array de post IDs.
	 */
	private function get_active_ads_cached( string $zone ): array {
		if ( $this->cache ) {
			return $this->cache->get_or_query( $zone, function () use ( $zone ) {
				return $this->get_active_ads( $zone );
			} );
		}
		return $this->get_active_ads( $zone );
	}

	/**
	 * Obtener anuncios activos para una zona (query directa).
	 *
	 * @param string $zone Nombre de la zona.
	 * @return array Array de post IDs.
	 */
	public function get_active_ads( string $zone ): array {
		// Determinar la pagina y campo meta segun la zona.
		$zone_parts = $this->parse_zone( $zone );
		if ( ! $zone_parts ) {
			return array();
		}

		$meta_field = '_geo_anuncio_' . $zone_parts['page'];
		$slot       = $zone_parts['slot'];
		$today      = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => CPT_Anuncio::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_geo_prioridad',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_geo_fecha_comienzo',
						'value'   => $today,
						'compare' => '<=',
						'type'    => 'DATE',
					),
					array(
						'key'     => '_geo_fecha_comienzo',
						'compare' => 'NOT EXISTS',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_geo_fecha_fin',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => '_geo_fecha_fin',
						'compare' => 'NOT EXISTS',
					),
				),
			),
		);

		$query  = new \WP_Query( $args );
		$result = array();

		foreach ( $query->posts as $post_id ) {
			$zones = get_post_meta( $post_id, $meta_field, true );
			if ( is_array( $zones ) && in_array( $slot, $zones, true ) ) {
				$result[] = $post_id;
			}
		}

		return $result;
	}

	/**
	 * Parsear zona en pagina + slot.
	 * Ejemplo: "home_vertical_1" → { page: "home", slot: "vertical_1" }
	 *
	 * @param string $zone Nombre completo de la zona.
	 * @return array|null Array con page y slot, o null si invalido.
	 */
	private function parse_zone( string $zone ): ?array {
		$pages = array( 'home', 'categoria', 'subcategoria' );

		foreach ( $pages as $page ) {
			if ( str_starts_with( $zone, $page . '_' ) ) {
				return array(
					'page' => $page,
					'slot' => substr( $zone, strlen( $page ) + 1 ),
				);
			}
		}

		return null;
	}

	/**
	 * Construir HTML con picture/source para responsive.
	 *
	 * @param array  $ad_ids IDs de anuncios activos.
	 * @param string $zone   Nombre de la zona.
	 * @return string HTML.
	 */
	private function build_html( array $ad_ids, string $zone ): string {
		$zone_parts = $this->parse_zone( $zone );
		$slot       = $zone_parts['slot'] ?? '';
		$format     = $this->detect_primary_format( $slot );

		ob_start();
		?>
		<div class="geoad-zone geoad-zone--<?php echo esc_attr( $format ); ?>"
		     data-zone="<?php echo esc_attr( $zone ); ?>"
		     style="aspect-ratio: <?php echo esc_attr( self::ASPECT_RATIOS[ $format ] ?? '16 / 9' ); ?>">
			<?php
			$first = true;
			foreach ( $ad_ids as $ad_id ) :
				$enlace = get_post_meta( $ad_id, '_geo_enlace', true );
				?>
				<div class="geoad-banner <?php echo $first ? 'active' : ''; ?>"
				     data-ad-id="<?php echo esc_attr( $ad_id ); ?>">
					<?php if ( $enlace ) : ?>
					<a href="<?php echo esc_url( $enlace ); ?>" target="_blank" rel="noopener noreferrer">
					<?php endif; ?>

					<?php echo $this->render_picture( $ad_id, $format ); ?>

					<?php if ( $enlace ) : ?>
					</a>
					<?php endif; ?>
				</div>
				<?php
				$first = false;
			endforeach;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renderizar elemento <picture> con sources responsive.
	 *
	 * @param int    $ad_id  ID del anuncio.
	 * @param string $format Formato principal.
	 * @return string HTML del picture.
	 */
	private function render_picture( int $ad_id, string $format ): string {
		$images = array(
			'movil'      => (int) get_post_meta( $ad_id, '_geo_imagen_movil', true ),
			'cuadrado'   => (int) get_post_meta( $ad_id, '_geo_imagen_cuadrado', true ),
			'horizontal' => (int) get_post_meta( $ad_id, '_geo_imagen_horizontal', true ),
			'vertical'   => (int) get_post_meta( $ad_id, '_geo_imagen_vertical', true ),
		);

		// Fallback: si falta una imagen, usar la del formato principal.
		$fallback_id = $images[ $format ] ?: $this->find_fallback_image( $images );
		if ( ! $fallback_id ) {
			return '';
		}

		$fallback_url = wp_get_attachment_image_url( $fallback_id, 'full' );
		if ( ! $fallback_url ) {
			return '';
		}

		ob_start();
		?>
		<picture>
			<?php foreach ( self::BREAKPOINTS as $img_format => $media_query ) : ?>
				<?php
				if ( $img_format === $format ) {
					continue; // El formato principal va en el <img> fallback.
				}
				$img_id = $images[ $img_format ] ?: $fallback_id;
				$url    = wp_get_attachment_image_url( $img_id, 'full' );
				if ( ! $url ) {
					continue;
				}
				?>
				<source media="<?php echo esc_attr( $media_query ); ?>"
				        srcset="<?php echo esc_url( $url ); ?>">
			<?php endforeach; ?>
			<img src="<?php echo esc_url( $fallback_url ); ?>"
			     alt="<?php echo esc_attr( get_post_meta( $ad_id, '_geo_descripcion', true ) ); ?>"
			     loading="lazy"
			     width="<?php echo esc_attr( $this->get_format_width( $format ) ); ?>"
			     height="<?php echo esc_attr( $this->get_format_height( $format ) ); ?>">
		</picture>
		<?php
		return ob_get_clean();
	}

	/**
	 * Detectar formato principal segun el nombre del slot.
	 *
	 * @param string $slot Nombre del slot (ej: "vertical_1").
	 * @return string Formato (vertical, horizontal, cuadrado, movil).
	 */
	private function detect_primary_format( string $slot ): string {
		foreach ( array( 'vertical', 'horizontal', 'cuadrado' ) as $f ) {
			if ( str_starts_with( $slot, $f ) ) {
				return $f;
			}
		}
		return 'horizontal';
	}

	/**
	 * Buscar imagen de fallback entre las disponibles.
	 *
	 * @param array $images Array de image IDs por formato.
	 * @return int ID del primer attachment encontrado, o 0.
	 */
	private function find_fallback_image( array $images ): int {
		foreach ( $images as $id ) {
			if ( $id > 0 ) {
				return $id;
			}
		}
		return 0;
	}

	/**
	 * Obtener ancho del formato.
	 *
	 * @param string $format Formato.
	 * @return int Ancho en px.
	 */
	private function get_format_width( string $format ): int {
		return match ( $format ) {
			'vertical'   => 285,
			'cuadrado'   => 285,
			'horizontal' => 1230,
			'movil'      => 1000,
			default      => 1230,
		};
	}

	/**
	 * Obtener alto del formato.
	 *
	 * @param string $format Formato.
	 * @return int Alto en px.
	 */
	private function get_format_height( string $format ): int {
		return match ( $format ) {
			'vertical'   => 627,
			'cuadrado'   => 285,
			'horizontal' => 350,
			'movil'      => 400,
			default      => 350,
		};
	}

	/**
	 * Encolar CSS frontend solo cuando se usa el shortcode.
	 */
	private function maybe_enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}
		wp_enqueue_style( 'geoad-frontend' );
		$this->assets_enqueued = true;
	}
}
