<?php
/**
 * Plugin Name: Sharva Jewellery Settings
 * Plugin URI: https://sharvaexports.com
 * Description: Dynamic jewellery pricing system for WooCommerce based on metal, purity, weight, and diamonds
 * Version: 1.0.5
 * Author: Rahul Pranami
 * Author URI: https://sharvaexports.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: jewellery-settings
 * Domain Path: /languages
 *
 * @package Jewellery_Settings
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'JEWELLERY_SETTINGS_VERSION', '1.0.8' );
define( 'JEWELLERY_SETTINGS_PATH', plugin_dir_path( __FILE__ ) );
define( 'JEWELLERY_SETTINGS_URL', plugin_dir_url( __FILE__ ) );
define( 'JEWELLERY_SETTINGS_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader
if ( file_exists( JEWELLERY_SETTINGS_PATH . 'vendor/autoload.php' ) ) {
	require_once JEWELLERY_SETTINGS_PATH . 'vendor/autoload.php';
}

// Initialize Update Checker
if ( class_exists( 'YahnisElsts\PluginUpdateChecker\V5\PucFactory' ) ) {
	$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\V5\PucFactory::buildUpdateChecker(
		'https://github.com/RahulPranami/Jewellery-Settings',
		__FILE__,
		'jewellery-settings'
	);
	// Optional: Set the branch that contains the stable release.
	$myUpdateChecker->setBranch( 'main' );
}

// Load required files
require_once JEWELLERY_SETTINGS_PATH . 'includes/class-jewellery-plugin.php';
require_once JEWELLERY_SETTINGS_PATH . 'includes/class-admin.php';
require_once JEWELLERY_SETTINGS_PATH . 'includes/class-pricing-engine.php';
require_once JEWELLERY_SETTINGS_PATH . 'includes/class-sync-handler.php';
require_once JEWELLERY_SETTINGS_PATH . 'includes/class-rest-api.php';

/**
 * Initialize the plugin
 */
function jewellery_settings_init() {
	Jewellery_Settings\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'jewellery_settings_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, array( 'Jewellery_Settings\Plugin', 'activate' ) );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'Jewellery_Settings\Plugin', 'deactivate' ) );
