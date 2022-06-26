<?php
namespace Woo_BG\Shipping;

defined( 'ABSPATH' ) || exit;

class CheckoutLayout {
	public function __construct() {
		if ( woo_bg_get_option( 'nap', 'alternative_shipping_table' ) === 'yes' ) {
			add_filter( 'woocommerce_locate_template', array( __CLASS__, 'intercept_shipping_table_template' ), 10, 3 );
		}
	}

	public static function intercept_shipping_table_template( $template, $template_name, $template_path ) {
		if ( is_checkout() && $template_name === 'cart/cart-shipping.php' ) {
			$template = woo_bg()->plugin_dir_path() . 'templates/cart/cart-shipping.php';
		}

		return $template;
	}
}
