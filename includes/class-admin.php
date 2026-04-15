<?php
/**
 * Admin class for settings page
 *
 * @package Jewellery_Settings
 */

namespace Jewellery_Settings;

/**
 * Admin Class
 */
class Admin {

	/**
	 * Instance
	 *
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Admin
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
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_jewellery_preview_price', array( $this, 'ajax_preview_price' ) );
		add_action( 'wp_ajax_nopriv_jewellery_get_breakdown', array( $this, 'ajax_get_breakdown' ) );
		add_action( 'wp_ajax_jewellery_get_breakdown', array( $this, 'ajax_get_breakdown' ) );
		add_action( 'wp_ajax_jewellery_sync_prices', array( $this, 'ajax_sync_prices' ) );
		add_filter( 'plugin_action_links_' . JEWELLERY_SETTINGS_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * AJAX get price breakdown
	 */
	public function ajax_get_breakdown() {
		check_ajax_referer( 'jewellery_breakdown_nonce', 'nonce' );

		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
		if ( ! $variation_id ) {
			wp_send_json_error( 'Missing variation ID' );
		}

		$pricing_engine = Pricing_Engine::get_instance();
		
		$weight = $pricing_engine->get_product_weight( $variation_id );
		$metal = $pricing_engine->get_metal_from_variation( $variation_id );
		$purity = $pricing_engine->get_purity_from_variation( $variation_id );
		$diamond_carat = $pricing_engine->get_diamond_carat_from_variation( $variation_id );

		$result = $pricing_engine->calculate_price( $weight, $metal, $purity, $diamond_carat );

		wp_send_json_success( $result );
	}

	/**
	 * Add Settings link on the Plugins list page
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=jewellery_pricing' ) ) . '">' . esc_html__( 'Settings', 'jewellery-settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add menu page
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Jewellery Pricing', 'jewellery-settings' ),
			__( 'Jewellery Pricing', 'jewellery-settings' ),
			'manage_woocommerce',
			'jewellery_pricing',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'jewellery_settings_group',
			'jewellery_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Settings input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$sanitized = array();

		// Sanitize numeric fields
		$numeric_fields = array(
			'gold_price',
			'silver_price',
			'gold_diamond_rate',
			'silver_diamond_rate',
			'gold_making_value',
			'silver_making_value',
			'gold_other_charges',
			'silver_other_charges',
		);

		foreach ( $numeric_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$value = floatval( $input[ $field ] );
				// Validate no negative values
				$sanitized[ $field ] = max( 0, $value );
			}
		}

		// Sanitize select fields
		$sanitized['gold_making_type'] = isset( $input['gold_making_type'] ) && in_array( $input['gold_making_type'], array( 'percentage', 'flat_per_gram' ), true ) ? $input['gold_making_type'] : 'percentage';
		$sanitized['silver_making_type'] = isset( $input['silver_making_type'] ) && in_array( $input['silver_making_type'], array( 'percentage', 'flat_per_gram' ), true ) ? $input['silver_making_type'] : 'percentage';

		// Sanitize checkbox fields
		$sanitized['show_breakdown'] = isset( $input['show_breakdown'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'jewellery-settings' ) );
		}

		$settings = get_option( 'jewellery_settings', array() );
		$last_synced = isset( $settings['last_synced'] ) ? intval( $settings['last_synced'] ) : 0;
		?>
		<div class="wrap jewellery-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="jewellery-container">
				<div class="jewellery-main">
					<form method="post" action="options.php">
						<?php settings_fields( 'jewellery_settings_group' ); ?>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="show_breakdown"><?php esc_html_e( 'Show Calculation on Product Page', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="show_breakdown" name="jewellery_settings[show_breakdown]" value="1" <?php checked( $settings['show_breakdown'] ?? 0, 1 ); ?> />
									<p class="description"><?php esc_html_e( 'Enable this to show the detailed price breakdown on the product page.', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="gold_price"><?php esc_html_e( 'Gold Price (24K)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="gold_price" name="jewellery_settings[gold_price]" value="<?php echo esc_attr( $settings['gold_price'] ?? 0 ); ?>" step="0.01" min="0" />
									<p class="description"><?php esc_html_e( 'Price per gram', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="silver_price"><?php esc_html_e( 'Silver Price (925)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="silver_price" name="jewellery_settings[silver_price]" value="<?php echo esc_attr( $settings['silver_price'] ?? 0 ); ?>" step="0.01" min="0" />
									<p class="description"><?php esc_html_e( 'Price per gram', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr class="jewellery-derived-prices-row">
								<th colspan="2">
									<h4>
										<?php esc_html_e( 'Derived Gold Prices (Auto-calculated)', 'jewellery-settings' ); ?>
									</h4>
								</th>
							</tr>
							<tr class="jewellery-derived-prices">
								<th scope="row">
									<label for="gold_18k_price"><?php esc_html_e( '18K Gold Price', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="text" id="gold_18k_price" readonly value="<?php echo esc_attr( $this->calculate_purity_price( $settings['gold_price'] ?? 0, 18 ) ); ?>" />
									<p class="description"><?php esc_html_e( '24K × (18/24)', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr class="jewellery-derived-prices">
								<th scope="row">
									<label for="gold_14k_price"><?php esc_html_e( '14K Gold Price', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="text" id="gold_14k_price" readonly value="<?php echo esc_attr( $this->calculate_purity_price( $settings['gold_price'] ?? 0, 14 ) ); ?>" />
									<p class="description"><?php esc_html_e( '24K × (14/24)', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr class="jewellery-derived-prices">
								<th scope="row">
									<label for="gold_9k_price"><?php esc_html_e( '9K Gold Price', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="text" id="gold_9k_price" readonly value="<?php echo esc_attr( $this->calculate_purity_price( $settings['gold_price'] ?? 0, 9 ) ); ?>" />
									<p class="description"><?php esc_html_e( '24K × (9/24)', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="gold_diamond_rate"><?php esc_html_e( 'Diamond Price Per Carat (Gold)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="gold_diamond_rate" name="jewellery_settings[gold_diamond_rate]" value="<?php echo esc_attr( $settings['gold_diamond_rate'] ?? ( $settings['diamond_rate'] ?? 0 ) ); ?>" step="0.01" min="0" />
									<p class="description"><?php esc_html_e( 'Price per carat for gold / rose gold products', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="silver_diamond_rate"><?php esc_html_e( 'Diamond Price Per Carat (Silver)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="silver_diamond_rate" name="jewellery_settings[silver_diamond_rate]" value="<?php echo esc_attr( $settings['silver_diamond_rate'] ?? 0 ); ?>" step="0.01" min="0" />
									<p class="description"><?php esc_html_e( 'Price per carat for silver products', 'jewellery-settings' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="gold_other_charges"><?php esc_html_e( 'Other Charges (Gold)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="gold_other_charges" name="jewellery_settings[gold_other_charges]" value="<?php echo esc_attr( $settings['gold_other_charges'] ?? '' ); ?>" step="0.01" min="0" />
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="silver_other_charges"><?php esc_html_e( 'Other Charges (Silver)', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="silver_other_charges" name="jewellery_settings[silver_other_charges]" value="<?php echo esc_attr( $settings['silver_other_charges'] ?? '' ); ?>" step="0.01" min="0" />
								</td>
							</tr>

							<tr>
								<th colspan="2"><h3><?php esc_html_e( 'Making Charges - Gold & Rose Gold', 'jewellery-settings' ); ?></h3></th>
							</tr>

							<tr>
								<th scope="row">
									<label for="gold_making_type"><?php esc_html_e( 'Making Type', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<select id="gold_making_type" name="jewellery_settings[gold_making_type]">
										<option value="percentage" <?php selected( $settings['gold_making_type'] ?? 'percentage', 'percentage' ); ?>>
											<?php esc_html_e( 'Percentage (%)', 'jewellery-settings' ); ?>
										</option>
										<option value="flat_per_gram" <?php selected( $settings['gold_making_type'] ?? 'percentage', 'flat_per_gram' ); ?>>
											<?php esc_html_e( 'Flat per Gram', 'jewellery-settings' ); ?>
										</option>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="gold_making_value"><?php esc_html_e( 'Making Value', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="gold_making_value" name="jewellery_settings[gold_making_value]" value="<?php echo esc_attr( $settings['gold_making_value'] ?? 0 ); ?>" step="0.01" min="0" />
									<p class="description" id="gold_making_desc"></p>
								</td>
							</tr>

							<tr>
								<th colspan="2"><h3><?php esc_html_e( 'Making Charges - Silver', 'jewellery-settings' ); ?></h3></th>
							</tr>

							<tr>
								<th scope="row">
									<label for="silver_making_type"><?php esc_html_e( 'Making Type', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<select id="silver_making_type" name="jewellery_settings[silver_making_type]">
										<option value="percentage" <?php selected( $settings['silver_making_type'] ?? 'percentage', 'percentage' ); ?>>
											<?php esc_html_e( 'Percentage (%)', 'jewellery-settings' ); ?>
										</option>
										<option value="flat_per_gram" <?php selected( $settings['silver_making_type'] ?? 'percentage', 'flat_per_gram' ); ?>>
											<?php esc_html_e( 'Flat per Gram', 'jewellery-settings' ); ?>
										</option>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="silver_making_value"><?php esc_html_e( 'Making Value', 'jewellery-settings' ); ?></label>
								</th>
								<td>
									<input type="number" id="silver_making_value" name="jewellery_settings[silver_making_value]" value="<?php echo esc_attr( $settings['silver_making_value'] ?? 0 ); ?>" step="0.01" min="0" />
									<p class="description" id="silver_making_desc"></p>
								</td>
							</tr>
						</table>

						<?php submit_button(); ?>
					</form>
				</div>

				<div class="jewellery-sidebar">
					<div class="jewellery-box">
						<h3><?php esc_html_e( 'Price Preview Calculator', 'jewellery-settings' ); ?></h3>
						<div id="jewellery-preview">
							<div class="preview-form">
								<label><?php esc_html_e( 'Weight (grams)', 'jewellery-settings' ); ?></label>
								<input type="number" id="preview_weight" step="0.01" min="0" value="10" />

								<label><?php esc_html_e( 'Metal', 'jewellery-settings' ); ?></label>
								<select id="preview_metal">
									<option value="gold"><?php esc_html_e( 'Gold', 'jewellery-settings' ); ?></option>
									<option value="rose-gold"><?php esc_html_e( 'Rose Gold', 'jewellery-settings' ); ?></option>
									<option value="silver"><?php esc_html_e( 'Silver', 'jewellery-settings' ); ?></option>
								</select>

								<label><?php esc_html_e( 'Purity', 'jewellery-settings' ); ?></label>
								<select id="preview_purity">
									<option value="24"><?php esc_html_e( '24K', 'jewellery-settings' ); ?></option>
									<option value="18"><?php esc_html_e( '18K', 'jewellery-settings' ); ?></option>
									<option value="14"><?php esc_html_e( '14K', 'jewellery-settings' ); ?></option>
									<option value="9"><?php esc_html_e( '9K', 'jewellery-settings' ); ?></option>
									<option value="925"><?php esc_html_e( '925 Silver', 'jewellery-settings' ); ?></option>
								</select>

								<label><?php esc_html_e( 'Diamond Carat', 'jewellery-settings' ); ?></label>
								<input type="number" id="preview_diamond" step="0.01" min="0" value="0" />

								<button type="button" class="button" id="preview_calculate"><?php esc_html_e( 'Calculate', 'jewellery-settings' ); ?></button>
							</div>

							<div id="preview_result" style="display: none; margin-top: 15px; padding: 15px; background: #f5f5f5; border-left: 4px solid #0073aa;">
								<strong><?php esc_html_e( 'Calculated Price:', 'jewellery-settings' ); ?></strong>
								<div id="preview_price" style="font-size: 24px; color: #0073aa; margin-top: 10px;">₹0.00</div>
								<div id="preview_breakdown" style="margin-top: 10px; font-size: 12px; color: #666;"></div>
							</div>
						</div>
					</div>

					<div class="jewellery-box">
						<h3><?php esc_html_e( 'Sync Prices', 'jewellery-settings' ); ?></h3>
						<p><?php esc_html_e( 'Update all product variations with calculated prices', 'jewellery-settings' ); ?></p>
						<button type="button" class="button button-primary" id="sync_button"><?php esc_html_e( 'Sync All Prices', 'jewellery-settings' ); ?></button>
						<div id="sync_progress" style="display: none; margin-top: 10px;">
							<div class="progress-bar" style="width: 100%; height: 20px; background: #ddd; border-radius: 3px; overflow: hidden;">
								<div id="progress_fill" style="height: 100%; width: 0%; background: #0073aa; transition: width 0.3s;"></div>
							</div>
							<p id="sync_status" style="margin-top: 5px; font-size: 12px;"></p>
						</div>
						<?php if ( $last_synced > 0 ) : ?>
							<p style="margin-top: 10px; font-size: 12px; color: #666;">
								<?php
								printf(
									esc_html__( 'Last synced: %s', 'jewellery-settings' ),
									esc_html( wp_date( 'Y-m-d H:i:s', $last_synced ) )
								);
								?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate purity price
	 *
	 * @param float $gold_price Base gold price.
	 * @param int   $purity Purity value.
	 * @return float
	 */
	private function calculate_purity_price( $gold_price, $purity ) {
		return round( $gold_price * ( $purity / 24 ), 2 );
	}

	/**
	 * AJAX preview price
	 */
	public function ajax_preview_price() {
		check_ajax_referer( 'jewellery_nonce', 'nonce' );

		$weight = isset( $_POST['weight'] ) ? floatval( $_POST['weight'] ) : 0;
		$metal = isset( $_POST['metal'] ) ? sanitize_text_field( wp_unslash( $_POST['metal'] ) ) : 'gold';
		$purity = isset( $_POST['purity'] ) ? floatval( $_POST['purity'] ) : 24;
		$diamond = isset( $_POST['diamond'] ) ? floatval( $_POST['diamond'] ) : 0;

		$pricing_engine = Pricing_Engine::get_instance();
		$result = $pricing_engine->calculate_price( $weight, $metal, $purity, $diamond );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX sync prices
	 */
	public function ajax_sync_prices() {
		check_ajax_referer( 'jewellery_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jewellery-settings' ) );
		}

		$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$limit  = isset( $_POST['limit'] ) ? min( intval( $_POST['limit'] ), 100 ) : 20;

		$sync_handler = Sync_Handler::get_instance();
		$result = $sync_handler->sync_all_products( $offset, $limit );

		wp_send_json_success( $result );
	}
}
