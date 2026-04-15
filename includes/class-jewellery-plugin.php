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

		// Frontend price breakdown
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_price_breakdown' ), 15 );
	}

	/**
	 * Display price breakdown on product page
	 */
	public function display_price_breakdown() {
		$settings = get_option( 'jewellery_settings', array() );
		if ( empty( $settings['show_breakdown'] ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		?>
		<div id="jewellery-price-breakdown" style="margin: 20px 0; padding: 15px; border: 1px solid #eee; border-radius: 5px; background: #fafafa; display: none;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Price Breakdown', 'jewellery-settings' ); ?></h4>
			<div class="breakdown-content" style="font-size: 14px;">
				<!-- Content will be populated via JS when variation is selected -->
				<p><?php esc_html_e( 'Select an option to see price details.', 'jewellery-settings' ); ?></p>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).on('found_variation', 'form.variations_form', function(event, variation) {
					var $container = $('#jewellery-price-breakdown');
					var $content = $container.find('.breakdown-content');
					
					// We need to fetch the breakdown from the server because variation object doesn't have it
					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'jewellery_get_breakdown',
							variation_id: variation.variation_id,
							nonce: '<?php echo esc_js( wp_create_nonce( 'jewellery_breakdown_nonce' ) ); ?>'
						},
						success: function(response) {
							if (response.success) {
								var data = response.data;
								var html = '';
								var formatter = new Intl.NumberFormat('en-IN', {
									style: 'currency',
									currency: 'INR',
									minimumFractionDigits: 2
								});

								html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Gold/Metal:</span> <strong>' + formatter.format(data.metal_price) + '</strong></div>';
								html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Making Charges:</span> <strong>' + formatter.format(data.making) + '</strong></div>';
								if (data.diamond_price > 0) {
									html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Diamond Price:</span> <strong>' + formatter.format(data.diamond_price) + '</strong></div>';
								}
								if (data.other_charges > 0) {
									html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Other Charges:</span> <strong>' + formatter.format(data.other_charges) + '</strong></div>';
								}
								html += '<hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">';
								html += '<div style="display:flex; justify-content:space-between; font-weight:bold; font-size:16px;"><span>Total:</span> <span>' + formatter.format(data.final_price) + '</span></div>';
								
								$content.html(html);
								$container.slideDown();
							}
						}
					});
				});

				$(document).on('reset_data', 'form.variations_form', function() {
					$('#jewellery-price-breakdown').slideUp();
				});
			});
		</script>
		<?php
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
					'show_breakdown'       => 0,
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
