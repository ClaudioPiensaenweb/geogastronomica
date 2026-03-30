<?php
/**
 * Insercion automatica de anuncios dentro del contenido de articulos.
 *
 * Inyecta el shortcode [geoad] despues del parrafo N del contenido.
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
		// Solo en entradas individuales (post), no paginas ni CPTs.
		if ( ! is_singular( 'post' ) || is_admin() ) {
			return $content;
		}

		$config = Settings::get_inject_config();

		if ( empty( $config['enabled'] ) ) {
			return $content;
		}

		$injections = $config['injections'] ?? array();
		if ( empty( $injections ) ) {
			return $content;
		}

		// Dividir el contenido en parrafos usando </p> como separador.
		// explode preserva el orden y es mas rapido que preg_split.
		$parts = explode( '</p>', $content );
		$total_paragraphs = count( $parts ) - 1; // El ultimo trozo es post-ultimo-</p>

		// No inyectar en articulos muy cortos.
		if ( $total_paragraphs < 2 ) {
			return $content;
		}

		// Construir mapa: numero_de_parrafo => html_a_insertar.
		$inject_map = array();
		foreach ( $injections as $inj ) {
			if ( empty( $inj['zone'] ) || empty( $inj['after'] ) ) {
				continue;
			}

			$after = (int) $inj['after'];

			// No inyectar mas alla del final del articulo.
			if ( $after > $total_paragraphs ) {
				continue;
			}

			$html = $this->render_zone( sanitize_key( $inj['zone'] ) );
			if ( ! $html ) {
				continue;
			}

			// Si ya hay algo en esa posicion (dos inyecciones en el mismo parrafo),
			// concatenar con separacion.
			$inject_map[ $after ] = ( $inject_map[ $after ] ?? '' ) . $html;
		}

		if ( empty( $inject_map ) ) {
			return $content;
		}

		// Reconstruir el contenido insertando los banners en las posiciones.
		$result = '';
		foreach ( $parts as $i => $part ) {
			$result .= $part;

			// Restaurar el </p> que explode eliminó (salvo en el ultimo trozo).
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
	 * Renderizar una zona y devolver el HTML solo si hay anuncios activos.
	 *
	 * Usa do_shortcode internamente. Si la zona esta vacia el shortcode
	 * devuelve solo el script de eliminacion del wrapper (sin .geoad-zone),
	 * en cuyo caso esta funcion devuelve cadena vacia.
	 *
	 * @param string $zone Nombre de la zona (ej: subcategoria_horizontal_1).
	 * @return string HTML del banner o cadena vacia.
	 */
	private function render_zone( string $zone ): string {
		$html = do_shortcode( '[geoad zone="' . esc_attr( $zone ) . '"]' );

		// Si no hay anuncios, el shortcode devuelve un <script> de limpieza,
		// nunca un .geoad-zone. Filtramos por eso.
		if ( strpos( $html, 'geoad-zone' ) === false ) {
			return '';
		}

		return '<div class="geoad-inline-inject">' . $html . '</div>';
	}
}
