<?php
/**
 * Main plugin class
 *
 * @package Jewellery_Settings
 */

namespace Jewellery_Settings;

/**
 * Main Plugin Class
 */
class Plugin {

	/**
	 * Instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Plugin
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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Admin and REST API are initialized directly — this runs during plugins_loaded,
		// which is before admin_menu fires, so hook registration inside Admin will work.
		if ( is_admin() ) {
			Admin::get_instance();
		}
		Rest_API::get_instance();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		// Only load on our settings page
		if ( isset( $screen->id ) && strpos( $screen->id, 'jewellery_pricing' ) !== false ) {
			wp_enqueue_script(
				'jewellery-admin',
				JEWELLERY_SETTINGS_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				JEWELLERY_SETTINGS_VERSION,
				true
			);

			wp_localize_script(
				'jewellery-admin',
				'jewellerySettings',
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'jewellery_nonce' ),
					'adminUrl' => admin_url(),
				)
			);

			wp_enqueue_style(
				'jewellery-admin',
				JEWELLERY_SETTINGS_URL . 'assets/css/admin.css',
				array(),
				JEWELLERY_SETTINGS_VERSION
			);
		}
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		// Frontend scripts can be added here if needed
	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		// Create default options if they don't exist
		if ( ! get_option( 'jewellery_settings' ) ) {
			update_option(
				'jewellery_settings',
				array(
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
				)
			);
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
