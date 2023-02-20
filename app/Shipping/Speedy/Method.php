<?php
namespace Woo_BG\Shipping\Speedy;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_speedy";
	public $container = '';
	public $cookie_data = '';
	public $package = '';
	public $free_shipping = false;

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = self::METHOD_ID; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - Speedy', 'woo-bg' );  // Title shown in admin
		$this->method_description = __( 'Enables Speedy delivery and automatically calculate shipping price.', 'woo-bg' ); // Description shown in admin
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
		if ( is_array( $this->cookie_data ) ) {
			$this->cookie_data['fixed_price'] = $this->fixed_price;
		}

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
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
				( isset( $this->cookie_data['mysticQuarter'] ) && $this->cookie_data['other'] )
			) 
		) {
			if ( empty( $this->fixed_price ) && $payment_by_data['payment'][ 'courierServicePayer' ] !== 'RECIPIENT' ) {
				$this->free_shipping = true;
			}

			$request_data = $this->calculate_shipping_price_from_api();
			$rate['meta_data']['validated'] = true;

			if ( isset( $request_data['errors'] ) ) {
				$rate['meta_data']['validated'] = false;
				$rate['meta_data']['errors'] = $request_data['errors'];
			} elseif ( isset( $request_data['price'] )  ) {
				$rate['cost'] = $request_data['price'];
			}
		}

		if ( $this->free_shipping ) {
			$rate['label'] = sprintf( __( '%s: Free shipping', 'woo-bg' ), $rate['label'] );
			$rate[ 'cost' ] = 0;
		} 

		if ( !$this->free_shipping && !empty( $this->fixed_price ) ) {
			$rate[ 'cost' ] = woo_bg_tax_based_price( $this->fixed_price );
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
				'title'       => __( 'Title', 'woo-bg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-bg' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'delivery_type'    => array(
				'title'             => __( 'Delivery Type', 'woo-bg' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'options'           => array(
					'office' => __( 'Office', 'woo-bg' ),
					'address' => __( 'Address', 'woo-bg' ),
				),
			),
			'test'    => array(
				'title'             => __( 'Review and test', 'woo-bg' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => woo_bg_get_shipping_tests_options(),
			),
			'free_shipping_over' => array(
				'title'       => __( 'Free shipping over', 'woo-bg' ),
				'type'        => 'text',
				'placeholder' => '0',
				'description' => __( 'Free shipping over total cart price.', 'woo-bg' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'fixed_price' => array(
				'title'       => __( 'Fixed price', 'woo-bg' ),
				'type'        => 'text',
				'placeholder' => '0',
				'description' => __( 'Enter a fixed price that the users will pay. The remaining will be payed by you.', 'woo-bg' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	public function is_available( $package ) {
		//$countries = $this->container[ Client::SPEEDY_COUNTRIES ]->get_countries();
		//Ne raboti dobre s GR i RO
		//$countries = array( 'BG', 'GR', 'RO' );
		$countries = array( 'BG' );

		if ( in_array( $package['destination']['country'], $countries ) ) {
			return true;
		}
	}

	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	public static function get_cookie_data() {
		return ( isset( $_COOKIE[ 'woo-bg--speedy-address' ] ) ) ? json_decode( stripslashes( urldecode( $_COOKIE[ 'woo-bg--speedy-address' ] ) ), 1 ) : '';
	}

	public function calculate_shipping_price_from_api() {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/speedy/calculate_label', $this->generate_label(), $this );

		WC()->session->set( 'woo-bg-speedy-label' , $request_body );

		$request = $this->container[ Client::SPEEDY ]->api_call( $this->container[ Client::SPEEDY ]::CALC_LABELS_ENDPOINT, $request_body );

		if ( !isset( $request ) ) {
			$data['errors'] = __( 'Calculation failed. Please try again.', 'woo-bg' );
		} else if ( isset( $request['error'] ) || isset( $request['calculations'][0]['error'] ) ) {
			if ( isset( $request['calculations'][0]['error'] ) ) {
				$data['errors'] = $request['calculations'][0]['error']['message'];
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

		$content_data = $this->generate_content_data();
		$services_data = $this->generate_services_data();
		$payment_by_data = $this->generate_payment_by_data();

		return array_merge( $label, $content_data, $services_data, $payment_by_data );
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
		$recipient = array(
			'privatePerson' => true,
			'clientName' => $this->cookie_data[ 'receiver' ],
			'phone1' => array(
				'number' => $this->cookie_data[ 'phone' ],
			)
		);

		if ( $this->cookie_data['type'] === 'address' ) {
			$recipient[ 'addressLocation' ] = $this->generate_recipient_address();
			$recipient[ 'address' ] = $this->generate_recipient_address();
		} else if ( $this->cookie_data['type'] === 'office' ) {
			$recipient[ 'pickupOfficeId' ] = $this->generate_recipient_office_code();
		}

		return $recipient;
	}

	private function generate_recipient_address() {
		$raw_city = sanitize_text_field( $this->cookie_data['city'] );
		$raw_state = sanitize_text_field( $this->cookie_data['state'] );
		$cities_data = $this->container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state );
		
		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) || !isset( $cities_data['cities'][ $cities_data['city_key'] ] ) ) {
			return( [] );
		}

		$address = array(
			'siteId' => $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ],
		);

		if ( !empty( $this->cookie_data['selectedAddress']['type'] ) && $this->cookie_data['selectedAddress']['type'] === 'streets' ) {
			$address["streetId"] = str_replace('street-', '', $this->cookie_data['selectedAddress']['orig_key'] ); 
			$address["streetNo"] = $this->cookie_data['streetNumber'];
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
			'parcelsCount' => 1,
			'totalWeight' => 0,
			'package' => 'BOX',
		);

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			$_product = wc_get_product( $item[ 'product_id' ] );
			
			if ( $item['data']->get_weight() ) {
				$content['totalWeight'] += wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];
			}

			$names[] = $item['data']->get_name();
		}

		if ( !$content['totalWeight'] ) {
			$content['totalWeight'] = apply_filters( 'woo_bg/speedy/label/weight', 1, $this->package, $this );
		}

		$content['contents'] = implode( ',', $names );

		return array(
			'content' => $content,
		);
	}

	private function generate_services_data() {
		$services = array(
			'autoAdjustPickupDate' => true, 
			'serviceId' => 505,
			'serviceIds' => array( 505 ),
			'additionalServices' => [],
		);

		$os_value = 0;
		$is_fragile = false;

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			$_product = wc_get_product( $item[ 'product_id' ] );
			
			if ( $product_os_value = $_product->get_meta( '_woo_bg_os_value' ) ) {
				$os_value += $product_os_value * $item['quantity'];

				if ( $_product->get_meta( '_woo_bg_fragile' ) === 'on' ) {
					$is_fragile = true;
				}
			}
		}

		if ( $os_value ) {
			$services['additionalServices']['declaredValue'] = array(
				'amount' => number_format( $os_value, 2 ), 
				'fragile' => $is_fragile, 
				"ignoreIfNotApplicable" => true 
			);
		}

		if ( $this->cookie_data['payment'] === 'cod' ) {
			$services['additionalServices']['cod'] = array(
				'amount' => $this->get_package_total(), 
				'processingType' => ( wc_string_to_bool( woo_bg_get_option( 'speedy', 'ppp' ) ) ) ? 'POSTAL_MONEY_TRANSFER' : 'CASH',
			);

			if ( 
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
					'returnShipmentServiceId' => 505, 
					'returnShipmentPayer' => 'RECIPIENT' 
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
	
	private function generate_payment_by_data() {
		$payment = array(
			"courierServicePayer" => "RECIPIENT",
			"declaredValuePayer" => "RECIPIENT",
			"packagePayer" => "RECIPIENT",
		);

		if ( isset( $this->cookie_data['payment'] ) && $this->cookie_data['payment'] !== 'cod' ) {
			$payment[ 'declaredValuePayer' ] = 'SENDER';
			$payment[ 'packagePayer' ] = 'SENDER';
		}

		if ( !empty( $this->fixed_price ) && $payment[ 'courierServicePayer' ] === 'RECIPIENT' ) {
			$payment[ 'courierServicePayer' ] = 'SENDER';
		}

		if ( !empty( $this->free_shipping_over ) && $this->get_package_total() > $this->free_shipping_over ) {
			$this->free_shipping = true;
			
			if ( isset( $this->cookie_data['fixed_price'] ) ) {
				unset( $this->cookie_data['fixed_price'] );
			}

			if ( $payment[ 'courierServicePayer' ] === 'RECIPIENT' ) {
				$payment[ 'courierServicePayer' ] = 'SENDER';
			}
		}

		return array(
			'payment' => $payment,
		);
	}

	protected function get_package_total() {
		$total = WC()->cart->total;

		if ( !$this->free_shipping && WC()->cart->shipping_total ) {
			$total -= ( WC()->cart->shipping_total + WC()->cart->shipping_tax_total );
		}

		return number_format( $total, 2 );
	}

	public static function validate_speedy_method( $fields, $errors ){
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_speedy' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];
				
				if ( $data->method_id === 'woo_bg_speedy' && ! $data->meta_data['validated'] ) {
					$meta_data = $data->get_meta_data();

					if ( !empty( $meta_data['errors'] ) ) {
						$errors->add( 'validation', sprintf( __( 'Speedy - %s', 'woo-bg' ), $meta_data['errors'] ) );
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'woo-bg' ) );
					}
				}

				$cookie_data = self::get_cookie_data();

				if ( $cookie_data['type'] === 'office' &&  empty( $cookie_data['selectedOffice'] ) ) {
					$errors->add( 'validation', __( 'Please choose a office.', 'woo-bg' ) );
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
						$order->update_meta_data( 'woo_bg_speedy_label', WC()->session->get( 'woo-bg-speedy-label' ) );
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

		wp_enqueue_style(
			'woo-bg-css-speedy',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'speedy-frontend.css' )
		);
	}

	public static function add_label_number_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $email->id !== 'customer_processing_order' ) {
			return;
		}

		$label = $order->get_meta( 'woo_bg_speedy_label' );

		if ( !isset( $label['id'] ) ) {
			return;
		}

		$number = $label['id'];
		$url = 'https://www.speedy.bg/bg/track-shipment?shipmentNumber=' . $number;

		$track_number_text = sprintf( 
			__( 'Label number: %s. %s', 'woo-bg' ), 
			$number, 
			sprintf( '<a href="%s" target="_blank">%s</a>',
				$url,
				__( 'Track your order.' , 'woo-bg' )
			)
		);

		$track_number_text = apply_filters( 'woo_bg/speedy/track_number_text_in_email', $track_number_text, $url, $order );

		echo wpautop( $track_number_text );
	}
}

