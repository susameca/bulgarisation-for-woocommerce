<?php
namespace Woo_BG\Shipping;
use Woo_BG\Admin\Econt as Econt_Admin;

defined( 'ABSPATH' ) || exit;

class Register {
	public function __construct( $container ) {
		self::maybe_register_econt();
		self::maybe_register_cvc();

		if ( 
			woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' || 
			woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' || 
			woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' 
		) {
			add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ), 1, 2 );
		}
	}

	public static function maybe_register_econt() {
		if ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ) {
			new Econt\Address();
			new Econt\Office();
			new Econt_Admin();

			add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_econt_method') );
			add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\Econt\Method', 'validate_econt_method'), 20, 2);
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Econt\Method', 'save_label_data_to_order'), 20, 2);
			add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\Econt\Method', 'enqueue_scripts' ) );
		}
	}

	public static function maybe_register_cvc() {
		if ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ) {
			new CVC\Address();
			new CVC\Office();
			//new Econt_Admin();

			add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_cvc_method') );
			//add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\CVC\Method', 'validate_cvc_method'), 20, 2);
			//add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\CVC\Method', 'save_label_data_to_order'), 20, 2);
			add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\CVC\Method', 'enqueue_scripts' ) );
		}
	}

	public static function register_econt_method( $methods ) {
		$methods[ Econt\Method::METHOD_ID ] = 'Woo_BG\Shipping\Econt\Method';

		return $methods;
	}

	public static function register_cvc_method( $methods ) {
		$methods[ CVC\Method::METHOD_ID ] = 'Woo_BG\Shipping\CVC\Method';

		return $methods;
	}

	public static function update_order_review( $array ) {
	    $packages = WC()->cart->get_shipping_packages();

	    foreach ($packages as $key => $value) {
	        $shipping_session = "shipping_for_package_$key";
	        unset(WC()->session->$shipping_session);
	    }

	    WC()->cart->calculate_shipping();
	    return;
	}
}
