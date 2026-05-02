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

		// Ring size guide and custom dropdown
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_ring_size_guide' ) );

		// Auto-add ring size attribute for 'Ring' category
		add_action( 'woocommerce_process_product_meta', array( $this, 'auto_add_ring_size_attribute' ), 20 );
	}

	/**
	 * Automatically add ring size attribute to products in the 'Ring' category
	 *
	 * @param int $product_id Product ID.
	 */
	public function auto_add_ring_size_attribute( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		// Check if in 'Ring' or 'Rings' category
		$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		if ( ! in_array( 'ring', $categories, true ) && ! in_array( 'rings', $categories, true ) ) {
			return;
		}

		$attributes = $product->get_attributes();

		if ( ! isset( $attributes['pa_ring-size'] ) ) {
			$attribute = new \WC_Product_Attribute();
			$attribute_id = wc_attribute_taxonomy_id_by_name( 'pa_ring-size' );
			
			if ( ! $attribute_id ) {
				return;
			}

			$attribute->set_id( $attribute_id );
			$attribute->set_name( 'pa_ring-size' );
			
			// Get all available terms for pa_ring-size
			$terms = get_terms( array( 'taxonomy' => 'pa_ring-size', 'hide_empty' => false ) );
			$term_slugs = wp_list_pluck( $terms, 'slug' );
			
			if ( empty( $term_slugs ) ) {
				return;
			}

			$attribute->set_options( $term_slugs );
			$attribute->set_position( 0 );
			$attribute->set_visible( true );
			$attribute->set_variation( true );

			$attributes['pa_ring-size'] = $attribute;
			$product->set_attributes( $attributes );
			$product->save();
		}
	}

	/**
	 * Add ring size guide and custom dropdown to product page
	 */
	public function add_ring_size_guide() {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		// Only show if product has ring size attribute
		$ring_sizes = wc_get_product_terms( $product->get_id(), 'pa_ring-size', array( 'fields' => 'names' ) );
		if ( empty( $ring_sizes ) ) {
			return;
		}
		?>

		<div class="sharva-size-wrapper">

			<!-- Label -->
			<div class="sharva-size-label">
				<span><?php esc_html_e( 'Ring Size:', 'jewellery-settings' ); ?></span>
				<a href="#" class="sharva-size-guide-link" onclick="openSizeGuideTable(event)">
					📏 <?php esc_html_e( 'Size Guide', 'jewellery-settings' ); ?>
				</a>
			</div>

			<!-- Custom Dropdown -->
			<div class="sharva-dropdown-wrap">
				<div class="sharva-dropdown-selected" id="sharvaDropdownSelected" onclick="toggleSharvaDropdown()">
					<span id="sharvaSelectedText"><?php esc_html_e( 'Select Ring Size', 'jewellery-settings' ); ?></span>
					<svg class="sharva-arrow" id="sharvaArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="6 9 12 15 18 9"/>
					</svg>
				</div>

				<div class="sharva-dropdown-list" id="sharvaDropdownList">
					<?php foreach ( $ring_sizes as $size ) : ?>
						<div class="sharva-dropdown-item" 
							 data-value="<?php echo esc_attr( strtolower( $size ) ); ?>"
							 data-title="<?php echo esc_attr( $size ); ?>"
							 onclick="selectSharvaSize(this)">
							<?php echo esc_html( $size ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Size Guide Table Popup -->
			<div id="sharvaGuideModal" class="sharva-guide-modal">
				<div class="sharva-guide-content">
					<div class="sharva-guide-header">
						<h3><?php esc_html_e( 'Ring Size Guide', 'jewellery-settings' ); ?></h3>
						<span onclick="closeSizeGuideTable()">&times;</span>
					</div>
					<p style="color:#666;font-size:14px;margin-bottom:16px;">
						<?php esc_html_e( 'Find your perfect ring size using the chart below.', 'jewellery-settings' ); ?>
					</p>

					<div style="margin-bottom: 20px; text-align: center;">
						<a href="https://sharvaexports.com/wp-content/uploads/2026/04/ring-size-chart.pdf" target="_blank" style="display: inline-block; padding: 8px 15px; background: #8B6914; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
							📄 <?php esc_html_e( 'Download Size Chart (PDF)', 'jewellery-settings' ); ?>
						</a>
						<br>
						<img src="https://sharvaexports.com/wp-content/uploads/2026/04/ring-size-chart.webp" alt="Ring Size Chart" style="max-width: 100%; height: auto; border: 1px solid #eee; border-radius: 8px;">
					</div>

					<div class="sharva-guide-tip">
						<strong><?php esc_html_e( '💡 How to measure:', 'jewellery-settings' ); ?></strong> <?php esc_html_e( 'Wrap a strip of paper around your finger, mark where it overlaps, then measure the length in mm — that is your circumference.', 'jewellery-settings' ); ?>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles
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

								html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Metal:</span> <strong>' + formatter.format(data.metal_price) + '</strong></div>';
								html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Making Charges:</span> <strong>' + formatter.format(data.making) + '</strong></div>';
								if (data.diamond_price > 0) {
									html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Diamond Price:</span> <strong>' + formatter.format(data.diamond_price) + '</strong></div>';
								}
								if (data.other_charges > 0) {
									html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Other Charges:</span> <strong>' + formatter.format(data.other_charges) + '</strong></div>';
								}
								html += '<hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">';
								html += '<div style="display:flex; justify-content:space-between; font-weight:bold; font-size:16px;"><span>Total (without GST):</span> <span>' + formatter.format(data.final_price) + '</span></div>';
								
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
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'jewellery-frontend',
			JEWELLERY_SETTINGS_URL . 'assets/css/frontend.css',
			array(),
			JEWELLERY_SETTINGS_VERSION
		);

		wp_enqueue_script(
			'jewellery-frontend',
			JEWELLERY_SETTINGS_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			JEWELLERY_SETTINGS_VERSION,
			true
		);
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
