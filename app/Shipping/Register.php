<?php
namespace Woo_BG\Shipping;
use Woo_BG\Admin\Econt as Econt_Admin;
use Woo_BG\Cron\Econt as Econt_Cron;
use Woo_BG\Admin\CVC as CVC_Admin;
use Woo_BG\Cron\CVC as CVC_Cron;
use Woo_BG\Admin\Speedy as Speedy_Admin;
use Woo_BG\Cron\Speedy as Speedy_Cron;

defined( 'ABSPATH' ) || exit;

class Register {
	public function __construct( $container ) {
		self::maybe_register_econt();
		self::maybe_register_speedy();
		self::maybe_register_cvc();

		if ( woo_bg_is_shipping_enabled() ) {
			add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ), 1, 2 );
			
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
