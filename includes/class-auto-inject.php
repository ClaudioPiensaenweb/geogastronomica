<?php
/**
 * Insercion automatica de anuncios dentro del contenido de articulos.
 *
 * Inyecta el shortcode [geoad] despues del parrafo N del contenido.
 * Siempre fuerza formato horizontal para encajar bien en el flujo de texto.
 * Si no hay anuncios activos para la zona configurada no emite nada al DOM.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inyecta anuncios entre parrafos del contenido usando the_content filter.
 */
class Auto_Inject {

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_filter( 'the_content', array( $this, 'inject' ), 20 );
	}

	/**
	 * Inyectar anuncios en el contenido del post.
	 *
	 * @param string $content Contenido HTML del post.
	 * @return string Contenido modificado.
	 */
	public function inject( string $content ): string {
		if ( ! is_singular( 'post' ) || is_admin() ) {
			return $content;
		}

		$config = Settings::get_inject_config();

		if ( empty( $config['enabled'] ) ) {
			return $content;
		}

		// Determinar si aplicar segun el dispositivo configurado.
		// Se usa CSS para ocultar segun breakpoint, no deteccion de UA.
		$show_desktop = ! empty( $config['show_desktop'] );
		$show_mobile  = ! empty( $config['show_mobile'] );

		if ( ! $show_desktop && ! $show_mobile ) {
			return $content;
		}

		$injections = $config['injections'] ?? array();
		if ( empty( $injections ) ) {
			return $content;
		}

		// Clase CSS para visibilidad por dispositivo.
		$device_class = '';
		if ( $show_desktop && ! $show_mobile ) {
			$device_class = ' geoad-inject--desktop-only';
		} elseif ( ! $show_desktop && $show_mobile ) {
			$device_class = ' geoad-inject--mobile-only';
		}

		$parts            = explode( '</p>', $content );
		$total_paragraphs = count( $parts ) - 1;

		if ( $total_paragraphs < 2 ) {
			return $content;
		}

		// Construir mapa: numero_parrafo => html.
		// Si la posicion target es adyacente a una imagen, desliza al siguiente hueco valido.
		$inject_map = array();
		foreach ( $injections as $inj ) {
			if ( empty( $inj['zone'] ) || empty( $inj['after'] ) ) {
				continue;
			}

			$after = (int) $inj['after'];
			if ( $after > $total_paragraphs ) {
				continue;
			}

			// Deslizar hasta maximo 4 parrafos si el hueco esta junto a una imagen.
			$position = $after;
			$attempts = 0;
			while ( $attempts < 4 && $position <= $total_paragraphs ) {
				if ( ! $this->is_adjacent_to_image( $parts, $position - 1 ) ) {
					break;
				}
				$position++;
				$attempts++;
			}
			if ( $position > $total_paragraphs ) {
				continue; // Sin hueco valido, no inyectar.
			}

			$html = $this->render_zone( sanitize_key( $inj['zone'] ), $device_class );
			if ( ! $html ) {
				continue;
			}

			$inject_map[ $position ] = ( $inject_map[ $position ] ?? '' ) . $html;
		}

		if ( empty( $inject_map ) ) {
			return $content;
		}

		$result = '';
		foreach ( $parts as $i => $part ) {
			$result .= $part;
			if ( $i < count( $parts ) - 1 ) {
				$result .= '</p>';
				$paragraph_number = $i + 1;
				if ( isset( $inject_map[ $paragraph_number ] ) ) {
					$result .= $inject_map[ $paragraph_number ];
				}
			}
		}

		return $result;
	}

	/**
	 * Detectar si una posicion de inyeccion es adyacente a una imagen.
	 *
	 * Comprueba dos casos:
	 * 1. El parrafo actual contiene una imagen (<img> o <figure>).
	 * 2. El contenido que sigue al parrafo empieza con un bloque de imagen.
	 *
	 * Cubre tanto el editor clasico (<p><img...></p>) como Gutenberg
	 * (<figure class="wp-block-image">, <figure class="wp-block-gallery">).
	 *
	 * @param array $parts Array de segmentos del contenido (split por </p>).
	 * @param int   $index Indice del parrafo tras el que se quiere inyectar.
	 * @return bool True si la posicion es adyacente a una imagen.
	 */
	private function is_adjacent_to_image( array $parts, int $index ): bool {
		// El parrafo actual contiene una imagen o abre un blockquote.
		if ( isset( $parts[ $index ] ) && preg_match( '/<img[\s>]|<figure[\s>]|<blockquote[\s>]/i', $parts[ $index ] ) ) {
			return true;
		}

		// El contenido que sigue empieza con imagen (Gutenberg/clasico) o cierra un blockquote
		// (lo que significaria que el </p> splitado pertenecia al interior del bloque de cita).
		if ( isset( $parts[ $index + 1 ] ) ) {
			$next = ltrim( $parts[ $index + 1 ] );
			if ( preg_match( '/^<figure[\s>]|^<img[\s>]|^<\/blockquote/i', $next ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renderizar zona forzando siempre formato horizontal.
	 *
	 * Aunque la zona sea vertical (ej: subcategoria_vertical_1), se renderiza
	 * con format="horizontal" para encajar bien dentro del contenido del articulo.
	 * Si no hay imagen horizontal en el anuncio, se usa la vertical escalada.
	 *
	 * @param string $zone         Zona configurada.
	 * @param string $device_class Clase CSS de visibilidad por dispositivo.
	 * @return string HTML o cadena vacia si no hay anuncios.
	 */
	private function render_zone( string $zone, string $device_class = '' ): string {
		// format="horizontal" fuerza el CSS class y la imagen horizontal.
		$html = do_shortcode( '[geoad zone="' . esc_attr( $zone ) . '" format="horizontal"]' );

		if ( strpos( $html, 'geoad-zone' ) === false ) {
			return '';
		}

		return '<div class="geoad-inline-inject' . esc_attr( $device_class ) . '">' . $html . '</div>';
	}
}
