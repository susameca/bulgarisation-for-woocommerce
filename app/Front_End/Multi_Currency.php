<?php
namespace Woo_BG\Front_End;
defined( 'ABSPATH' ) || exit;

class Multi_Currency {

	public function __construct() {
		if ( !( is_admin() && !wp_doing_ajax() ) ) {
			add_filter( 'wc_price', array( __CLASS__, 'display_price_in_multiple_currencies' ), 1000 );
		}

		add_action( 'woo_bg/invoice/pdf/dompdf', array( __CLASS__, 'add_wc_price_filter_to_pdfs' ) );

		if ( woo_bg_get_option( 'multi_currency', 'cart_rate_message' ) === 'yes' ) {
			add_action( 'woocommerce_proceed_to_checkout' , array( __CLASS__, 'rate_message' ), 1 );
		}

		if ( woo_bg_get_option( 'multi_currency', 'product_rate_message' ) === 'yes' ) {
			add_action( 'woocommerce_product_meta_start' , array( __CLASS__, 'rate_message_product' ), 10 );
		}

		if ( woo_bg_get_option( 'multi_currency', 'shop_rate_message' ) === 'yes' ) {
			add_action( 'woocommerce_after_shop_loop_item' , array( __CLASS__, 'rate_message' ), 5 );
		}

		if ( woo_bg_get_option( 'multi_currency', 'email_rate_message' ) === 'yes' ) {
			add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'add_rate_row_email' ), 10, 2 );
		}

		if ( woo_bg_get_option( 'multi_currency', 'checkout_message' ) ) {
			add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'checkout_message' ), 5 );
		}
	}

	public static function add_wc_price_filter_to_pdfs() {
		add_filter( 'wc_price', array( __CLASS__, 'display_price_in_multiple_currencies' ), 10 );
	}

	public static function display_price_in_multiple_currencies( $price_html ) {
		if ( strpos( $price_html, 'woo-bg--currency' ) !== false ) {
			return $price_html;
		}

		$current_currency = get_woocommerce_currency();
		$price_html_copy = str_replace( ' ', '', $price_html );
		preg_match( '/[0-9.,]+/', $price_html_copy, $matches );
		$price = isset( $matches[0] ) ? $matches[0] : 0;
		$price =  str_replace( [ wc_get_price_thousand_separator(), wc_get_price_decimal_separator() ], [ '', '.' ], $matches[0] );
		
		if( $current_currency == 'BGN' ) {
			$price_eur = self::convert_to_eur($price);
		
			$formatted_price_eur = "<span class=\"woocommerce-Price-amount amount woo-bg--currency amount-eur\"> / " . $price_eur . "&nbsp;€ </span>";

			return $price_html . $formatted_price_eur;
		} elseif ( $current_currency == 'EUR' ) {
			$price_bgn = self::convert_to_bgn($price);
			$formatted_price_eur = "<span class=\"woocommerce-Price-amount amount woo-bg--currency amount-bgn\"> / " . $price_bgn . "&nbsp;лв. </span>";

			return $price_html . $formatted_price_eur;
		}

		return $price_html;
	}

	public static function get_eur_rate() { 
		return 1.95583;
	}

	public static function price_to_float( $price ) {
		$price = str_replace( wc_get_price_thousand_separator(), '.', $price );
		$price = preg_replace( "/[^0-9\.]/", "", $price );
		$price = str_replace( '.', '',substr( $price, 0, -3 ) ) . substr( $price, -3 );

		return (float) $price;
	} 

	public static function float_rate( $price ) {
		return (float) $price;
	} 

	public static function convert_to_eur( $price ) {
		$new_price = self::price_to_float( $price ) / self::float_rate( self::get_eur_rate() );

		return number_format( $new_price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
	}

	public static function convert_to_bgn( $price ) {
		$new_price = self::price_to_float( $price ) * self::float_rate( self::get_eur_rate() );

		return number_format( $new_price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
	}

	public static function rate_message() {
		$html = '<span class="rate_cart_page">' . __( 'Rate: 1 EUR = 1.95583 BGN', 'woo-bg' ) . '</span>';

		echo apply_filters( 'woo_bg/bgn_eur/rate_message', $html );
	}

	public static function rate_message_product() {
		$html = '<span class="rate_cart_page">' . __( 'Rate: 1 EUR = 1.95583 BGN', 'woo-bg' ) . '</span>';

		echo apply_filters( 'woo_bg/bgn_eur/rate_message_product', $html );
	}

	public static function checkout_message() {
		$html = '<div class="foreign-currency-checkout woocommerce-info">' . woo_bg_get_option( 'multi_currency', 'checkout_message' ) . '</div>';

		echo apply_filters( 'woo_bg/bgn_eur/checkout_message', $html );
	}

	public static function add_rate_row_email( $total_rows, $myorder_obj ) {
		$total_rows['used_rate'] = array(
			'label' => __( 'Fixed conversion rate:', 'woo-bg' ),
			'value'   => '1 € = 1.95583 лв.'
		);
		 
		return $total_rows;
	}
}
