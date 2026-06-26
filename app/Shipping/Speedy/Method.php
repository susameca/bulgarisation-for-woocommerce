<?php
namespace Woo_BG\Shipping\Speedy;
use Woo_BG\Container\Client;

use Woo_BG\Shipping\Packer\Carton_Packer;
use Woo_BG\Shipping\Packer\APSPackage;
use Woo_BG\Shipping\Packer\Product;
use Woo_BG\Shipping\Packer\Size;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_speedy";
	const VOLUMETRIC_WEIGHT_DIVISOR = 5000;
	const MAX_PARCELS = 10;

	public $container, $cookie_data, $package, $delivery_type, $free_shipping_over, $fixed_price, $test, $tax_status, $free_shipping = false;
	private $content_data = null;
	private static $aps_box_sizes = array(
		array(
			'name'       => 'APS',
			'length'     => 62,
			'width'      => 36,
			'height'     => 38,
			'max_weight' => 20,
		),
	);

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = self::METHOD_ID; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - Speedy', 'bulgarisation-for-woocommerce' );  // Title shown in admin
		$this->method_description = __( 'Enables Speedy delivery and automatically calculate shipping price.', 'bulgarisation-for-woocommerce' ); // Description shown in admin
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
		$this->enabled = 'yes';

		$this->init();
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		$this->title                = $this->get_option( 'title' );
		$this->delivery_type        = $this->get_option( 'delivery_type' );
		$this->free_shipping_over   = $this->get_option( 'free_shipping_over' );
		$this->fixed_price          = $this->get_option( 'fixed_price' );
		$this->test                 = $this->get_option( 'test' );
		$this->tax_status           = ( wc_tax_enabled() ) ? 'taxable' : 'none' ;

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package = Array() ) {
		$this->cookie_data = self::get_cookie_data();
		$this->package = $package;
		$this->content_data = null;

		$disable_aps = ( $this->delivery_type === 'automat' && ! APSPackage::package_fits_largest_box( $this->package, self::$aps_box_sizes, self::VOLUMETRIC_WEIGHT_DIVISOR ) );

		if( apply_filters( 'woo_bg/speedy/rate/disable_aps', $disable_aps, $this ) ) {
			return;
		}

		if ( ! $this->package_fits_max_parcels() ) {
			return;
		}

		do_action( 'woo_bg/speedy/rate/before_calculate', $this );

		if ( is_array( $this->cookie_data ) ) {
			$this->cookie_data['fixed_price'] = $this->fixed_price;
		}

		$rate = array(
			'label' => $this->title,
			'meta_data' => [],
		);

		$rate['meta_data']['delivery_type'] = $this->delivery_type;
		$rate['meta_data']['validated'] = false;
		$payment_by_data = $this->generate_payment_by_data();
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');
		$country = apply_filters('woo_bg/speedy/country_for_validation', $package['destination']['country'], $this );

		if ( 
			isset( $this->cookie_data['type'] ) && 
			$this->cookie_data['type'] === $this->delivery_type && 
			( !empty( $chosen_shippings ) && ( $chosen_shippings[0] === $this->id . ':' . $this->instance_id ) ) &&
			( 
				( isset( $this->cookie_data['other'] ) && $this->cookie_data['other'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['streetNumber']) && $this->cookie_data['streetNumber'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['selectedOffice'] ) && $this->cookie_data['selectedOffice'] ) ||
				( isset( $this->cookie_data['mysticQuarter'] ) && ( $this->cookie_data['other'] || $this->cookie_data['streetNumber'] ) )
			) 
		) {
			$request_data = $this->calculate_shipping_price_from_api();
			$rate['meta_data']['validated'] = true;
			$rate['request_data'] = $request_data;

			if ( isset( $request_data['errors'] ) ) {
				$rate['meta_data']['validated'] = false;
				$rate['meta_data']['errors'] = $request_data['errors'];
			} elseif ( isset( $request_data['price'] )  ) {
				$rate['cost'] = $request_data['price'];

				if ( wc_tax_enabled() ) {
					$rate[ 'taxes' ] = woo_bg_get_shipping_rate_taxes( $request_data['price'], $country );
				}
			}
		}


		if ( $this->free_shipping ) {
			$rate['meta_data']['free_shipping'] = true;
			$rate[ 'cost' ] = 0;
			unset( $rate[ 'taxes' ] );
		} 

		if ( !$this->free_shipping && !empty( $this->fixed_price ) ) {
			$tax_rate = apply_filters('woo_bg/shipping/fixed_price_tax_rate', 20, $country );
			$rate[ 'cost' ] = woo_bg_tax_based_price( $this->fixed_price, $tax_rate );
			
			if ( wc_tax_enabled() ) {
				$rate[ 'taxes' ] = woo_bg_get_shipping_rate_taxes( $rate[ 'cost' ], $country );
			}
		}

		$rate['meta_data']['cookie_data'] = $this->cookie_data;
		$rate = apply_filters( 'woo_bg/speedy/rate', $rate, $this );

		// Register the rate
		$this->add_rate( $rate );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'            => array(
				'title'       => __( 'Title', 'bulgarisation-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'bulgarisation-for-woocommerce' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'delivery_type'    => array(
				'title'             => __( 'Delivery Type', 'bulgarisation-for-woocommerce' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'options'           => array(
					'office' => __( 'Office', 'bulgarisation-for-woocommerce' ),
					'automat' => __( 'Automat', 'bulgarisation-for-woocommerce' ),
					'address' => __( 'Address', 'bulgarisation-for-woocommerce' ),
				),
			),
			'test'    => array(
				'title'             => __( 'Review and test', 'bulgarisation-for-woocommerce' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => woo_bg_get_shipping_tests_options(),
			),
			'free_shipping_over' => array(
				'title'       => __( 'Free shipping over', 'bulgarisation-for-woocommerce' ),
				'type'        => 'number',
				'placeholder' => '0',
				'description' => __( 'Free shipping over total cart price.', 'bulgarisation-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'fixed_price' => array(
				'title'       => __( 'Fixed price', 'bulgarisation-for-woocommerce' ),
				'type'        => 'number',
				'placeholder' => '0',
				'description' => __( 'Enter a fixed price that will be payed by the client, and will be included in the COD.', 'bulgarisation-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	public function is_available( $package ) {
		$countries = apply_filters( 'woo_bg/speedy/available_countries', array( 'BG' ) );

		if ( in_array( $package['destination']['country'], $countries ) ) {
			return true;
		}
	}

	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	public static function get_cookie_data() {
		$cookie_data = '';

		if (  isset( $_COOKIE[ 'woo-bg--speedy-address' ] ) ) {
			$cookie_data = json_decode( stripslashes( urldecode( sanitize_text_field( $_COOKIE[ 'woo-bg--speedy-address' ] ) ) ), 1 );
		}
		
		return $cookie_data;
	}

	public function calculate_shipping_price_from_api() {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/speedy/calculate_label', $this->generate_label(), $this );
		
		WC()->session->set( 'woo-bg-speedy-label' , $request_body );

		$request = $this->container[ Client::SPEEDY ]->label_request( 'calculate', $request_body );
		
		if ( !isset( $request ) ) {
			$data['errors'] = __( 'Calculation failed. Please try again.', 'bulgarisation-for-woocommerce' );
		} else if ( isset( $request['error'] ) || isset( $request['calculations'][0]['error'] ) ) {
			if ( isset( $request['calculations'][0]['error'] ) ) {
				$data['errors'] = $request['calculations'][0]['error']['message'];
			} else if ( isset( $request['success'] ) && !$request['success'] && isset( $request['error'] ) ) {
				$data['errors'] = $request['error'];
			} else {
				$data['errors'] = $request['error']['message'];
			}
		} else if ( isset( $request['calculations'] ) ) {
			$calc_data = $request['calculations'][0];
			$data['price_with_vat'] = number_format( $calc_data['price']['total'], 2 );
			$data['price_without_vat'] = number_format( $calc_data['price']['amount'], 2 );
			$data['vat'] = number_format( $calc_data['price']['vat'], 2 );

			$data['price'] = ( wc_tax_enabled() ) ? $data['price_without_vat'] : $data['price_with_vat'];
		}

		return $data;
	}

	private function generate_label() {
		$label = array(
			'sender' => $this->generate_sender_data(),
		);

		if ( $this->cookie_data ) {
			$label['recipient'] = $this->generate_recipient_data();
		}

		$services_data = '';
		$payment_by_data = '';
		$country = '';
		if ( $this->cookie_data && isset( $this->cookie_data['country'] ) ) {
			$country = $this->cookie_data['country'];
		}

		if ( $country !== 'BG' ) {
			$services_data = $this->generate_services_data( '202', $country );
			$payment_by_data = $this->generate_payment_by_data( 'SENDER' );
		} else {
			$services_data = $this->generate_services_data();
			$payment_by_data = $this->generate_payment_by_data();
		}

		$content_data = $this->get_content_data();

		return array_merge( $label, $content_data, $services_data, $payment_by_data );
	}

	private function get_content_data() {
		if ( null === $this->content_data ) {
			$this->content_data = $this->generate_content_data();
		}

		return $this->content_data;
	}

	private function package_fits_max_parcels(): bool {
		$content_data = $this->get_content_data();
		$parcels = $content_data['content']['parcels'] ?? array();
		$max_parcels = (int) apply_filters( 'woo_bg/speedy/max_parcels', self::MAX_PARCELS, $this->package, $this );

		return $max_parcels <= 0 || count( $parcels ) <= $max_parcels;
	}

	private function generate_sender_data() {
		$send_from = woo_bg_get_option( 'speedy', 'send_from' );
		$sender = array(
			'clientId' => $this->container[ Client::SPEEDY_PROFILE ]->get_profile_data()['clientId'],
			'contactName' => woo_bg_get_option( 'speedy', 'name' ),
			'phone1' => array(
				'number' => woo_bg_get_option( 'speedy', 'phone' ),
			),
		);

		if ( $send_from === 'office' ) {
			$sender['dropoffOfficeId'] = $this->generate_sender_office_code();
		}

		return $sender;
	}

	private function generate_sender_office_code() {
		return str_replace( 'officeID-', '', woo_bg_get_option( 'speedy_send_from', 'office' ) );
	}

	private function generate_recipient_data() {
		$session_customer = WC()->session->get( 'customer' );
		
		$recipient = array(
			'privatePerson' => true,
			'clientName' => $this->cookie_data[ 'receiver' ],
			'phone1' => array(
				'number' => woo_bg_format_phone( $this->cookie_data[ 'phone' ], $this->cookie_data[ 'country' ] ),
			),
		);

		if ( !empty( $session_customer[ 'email' ] ) ) {
			$recipient['email'] = $session_customer[ 'email' ];
		}

		if ( isset( $this->cookie_data['billing_to_company'] ) && $this->cookie_data['billing_to_company'] ) {
			$recipient['privatePerson'] = false;
			$recipient['contactName'] = $recipient['clientName'];
			$recipient['clientName'] = $this->cookie_data['billing_company'];
		}


		if ( $this->cookie_data['type'] === 'address' ) {
			$recipient[ 'addressLocation' ] = $this->generate_recipient_address();
			$recipient[ 'address' ] = $this->generate_recipient_address();
		} else if ( in_array( $this->cookie_data['type'], [ 'office', 'automat' ], true ) ) {
			$recipient[ 'pickupOfficeId' ] = $this->generate_recipient_office_code();
			$recipient[ 'country' ] = $this->cookie_data[ 'country' ];
		}

		return $recipient;
	}

	private function generate_recipient_address() {
		$raw_city = sanitize_text_field( $this->cookie_data['city'] );
		$raw_state = sanitize_text_field( $this->cookie_data['state'] );
		$country_id = $this->container[ Client::SPEEDY_COUNTRIES ]->get_country_id( sanitize_text_field( $this->cookie_data['country'] ) );
		$cities_data = $this->container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state, $country_id );

		
		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) || !isset( $cities_data['cities'][ $cities_data['city_key'] ] ) ) {
			return( [] );
		}

		$address = array(
			'countryId' => $country_id,
			'siteId' => $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ],
		);

		if ( !empty( $this->cookie_data['selectedAddress']['type'] ) && $this->cookie_data['selectedAddress']['type'] === 'streets' ) {
			$address["streetId"] = str_replace('street-', '', $this->cookie_data['selectedAddress']['orig_key'] );
			$parts = explode( ',', $this->cookie_data['streetNumber'] );
			$address["streetNo"] = array_shift( $parts );

			if ( !empty( $parts ) ) {
				$address["addressNote"] = implode( ' ', $parts );
			}
		} else if ( 
			!empty( $this->cookie_data['selectedAddress']['type'] ) && $this->cookie_data['selectedAddress']['type'] === 'quarters' || 
			$this->cookie_data['mysticQuarter'] 
		) {
			if ( !empty( $this->cookie_data['mysticQuarter'] ) ) {
				$address["addressNote"] = $this->cookie_data['mysticQuarter'] . ' ' . $this->cookie_data[ 'other' ];
			} else {
				$address["complexId"] = str_replace('qtr-', '', $this->cookie_data['selectedAddress']['orig_key'] );

				if ( !empty( $this->cookie_data[ 'other' ] ) ) {
					$parts = explode( ' ', $this->cookie_data[ 'other' ] );

					$address["blockNo"] = $parts[0];

					if ( isset( $parts[1] ) ) {
						$address["entranceNo"] = $parts[1];
					}

					if ( isset( $parts[2] ) ) {
						$address["floorNo"] = $parts[2];
					}

					if ( isset( $parts[3] ) ) {
						$address["apartmentNo"] = $parts[3];
					}

					if ( isset( $parts[4] ) ) {
						unset( $parts[0], $parts[1], $parts[2], $parts[3] );

						$address["addressNote"] = implode( ' ', $parts ) ;
					}
				}
			}
		}

		return $address;
	}

	private function generate_recipient_office_code() {
		return ( isset( $this->cookie_data['selectedOffice'] ) ) ? $this->cookie_data['selectedOffice'] : '';
	}

	private function generate_content_data() {
		$names = array();
		$content = array(
			'package' => 'BOX',
		);

		$weigth          = 0;
		$sizes           = [];
		$product_weights = [];
		$auto_sizes = wc_string_to_bool( woo_bg_get_option( 'speedy', 'auto_size' ) );

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			$item_weight = 0;

			if ( $item['data']->get_weight() ) {
				$item_weight = wc_get_weight( $item['data']->get_weight(), 'kg' );
				$weigth += $item_weight * $item['quantity'];
			}

			$force = woo_bg_get_option( 'speedy', 'force_variations_in_desc' );
			$name = $item['data']->get_name();
			
			if ( $force === 'yes' && is_a( $item['data'], 'WC_Product_Variation' ) ) {
				$name .= ' - ' . $item['data']->get_attribute_summary();
			}

			$name = woo_bg_normalize_text_for_label( $name );
			$names[] = $name;

			if ( $auto_sizes && $item['data']->get_length() && $item['data']->get_width() && $item['data']->get_height() ) {
				$quantity = ! empty( $item['quantity'] ) ? max( 1, (int) $item['quantity'] ) : 1;

				foreach ( range( 1, $quantity ) as $i ) {
					$sizes[] = new Product( 
						$name, 
						new Size( 
							wc_get_dimension( $item['data']->get_length(), 'mm', get_option( 'woocommerce_dimension_unit' ) ), 
							wc_get_dimension( $item['data']->get_width(), 'mm', get_option( 'woocommerce_dimension_unit' ) ), 
							wc_get_dimension( $item['data']->get_height(), 'mm', get_option( 'woocommerce_dimension_unit' ) ),
						) 
					);
					$product_weights[] = $item_weight;
				}
			}
		}

		if ( !$weigth ) {
			$weigth = apply_filters( 'woo_bg/speedy/label/weight', 1, $this->package, $this );
		}

		$content['contents'] = woo_bg_normalize_text_for_label( implode( ',', $names ), 99 );

		$pack_sizes = $auto_sizes ? apply_filters( 'woo_bg/speedy/label/sizes', [
			'width' => 17,
			'depth' => 17,
			'height' => 17,
		], $this->package, $this ) : array();
		$max_weight = self::get_max_parcel_weight( $this->delivery_type, woo_bg_get_option( 'speedy', 'send_from' ) );

		if ( $auto_sizes && !empty( $sizes ) ) {
			$carton_sizes = $this->delivery_type === 'automat' ? self::get_aps_box_sizes_for_packer() : array();
			$content['parcels'] = self::generate_parcels_from_products( $sizes, $product_weights, $carton_sizes, $max_weight );
		} else {
			$content['parcels'] = self::generate_parcels( $weigth, $pack_sizes, $max_weight );
		}

		return array(
			'content' => $content,
		);
	}

	private static function generate_parcels_from_products( array $products, array $product_weights, array $carton_sizes, float $max_weight ): array {
		$packer      = new Carton_Packer();
		$groups      = array();
		$group_items = array();
		$group_weights = array();

		foreach ( $products as $index => $product ) {
			$product_weight    = isset( $product_weights[ $index ] ) ? (float) $product_weights[ $index ] : 0;
			$candidate_items   = array_merge( $group_items, array( $product ) );
			$candidate_weights = array_merge( $group_weights, array( $product_weight ) );

			if ( empty( $group_items ) || self::products_fit_parcel_weight( $packer, $candidate_items, $candidate_weights, $carton_sizes, $max_weight ) ) {
				$group_items   = $candidate_items;
				$group_weights = $candidate_weights;
				continue;
			}

			$groups[] = array(
				'products' => $group_items,
				'weights'  => $group_weights,
			);

			$group_items   = array( $product );
			$group_weights = array( $product_weight );
		}

		if ( ! empty( $group_items ) ) {
			$groups[] = array(
				'products' => $group_items,
				'weights'  => $group_weights,
			);
		}

		$parcels = array();

		foreach ( $groups as $group ) {
			$result     = $packer->find_best_carton( $group['products'], $carton_sizes );
			$pack_sizes = self::get_pack_sizes_from_result( $result );
			$weight     = array_sum( $group['weights'] );

			foreach ( self::generate_parcels( $weight, $pack_sizes, $max_weight ) as $parcel ) {
				$parcels[] = $parcel;
			}
		}

		foreach ( $parcels as $key => &$parcel ) {
			$parcel['seqNo'] = $key + 1;
		}

		unset( $parcel );

		return $parcels;
	}

	private static function products_fit_parcel_weight( Carton_Packer $packer, array $products, array $weights, array $carton_sizes, float $max_weight ): bool {
		if ( $max_weight <= 0 ) {
			return true;
		}

		$weight = array_sum( $weights );

		if ( self::use_volumetric_weight_for_parcels() ) {
			$result     = $packer->find_best_carton( $products, $carton_sizes );
			$pack_sizes = self::get_pack_sizes_from_result( $result );
			$weight     = self::get_parcel_count_weight( $weight, $pack_sizes );
		}

		return $weight <= $max_weight;
	}

	private static function get_pack_sizes_from_result( $result ): array {
		return array(
			'width'  => wc_get_dimension( $result->W, 'cm', 'mm' ),
			'depth'  => wc_get_dimension( $result->L, 'cm', 'mm' ),
			'height' => wc_get_dimension( $result->H, 'cm', 'mm' ),
		);
	}

	private static function generate_parcels( $weight, array $pack_sizes, float $max_weight ): array {
		$min_weight = (float) apply_filters( 'woo_bg/speedy/min_parcel_weight', 0.100 );
		$weight     = is_numeric( $weight ) ? (float) $weight : $min_weight;
		$weight     = max( $min_weight, $weight );
		$count_weight = max( $min_weight, self::get_parcel_count_weight( $weight, $pack_sizes ) );
		$parcels    = array();

		if ( $max_weight <= 0 ) {
			return array( self::generate_parcel( 1, $weight, $pack_sizes ) );
		}

		if ( abs( $count_weight - $weight ) > 0.0001 ) {
			$parcel_count = (int) ceil( $count_weight / $max_weight );
			$parcel_count = max( 1, $parcel_count );
			$parcel_weight = $weight / $parcel_count;

			for ( $i = 1; $i <= $parcel_count; $i++ ) {
				$parcels[] = self::generate_parcel( $i, max( $min_weight, $parcel_weight ), $pack_sizes );
			}

			return $parcels;
		}

		while ( $count_weight > $max_weight ) {
			$parcels[] = self::generate_parcel( count( $parcels ) + 1, $max_weight, $pack_sizes );
			$count_weight -= $max_weight;
			$weight       -= $max_weight;
		}

		$parcels[] = self::generate_parcel( count( $parcels ) + 1, max( $min_weight, $weight ), $pack_sizes );

		return $parcels;
	}

	private static function generate_parcel( int $seq_no, float $weight, array $pack_sizes ): array {
		$parcel = array(
			'seqNo'  => $seq_no,
			'weight' => round( $weight, 3 ),
		);

		if ( ! empty( $pack_sizes ) ) {
			$parcel['size'] = $pack_sizes;
		}

		return $parcel;
	}

	public static function get_max_parcel_weight( $delivery_type = null, $send_from_type = null ): float {
		$max_weight = self::use_volumetric_weight_for_parcels() || $delivery_type !== 'address' || $send_from_type === 'office' ? 32 : 50;

		return (float) apply_filters( 'woo_bg/speedy/max_parcel_weight', $max_weight, $delivery_type, $send_from_type );
	}

	public static function use_volumetric_weight_for_parcels(): bool {
		return wc_string_to_bool( woo_bg_get_option( 'speedy', 'declared_value' ) );
	}

	public static function get_parcel_volumetric_weight( array $pack_sizes ): float {
		if (
			empty( $pack_sizes['width'] ) ||
			empty( $pack_sizes['depth'] ) ||
			empty( $pack_sizes['height'] )
		) {
			return 0;
		}

		return ( (float) $pack_sizes['width'] * (float) $pack_sizes['depth'] * (float) $pack_sizes['height'] ) / self::VOLUMETRIC_WEIGHT_DIVISOR;
	}

	private static function get_parcel_count_weight( float $real_weight, array $pack_sizes ): float {
		if ( ! self::use_volumetric_weight_for_parcels() ) {
			return $real_weight;
		}

		$volumetric_weight = self::get_parcel_volumetric_weight( $pack_sizes );

		return $volumetric_weight > 0 ? $volumetric_weight : $real_weight;
	}

	private static function get_aps_box_sizes_for_packer() {
		$box_sizes = array();

		foreach ( self::$aps_box_sizes as $box_size ) {
			$box_sizes[] = array(
				'name'   => $box_size['name'],
				'length' => wc_get_dimension( $box_size['length'], 'mm', 'cm' ),
				'width'  => wc_get_dimension( $box_size['width'], 'mm', 'cm' ),
				'height' => wc_get_dimension( $box_size['height'], 'mm', 'cm' ),
			);
		}

		return $box_sizes;
	}

	private function generate_services_data( $service_id = '505', $country = 'BG' ) {
		$services = array(
			'autoAdjustPickupDate' => true, 
			'serviceId' => $service_id,
			'serviceIds' => array( $service_id ),
			'additionalServices' => [],
		);
		$is_fragile = wc_string_to_bool( woo_bg_get_option( 'speedy', 'declared_value' ) );

		if ( $is_fragile ) {
			$services['additionalServices']['declaredValue'] = array(
				'amount' => woo_bg_get_package_total(), 
				'fragile' => $is_fragile, 
				"ignoreIfNotApplicable" => true 
			);
		}

		if ( $this->cookie_data['payment'] === 'cod' ) {
			$cod_data = [];

			if ( $service_id === '505' ) {
				$cod_data = array(
					'amount' => woo_bg_get_package_total(),
					'processingType' => ( wc_string_to_bool( woo_bg_get_option( 'speedy', 'ppp' ) ) ) ? 'POSTAL_MONEY_TRANSFER' : 'CASH',
				);
			} else {
				$cod_data = array(
					'amount' => woo_bg_get_package_total()
				);
			}

			$services['additionalServices']['cod'] = $cod_data;

			if ( wc_string_to_bool( woo_bg_get_option( 'speedy', 'kb' ) ) && wc_tax_enabled() ) {
				$services['additionalServices']['cod']['fiscalReceiptItems'] = array();

				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( !$cart_item['line_total'] || $cart_item['line_total'] <= 0 ) {
						continue;
					}
					
					$rate = round( ( $cart_item['line_tax'] / $cart_item['line_total'] ) * 100 );
					$services['additionalServices']['cod']['fiscalReceiptItems'][] = [
						'description' => woo_bg_normalize_text_for_label( $cart_item['data']->get_name(), 49 ),
						'vatGroup' => woo_bg_get_vat_group_from_rate( $rate ),
						'amount' => number_format( $cart_item['line_total'], 2, '.', '' ),
						'amountWithVat' => number_format( $cart_item['line_total'] + $cart_item['line_tax'], 2, '.', '' ),
					];
				}

				if ( apply_filters('woo_bg/shipping/package_total_includes_fees', true ) && WC()->cart->get_fee_total() > 0 ) {
					$total = floatval( WC()->cart->get_fee_total() );
					$tax = floatval( WC()->cart->get_fee_tax() );
					$rate = round( ( $tax / $total ) * 100 );

					$services['additionalServices']['cod']['fiscalReceiptItems'][] = [
						'description' => __( 'Fees', 'bulgarisation-for-woocommerce' ),
						'vatGroup' => woo_bg_get_vat_group_from_rate( $rate ),
						'amount' => number_format( $total, 2, '.', '' ),
						'amountWithVat' => number_format( $total + $tax, 2, '.', '' ),
					];
				}
			}

			if ( 
				$country !== 'GR' &&
				$this->test !== 'no' && 
				! ( isset( $this->cookie_data['selectedOfficeType'] ) && $this->cookie_data['selectedOfficeType'] == 'APT' )
			) {
				if ( $this->test == 'review' ) {
					$test = 'OPEN';
				} else if ( $this->test == 'test' ) {
					$test = 'TEST';
				}

				$services['additionalServices']['obpd'] = array(
					'option' => $test, 
					'returnShipmentServiceId' => $service_id, 
					'returnShipmentPayer' => 'SENDER' 
				);
			}
		}


		if ( empty( $services['additionalServices'] ) ) {
			unset( $services['additionalServices'] );
		}

		return array(
			'service' => $services,
		);
	}
	
	private function generate_payment_by_data( $shipping_costs_payer = 'RECIPIENT' ) {
		$payment = array(
			"courierServicePayer" => $shipping_costs_payer,
			"declaredValuePayer" => $shipping_costs_payer,
			"packagePayer" => $shipping_costs_payer,
		);

		if ( isset( $this->cookie_data['payment'] ) && $this->cookie_data['payment'] !== 'cod' ) {
			$payment[ 'courierServicePayer' ] = 'SENDER';
			$payment[ 'declaredValuePayer' ] = 'SENDER';
			$payment[ 'packagePayer' ] = 'SENDER';
		}

		if ( !empty( $this->fixed_price ) && $payment[ 'courierServicePayer' ] === 'RECIPIENT' ) {
			$payment[ 'courierServicePayer' ] = 'SENDER';
		}

		if ( 
			woo_bg_has_free_shipping_coupon_in_cart() ||
			( !empty( $this->free_shipping_over ) && woo_bg_get_package_total() > $this->free_shipping_over )
		) {
			$this->free_shipping = true;
			
			if ( isset( $this->cookie_data['fixed_price'] ) ) {
				unset( $this->cookie_data['fixed_price'] );
			}

			if ( $payment[ 'courierServicePayer' ] === 'RECIPIENT' ) {
				$payment[ 'courierServicePayer' ] = 'SENDER';
				$payment[ 'declaredValuePayer' ] = 'SENDER';
				$payment[ 'packagePayer' ] = 'SENDER';
			}
		}

		if ( woo_bg_get_option( 'speedy', 'administrative_fee' ) === 'yes' ) {
			$payment["administrativeFee"] = true;
		}

		return array(
			'payment' => $payment,
		);
	}

	public static function validate_speedy_method( $fields, $errors ) {
		if ( ! WC()->cart->needs_shipping() ) {
			return;
		}

		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_speedy' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];
				
				if ( $data->method_id === 'woo_bg_speedy' && ! $data->meta_data['validated'] ) {
					$meta_data = $data->get_meta_data();

					if ( !empty( $meta_data['errors'] ) ) {
						$errors->add( 'validation', sprintf( __( 'Speedy - %s', 'bulgarisation-for-woocommerce' ), $meta_data['errors'] ) );
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'bulgarisation-for-woocommerce' ) );
					}
				} elseif ( $data->method_id === 'woo_bg_speedy' ) {
					$cookie_data = self::get_cookie_data();

					if ( 
						!empty( $cookie_data ) && 
						( !empty($cookie_data['type'] ) && $cookie_data['type'] === 'office' ) &&  
						empty( $cookie_data['selectedOffice'] ) 
					) {
						$errors->add( 'validation', __( 'Please choose a office.', 'bulgarisation-for-woocommerce' ) );
					}	

					if ( 
						!empty( $cookie_data ) && 
						( !empty($cookie_data['type'] ) && $cookie_data['type'] === 'automat' ) &&  
						empty( $cookie_data['selectedOffice'] ) 
					) {
						$errors->add( 'validation', __( 'Please choose a automat.', 'bulgarisation-for-woocommerce' ) );
					}	
				}
			}
		}
	}

	public static function save_label_data_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( !empty( $order->get_items( 'shipping' ) ) ) {
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_speedy' ) {
					$cookie_data = '';

					if ( WC()->session->get( 'woo-bg-speedy-label' ) ) {
						$order->update_meta_data( 'woo_bg_speedy_label', apply_filters( 'woo_bg/speedy/label_before_save', WC()->session->get( 'woo-bg-speedy-label' ), $order ) );
						WC()->session->__unset( 'woo-bg-speedy-label' );
					}

					foreach ( $shipping->get_meta_data() as $meta_data ) {
						$data = $meta_data->get_data();

						if ( $data['key'] == 'cookie_data' ) {
							$cookie_data = $data['value'];
						}
					}

					if ( $cookie_data ) {
						$order->update_meta_data( 'woo_bg_speedy_cookie_data', $cookie_data );
					}

					$order->save();
					break;
				}
			}
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-speedy',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'speedy-frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);
		
		wp_localize_script( 'woo-bg-js-speedy', 'wooBg_speedy_address', array(
			'i18n' => Address::get_i18n(),
		) );

		wp_localize_script( 'woo-bg-js-speedy', 'wooBg_speedy', array(
			'i18n' => Office::get_i18n(),
		) );

		wp_localize_script( 'woo-bg-js-speedy', 'wooBg_speedy_automat', array(
			'i18n' => Office::get_automat_i18n(),
		) );

		wp_enqueue_style(
			'woo-bg-css-speedy',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'speedy-frontend.css' )
		);
	}

	public static function add_label_number_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		$email_ids_to_send = [ 'customer_completed_order' ];

		if ( woo_bg_get_option( 'speedy', 'label_after_checkout' ) === 'yes' ) {
			$email_ids_to_send[] = 'customer_processing_order';
			$email_ids_to_send[] = 'customer_on_hold_order';
		}
		
		$email_ids_to_send = apply_filters( 'woo_bg/speedy/emails_to_send_label_number', $email_ids_to_send );

		if ( !in_array( $email->id, $email_ids_to_send ) ) {
			return;
		}

		$label = $order->get_meta( 'woo_bg_speedy_label' );

		if ( !isset( $label['id'] ) ) {
			return;
		}

		$number = $label['id'];
		$url = 'https://www.speedy.bg/bg/track-shipment?shipmentNumber=' . $number;

		$track_number_text = sprintf( 
			__( 'Label number: %s. %s', 'bulgarisation-for-woocommerce' ), 
			$number, 
			sprintf( '<a href="%s" target="_blank">%s</a>',
				$url,
				__( 'Track your order.' , 'bulgarisation-for-woocommerce' )
			)
		);

		$track_number_text = apply_filters( 'woo_bg/speedy/track_number_text_in_email', $track_number_text, $url, $order );

		echo wp_kses_post( wpautop( $track_number_text ) );
	}
}
