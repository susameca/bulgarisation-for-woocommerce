<?php
namespace Woo_BG\Front_End;
defined( 'ABSPATH' ) || exit;

class Multi_Currency_Converter {
	public static function update_products_from_bgn_to_eur() {
		self::verify_price_update();
		
		global $woo_bg_messages;
		$woo_bg_messages = [];

		$step = isset( $_REQUEST['step'] ) ? sanitize_text_field( $_REQUEST['step'] ) : 1;
		$response = [
			'stats' => self::get_page_stats(),
			'action' => 'woo_bg_change_bgn_to_eur',
			'data' => [
				'_wpnonce' => $_REQUEST['_wpnonce'],
			],
		];

		$products = wc_get_products( array(
		    'limit' => 5,
		    'page' => $step,
		) );
		
		if ( !empty( $products ) ) {
			foreach ( $products as $product ) {
				if ( ! self::is_product_already_converted( $product ) ) {
					if ( $product->is_type('variable') ) {
						self::convert_product_variable_price( $product );
					} else {
						self::convert_product_price( $product );
					}

					$product->update_meta_data( 'woo_bg_converted', 'yes' );
					$product->save();
				} else {
					$woo_bg_messages[] = sprintf( __( '%s - price already updated.', 'woo-bg' ), $product->get_formatted_name() );
				}
			}
		}

		if ( !empty( $woo_bg_messages ) ) {
			foreach ( $woo_bg_messages as $message ) {
				$response['log'] .= wpautop( $message );
			}
		}

		if ( $response['stats']['percents'] < 100 ) {
			$response['data']['step'] = $step + 1;
			$response['loading_text'] = $response['stats']['percents'] . "%";
		} else {
			$response['action'] = 'woo_bg_change_shop_currency_to_eur';
		}

		wp_send_json_success( $response );
		wp_die();
	}

	public static function get_page_stats() {
		$step = isset( $_REQUEST['step'] ) ? sanitize_text_field( $_REQUEST['step'] ) : 1;
		$products = new \WP_Query( [
			'post_type' => 'product',
			'posts_per_page' => 1,
			'fields' => 'ids',
		] );

		$all_products = $products->found_posts;
		$current_count = $step * 5;
		$percent = ( $current_count / $all_products ) * 100;

		return [
			'percents' => min( number_format( $percent, 2, '.', '' ), 100 ),
			'all_products' => $all_products,
			'current_count' => $current_count,
		];
	}

	public static function verify_price_update() {
		$errors = [];
		
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woo_bg_change_bgn_to_eur' ) ) {
			$errors[] = __( 'Nonce was not provided!', 'woo-bg' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			$errors[] = __( 'You cannot change the prices.', 'woo-bg' );
		}

		if ( !empty( $errors ) ) {
			wp_send_json_error( [
				'message' => implode( ' ', $errors ),
			] );
			wp_die();
		}
	}

	public static function is_product_already_converted( $product ) {
		return ( $product->get_meta( 'woo_bg_converted' ) );
	}

	public static function convert_product_price( $product ) {
		global $woo_bg_messages;

		$old_price = $product->get_regular_price();
		$new_price = Multi_Currency::convert_to_eur( $product->get_regular_price() );

		$product->update_meta_data( 'woo_bg_regular_price_bgn', $old_price );
		$product->set_regular_price( $new_price );

		$woo_bg_messages[] = sprintf( __( '%s - Change regular price from %s to %s', 'woo-bg' ), $product->get_formatted_name(), $old_price, $new_price );

		if ( $sale_price = $product->get_sale_price() ) {
			$new_sale_price = Multi_Currency::convert_to_eur( $sale_price );

			$product->update_meta_data( 'woo_bg_sale_price_bgn', $sale_price );
			$product->set_sale_price( $new_sale_price );
			$woo_bg_messages[] = sprintf( __( '%s - Change sale price from %s to %s', 'woo-bg' ), $product_name, $sale_price, $new_sale_price );
		}

		$product->update_meta_data( 'woo_bg_converted', 'yes' );
		$product->save();
	}

	public static function convert_product_variable_price( $product ) {
		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			self::convert_product_price( $variation );
		}
	}

	public static function change_shop_currency_to_eur() {
		self::verify_price_update();

		update_option('woocommerce_currency', 'EUR');

		wp_send_json_success( [
			'log' => wpautop( __( 'Shop currency changed to EUR', 'woo-bg' ) ),
		] );
	}
}
