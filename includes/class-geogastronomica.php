<?php
/**
 * Clase principal del plugin GeoGastronomica.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase bootstrap del plugin. Singleton.
 */
class GeoGastronomica {

	/**
	 * Instancia unica.
	 *
	 * @var GeoGastronomica|null
	 */
	private static ?GeoGastronomica $instance = null;

	/**
	 * Version del plugin.
	 */
	public const VERSION = '1.0.0';

	/**
	 * Prefijo para meta keys.
	 */
	public const META_PREFIX = '_geo_';

	/**
	 * Text domain.
	 */
	public const TEXT_DOMAIN = 'geogastronomica';

	/**
	 * Obtener la instancia unica.
	 *
	 * @return GeoGastronomica
	 */
	public static function get_instance(): GeoGastronomica {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor privado (Singleton).
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Registrar hooks de WordPress.
	 */
	private function init_hooks(): void {
		$this->init_components();
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Inicializar componentes del plugin.
	 */
	private function init_components(): void {
		( new CPT_Anuncio() )->init();
		( new Meta_Boxes() )->init();

		$cache = new Cache_Manager();
		$cache->init();

		( new Shortcode_GeoAd() )->init( $cache );
		( new Cron_Manager() )->init();
		( new Admin_Columns() )->init();

		$stats = new Stats_Tracker();
		$stats->init();

		( new REST_Stats( $stats ) )->init();

		$this->init_update_checker();
	}

	/**
	 * Configurar actualizaciones desde GitHub.
	 */
	private function init_update_checker(): void {
		if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
			return;
		}

		\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/piensaenweb/geogastronomica/',
			GEO_PLUGIN_FILE,
			'geogastronomica'
		);
	}

	/**
	 * Cargar traducciones.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			dirname( plugin_basename( GEO_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Ejecutar al activar el plugin.
	 */
	public static function activate(): void {
		Cron_Manager::schedule();
		Stats_Tracker::schedule();
		Stats_Tracker::create_table();
	}

	/**
	 * Ejecutar al desactivar el plugin.
	 */
	public static function deactivate(): void {
		Cron_Manager::unschedule();
		Stats_Tracker::unschedule();
	}
}
