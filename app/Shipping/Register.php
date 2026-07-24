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

use Woo_BG\Admin\Pigeon as Pigeon_Admin;
use Woo_BG\Cron\Pigeon as Pigeon_Cron;

defined( 'ABSPATH' ) || exit;

class Register {
	public function __construct( $container ) {
		self::maybe_register_econt();
		self::maybe_register_speedy();
		self::maybe_register_boxnow();
		self::maybe_register_pigeon();
		self::maybe_register_cvc();

		if ( woo_bg_is_shipping_enabled() ) {
			add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ), 0 );
			add_filter( 'wc_cart_totals_shipping_method_cost', array( __CLASS__, 'change_price_label_if_not_calculated' ), 10, 2 );
			add_filter( 'woocommerce_shipping_rate_label', array( __CLASS__, 'add_fsh_nc_labels' ), 100, 2 );
			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( __CLASS__, 'set_shipping_rate_taxes_for_recalculation' ), 10, 2 );
		}
	}

	public static function maybe_register_econt() {
		if ( woo_bg_get_option( 'apis', 'enable_econt' ) !== 'yes' ) {
			return;
		}

		new Econt_Cron();
		new Econt\Address();
		new Econt\Office();
		new Econt\APSBoxes();
		new Econt_Admin();

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_econt_method' ) );
		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\Econt\Method', 'validate_econt_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Econt\Method', 'save_label_data_to_order' ), 200, 2 );

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
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Speedy\Method', 'save_label_data_to_order' ), 200, 2 );

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
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\BoxNow\Method', 'save_label_data_to_order' ), 200, 2 );
		
		if ( woo_bg_get_option( 'boxnow_send_from', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\BoxNow', 'generate_label_after_order_generated' ), 25 );
		}

		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\BoxNow\Method', 'add_label_number_to_email' ), 1, 4 );
		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\BoxNow\Method', 'enqueue_scripts' ) );
	}

	public static function maybe_register_pigeon() {
		if ( woo_bg_get_option( 'apis', 'enable_pigeon' ) !== 'yes' ) {
			return;
		}

		new Pigeon_Cron();
		new Pigeon\Address();
		new Pigeon\Office();
		new Pigeon\Locker();
		new Pigeon\APSBoxes();
		new Pigeon\PackageBoxes();
		new Pigeon_Admin();

		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_pigeon_method' ) );
		add_action( 'woocommerce_after_checkout_validation', array( 'Woo_BG\Shipping\Pigeon\Method', 'validate_pigeon_method' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Shipping\Pigeon\Method', 'save_label_data_to_order' ), 200, 2 );

		if ( woo_bg_get_option( 'pigeon', 'label_after_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'Woo_BG\Admin\Pigeon', 'generate_label_after_order_generated' ), 25 );
		}
		
		add_action( 'woocommerce_email_order_details', array( 'Woo_BG\Shipping\Pigeon\Method', 'add_label_number_to_email' ), 1, 4 );
		add_action( 'wp_enqueue_scripts', array( 'Woo_BG\Shipping\Pigeon\Method', 'enqueue_scripts' ) );
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

	public static function register_pigeon_method( $methods ) {
		$methods[ Pigeon\Method::METHOD_ID ] = 'Woo_BG\Shipping\Pigeon\Method';

		return $methods;
	}

	public static function update_order_review( $source ) {
		self::update_session( $source );
		
	    $packages = WC()->cart->get_shipping_packages();

	    foreach ( $packages as $key => $value ) {
	        $shipping_session = "shipping_for_package_$key";
	        unset( WC()->session->$shipping_session );
	    }

	    WC()->cart->calculate_shipping();
	    
	    return;
	}

	public static function change_price_label_if_not_calculated( $output, $method ) {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen_method = $chosen_methods[0] ?? '';

		if ( strpos( $method->label, __('Free shipping', 'bulgarisation-for-woocommerce') ) !== false ) {
			$output = __( 'Free shipping', 'bulgarisation-for-woocommerce' );
		} else if( $chosen_method !== $method->get_id() && $method->cost <= 0 ) {
			$output = __( 'Choose to calculate', 'bulgarisation-for-woocommerce' );	
		} else if ( strpos( $method->get_method_id(), 'woo_bg' ) !== false && $method->cost <= 0 ) {
			$output = __( 'Not calculated', 'bulgarisation-for-woocommerce' );
		}

		return $output;
	}

	public static function add_fsh_nc_labels( $label, $rate ) {
		if ( strpos( $label, 'woocommerce-Price-amount' ) !== false ) {
			return $label;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

		$chosen_method = $chosen_methods[0] ?? '';


		$meta_data = $rate->get_meta_data();

		if ( !empty( $meta_data['free_shipping'] ) && $meta_data['free_shipping'] == true ) {
			$label = sprintf( __( '%s: <span class="woocommerce-Price-amount">%s</span>', 'bulgarisation-for-woocommerce' ), $label, __( 'Free shipping', 'bulgarisation-for-woocommerce' ) );
		} else if ( 
			( 
				!woo_bg_is_pro_activated() || 
				woo_bg_get_option( 'shipping_methods', 'change_radio_buttons_to_images' ) !== 'yes'
			) && 
			empty( $rate->get_cost() )
		) {
			if ( $chosen_method !== $rate->get_id() ) {
				$label = sprintf( __( '%s: <span class="woocommerce-Price-amount">%s</span>', 'bulgarisation-for-woocommerce' ), $label, __( 'Choose to calculate', 'bulgarisation-for-woocommerce' ) );	
			} else {
				$label = sprintf( __( '%s: <span class="woocommerce-Price-amount">%s</span>', 'bulgarisation-for-woocommerce' ), $label, __( 'Not calculated', 'bulgarisation-for-woocommerce' ) );
			}
		}

		return $label;
	}

	public static function set_shipping_rate_taxes_for_recalculation( $method, $calculate_tax_for ) {
		if ( strpos( $method[ 'method_id' ], 'woo_bg' ) === false ) {
			return;
		}

		if ( wc_tax_enabled() ) {
			$country = $calculate_tax_for['country'] ?? 'BG';
			$method->set_taxes( woo_bg_get_shipping_rate_taxes( $method->get_total(), $country ) );
		}
	}

	public static function update_session( $source = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		$wc = WC();

		if ( ! $wc || ! $wc->session || ! $wc->customer ) {
			return;
		}

		$post_data = self::get_checkout_post_data( $source );

		if ( empty( $post_data ) ) {
			return;
		}

		if ( isset( $post_data['payment_method'] ) && is_string( $post_data['payment_method'] ) ) {
			$wc->session->set( 'chosen_payment_method', $post_data['payment_method'] );
		}

		self::update_chosen_shipping_methods( $post_data['shipping_method'] ?? array() );

		$customer_props = self::get_customer_address_props( $post_data, 'billing' );
		$shipping_from  = ( wc_ship_to_billing_address_only() || empty( $post_data['ship_to_different_address'] ) ) ? 'billing' : 'shipping';

		$customer_props = array_merge(
			$customer_props,
			self::get_customer_address_props( $post_data, $shipping_from, 'shipping' )
		);

		$should_save_customer = ! empty( $customer_props );

		if ( $customer_props ) {
			$wc->customer->set_props( $customer_props );
		}

		if ( array_key_exists( 'has_full_address', $post_data ) && is_scalar( $post_data['has_full_address'] ) ) {
			$wc->customer->set_calculated_shipping( wc_string_to_bool( $post_data['has_full_address'] ) );
			$should_save_customer = true;
		}

		if ( $should_save_customer ) {
			$wc->customer->save();
		}
	}

	private static function get_checkout_post_data( $source ) {
		$parsed_data = array();

		if ( is_string( $source ) && '' !== $source ) {
			$parsed_data = self::parse_post_data_string( $source );
		} elseif ( is_array( $source ) ) {
			$parsed_data = self::clean_post_value( $source );
		} elseif ( isset( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_post_data = $_POST['post_data']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( is_string( $raw_post_data ) ) {
				$parsed_data = self::parse_post_data_string( $raw_post_data );
			}
		}

		$posted_data = array();

		foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( 'post_data' === $key ) {
				continue;
			}

			$posted_data[ $key ] = self::clean_post_value( $value );
		}

		return self::normalize_checkout_post_data( array_replace_recursive( $parsed_data, $posted_data ) );
	}

	private static function parse_post_data_string( $post_data ) {
		$parsed = array();

		parse_str( $post_data, $parsed );

		return self::clean_post_value( $parsed );
	}

	private static function clean_post_value( $value ) {
		return wc_clean( wp_unslash( $value ) );
	}

	private static function normalize_checkout_post_data( $post_data ) {
		$aliases = array(
			'billing_country'    => 'country',
			'billing_state'      => 'state',
			'billing_postcode'   => 'postcode',
			'billing_city'       => 'city',
			'billing_address_1'  => 'address',
			'billing_address_2'  => 'address_2',
			'shipping_country'   => 's_country',
			'shipping_state'     => 's_state',
			'shipping_postcode'  => 's_postcode',
			'shipping_city'      => 's_city',
			'shipping_address_1' => 's_address',
			'shipping_address_2' => 's_address_2',
		);

		foreach ( $aliases as $canonical_key => $alias_key ) {
			if (
				( ! array_key_exists( $canonical_key, $post_data ) || null === $post_data[ $canonical_key ] )
				&& array_key_exists( $alias_key, $post_data )
			) {
				$post_data[ $canonical_key ] = $post_data[ $alias_key ];
			}
		}

		return $post_data;
	}

	private static function update_chosen_shipping_methods( $posted_shipping_methods ) {
		if ( is_string( $posted_shipping_methods ) ) {
			$posted_shipping_methods = array( 0 => $posted_shipping_methods );
		}

		if ( ! is_array( $posted_shipping_methods ) ) {
			return;
		}

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$updated                 = false;

		if ( ! is_array( $chosen_shipping_methods ) ) {
			$chosen_shipping_methods = array();
		}

		foreach ( $posted_shipping_methods as $index => $method_id ) {
			if ( ! is_string( $method_id ) ) {
				continue;
			}

			if (
				! array_key_exists( $index, $chosen_shipping_methods )
				|| $chosen_shipping_methods[ $index ] !== $method_id
			) {
				$chosen_shipping_methods[ $index ] = $method_id;
				$updated = true;
			}
		}

		if ( $updated ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		}
	}

	private static function get_customer_address_props( $post_data, $source_prefix, $target_prefix = null ) {
		$target_prefix = $target_prefix ?: $source_prefix;
		$props         = array();
		$fields        = array( 'country', 'state', 'postcode', 'city', 'address_1', 'address_2' );

		foreach ( $fields as $field ) {
			$source_key = "{$source_prefix}_{$field}";

			if ( ! array_key_exists( $source_key, $post_data ) ) {
				continue;
			}

			$value = $post_data[ $source_key ];

			if ( null !== $value && is_scalar( $value ) ) {
				$props[ "{$target_prefix}_{$field}" ] = $value;
			}
		}

		return $props;
	}
}
