<?php
/**
 * Sync Handler class for bulk price updates
 *
 * @package Jewellery_Settings
 */

namespace Jewellery_Settings;

/**
 * Sync Handler Class
 */
class Sync_Handler {

	/**
	 * Instance
	 *
	 * @var Sync_Handler
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Sync_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sync all products
	 *
	 * @param int $offset Pagination offset.
	 * @param int $limit Items per batch.
	 * @return array
	 */
	public function sync_all_products( $offset = 0, $limit = 10 ) {
		// Get variable products
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'variable',
				),
			),
			'fields'         => 'ids',
		);

		$products = get_posts( $args );

		if ( empty( $products ) ) {
			// Update last synced timestamp
			$settings = get_option( 'jewellery_settings', array() );
			$settings['last_synced'] = time();
			update_option( 'jewellery_settings', $settings );

			return array(
				'success' => true,
				'message' => __( 'Sync completed', 'jewellery-settings' ),
				'complete' => true,
			);
		}

		$total_variations = 0;
		$updated_variations = 0;
		$errors = array();

		foreach ( $products as $product_id ) {
			$result = $this->sync_product_variations( $product_id );
			$total_variations += $result['total'];
			$updated_variations += $result['updated'];
			if ( ! empty( $result['errors'] ) ) {
				$errors = array_merge( $errors, $result['errors'] );
			}
		}

		// Log the sync
		$this->log_sync( count( $products ), $total_variations, $updated_variations, $errors );

		return array(
			'success'        => true,
			'products'       => count( $products ),
			'variations'     => $updated_variations,
			'offset'         => $offset + $limit,
			'total_products' => $this->get_total_products_count(),
			'errors'         => $errors,
		);
	}

	/**
	 * Sync variations for a single product
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function sync_product_variations( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return array(
				'total'   => 0,
				'updated' => 0,
				'errors'  => array(),
			);
		}

		$variations = $product->get_children();
		$pricing_engine = Pricing_Engine::get_instance();
		$updated = 0;
		$errors = array();

		foreach ( $variations as $variation_id ) {
			try {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation ) {
					$errors[] = sprintf( 'Variation %d not found', $variation_id );
					continue;
				}

				// Get attributes
				$weight = $pricing_engine->get_product_weight( $variation_id );
				if ( $weight <= 0 ) {
					$errors[] = sprintf( 'Variation %d has no weight', $variation_id );
					continue;
				}

				$metal = $pricing_engine->get_metal_from_variation( $variation_id );
				$purity = $pricing_engine->get_purity_from_variation( $variation_id );
				$diamond_carat = $pricing_engine->get_diamond_carat_from_variation( $variation_id );

				// Calculate price
				$price_result = $pricing_engine->calculate_price( $weight, $metal, $purity, $diamond_carat );

				if ( isset( $price_result['error'] ) ) {
					$errors[] = sprintf( 'Variation %d: %s', $variation_id, $price_result['error'] );
					continue;
				}

				// Update product prices
				$final_price = $price_result['final_price'];
				$variation->set_regular_price( $final_price );
				$variation->set_price( $final_price );
				$variation->save();

				$updated++;
			} catch ( \Exception $e ) {
				$errors[] = sprintf( 'Variation %d error: %s', $variation_id, $e->getMessage() );
			}
		}

		return array(
			'total'   => count( $variations ),
			'updated' => $updated,
			'errors'  => $errors,
		);
	}

	/**
	 * Log sync operation
	 *
	 * @param int   $products_count Number of products synced.
	 * @param int   $total_variations Total variations.
	 * @param int   $updated_variations Updated variations.
	 * @param array $errors Errors encountered.
	 */
	private function log_sync( $products_count, $total_variations, $updated_variations, $errors ) {
		$log_message = sprintf(
			'Sync completed: %d products, %d/%d variations updated at %s',
			$products_count,
			$updated_variations,
			$total_variations,
			wp_date( 'Y-m-d H:i:s' )
		);

		if ( ! empty( $errors ) ) {
			$log_message .= '. Errors: ' . implode( ', ', array_slice( $errors, 0, 5 ) );
		}

		// Store in logs
		error_log( 'Jewellery Settings: ' . $log_message );
	}

	/**
	 * Get total variable products count
	 *
	 * @return int
	 */
	public function get_total_products_count() {
		$args = array(
			'post_type' => 'product',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'variable',
				),
			),
			'fields'    => 'ids',
		);

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}
}
