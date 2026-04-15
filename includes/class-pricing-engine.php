<?php
/**
 * Pricing Engine class for calculations
 *
 * @package Jewellery_Settings
 */

namespace Jewellery_Settings;

/**
 * Pricing Engine Class
 */
class Pricing_Engine {

	/**
	 * Instance
	 *
	 * @var Pricing_Engine
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Pricing_Engine
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Calculate price for a product variation
	 *
	 * @param float $weight Weight in grams.
	 * @param string $metal Metal type (gold, rose-gold, silver).
	 * @param float $purity Purity value (24, 18, 14, 9, 925).
	 * @param float $diamond_carat Diamond carat.
	 * @return array
	 */
	public function calculate_price( $weight, $metal, $purity, $diamond_carat = 0 ) {
		$settings = get_option( 'jewellery_settings', array() );

		// Validate inputs
		if ( $weight < 0 ) {
			return array(
				'error' => __( 'Weight cannot be negative', 'jewellery-settings' ),
			);
		}

		// Calculate metal price
		$metal_price = $this->calculate_metal_price( $weight, $metal, $purity, $settings );

		// Calculate making charges
		$making = $this->calculate_making( $weight, $metal, $metal_price, $settings );

		// Calculate diamond price
		$diamond_price = $this->calculate_diamond_price( $diamond_carat, $metal, $purity, $settings );

		// Other charges (per metal)
		$other_charges = $this->calculate_other_charges( $metal, $purity, $settings );

		// Calculate subtotal
		$subtotal = $metal_price + $making + $diamond_price + $other_charges;

		// Final price (no GST calculation, WooCommerce handles it)
		$final_price = max( 0, $subtotal );

		return array(
			'final_price'   => $final_price,
			'metal_price'   => $metal_price,
			'making'        => $making,
			'diamond_price' => $diamond_price,
			'other_charges' => $other_charges,
			'breakdown'     => array(
				'weight'        => $weight,
				'metal'         => $metal,
				'purity'        => $purity,
				'diamond_carat' => $diamond_carat,
			),
		);
	}

	/**
	 * Calculate metal price
	 *
	 * @param float $weight Weight in grams.
	 * @param string $metal Metal type.
	 * @param float $purity Purity value.
	 * @param array $settings Plugin settings.
	 * @return float
	 */
	private function calculate_metal_price( $weight, $metal, $purity, $settings ) {
		$gold_price = floatval( $settings['gold_price'] ?? 0 );
		$silver_price = floatval( $settings['silver_price'] ?? 0 );

		// Determine if silver
		$is_silver = ( $metal === 'silver' || $purity == 925 );

		if ( $is_silver ) {
			return $weight * $silver_price;
		}

		// Gold calculation: weight × (gold_price × (purity / 24))
		$purity_factor = $purity / 24;
		return $weight * $gold_price * $purity_factor;
	}

	/**
	 * Calculate making charges
	 *
	 * @param float $weight Weight in grams.
	 * @param string $metal Metal type.
	 * @param float $metal_price Calculated metal price.
	 * @param array $settings Plugin settings.
	 * @return float
	 */
	private function calculate_making( $weight, $metal, $metal_price, $settings ) {
		$is_silver = ( $metal === 'silver' );

		if ( $is_silver ) {
			$making_type = $settings['silver_making_type'] ?? 'percentage';
			$making_value = floatval( $settings['silver_making_value'] ?? 0 );
		} else {
			$making_type = $settings['gold_making_type'] ?? 'percentage';
			$making_value = floatval( $settings['gold_making_value'] ?? 0 );
		}

		if ( $making_type === 'percentage' ) {
			return $metal_price * ( $making_value / 100 );
		} else {
			// flat_per_gram
			return $weight * $making_value;
		}
	}

	/**
	 * Calculate diamond price
	 *
	 * @param float  $diamond_carat Diamond carat.
	 * @param string $metal Metal type.
	 * @param float  $purity Purity value.
	 * @param array  $settings Plugin settings.
	 * @return float
	 */
	private function calculate_diamond_price( $diamond_carat, $metal, $purity, $settings ) {
		$is_silver = ( $metal === 'silver' || $purity == 925 );

		if ( $is_silver ) {
			$rate = floatval( $settings['silver_diamond_rate'] ?? ( $settings['diamond_rate'] ?? 0 ) );
		} else {
			$rate = floatval( $settings['gold_diamond_rate'] ?? ( $settings['diamond_rate'] ?? 0 ) );
		}

		return $diamond_carat * $rate;
	}

	/**
	 * Calculate other charges (per metal, flat amount per item)
	 *
	 * @param string $metal Metal type.
	 * @param float  $purity Purity value.
	 * @param array  $settings Plugin settings.
	 * @return float
	 */
	private function calculate_other_charges( $metal, $purity, $settings ) {
		$is_silver = ( $metal === 'silver' || $purity == 925 );

		if ( $is_silver ) {
			return floatval( $settings['silver_other_charges'] ?? 0 );
		}

		return floatval( $settings['gold_other_charges'] ?? 0 );
	}

	/**
	 * Get metal type from product variation
	 *
	 * @param int $variation_id Variation product ID.
	 * @return string
	 */
	public function get_metal_from_variation( $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! is_a( $variation, 'WC_Product_Variation' ) ) {
			return 'gold';
		}

		$metal = $variation->get_attribute( 'pa_metal' );
		if ( empty( $metal ) ) {
			return 'gold';
		}

		return strtolower( trim( $metal ) );
	}

	/**
	 * Get purity from product variation
	 *
	 * @param int $variation_id Variation product ID.
	 * @return float
	 */
	public function get_purity_from_variation( $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! is_a( $variation, 'WC_Product_Variation' ) ) {
			return 24;
		}

		$purity_attr = $variation->get_attribute( 'pa_purity' );
		if ( empty( $purity_attr ) ) {
			return 24;
		}

		// Convert purity attribute to numeric value
		$purity_attr = strtolower( trim( $purity_attr ) );

		// Map purity strings to numbers
		$purity_map = array(
			'24k'  => 24,
			'24'   => 24,
			'18k'  => 18,
			'18'   => 18,
			'14k'  => 14,
			'14'   => 14,
			'9k'   => 9,
			'9'    => 9,
			'925'  => 925,
			'92.5' => 925,
		);

		return $purity_map[ $purity_attr ] ?? 24;
	}

	/**
	 * Get diamond carat from product variation
	 *
	 * @param int $variation_id Variation product ID.
	 * @return float
	 */
	public function get_diamond_carat_from_variation( $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! is_a( $variation, 'WC_Product_Variation' ) ) {
			return 0;
		}

		// Possible keys based on "Diamonds (Carat)"
		$attribute_keys = array(
			'pa_diamonds-carat',
			'diamonds-carat',
			'pa_diamonds_carat',
			'diamonds_carat',
			'Diamonds (Carat)',
			'diamonds-(carat)',
			'pa_diamonds',
			'diamonds',
		);

		$value = '';
		foreach ( $attribute_keys as $key ) {
			$value = $variation->get_attribute( $key );
			if ( ! empty( $value ) ) {
				break;
			}
		}

		// Fallback: Check variation meta directly if attribute call fails
		if ( empty( $value ) ) {
			// WC Variations often store attributes in meta with 'attribute_' prefix
			$meta_keys = array(
				'attribute_pa_diamonds-carat',
				'attribute_diamonds-carat',
				'attribute_pa_diamonds_carat',
				'attribute_diamonds_carat',
			);
			foreach ( $meta_keys as $meta_key ) {
				$meta_val = get_post_meta( $variation_id, $meta_key, true );
				if ( ! empty( $meta_val ) ) {
					$value = $meta_val;
					break;
				}
			}
		}

		if ( $value !== '' && $value !== false ) {
			return floatval( $value );
		}

		return 0;
	}

	/**
	 * Get weight from product
	 *
	 * @param int $product_id Product ID.
	 * @return float
	 */
	public function get_product_weight( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return 0;
		}

		$weight = $product->get_weight();
		return ! empty( $weight ) ? floatval( $weight ) : 0;
	}
}
