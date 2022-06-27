<?php
namespace Woo_BG\Front_End\Checkout;
use Ddeboer\Vatin\Validator;

defined( 'ABSPATH' ) || exit;

class EU_Vat {
	private static $eu_countries = array();

	private static $country_codes_patterns = array(
		'AT' => 'U[A-Z\d]{8}',
		'BE' => '0\d{9}',
		'BG' => '\d{9,10}',
		'CY' => '\d{8}[A-Z]',
		'CZ' => '\d{8,10}',
		'DE' => '\d{9}',
		'DK' => '(\d{2} ?){3}\d{2}',
		'EE' => '\d{9}',
		'EL' => '\d{9}',
		'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
		'FI' => '\d{8}',
		'FR' => '([A-Z]{2}|[A-Z0-9]{2})\d{9}',
		'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
		'HR' => '\d{11}',
		'HU' => '\d{8}',
		'IE' => '[A-Z\d]{8,10}',
		'IT' => '\d{11}',
		'LT' => '(\d{9}|\d{12})',
		'LU' => '\d{8}',
		'LV' => '\d{11}',
		'MT' => '\d{8}',
		'NL' => '\d{9}B\d{2}',
		'PL' => '\d{10}',
		'PT' => '\d{9}',
		'RO' => '\d{2,10}',
		'SE' => '\d{12}',
		'SI' => '\d{8}',
		'SK' => '\d{10}',
	);

	private static $data = array(
		'vat_number' => false,
		'validation' => false,
	);

	private static $ip_country = false;

	public function __construct() {
		add_action( 'woocommerce_checkout_fields', array( __CLASS__, 'vat_number_field' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'process_checkout' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'ajax_update_checkout_totals' ) );

		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'set_order_data' ) );
		add_action( 'woocommerce_create_refund', array( __CLASS__, 'set_refund_data' ) );


		// Add VAT to addresses.
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'formatted_billing_address' ), 10, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'output_company_vat_number' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'localisation_address_formats' ), 10, 2 );

		// Digital goods taxable location.
		add_filter( 'woocommerce_get_tax_location', array( __CLASS__, 'woocommerce_get_tax_location' ), 10, 2 );

		// Add VAT Number in order endpoint (REST API).
		add_filter( 'woocommerce_api_order_response', array( __CLASS__, 'add_vat_number_to_order_response' ) );
		add_filter( 'woocommerce_rest_prepare_shop_order', array( __CLASS__, 'add_vat_number_to_order_response' ) );
	}

	public static function get_eu_countries() {
		if ( empty( self::$eu_countries ) ) {
			self::$eu_countries = apply_filters( 'woo_bg/eu_vat_number/country_codes', array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'IM', 'MC', ) );
		}
		return self::$eu_countries;
	}

	public static function reset() {
		WC()->customer->set_is_vat_exempt( false );
		self::$data = array(
			'vat_number' => false,
			'validation' => false,
		);
	}

	public static function vat_number_field( $fields ) {
		if ( ! WC()->cart->needs_payment() ) {
			return $fields;
		}

		$fields['billing']['billing_vat_number'] = array(
			'label'    => __( 'VAT number', 'woo-bg' ),
			'required' => ( woo_bg_get_option('nap', 'dds_number_required' ) !== 'no' ),
			'class'    => array(
				'form-row-first',
				'update_totals_on_change',
				'woo-bg-company-info',
			),
			'id' => 'woo_bg_eu_vat_number',
			'priority' => 120,
		);

		return $fields;
	}

	public static function get_vat_number_prefix( $country ) {
		switch ( $country ) {
			case 'GR' :
				$vat_prefix = 'EL';
			break;
			case 'IM' :
				$vat_prefix = 'GB';
			break;
			case 'MC' :
				$vat_prefix = 'FR';
			break;
			default :
				$vat_prefix = $country;
			break;
		}
		return $vat_prefix;
	}

	public static function get_formatted_vat_number( $vat ) {
		$vat = strtoupper( str_replace( array( ' ', '-', '_', '.' ), '', $vat ) );

		if ( in_array( substr( $vat, 0, 2 ), array_merge( self::get_eu_countries(), array( 'EL' ) ) ) ) {
			$vat = substr( $vat, 2 );
		}

		return $vat;
	}

	public static function get_ip_country() {
		if ( false === self::$ip_country ) {
			$geoip            = \WC_Geolocation::geolocate_ip();
			self::$ip_country = $geoip['country'];
		}
		return self::$ip_country;
	}

	public static function vat_number_is_valid( $vat_number, $country ) {
		$vat_prefix     = self::get_vat_number_prefix( $country );
		$transient_name = 'vat_number_' . $vat_prefix . $vat_number;
		$cached_result  = get_transient( $transient_name );

		if ( ! empty( $cached_result ) ) {
			return 'yes' === $cached_result;
		}

		$vies = new Validator;

		$vat_number = str_replace( array( ' ', '.', '-', ',', ', ' ), '', trim( $vat_number ) );

		if ( ! isset( self::$country_codes_patterns[ $vat_prefix ] ) ) {
			return new \WP_Error( 'api', __( 'Invalid country code', 'woo-bg' ) );
		}

		try {
			$maybe_vies_validation = ( woo_bg_get_option( 'checkout', 'enable_vies' ) === 'yes' ); 
			
			$is_valid = $vies->isValid( $vat_prefix . $vat_number, $maybe_vies_validation );

			set_transient( $transient_name, $is_valid ? 'yes' : 'no', 7 * DAY_IN_SECONDS );
			return $is_valid;
		} catch( SoapFault $e ) {
			return new \WP_Error( 'api', __( 'Error communicating with the VAT validation server - please try again', 'woo-bg' ) );
		}

		return false;
	}

	public static function validate( $vat_number, $billing_country ) {
		$vat_number = self::get_formatted_vat_number( $vat_number );
		$valid      = self::vat_number_is_valid( $vat_number, $billing_country );

		if ( is_wp_error( $valid ) ) {
			self::$data['vat_number'] = $vat_number;
			self::$data['validation'] = array(
				'valid' => null,
				'error' => $valid->get_error_message(),
			);
		} else {
			self::$data['vat_number'] = $valid ? self::get_vat_number_prefix( $billing_country ) . $vat_number : $vat_number;
			self::$data['validation'] = array(
				'valid' => $valid,
				'error' => false,
			);
		}
	}

	public static function is_base_country_match( $billing_country, $shipping_country ) {
		if ( 'GB' === WC()->countries->get_base_country() && 'IM' === $billing_country ) {
			return true;
		}

		if ( 'IM' === WC()->countries->get_base_country() && 'GB' === $billing_country ) {
			return true;
		}

		return ( WC()->countries->get_base_country() === $billing_country );
	}

	public static function maybe_set_vat_exempt( $exempt, $billing_country, $shipping_country ) {
		$base_country_match = self::is_base_country_match( $billing_country, $shipping_country );

		if ( ! $base_country_match ) {
			$exempt = apply_filters( 'woo_bg_vat_number_set_is_vat_exempt', $exempt, $base_country_match, $billing_country, $shipping_country );
			WC()->customer->set_is_vat_exempt( $exempt );
		}
	}

	public static function process_checkout() {
		self::reset();

		$billing_country  = wc_clean( $_POST['billing_country'] );
		$shipping_country = wc_clean( ! empty( $_POST['shipping_country'] ) && ! empty( $_POST['ship_to_different_address'] ) ? $_POST['shipping_country'] : $_POST['billing_country'] );

		if ( woo_bg_get_option('nap', 'dds_number_required' ) !== 'no' ) {
			self::validate( wc_clean( $_POST['billing_vat_number'] ), $billing_country );

			if ( false === self::$data['validation']['valid'] && isset( $_REQUEST[ 'billing_to_company' ] ) && sanitize_text_field( $_REQUEST[ 'billing_to_company' ] ) ) {
				wc_add_notice( sprintf( __( 'You have entered an invalid %1$s (%2$s) for your billing country (%3$s).', 'woo-bg' ), __( 'VAT number', 'woo-bg' ), self::$data['vat_number'], $billing_country ), 'error' );
			} else {
				wc_add_notice( self::$data['validation']['error'], 'error' );
			}
		}
	}

	public static function ajax_update_checkout_totals( $form_data ) {
		// If order total is zero (free), don't need to proceed.
		if ( ! WC()->cart->needs_payment() ) {
			return $form_data;
		}

		parse_str( $form_data, $form_data );

		self::reset();

		if ( empty( $form_data['billing_country'] ) && empty( $form_data['shipping_country'] ) ) {
			return $form_data;
		}

		if ( woo_bg_get_option('nap', 'dds_number_required' ) !== 'no' ) {
			if ( in_array( $form_data['billing_country'], self::get_eu_countries() ) && ! empty( $form_data['billing_vat_number'] ) ) {
				$shipping_country = wc_clean( ! empty( $form_data['shipping_country'] ) && ! empty( $form_data['ship_to_different_address'] ) ? $form_data['shipping_country'] : $form_data['billing_country'] );

				self::validate( wc_clean( $form_data['billing_vat_number'] ), $form_data['billing_country'] );

				if ( true === (bool) self::$data['validation']['valid'] ) {
					self::maybe_set_vat_exempt( true, $form_data['billing_country'], $shipping_country );
				} else {
					wc_add_notice( sprintf( __( 'You have entered an invalid VAT number (%1$s) for your billing country (%2$s).', 'woo-bg' ), $form_data['billing_vat_number'], $form_data['billing_country'] ), 'error' );
				}
			}
		}
	}

	public static function cart_has_digital_goods() {
		$has_digital_goods = false;

		if ( WC()->cart->get_cart() ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];
				if ( ! $_product->needs_shipping() ) {
					$has_digital_goods = true;
				}
			}
		}

		return apply_filters( 'woocommerce_cart_has_digital_goods', $has_digital_goods );
	}

	public static function formatted_billing_address( $address, $order ) {
		$vat_id = woo_bg_get_vat_from_order( $order );

		if ( $vat_id ) {
			$address['vat_id'] = $vat_id;
		}
		return $address;
	}

	public static function output_company_vat_number( $formats, $args ) {
		if ( isset( $args['vat_id'] ) ) {
			$formats['{vat_id}'] = sprintf( __( 'VAT Number: %s', 'woo-bg' ), $args['vat_id'] );
		} else {
			$formats['{vat_id}'] = '';
		}
		return $formats;
	}

	public static function localisation_address_formats( $formats ) {
		foreach ( $formats as $key => $format ) {
			if ( 'default' === $key || in_array( $key, self::get_eu_countries() ) ) {
				$formats[ $key ] .= "\n{vat_id}";
			}
		}
		return $formats;
	}

	public static function woocommerce_get_tax_location( $location, $tax_class = '' ) {
		if ( ! empty( WC()->customer ) && sanitize_title( $tax_class ) === woo_bg_get_option( 'checkout', 'digital_tax_classes' ) ) {
			return array(
				WC()->customer->get_billing_country(),
				WC()->customer->get_billing_state(),
				WC()->customer->get_billing_postcode(),
				WC()->customer->get_billing_city(),
			);
		}

		return $location;
	}

	public static function add_vat_number_to_order_response( $response ) {
		if ( is_a( $response, 'WP_REST_Response' ) ) {
			$response->data['vat_number'] = get_post_meta( $response->data['id'], '_billing_vat_number', true );
		} elseif ( is_array( $response ) && ! empty( $response['id'] ) ) {
			// Legacy endpoint.
			$response['vat_number'] = get_post_meta( $response['id'], '_billing_vat_number', true );
		}

		return $response;
	}

	public static function set_order_data( $order ) {
		$order->update_meta_data( '_billing_vat_number', self::$data['vat_number'] );
		$order->update_meta_data( '_vat_number_is_validated', ! is_null( self::$data['validation']['valid'] ) ? 'true' : 'false' );
		$order->update_meta_data( '_vat_number_is_valid', true === self::$data['validation']['valid'] ? 'true' : 'false' );

		if ( false !== self::get_ip_country() ) {
			$order->update_meta_data( '_customer_ip_country', self::get_ip_country() );
			$order->update_meta_data( '_customer_self_declared_country', ! empty( $_POST['location_confirmation'] ) ? 'true' : 'false' );
		}
	}

	public static function set_refund_data( $refund ) {
		$order = wc_get_order( $refund->get_parent_id() );
		$refund->update_meta_data( '_billing_vat_number', woo_bg_get_vat_from_order( $order ) );
	}
}
