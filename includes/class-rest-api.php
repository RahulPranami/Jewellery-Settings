<?php
/**
 * REST API class for mobile app support
 *
 * @package Jewellery_Settings
 */

namespace Jewellery_Settings;

/**
 * REST API Class
 */
class Rest_API {

	/**
	 * Instance
	 *
	 * @var Rest_API
	 */
	private static $instance = null;

	/**
	 * API namespace
	 */
	const NAMESPACE = 'jewellery/v1';

	/**
	 * Get instance
	 *
	 * @return Rest_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 */
	public function register_routes() {
		// GET /wp-json/jewellery/v1/settings
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => '__return_true',
			)
		);

		// POST /wp-json/jewellery/v1/settings
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => $this->get_settings_args(),
			)
		);

		// POST /wp-json/jewellery/v1/preview
		register_rest_route(
			self::NAMESPACE,
			'/preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'preview_price' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_preview_args(),
			)
		);

		// POST /wp-json/jewellery/v1/sync
		register_rest_route(
			self::NAMESPACE,
			'/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sync_prices' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'offset' => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'limit'  => array(
						'type'    => 'integer',
						'default' => 10,
					),
				),
			)
		);
	}

	/**
	 * Get settings arguments
	 *
	 * @return array
	 */
	private function get_settings_args() {
		return array(
			'gold_price'          => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'silver_price'        => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'gold_diamond_rate'   => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'silver_diamond_rate' => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'gold_other_charges'  => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'silver_other_charges' => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'gold_making_type'    => array(
				'type'  => 'string',
				'enum'  => array( 'percentage', 'flat_per_gram' ),
			),
			'gold_making_value'   => array(
				'type'    => 'number',
				'minimum' => 0,
			),
			'silver_making_type'  => array(
				'type'  => 'string',
				'enum'  => array( 'percentage', 'flat_per_gram' ),
			),
			'silver_making_value' => array(
				'type'    => 'number',
				'minimum' => 0,
			),
		);
	}

	/**
	 * Get preview arguments
	 *
	 * @return array
	 */
	private function get_preview_args() {
		return array(
			'weight'        => array(
				'type'     => 'number',
				'required' => true,
				'minimum'  => 0,
			),
			'metal'         => array(
				'type'     => 'string',
				'required' => true,
				'enum'     => array( 'gold', 'rose-gold', 'silver' ),
			),
			'purity'        => array(
				'type'     => 'number',
				'required' => true,
			),
			'diamond_carat' => array(
				'type'    => 'number',
				'default' => 0,
				'minimum' => 0,
			),
		);
	}

	/**
	 * Check admin permission
	 *
	 * @return bool|\WP_Error
	 */
	public function check_admin_permission() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this endpoint', 'jewellery-settings' ),
				array( 'status' => 403 )
			);
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint', 'jewellery-settings' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * GET /settings endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_settings( $request ) {
		$settings = get_option( 'jewellery_settings', array() );

		// Ensure all fields are present
		$defaults = array(
			'gold_price'           => 0,
			'silver_price'         => 0,
			'gold_diamond_rate'    => 0,
			'silver_diamond_rate'  => 0,
			'gold_other_charges'   => 0,
			'silver_other_charges' => 0,
			'gold_making_type'     => 'percentage',
			'gold_making_value'    => 0,
			'silver_making_type'   => 'percentage',
			'silver_making_value'  => 0,
			'last_synced'          => 0,
		);

		$settings = array_merge( $defaults, $settings );

		// Add calculated values
		$settings['18k_price'] = round( $settings['gold_price'] * ( 18 / 24 ), 2 );
		$settings['14k_price'] = round( $settings['gold_price'] * ( 14 / 24 ), 2 );
		$settings['9k_price'] = round( $settings['gold_price'] * ( 9 / 24 ), 2 );

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * POST /settings endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$params = $request->get_json_params();
		$settings = get_option( 'jewellery_settings', array() );

		// Sanitize and validate
		$updatable_fields = array(
			'gold_price',
			'silver_price',
			'gold_diamond_rate',
			'silver_diamond_rate',
			'gold_other_charges',
			'silver_other_charges',
			'gold_making_type',
			'gold_making_value',
			'silver_making_type',
			'silver_making_value',
		);

		foreach ( $updatable_fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				if ( in_array( $field, array( 'gold_price', 'silver_price', 'gold_diamond_rate', 'silver_diamond_rate', 'gold_other_charges', 'silver_other_charges', 'gold_making_value', 'silver_making_value' ), true ) ) {
					$settings[ $field ] = max( 0, floatval( $params[ $field ] ) );
				} elseif ( in_array( $field, array( 'gold_making_type', 'silver_making_type' ), true ) ) {
					if ( in_array( $params[ $field ], array( 'percentage', 'flat_per_gram' ), true ) ) {
						$settings[ $field ] = $params[ $field ];
					}
				}
			}
		}

		update_option( 'jewellery_settings', $settings );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully', 'jewellery-settings' ),
				'data'    => $settings,
			),
			200
		);
	}

	/**
	 * POST /preview endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function preview_price( $request ) {
		$weight = floatval( $request->get_param( 'weight' ) );
		$metal = sanitize_text_field( $request->get_param( 'metal' ) );
		$purity = floatval( $request->get_param( 'purity' ) );
		$diamond_carat = floatval( $request->get_param( 'diamond_carat' ) ?? 0 );

		$pricing_engine = Pricing_Engine::get_instance();
		$result = $pricing_engine->calculate_price( $weight, $metal, $purity, $diamond_carat );

		if ( isset( $result['error'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result['error'],
				),
				400
			);
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /sync endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function sync_prices( $request ) {
		$offset = intval( $request->get_param( 'offset' ) ?? 0 );
		$limit = intval( $request->get_param( 'limit' ) ?? 10 );

		// Limit to reasonable values
		$limit = min( $limit, 50 );

		$sync_handler = Sync_Handler::get_instance();
		$result = $sync_handler->sync_all_products( $offset, $limit );

		return new \WP_REST_Response( $result, 200 );
	}
}
