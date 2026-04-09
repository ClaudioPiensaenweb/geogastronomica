<?php
/**
 * Plugin Name: GeoGastronomica
 * Plugin URI:  https://geogastronomica.com
 * Description: Sistema de gestion de banners y anuncios publicitarios propios para WordPress.
 * Version:     2.0.3
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
			esc_html__( 'GeoGastronomica requiere PHP %s o superior. El plugin ha sido desactivado.', 'geogastronomica' ),
			'8.0'
		);
		echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
	} );
	deactivate_plugins( plugin_basename( __FILE__ ) );
	return;
}

define( 'GEO_PLUGIN_FILE', __FILE__ );
define( 'GEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Cargar clases directamente (sin Composer autoload para fiabilidad).
require_once GEO_PLUGIN_DIR . 'includes/helpers/security.php';
require_once GEO_PLUGIN_DIR . 'includes/class-cpt-anuncio.php';
require_once GEO_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once GEO_PLUGIN_DIR . 'includes/class-shortcode-geoad.php';
require_once GEO_PLUGIN_DIR . 'includes/class-cache-manager.php';
require_once GEO_PLUGIN_DIR . 'includes/class-cron-manager.php';
require_once GEO_PLUGIN_DIR . 'includes/class-admin-columns.php';
require_once GEO_PLUGIN_DIR . 'includes/class-admin-order.php';
require_once GEO_PLUGIN_DIR . 'includes/class-stats-tracker.php';
require_once GEO_PLUGIN_DIR . 'includes/class-rest-stats.php';
require_once GEO_PLUGIN_DIR . 'includes/class-settings.php';
require_once GEO_PLUGIN_DIR . 'includes/class-admin-guide.php';
require_once GEO_PLUGIN_DIR . 'includes/class-auto-inject.php';
require_once GEO_PLUGIN_DIR . 'includes/class-geogastronomica.php';

// Plugin-update-checker (si existe).
$puc_autoload = GEO_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/load-v5p6.php';
if ( file_exists( $puc_autoload ) ) {
	require_once $puc_autoload;
}

// Hooks de activacion y desactivacion.
register_activation_hook( __FILE__, array( 'GeoGastronomica', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GeoGastronomica', 'deactivate' ) );

// Inicializar el plugin.
GeoGastronomica::get_instance();
