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

		// Size guide and custom dropdown
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_size_guide' ) );

		// Auto-add attributes based on category mapping
		add_action( 'woocommerce_process_product_meta', array( $this, 'auto_add_category_attributes' ), 20 );
	}

	/**
	 * Automatically add attributes to products based on category mapping
	 *
	 * @param int $product_id Product ID.
	 */
	public function auto_add_category_attributes( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$mapping_settings = get_option( 'jewellery_attribute_mapping', array( 'mappings' => array() ) );
		$mappings = $mapping_settings['mappings'] ?? array();

		if ( empty( $mappings ) ) {
			return;
		}

		$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		$attributes = $product->get_attributes();
		$changed = false;

		foreach ( $mappings as $mapping ) {
			$cat_slug = $mapping['category'];
			$attr_slug = $mapping['attribute'];

			if ( in_array( $cat_slug, $categories, true ) ) {
				// Check if the global attribute exists, if not, create it
				$attribute_id = wc_attribute_taxonomy_id_by_name( $attr_slug );
				
				if ( ! $attribute_id ) {
					// Remove 'pa_' prefix for wc_create_attribute
					$raw_attr_name = str_replace( 'pa_', '', $attr_slug );
					$label = ucwords( str_replace( array( 'pa_', '-', '_' ), array( '', ' ', ' ' ), $attr_slug ) );
					
					$attribute_id = wc_create_attribute( array(
						'name'         => $label,
						'slug'         => $raw_attr_name,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					) );

					// Register the taxonomy immediately so it can be used in this request
					register_taxonomy(
						$attr_slug,
						apply_filters( 'woocommerce_taxonomy_objects_' . $attr_slug, array( 'product' ) ),
						apply_filters( 'woocommerce_taxonomy_args_' . $attr_slug, array(
							'labels'       => array( 'name' => $label ),
							'hierarchical' => false,
							'show_ui'      => false,
							'query_var'    => true,
							'rewrite'      => false,
						) )
					);

					// Clear transient to ensure WC picks up the new attribute
					delete_transient( 'wc_attribute_taxonomies' );
				}

				if ( ! isset( $attributes[ $attr_slug ] ) ) {
					$attribute = new \WC_Product_Attribute();
					$attribute->set_id( $attribute_id );
					$attribute->set_name( $attr_slug );
					
					// Get available terms
					$terms = get_terms( array( 'taxonomy' => $attr_slug, 'hide_empty' => false ) );
					$term_slugs = wp_list_pluck( $terms, 'slug' );
					
					// If no terms exist, we still add the attribute to the product 
					// so the admin sees it's required for this category.
					$attribute->set_options( $term_slugs );
					$attribute->set_position( 0 );
					$attribute->set_visible( true );
					$attribute->set_variation( true );

					$attributes[ $attr_slug ] = $attribute;
					$changed = true;
				} else {
					// Ensure it is set as a variation attribute
					if ( ! $attributes[ $attr_slug ]->get_variation() ) {
						$attributes[ $attr_slug ]->set_variation( true );
						$changed = true;
					}
				}
			}
		}

		if ( $changed ) {
			$product->set_attributes( $attributes );
			$product->save();
		}
	}

	/**
	 * Add size guide and custom dropdown to product page
	 */
	public function add_size_guide() {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		$mapping_settings = get_option( 'jewellery_attribute_mapping', array( 'mappings' => array() ) );
		$mappings = $mapping_settings['mappings'] ?? array();

		$active_mapping = null;
		$active_sizes = array();

		foreach ( $mappings as $mapping ) {
			$attr_slug = $mapping['attribute'];
			$sizes = wc_get_product_terms( $product->get_id(), $attr_slug, array( 'fields' => 'names' ) );
			
			if ( ! empty( $sizes ) ) {
				$active_mapping = $mapping;
				$active_sizes = $sizes;
				break;
			}
		}

		if ( ! $active_mapping ) {
			return;
		}

		$type = $active_mapping['type'] ?? 'ring';
		$label = ( 'ring' === $type ) ? __( 'Ring Size:', 'jewellery-settings' ) : __( 'Bangle Size:', 'jewellery-settings' );
		$placeholder = ( 'ring' === $type ) ? __( 'Select Ring Size', 'jewellery-settings' ) : __( 'Select Bangle Size', 'jewellery-settings' );
		$modal_title = ( 'ring' === $type ) ? __( 'Ring Size Guide', 'jewellery-settings' ) : __( 'Bangle Size Guide', 'jewellery-settings' );
		$attr_name = $active_mapping['attribute'];
		?>

		<div class="sharva-size-wrapper" data-attribute="<?php echo esc_attr( $attr_name ); ?>">

			<!-- Label -->
			<div class="sharva-size-label">
				<span><?php echo esc_html( $label ); ?></span>
				<a href="#" class="sharva-size-guide-link" onclick="openSizeGuideTable(event)">
					📏 <?php esc_html_e( 'Size Guide', 'jewellery-settings' ); ?>
				</a>
			</div>

			<!-- Custom Dropdown -->
			<div class="sharva-dropdown-wrap">
				<div class="sharva-dropdown-selected" id="sharvaDropdownSelected" onclick="toggleSharvaDropdown()">
					<span id="sharvaSelectedText"><?php echo esc_html( $placeholder ); ?></span>
					<svg class="sharva-arrow" id="sharvaArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="6 9 12 15 18 9"/>
					</svg>
				</div>

				<div class="sharva-dropdown-list" id="sharvaDropdownList">
					<?php foreach ( $active_sizes as $size ) : ?>
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
						<h3><?php echo esc_html( $modal_title ); ?></h3>
						<span onclick="closeSizeGuideTable()">&times;</span>
					</div>
					
					<?php if ( 'ring' === $type ) : ?>
						<p style="color:#666;font-size:14px;margin-bottom:16px;">
							<?php esc_html_e( 'Find your perfect ring size using the chart below.', 'jewellery-settings' ); ?>
						</p>

						<div style="margin-bottom: 20px; text-align: center;">
							<a href="<?php echo esc_url( plugins_url( 'assets/docs/ring-size-chart.pdf', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ) ); ?>" target="_blank" style="display: inline-block; padding: 8px 15px; background: #8B6914; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
								📄 <?php esc_html_e( 'Download Size Chart (PDF)', 'jewellery-settings' ); ?>
							</a>
							<br>
							<img src="<?php echo esc_url( plugins_url( 'assets/images/ring-size-chart.webp', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ) ); ?>" alt="Ring Size Chart" style="max-width: 100%; height: auto; border: 1px solid #eee; border-radius: 8px;">
						</div>

						<div class="sharva-guide-tip">
							<strong><?php esc_html_e( '💡 How to measure:', 'jewellery-settings' ); ?></strong> <?php esc_html_e( 'Wrap a strip of paper around your finger, mark where it overlaps, then measure the length in mm — that is your circumference.', 'jewellery-settings' ); ?>
						</div>
					<?php else : ?>
						<p style="color:#666;font-size:14px;margin-bottom:16px;">
							<?php esc_html_e( 'Use this chart to find your correct bangle or bracelet size.', 'jewellery-settings' ); ?>
						</p>

						<?php 
						$settings = get_option( 'jewellery_settings', array() );
						$bangle_img = $settings['bangle_size_guide_img'] ?? '';
						$bangle_pdf = $settings['bangle_size_guide_pdf'] ?? '';
						?>

						<?php if ( ! empty( $bangle_pdf ) || ! empty( $bangle_img ) ) : ?>
							<div style="margin-bottom: 20px; text-align: center;">
								<?php if ( ! empty( $bangle_pdf ) ) : ?>
									<a href="<?php echo esc_url( $bangle_pdf ); ?>" target="_blank" style="display: inline-block; padding: 8px 15px; background: #8B6914; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
										📄 <?php esc_html_e( 'Download Size Chart (PDF)', 'jewellery-settings' ); ?>
									</a>
									<br>
								<?php endif; ?>
								
								<?php if ( ! empty( $bangle_img ) ) : ?>
									<img src="<?php echo esc_url( $bangle_img ); ?>" alt="Bangle Size Chart" style="max-width: 100%; height: auto; border: 1px solid #eee; border-radius: 8px;">
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="sharva-table-responsive">
							<table class="sharva-size-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Indian Size', 'jewellery-settings' ); ?></th>
										<th><?php esc_html_e( 'Diameter (mm)', 'jewellery-settings' ); ?></th>
										<th><?php esc_html_e( 'Circumference (mm)', 'jewellery-settings' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr><td>2.2</td><td>54.0</td><td>169.4</td></tr>
									<tr><td>2.4</td><td>57.2</td><td>179.3</td></tr>
									<tr><td>2.5</td><td>58.7</td><td>184.4</td></tr>
									<tr><td>2.6</td><td>60.3</td><td>189.5</td></tr>
									<tr><td>2.8</td><td>63.5</td><td>199.4</td></tr>
									<tr><td>2.10</td><td>66.7</td><td>209.3</td></tr>
									<tr><td>2.12</td><td>69.9</td><td>219.5</td></tr>
									<tr><td>2.14</td><td>73.0</td><td>229.4</td></tr>
									<tr><td>3.0</td><td>76.2</td><td>239.4</td></tr>
								</tbody>
							</table>
						</div>

						<div class="sharva-guide-tip" style="margin-top: 20px;">
							<strong><?php esc_html_e( '📏 How to Measure Hand Circumference:', 'jewellery-settings' ); ?></strong>
							<p style="margin: 5px 0;"><?php esc_html_e( '1. Bring your thumb and little finger together.', 'jewellery-settings' ); ?></p>
							<p style="margin: 5px 0;"><?php esc_html_e( '2. Wrap a string around the widest part of your hand (across the knuckles).', 'jewellery-settings' ); ?></p>
							<p style="margin: 5px 0;"><?php esc_html_e( '3. Measure the string length to find your circumference.', 'jewellery-settings' ); ?></p>
						</div>
					<?php endif; ?>
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
				plugins_url( 'assets/js/admin.js', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ),
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
				plugins_url( 'assets/css/admin.css', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ),
				array(),
				JEWELLERY_SETTINGS_VERSION
			);
		}
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		if ( ! is_product() && ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		wp_enqueue_style(
			'jewellery-frontend',
			plugins_url( 'assets/css/frontend.css', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ),
			array(),
			JEWELLERY_SETTINGS_VERSION
		);

		if ( is_product() ) {
			wp_enqueue_script(
				'jewellery-frontend',
				plugins_url( 'assets/js/frontend.js', JEWELLERY_SETTINGS_PATH . 'jewellery-settings.php' ),
				array( 'jquery' ),
				JEWELLERY_SETTINGS_VERSION,
				true
			);
		}
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
