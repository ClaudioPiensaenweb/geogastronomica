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
	 * Flag para emitir CSS de fallback solo una vez.
	 *
	 * @var bool
	 */
	private bool $fallback_emitted = false;

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
		'horizontal' => '(min-width: 768px)',
		'vertical'   => '(min-width: 768px)',
	);

	/**
	 * Aspect ratios por formato.
	 */
	private const ASPECT_RATIOS = array(
		'vertical'   => '285 / 627',
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registrar assets en el hook correcto.
	 */
	public function register_assets(): void {
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
			array(
				'zone'          => '',
				'fallback_hide' => '',
				'sticky'        => '', // 'bottom' para sticky en movil
				'format'        => '', // forzar formato: 'horizontal' o 'vertical'
			),
			$atts,
			self::TAG
		);

		$zone = sanitize_key( $atts['zone'] );
		if ( empty( $zone ) ) {
			return defined( 'WP_DEBUG' ) && WP_DEBUG
				? '<!-- geoad: atributo zone requerido -->'
				: '';
		}

		$ads = $this->get_active_ads_cached( $zone );
		if ( empty( $ads ) ) {
			// Sin anuncios: eliminar el wrapper .brxe-shortcode del DOM
			// para que no ocupe espacio en la grid de Bricks.
			return '<script>!function(){var s=document.currentScript;if(s){var p=s.parentElement;if(p&&p.classList.contains("brxe-shortcode")){p.remove()}else{s.remove()}}}()</script>';
		}

		$this->maybe_enqueue_assets();
		// Siempre necesario: lazy video + sticky dismiss.
		wp_enqueue_script( 'geoad-rotation' );

		$output = '';

		// Si hay anuncios y hay un selector fallback, ocultarlo.
		$fallback = sanitize_text_field( $atts['fallback_hide'] );
		if ( ! empty( $fallback ) && ! $this->fallback_emitted ) {
			$output .= '<style>' . esc_html( $fallback ) . '{display:none!important}</style>';
			$this->fallback_emitted = true;
		}

		$force_format = sanitize_key( $atts['format'] );
		$output .= $this->build_html( $ads, $zone, sanitize_key( $atts['sticky'] ), $force_format );
		return $output;
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
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);

		$query  = new \WP_Query( $args );
		$result = array();

		foreach ( $query->posts as $post_id ) {
			// Filtrar por fechas en PHP (mas robusto que meta_query con strings vacios).
			$inicio = get_post_meta( $post_id, '_geo_fecha_comienzo', true );
			$fin    = get_post_meta( $post_id, '_geo_fecha_fin', true );
			if ( $inicio && $inicio > $today ) {
				continue;
			}
			if ( $fin && $fin < $today ) {
				continue;
			}

			// Filtrar por zona.
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
	 * Debug: diagnosticar por que una zona no muestra anuncios.
	 *
	 * @param string $zone Nombre de la zona.
	 * @return string Diagnostico.
	 */
	private function debug_zone( string $zone ): string {
		$parts = $this->parse_zone( $zone );
		if ( ! $parts ) {
			return 'zona invalida - no parsea (paginas validas: home, categoria, subcategoria)';
		}

		$meta_field = '_geo_anuncio_' . $parts['page'];
		$slot       = $parts['slot'];

		// 1. Hay anuncios publicados?
		$all = get_posts( array(
			'post_type'   => CPT_Anuncio::POST_TYPE,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );
		if ( empty( $all ) ) {
			return 'no hay ningun anuncio creado (CPT: ' . CPT_Anuncio::POST_TYPE . ')';
		}

		$published = get_posts( array(
			'post_type'   => CPT_Anuncio::POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );
		if ( empty( $published ) ) {
			return count( $all ) . ' anuncios existen pero ninguno publicado';
		}

		// 2. Filtro de fechas.
		$today       = current_time( 'Y-m-d' );
		$date_passed = array();
		foreach ( $published as $pid ) {
			$inicio = get_post_meta( $pid, '_geo_fecha_comienzo', true );
			$fin    = get_post_meta( $pid, '_geo_fecha_fin', true );
			$ok     = true;
			if ( $inicio && $inicio > $today ) {
				$ok = false;
			}
			if ( $fin && $fin < $today ) {
				$ok = false;
			}
			if ( $ok ) {
				$date_passed[] = $pid;
			}
		}
		if ( empty( $date_passed ) ) {
			return count( $published ) . ' publicados pero todos fuera de fecha (hoy=' . $today . ')';
		}

		// 3. Filtro de zona.
		$info = array();
		foreach ( $date_passed as $pid ) {
			$zones = get_post_meta( $pid, $meta_field, true );
			$pack  = get_post_meta( $pid, '_geo_pack', true );
			$info[] = 'ID=' . $pid
				. ' pack=' . ( $pack ?: 'none' )
				. ' meta[' . $meta_field . ']=' . ( is_array( $zones ) ? implode( ',', $zones ) : var_export( $zones, true ) )
				. ' busco=' . $slot
				. ' match=' . ( is_array( $zones ) && in_array( $slot, $zones, true ) ? 'SI' : 'NO' );
		}

		return count( $date_passed ) . ' activos, detalle: ' . implode( ' | ', $info );
	}

	/**
	 * Construir HTML con picture/source para responsive.
	 *
	 * @param array  $ad_ids IDs de anuncios activos.
	 * @param string $zone   Nombre de la zona.
	 * @param string $sticky Tipo sticky ('bottom' o '').
	 * @return string HTML.
	 */
	private function build_html( array $ad_ids, string $zone, string $sticky = '', string $force_format = '' ): string {
		$zone_parts   = $this->parse_zone( $zone );
		$slot         = $zone_parts['slot'] ?? '';
		$format       = ( $force_format && in_array( $force_format, array( 'vertical', 'horizontal' ), true ) )
			? $force_format
			: $this->detect_primary_format( $slot );
		$sticky_class = $sticky ? ' geoad-zone--sticky-' . esc_attr( $sticky ) : '';

		// Construir HTML de forma compacta para evitar que wpautop
		// convierta las lineas en blanco entre bloques PHP en <br>/<p>.
		$html  = '<div class="geoad-wrap">';
		$html .= '<div class="geoad-zone geoad-zone--' . esc_attr( $format ) . $sticky_class . '"';
		$html .= ' data-zone="' . esc_attr( $zone ) . '">';
		if ( $sticky ) {
			$html .= '<button class="geoad-sticky-close" aria-label="' . esc_attr__( 'Cerrar anuncio', 'geogastronomica' ) . '">';
			$html .= '<span aria-hidden="true">&times;</span></button>';
		}
		$first = true;
		foreach ( $ad_ids as $ad_id ) {
			$enlace        = get_post_meta( $ad_id, '_geo_enlace', true );
			$picture       = $this->render_picture( $ad_id, $format );
			$flag          = get_post_meta( $ad_id, '_geo_mostrar_publicidad', true );
			$mostrar_badge = ( '1' === (string) $flag ) ? '1' : '0';
			$html         .= '<div class="geoad-banner ' . ( $first ? 'active' : '' ) . '"';
			$html         .= ' data-ad-id="' . esc_attr( $ad_id ) . '"';
			$html         .= ' data-mostrar-publicidad="' . $mostrar_badge . '">';
			if ( $enlace ) {
				$html .= '<a href="' . esc_url( $enlace ) . '" target="_blank" rel="noopener noreferrer">';
			}
			$html .= $picture;
			if ( $enlace ) {
				$html .= '</a>';
			}
			$html  .= '</div>';
			$first  = false;
		}
		// El label siempre se renderiza — el JS lo muestra/oculta en cada rotacion
		// segun data-mostrar-publicidad del banner activo.
		// La clase inicial se calcula desde el primer banner para evitar parpadeo.
		$first_flag    = get_post_meta( $ad_ids[0], '_geo_mostrar_publicidad', true );
		$label_hidden  = ( '1' === (string) $first_flag ) ? '' : ' geoad-label--hidden';
		$privacy_url   = Settings::get_label_privacy_url();
		if ( $privacy_url ) {
			$html .= '<a class="geoad-label' . $label_hidden . '" href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener noreferrer nofollow">Publicidad</a>';
		} else {
			$html .= '<span class="geoad-label' . $label_hidden . '">Publicidad</span>';
		}
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Renderizar media del anuncio: imagen (<picture>) o video (<video>).
	 *
	 * @param int    $ad_id  ID del anuncio.
	 * @param string $format Formato principal determinado por la zona.
	 * @return string HTML.
	 */
	private function render_picture( int $ad_id, string $format ): string {
		$ids = array(
			'movil'      => (int) get_post_meta( $ad_id, '_geo_imagen_movil', true ),
			'horizontal' => (int) get_post_meta( $ad_id, '_geo_imagen_horizontal', true ),
			'vertical'   => (int) get_post_meta( $ad_id, '_geo_imagen_vertical', true ),
		);

		$primary_id = $ids[ $format ] ?: $this->find_fallback_image( $ids );
		if ( ! $primary_id ) {
			return '';
		}

		// Si el attachment principal es video, renderizar <video>.
		if ( $this->is_video( $primary_id ) ) {
			return $this->render_video( $primary_id, $ids['movil'], $format );
		}

		// Imagen: usar wp_get_attachment_image_url (no funciona para video).
		$primary_url = wp_get_attachment_image_url( $primary_id, 'full' );
		if ( ! $primary_url ) {
			return '';
		}

		$mobile_id  = $ids['movil'] ?: $primary_id;
		$mobile_url = wp_get_attachment_image_url( $mobile_id, 'full' );
		$alt        = esc_attr( get_post_meta( $ad_id, '_geo_descripcion', true ) );

		$w   = esc_attr( $this->get_format_width( $format ) );
		$h   = esc_attr( $this->get_format_height( $format ) );
		$src = esc_url( $primary_url );
		$html = '<picture>';
		if ( $mobile_url && $mobile_id !== $primary_id ) {
			$html .= '<source media="' . esc_attr( $this->get_mobile_media_query( $format ) ) . '" srcset="' . esc_url( $mobile_url ) . '">';
		}
		$html .= '<img src="' . $src . '" alt="' . $alt . '" loading="lazy" width="' . $w . '" height="' . $h . '">';
		$html .= '</picture>';
		return $html;
	}

	/**
	 * Detectar si un attachment es video.
	 *
	 * @param int $attachment_id ID del attachment.
	 * @return bool True si es video.
	 */
	private function is_video( int $attachment_id ): bool {
		$mime = get_post_mime_type( $attachment_id );
		return $mime && str_starts_with( $mime, 'video/' );
	}

	/**
	 * Renderizar <video> con lazy loading via data-src.
	 *
	 * El JS (geoad-rotation.js) activa la carga cuando el elemento
	 * entra en el viewport (IntersectionObserver, rootMargin 300px).
	 * Nunca se descarga hasta que el usuario llega a esa zona.
	 *
	 * @param int    $video_id  ID attachment del video principal.
	 * @param int    $mobile_id ID attachment del video/imagen para movil (0 = usa el principal).
	 * @param string $format    Formato de la zona.
	 * @return string HTML del video.
	 */
	private function render_video( int $video_id, int $mobile_id, string $format ): string {
		$video_url = wp_get_attachment_url( $video_id );
		if ( ! $video_url ) {
			return '';
		}

		$mime      = get_post_mime_type( $video_id );
		$w         = $this->get_format_width( $format );
		$h         = $this->get_format_height( $format );

		ob_start();
		?>
		<video class="geoad-video geoad-video-lazy"
		       muted loop playsinline preload="none"
		       width="<?php echo esc_attr( $w ); ?>"
		       height="<?php echo esc_attr( $h ); ?>">
			<?php
			// Si hay video movil distinto, prioritizarlo en pantallas pequenas.
			if ( $mobile_id && $mobile_id !== $video_id && $this->is_video( $mobile_id ) ) :
				$mobile_url  = wp_get_attachment_url( $mobile_id );
				$mobile_mime = get_post_mime_type( $mobile_id );
				?>
				<source data-src="<?php echo esc_url( $mobile_url ); ?>"
				        type="<?php echo esc_attr( $mobile_mime ); ?>"
				        media="<?php echo esc_attr( $this->get_mobile_media_query( $format ) ); ?>">
			<?php endif; ?>
			<source data-src="<?php echo esc_url( $video_url ); ?>"
			        type="<?php echo esc_attr( $mime ); ?>">
		</video>
		<?php
		return ob_get_clean();
	}

	/**
	 * Media query del creativo movil segun el formato de la zona.
	 *
	 * La zona vertical pasa a formato 5:2 cuando la parrilla colapsa a una
	 * columna (<=991px), asi que su creativo movil debe activarse en el
	 * mismo punto que el CSS (geoad-frontend.css). El resto de formatos
	 * cambia en el breakpoint movil estandar.
	 *
	 * Nota: el atributo media en <video><source> no lo respetan todos los
	 * navegadores (Firefox lo ignora); geoad-rotation.js lo evalua con
	 * matchMedia al activar el video para que la eleccion sea consistente.
	 *
	 * @param string $format Formato de la zona.
	 * @return string Media query CSS.
	 */
	private function get_mobile_media_query( string $format ): string {
		return 'vertical' === $format ? '(max-width: 991px)' : '(max-width: 767px)';
	}

	/**
	 * Detectar formato principal segun el nombre del slot.
	 *
	 * @param string $slot Nombre del slot (ej: "vertical_1").
	 * @return string Formato (vertical, horizontal, cuadrado, movil).
	 */
	private function detect_primary_format( string $slot ): string {
		foreach ( array( 'vertical', 'horizontal' ) as $f ) {
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
		wp_add_inline_script(
			'geoad-rotation',
			'window.geoAdConfig = ' . wp_json_encode( array(
				'interval' => Settings::get_rotation_interval(),
			) ) . ';',
			'before'
		);
		$this->assets_enqueued = true;
	}
}
