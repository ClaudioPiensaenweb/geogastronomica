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
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'geo_save_settings';

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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
	 * Registrar settings con la Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'geogastronomica_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitizar settings al guardar.
	 *
	 * @param array $input Datos del formulario.
	 * @return array Datos sanitizados.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		if ( isset( $input['github_token'] ) ) {
			$token = sanitize_text_field( $input['github_token'] );
			// Cifrar el token antes de guardarlo.
			$sanitized['github_token'] = self::encrypt( $token );
		}

		return $sanitized;
	}

	/**
	 * Renderizar pagina de ajustes.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options       = get_option( self::OPTION_NAME, array() );
		$has_token     = ! empty( $options['github_token'] );
		$masked_token  = $has_token ? str_repeat( '*', 20 ) . substr( self::decrypt( $options['github_token'] ), -6 ) : '';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GeoGastronomica — Ajustes', 'geogastronomica' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'geogastronomica_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="geo-github-token">
								<?php esc_html_e( 'Token GitHub (actualizaciones)', 'geogastronomica' ); ?>
							</label>
						</th>
						<td>
							<input type="password"
							       id="geo-github-token"
							       name="<?php echo esc_attr( self::OPTION_NAME ); ?>[github_token]"
							       value=""
							       class="regular-text"
							       autocomplete="off"
							       placeholder="<?php echo $has_token ? esc_attr( $masked_token ) : esc_attr__( 'github_pat_...', 'geogastronomica' ); ?>">
							<?php if ( $has_token ) : ?>
								<p class="description" style="color: #46b450;">
									<?php esc_html_e( 'Token configurado correctamente.', 'geogastronomica' ); ?>
									<?php esc_html_e( 'Deja el campo vacio para mantener el actual.', 'geogastronomica' ); ?>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'Necesario para recibir actualizaciones desde el repositorio privado de GitHub.', 'geogastronomica' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Version actual', 'geogastronomica' ); ?></th>
						<td>
							<code><?php echo esc_html( GeoGastronomica::VERSION ); ?></code>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Guardar ajustes', 'geogastronomica' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Obtener el token descifrado.
	 *
	 * @return string Token en texto plano, o vacio.
	 */
	public static function get_token(): string {
		// Prioridad: wp-config.php > ajustes del plugin > token embebido.
		if ( defined( 'GEO_GITHUB_TOKEN' ) && GEO_GITHUB_TOKEN ) {
			return GEO_GITHUB_TOKEN;
		}

		$options = get_option( self::OPTION_NAME, array() );
		if ( ! empty( $options['github_token'] ) ) {
			return self::decrypt( $options['github_token'] );
		}

		// Fallback: token embebido (ofuscado).
		return self::get_embedded_token();
	}

	/**
	 * Cifrar un valor con clave unica del sitio.
	 *
	 * @param string $value Valor a cifrar.
	 * @return string Valor cifrado (base64).
	 */
	private static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = self::get_encryption_key();
		$iv     = substr( hash( 'sha256', $key ), 0, 16 );
		$encrypted = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $encrypted );
	}

	/**
	 * Descifrar un valor.
	 *
	 * @param string $value Valor cifrado (base64).
	 * @return string Valor descifrado.
	 */
	private static function decrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = self::get_encryption_key();
		$iv     = substr( hash( 'sha256', $key ), 0, 16 );
		$decoded = base64_decode( $value );
		return openssl_decrypt( $decoded, 'AES-256-CBC', $key, 0, $iv ) ?: '';
	}

	/**
	 * Obtener clave de cifrado del sitio.
	 *
	 * @return string Clave derivada de AUTH_KEY de WordPress.
	 */
	private static function get_encryption_key(): string {
		return hash( 'sha256', ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'geogastronomica-fallback-key' ) );
	}

	/**
	 * Token embebido como fallback.
	 *
	 * @return string Token decodificado.
	 */
	private static function get_embedded_token(): string {
		$p = 'Z2l0aHViX3BhdF8xMUI2VEhESUkwbllQTnBn'
		   . 'Y0VZQWl6X2N0S0I5MHQxd1pyMnp2aDh3UzFz'
		   . 'MHdoZTNORlNNTmdQZUpqbndCMjVvdU9WQlpM'
		   . 'TFVMWnpGN2hwbFZh';
		return base64_decode( $p );
	}
}
