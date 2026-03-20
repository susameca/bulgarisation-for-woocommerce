<?php
namespace Woo_BG\Shipping\Pigeon;
use Woo_BG\Container\Client;

use Woo_BG\Shipping\Packer\Carton_Packer;
use Woo_BG\Shipping\Packer\Product;
use Woo_BG\Shipping\Packer\Size;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_pigeon";
	public $container, $cookie_data, $package, $delivery_type, $free_shipping_over, $fixed_price, $test, $tax_status, $free_shipping = false;

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = 'woo_bg_pigeon'; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - Pigeon Express', 'bulgarisation-for-woocommerce' );  // Title shown in admin
		$this->method_description = __( 'Enables Pigeon Express delivery and automatically calculate shipping price.', 'bulgarisation-for-woocommerce' ); // Description shown in admin
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

		do_action( 'woo_bg/pigeon/rate/before_calculate', $this );

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
			'meta_data' => [],
		);

		$rate['meta_data']['delivery_type'] = $this->delivery_type;
		$rate['meta_data']['validated'] = false;
		$payment_by_data = $this->generate_payment_by_data();
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		if ( 
			isset( $this->cookie_data['type'] ) && 
			$this->cookie_data['type'] === $this->delivery_type && 
			( !empty( $chosen_shippings ) && ( $chosen_shippings[0] === $this->id . ':' . $this->instance_id ) ) &&
			( 
				( isset( $this->cookie_data['other'] ) && $this->cookie_data['other'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['streetNumber']) && $this->cookie_data['streetNumber'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['selectedOffice'] ) && $this->cookie_data['selectedOffice'] ) ||
				( isset( $this->cookie_data['selectedLocker'] ) && $this->cookie_data['selectedLocker'] ) 
			) 
		) {
			$request_data = $this->calculate_shipping_price_from_api();

			$rate['request_data'] = $request_data;
			$rate['meta_data']['validated'] = true;
			$rate['meta_data']['cookie_data'] = $this->cookie_data;

			if ( isset( $request_data['errors'] ) ) {
				$rate['meta_data']['validated'] = false;
				$rate['meta_data']['errors'] = $request_data['errors'];
			} elseif ( $request_data['price']  ) {
				$rate['cost'] = $request_data['price'];

				if ( wc_tax_enabled() ) {
					$rate[ 'taxes' ] = woo_bg_get_shipping_rate_taxes( $request_data['price'] );
				}
			}
		}

		if ( $this->free_shipping ) {
			$rate['meta_data']['free_shipping'] = true;
			$rate[ 'cost' ] = 0;
			unset( $rate[ 'taxes' ] );
		}

		if ( !$this->free_shipping && !empty( $this->fixed_price ) ) {
			$rate[ 'cost' ] = woo_bg_tax_based_price( $this->fixed_price );
			
			if ( wc_tax_enabled() ) {
				$rate[ 'taxes' ] = woo_bg_get_shipping_rate_taxes( $rate[ 'cost' ] );
			}
		}

		$rate = apply_filters( 'woo_bg/pigeon/rate', $rate, $this );

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
					'locker' => __( 'Locker', 'bulgarisation-for-woocommerce' ),
					'address' => __( 'Address', 'bulgarisation-for-woocommerce' ),
				),
			),
			'test'    => array(
				'title'             => __( 'Review and test', 'bulgarisation-for-woocommerce' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => array(
					'no' => __( 'No', 'bulgarisation-for-woocommerce' ),
					'yes' => __( 'Yes', 'bulgarisation-for-woocommerce' ),
				),
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
		$countries = apply_filters( 'woo_bg/pigeon/available_countries', array( 'BG' ) );

		if ( in_array( $package['destination']['country'], $countries ) ) {
			return true;
		}
	}

	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	public static function get_cookie_data() {
		$cookie_data = '';

		if (  isset( $_COOKIE[ 'woo-bg--pigeon-address' ] ) ) {
			$cookie_data = json_decode( stripslashes( urldecode( sanitize_text_field( $_COOKIE[ 'woo-bg--pigeon-address' ] ) ) ), 1 );
		}
		
		return $cookie_data;
	}

	public function calculate_shipping_price_from_api() {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/pigeon/calculate_label', $this->generate_label(), $this );

		WC()->session->set( 'woo-bg-pigeon-label' , $request_body );

		$request = $this->container[ Client::PIGEON ]->api_call( $this->container[ Client::PIGEON ]::CALCULATE_ENDPOINT, $request_body, 'POST' );

		if ( isset( $request['success'] ) && !$request['success'] ) {
			$data['errors'] = $request['errors'];
		} else if ( isset( $request['data']['total_price'] ) ) {
			$data['price'] = woo_bg_tax_based_price( $request['data']['total_price'] );
		}

		return $data;
	}

	private function generate_label() {
		$sender_data = $this->generate_sender_data();
		$receiver_data = $this->generate_receiver_data();
		$cart_data = $this->generate_cart_data();
		$other_data = $this->generate_other_data();
		$payment_by_data = $this->generate_payment_by_data();

		return array_merge_recursive( $sender_data, $receiver_data, $cart_data, $other_data, $payment_by_data );
	}

	private function generate_sender_data() {
		$send_from = woo_bg_get_option( 'pigeon', 'send_from' );

		$label = [
			'pickup_type' => $send_from,
		];

		if ( $send_from == 'office' ) {
			$label['pickup_office_id'] = $this->generate_sender_office_code();
		} else {
			$label['pickup_address'] = [
				'city_id' => str_replace( 'cityID-', '', woo_bg_get_option( 'pigeon_send_from', 'city' ) ),
				'additional_info' => woo_bg_get_option( 'pigeon_send_from', 'address' ),
			];
		}

		return $label;
	}

	private function generate_sender_office_code() {
		$office = woo_bg_get_option( 'pigeon_send_from', 'office' );
		
		return str_replace( 'officeID-', '', $office );
	}

	private function generate_receiver_data() {
		if ( empty($this->cookie_data ) ) {
			return [];
		}
		
		$session_customer = WC()->session->get( 'customer' );

		$data = array(
			'delivery_type' => $this->cookie_data[ 'type' ],
			'receiver_name' => $this->cookie_data[ 'receiver' ],
			'receiver_phone' => array( $this->cookie_data[ 'phone' ] ),
		);

		if ( !empty( $session_customer[ 'email' ] ) ) {
			$data['receiver_email'] = $session_customer[ 'email' ];
		}

		if ( $this->cookie_data[ 'type' ] === 'address' ) {
			$data['delivery_address'] = $this->generate_receiver_address();
		} else if ( $this->cookie_data[ 'type' ] === 'office' ) {
			$data['delivery_office_id'] = absint( $this->generate_receiver_office_code() );
		} else if ( $this->cookie_data[ 'type' ] === 'locker' ) {
			$data['delivery_office_id'] = absint( $this->generate_receiver_locker_code() );
		}

		return $data;
	}

	private function generate_receiver_address() {
		$raw_city = Transliteration::latin2cyrillic( $this->cookie_data[ 'city' ] );
		$cities_data = $this->container[ Client::PIGEON_CITIES ]->get_filtered_cities( $raw_city, $this->cookie_data[ 'state' ] ) ;
		$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		$receiver_address = [
			'city_id' => $city_id,
			'street_id' => str_replace( 'street-', '', $this->cookie_data[ 'selectedAddress' ][ 'id' ] ),
			'additional_info' => $this->cookie_data[ 'streetNumber' ],
		];

		return $receiver_address;
	}

	private function generate_receiver_office_code() {
		return ( isset( $this->cookie_data['selectedOffice'] ) ) ? $this->cookie_data['selectedOffice'] : '';
	}

	private function generate_receiver_locker_code() {
		return ( isset( $this->cookie_data['selectedLocker'] ) ) ? $this->cookie_data['selectedLocker'] : '';
	}

	private function generate_cart_data() {
		$names = array();
		$cart_data = [
			'packages' => [
				[
					'weight' => 1,
				]
			],
			'inventory_items' => [],
		];
		$weight = 0;
		$sizes = [];
		$auto_sizes = wc_string_to_bool( woo_bg_get_option( 'pigeon', 'auto_size' ) );

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			if ( $item['data']->get_weight() ) {
				$item_weight = wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];
			}
	
			if ( $item_weight > 0 && $item_weight < 0.100 ) {
				$item_weight = 0.100;
			
			}

			$weight += $item_weight;			

			$force = woo_bg_get_option( 'pigeon', 'force_variations_in_desc' );

			$name = $item['data']->get_name();

			if ( $force === 'yes' && is_a( $item['data'], 'WC_Product_Variation' ) ) {
				if ( $item['data']->get_attribute_summary() ) {
					$name .= ' - ' . $item['data']->get_attribute_summary();
				} else if( !empty( $item['data']->get_attributes() ) ) {
					$name .= ' - ' . implode(', ', $item['data']->get_attributes() );
				}
			}

			$cart_data['inventory_items'][] = array(
				'description' => $name,
				'quantity' => $item['quantity'],
			);

			$names[] = $name;

			if ( $auto_sizes && $item['data']->get_length() && $item['data']->get_width() && $item['data']->get_height() ) {
				$sizes[] = new Product( 
					$name, 
					new Size( 
						wc_get_dimension( $item['data']->get_length(), 'mm', get_option( 'woocommerce_dimension_unit' ) ), 
						wc_get_dimension( $item['data']->get_width(), 'mm', get_option( 'woocommerce_dimension_unit' ) ), 
						wc_get_dimension( $item['data']->get_height(), 'mm', get_option( 'woocommerce_dimension_unit' ) ),
					) 
				);
			}
		}

		if ( !$weight ) {
			$weight = apply_filters( 'woo_bg/pigeon/label/weight', 1, $this->package, $this );
		}

		$cart_data['packages'][0]['weight'] = $weight;

		if ( empty( $sizes ) ) {
			$cart_data['packages'][0]['width'] = 40;
			$cart_data['packages'][0]['length'] = 40;
			$cart_data['packages'][0]['height'] = 40;
		} else if ( ( $auto_sizes || $this->cookie_data['type'] === 'locker' ) && !empty( $sizes ) ) {
			$packer = new Carton_Packer();
			$result = $packer->find_best_carton( $sizes );

			$cart_data['packages'][0]['width'] = wc_get_dimension( $result->W, 'cm', 'mm' );
			$cart_data['packages'][0]['length'] = wc_get_dimension( $result->L, 'cm', 'mm' );
			$cart_data['packages'][0]['height'] = wc_get_dimension( $result->H, 'cm', 'mm' );
		} 

		return $cart_data;
	}

	private function generate_other_data() {
		$other_data = [
			//'service_type' => woo_bg_get_option( 'pigeon', 'service_type' ),
			'service_type' => 'standard',
			'service_codes' => [],
		];

		$is_fragile = wc_string_to_bool( woo_bg_get_option( 'pigeon', 'declared_value' ) );
	
		if ( $is_fragile && $this->cookie_data[ 'type' ] !== 'locker' ) {
			$other_data['service_codes']['declared_value'] = woo_bg_get_package_total();
		}
		
		if ( woo_bg_get_option( 'pigeon', 'paper_return_receipt' ) === 'yes' ) {
			$other_data['service_codes']['paper_return_receipt'] = true;
		}

		if ( woo_bg_get_option( 'pigeon', 'return_receipt' ) === 'yes' ) {
			$other_data['service_codes']['return_receipt'] = true;
		}
		
		if ( woo_bg_get_option( 'pigeon', 'service_return_documents' ) === 'yes' ) {
			$other_data['service_codes']['service_return_documents'] = true;
		}
		
		if ( woo_bg_get_option( 'pigeon', 'id_verification_and_document_signature' ) === 'yes' ) {
			$other_data['service_codes']['ID_verification_and_document_signature'] = true;
		}
		
		if ( woo_bg_get_option( 'pigeon', 'cod_card_fee' ) === 'yes' ) {
			$other_data['service_codes']['cod_card_fee'] = true;
		}
		
		if ( $this->cookie_data[ 'type' ] !== 'locker' ) {
			if ( $this->test === 'yes' ) {
				$other_data['service_codes']['shipment_test_before_payment'] = true;
			}
		}

		return $other_data;
	}
	
	private function generate_payment_by_data() {
		$payment = [
			'who_pays' => 'receiver',
		];

		if ( $this->cookie_data['payment'] === 'cod' ) {
			$payment['service_codes']['cod_amount'] = woo_bg_get_package_total();
		}

		if ( isset( $this->cookie_data['payment'] ) && $this->cookie_data['payment'] !== 'cod' ) {
			$payment[ 'who_pays' ] = 'sender';
		}

		if ( !empty( $this->fixed_price ) && $payment[ 'who_pays' ] === 'receiver' ) {
			$payment[ 'who_pays' ] = 'sender';
			$payment['service_codes']['cod_amount'] += number_format($this->fixed_price, 2, '.', '');
		}

		if ( 
			woo_bg_has_free_shipping_coupon_in_cart() ||
			( !empty( $this->free_shipping_over ) && woo_bg_get_package_total() > $this->free_shipping_over )
		) {
			$this->free_shipping = true;
			
			if ( isset( $this->cookie_data['fixed_price'] ) ) {
				unset( $this->cookie_data['fixed_price'] );
			}

			if ( $payment[ 'who_pays' ] === 'receiver' ) {
				$payment[ 'who_pays' ] = 'sender';
			}

			unset( $payment['service_codes']['cod_amount'] );
		}

		return $payment;
	}

	public static function validate_pigeon_method( $fields, $errors ){
		if ( ! WC()->cart->needs_shipping() ) {
			return;
		}

		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_pigeon' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];

				if ( $data->method_id === 'woo_bg_pigeon' && ! $data->meta_data['validated'] ) {
					$meta_data = $data->get_meta_data();

					if ( !empty( $meta_data['errors'] ) && !empty( array_filter( $meta_data['errors'] ) ) ) {
						$message = array_merge( array( __( 'Pigeon Express - ', 'bulgarisation-for-woocommerce' ) ), wp_list_pluck( $meta_data['errors'], 0 ) );
						$errors->add( 'validation', implode( ' ', $message ) );
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'bulgarisation-for-woocommerce' ) );
					}
				}

				if ( $data->method_id === 'woo_bg_pigeon' ) { 
					$cookie_data = self::get_cookie_data();

					if ( 
						! empty( $cookie_data ) && 
						( !empty( $cookie_data['type'] ) && $cookie_data['type'] === 'office' ) &&  
						empty( $cookie_data['selectedOffice'] ) 
					) {
						$errors->add( 'validation', __( 'Please choose an office.', 'bulgarisation-for-woocommerce' ) );
					}

					if ( 
						! empty( $cookie_data ) && 
						( !empty( $cookie_data['type'] ) && $cookie_data['type'] === 'locker' ) &&  
						empty( $cookie_data['selectedLocker'] ) 
					) {
						$errors->add( 'validation', __( 'Please choose a locker.', 'bulgarisation-for-woocommerce' ) );
					}

					if(
						! empty( $cookie_data ) && 
						( !empty( $cookie_data['type'] ) && $cookie_data['type'] === 'address' ) &&  
						empty( $cookie_data['selectedAddress'] ) 
					) {
						$errors->add( 'validation', __( 'Please choose an address.', 'bulgarisation-for-woocommerce' ) ); 
					}
				}
			}
		}
	}

	public static function save_label_data_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( !empty( $order->get_items( 'shipping' ) ) ) {
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_pigeon' ) {
					$cookie_data = '';

					if ( WC()->session->get( 'woo-bg-pigeon-label' ) ) {
						$order->update_meta_data( 'woo_bg_pigeon_label', apply_filters( 'woo_bg/pigeon/label_before_save', WC()->session->get( 'woo-bg-pigeon-label' ), $order ) );
						WC()->session->__unset( 'woo-bg-pigeon-label' );
					}

					foreach ( $shipping->get_meta_data() as $meta_data ) {
						$data = $meta_data->get_data();

						if ( $data['key'] == 'cookie_data' ) {
							$cookie_data = $data['value'];
						}
					}

					if ( $cookie_data ) {
						$order->update_meta_data( 'woo_bg_pigeon_cookie_data', $cookie_data );
					}

					$order->save();
					break;
				}
			}
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-pigeon',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'pigeon-frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);
		
		wp_localize_script( 'woo-bg-js-pigeon', 'wooBg_pigeon_address', array(
			'i18n' => Address::get_i18n(),
		) );

		wp_localize_script( 'woo-bg-js-pigeon', 'wooBg_pigeon', array(
			'i18n' => Office::get_i18n(),
		) );

		wp_enqueue_style(
			'woo-bg-css-pigeon',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'pigeon-frontend.css' )
		);
	}
}