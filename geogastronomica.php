<?php
/**
 * Plugin Name: GeoGastronomica
 * Plugin URI:  https://geogastronomica.com
 * Description: Sistema de gestion de banners y anuncios publicitarios propios para WordPress.
 * Version:     1.0.0
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * Author:      Piensaenweb
 * Author URI:  https://piensaenweb.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geogastronomica
 * Domain Path: /languages
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check de version PHP minima.
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', function () {
		$message = sprintf(
			/* translators: %s: version minima de PHP requerida */
			esc_html__( 'GeoGastronomica requiere PHP %s o superior. El plugin ha sido desactivado.', 'geogastronomica' ),
			'8.0'
		);
		echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
	} );
	deactivate_plugins( plugin_basename( __FILE__ ) );
	return;
}

/**
 * Ruta absoluta al archivo principal del plugin.
 */
define( 'GEO_PLUGIN_FILE', __FILE__ );

/**
 * Ruta absoluta al directorio del plugin.
 */
define( 'GEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL del directorio del plugin.
 */
define( 'GEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload de Composer.
require_once GEO_PLUGIN_DIR . 'vendor/autoload.php';

// Hooks de activacion y desactivacion.
register_activation_hook( __FILE__, array( 'GeoGastronomica', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GeoGastronomica', 'deactivate' ) );

// Inicializar el plugin.
GeoGastronomica::get_instance();
