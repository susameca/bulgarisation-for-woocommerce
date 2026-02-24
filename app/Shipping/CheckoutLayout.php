<?php
namespace Woo_BG\Shipping;

defined( 'ABSPATH' ) || exit;

class CheckoutLayout {
	public function __construct() {
		add_filter('woocommerce_shipping_package_name', array( __CLASS__, 'change_shipping_package_name' ), 10, 2);
		
		if ( woo_bg_get_option( 'checkout', 'alternative_shipping_table' ) === 'yes' ) {
			add_filter( 'woocommerce_locate_template', array( __CLASS__, 'intercept_shipping_table_template' ), 10, 3 );
		}
	}

	public static function change_shipping_package_name( $name, $i ) {
		return __('Shipping', 'bulgarisation-for-woocommerce');
	}

	public static function intercept_shipping_table_template( $template, $template_name, $template_path ) {
		if ( is_checkout() && $template_name === 'cart/cart-shipping.php' ) {
			$template = woo_bg()->plugin_dir_path() . 'templates/cart/cart-shipping.php';
		}

		return $template;
	}
}
