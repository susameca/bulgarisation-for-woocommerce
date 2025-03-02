<?php
namespace Woo_BG\Shipping;

use Woo_BG\Admin\Econt as Econt_Admin;
use Woo_BG\Cron\Econt as Econt_Cron;

use Woo_BG\Admin\CVC as CVC_Admin;
use Woo_BG\Cron\CVC as CVC_Cron;

use Woo_BG\Admin\Speedy as Speedy_Admin;
use Woo_BG\Cron\Speedy as Speedy_Cron;

use Woo_BG\Admin\BoxNow as BoxNow_Admin;
use Woo_BG\Cron\BoxNow as BoxNow_Cron;

defined( 'ABSPATH' ) || exit;

class Register {
	public function __construct( $container ) {
		self::maybe_register_econt();
		self::maybe_register_speedy();
		self::maybe_register_boxnow();
		self::maybe_register_cvc();

		if ( woo_bg_is_shipping_enabled() ) {
			add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ), 1, 2 );
			add_filter( 'wc_cart_totals_shipping_method_cost', array( __CLASS__, 'change_price_label_if_not_calculated' ), 10, 2 );
			add_filter( 'woo_bg/speedy/rate', array( __CLASS__, 'add_not_calculated_label' ), 10, 2 );
			add_filter( 'woo_bg/econt/rate', array( __CLASS__, 'add_not_calculated_label' ), 10, 2 );
			add_filter( 'woo_bg/cvc/rate', array( __CLASS__, 'add_not_calculated_label' ), 10, 2 );
			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( __CLASS__, 'set_shipping_rate_taxes_for_recalculation' ), 10, 2 );
			
			new ProductAdditionalFields( $container );
		}
	}

	public static function maybe_register_econt() {
		if ( woo_bg_get_option( 'apis', 'enable_econt' ) !== 'yes' ) {
			return;
		}

		new Econt_Cron();
		new Econt\Address();
		new Econt\Office();
		new Econt_Admin();

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_econt_method' ) );
		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\Econt\Method', 'validate_econt_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Econt\Method', 'save_label_data_to_order' ), 20, 2 );

		if ( woo_bg_get_option( 'econt', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\Econt', 'generate_label_after_order_generated' ), 25 );
		}

		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\Econt\Method', 'add_label_number_to_email' ), 1, 4 );

		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\Econt\Method', 'enqueue_scripts' ) );
	}

	public static function maybe_register_speedy() {
		if ( woo_bg_get_option( 'apis', 'enable_speedy' ) !== 'yes' ) {
			return;
		}

		new Speedy_Cron();
		new Speedy\Address();
		new Speedy\Office();
		new Speedy_Admin();

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_speedy_method' ) );
		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\Speedy\Method', 'validate_speedy_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Speedy\Method', 'save_label_data_to_order' ), 20, 2 );

		if ( woo_bg_get_option( 'speedy', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\Speedy', 'generate_label_after_order_generated' ), 25 );
		}
		
		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\Speedy\Method', 'add_label_number_to_email' ), 1, 4 );

		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\Speedy\Method', 'enqueue_scripts' ) );
	}

	public static function maybe_register_cvc() {
		if ( woo_bg_get_option( 'apis', 'enable_cvc' ) !== 'yes' ) {
			return;
		}

		new CVC_Cron();
		new CVC\Address();
		new CVC\Office();
		new CVC_Admin();

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_cvc_method') );
		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\CVC\Method', 'validate_cvc_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\CVC\Method', 'save_label_data_to_order' ), 20, 2 );

		if ( woo_bg_get_option( 'cvc_sender', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\CVC', 'generate_label_after_order_generated' ), 25 );
		}
		
		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\CVC\Method', 'add_label_number_to_email' ), 1, 4 );

		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\CVC\Method', 'enqueue_scripts' ) );
	}

	public static function maybe_register_boxnow() {
		if ( woo_bg_get_option( 'apis', 'enable_boxnow' ) !== 'yes' ) {
			return;
		}

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_boxnow_method' ) );

		new BoxNow_Cron();
		new BoxNow\Apm();
		new BoxNow_Admin();

		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\BoxNow\Method', 'validate_boxnow_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\BoxNow\Method', 'save_label_data_to_order' ), 20, 2 );
		
		if ( woo_bg_get_option( 'boxnow_send_from', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\BoxNow', 'generate_label' ), 25 );
		}

		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\BoxNow\Method', 'add_label_number_to_email' ), 1, 4 );
		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\BoxNow\Method', 'enqueue_scripts' ) );
	}

	public static function register_econt_method( $methods ) {
		$methods[ Econt\Method::METHOD_ID ] = 'Woo_BG\Shipping\Econt\Method';

		return $methods;
	}

	public static function register_speedy_method( $methods ) {
		$methods[ Speedy\Method::METHOD_ID ] = 'Woo_BG\Shipping\Speedy\Method';

		return $methods;
	}

	public static function register_cvc_method( $methods ) {
		$methods[ CVC\Method::METHOD_ID ] = 'Woo_BG\Shipping\CVC\Method';

		return $methods;
	}

	public static function register_boxnow_method( $methods ) {
		$methods[ BoxNow\Method::METHOD_ID ] = 'Woo_BG\Shipping\BoxNow\Method';

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

	public static function change_price_label_if_not_calculated( $output, $method ) {
		if ( strpos( $method->get_method_id(), 'woo_bg' ) !== false && $method->cost <= 0 ) {
			$output = __( 'Not calculated', 'woo-bg' );
		}

		return $output;
	}

	public static function add_not_calculated_label( $rate, $method ) {
		if ( 
			( 
				!woo_bg_is_pro_activated() || 
				woo_bg_get_option( 'shipping_methods', 'change_radio_buttons_to_images' ) !== 'yes'
			) && 
			$rate['label'] === $method->title && 
			!$rate['cost']
		) {
			$rate['label'] = sprintf( __( '%s: Not calculated', 'woo-bg' ), $rate['label'] );
		}

		return $rate;
	}

	public static function set_shipping_rate_taxes_for_recalculation( $method, $calculate_tax_for ) {
		if ( strpos( $method[ 'method_id' ], 'woo_bg' ) === false ) {
			return;
		}

		if ( wc_tax_enabled() ) {
			$method->set_taxes( woo_bg_get_shipping_rate_taxes( $method->get_total() ) );
		}
	}
}
