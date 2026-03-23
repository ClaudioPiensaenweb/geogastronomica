<?php
/**
 * REST API endpoint para tracking de estadisticas.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra endpoint REST para recibir eventos de Beacon API.
 */
class REST_Stats {

	/**
	 * Namespace de la API.
	 */
	private const NAMESPACE = 'geoad/v1';

	/**
	 * Stats tracker.
	 *
	 * @var Stats_Tracker
	 */
	private Stats_Tracker $tracker;

	/**
	 * Constructor.
	 *
	 * @param Stats_Tracker $tracker Instancia del tracker.
	 */
	public function __construct( Stats_Tracker $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_tracking_data' ) );
	}

	/**
	 * Registrar rutas REST.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_track' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return $value > 0;
						},
					),
					'event_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return in_array( $value, array( 'impression', 'click' ), true );
						},
					),
					'_nonce'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Manejar request de tracking.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response Response.
	 */
	public function handle_track( \WP_REST_Request $request ): \WP_REST_Response {
		$nonce = $request->get_param( '_nonce' );
		if ( ! wp_verify_nonce( $nonce, 'geoad_tracking' ) ) {
			return new \WP_REST_Response( array( 'error' => 'invalid_nonce' ), 403 );
		}

		$post_id    = $request->get_param( 'post_id' );
		$event_type = $request->get_param( 'event_type' );

		if ( get_post_type( $post_id ) !== CPT_Anuncio::POST_TYPE ) {
			return new \WP_REST_Response( array( 'error' => 'invalid_post' ), 400 );
		}

		$success = $this->tracker->record_event( $post_id, $event_type );

		return new \WP_REST_Response(
			array( 'success' => $success ),
			$success ? 200 : 500
		);
	}

	/**
	 * Pasar datos de tracking al JS frontend.
	 */
	public function localize_tracking_data(): void {
		wp_register_script(
			'geoad-tracking',
			GEO_PLUGIN_URL . 'assets/js/geoad-tracking.js',
			array(),
			GeoGastronomica::VERSION,
			true
		);

		wp_localize_script(
			'geoad-tracking',
			'geodTrackingData',
			array(
				'endpoint' => esc_url_raw( rest_url( self::NAMESPACE . '/track' ) ),
				'nonce'    => wp_create_nonce( 'geoad_tracking' ),
			)
		);

		wp_enqueue_script( 'geoad-tracking' );
	}
}
