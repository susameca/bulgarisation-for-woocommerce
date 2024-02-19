<?php
namespace Woo_BG\Shipping\Econt;
use Woo_BG\Container\Client;
use Woo_BG\Admin\Econt as Econt_Admin;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_econt";
	public $container = '';
	public $cookie_data = '';
	public $package = '';
	public $free_shipping = false;

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = 'woo_bg_econt'; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - Econt', 'woo-bg' );  // Title shown in admin
		$this->method_description = __( 'Enables econt delivery and automatically calculate shipping price.', 'woo-bg' ); // Description shown in admin
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
		$this->sms                  = $this->get_option( 'sms' );
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

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
		);

		$rate['meta_data']['delivery_type'] = $this->delivery_type;
		$rate['meta_data']['validated'] = false;
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		if ( 
			isset( $this->cookie_data['type'] ) && 
			$this->cookie_data['type'] === $this->delivery_type && 
			( !empty( $chosen_shippings ) && ( $chosen_shippings[0] === $this->id . ':' . $this->instance_id ) ) &&
			( 
				( isset( $this->cookie_data['other'] ) && $this->cookie_data['other'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['streetNumber']) && $this->cookie_data['streetNumber'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['selectedOffice'] ) && $this->cookie_data['selectedOffice'] ) 
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
			}

			if ( !$rate['cost'] ) {
				$this->free_shipping = true;
			}
		}

		if ( $this->free_shipping && $rate['meta_data']['validated'] ) {
			$rate['label'] = sprintf( __( '%s: Free shipping', 'woo-bg' ), $rate['label'] );
		}

		$rate = apply_filters( 'woo_bg/econt/rate', $rate, $this );

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
			'sms'    => array(
				'title'             => __( 'SMS notification', 'woo-bg' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => array(
					'no' => __( 'No', 'woo-bg' ),
					'yes' => __( 'Yes', 'woo-bg' ),
				),
				'description' => __( 'Send customer notification for delivery.', 'woo-bg' ),
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
		//$countries = $this->container[ Client::ECONT_COUNTRIES ]->get_countries();
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
		return ( isset( $_COOKIE[ 'woo-bg--econt-address' ] ) ) ? json_decode( stripslashes( urldecode( $_COOKIE[ 'woo-bg--econt-address' ] ) ), 1 ) : '';
	}

	public function calculate_shipping_price_from_api() {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/econt/calculate_label', array(
			'label' => $this->generate_label(),
			'mode' => 'calculate',
		), $this );

		if ( isset( $request_body['label']['senderOfficeCode'] ) ) {
			unset( $request_body['label']['senderAddress'] );
		}

		WC()->session->set( 'woo-bg-econt-label' , $request_body );

		$request = $this->container[ Client::ECONT ]->api_call( $this->container[ Client::ECONT ]::LABELS_ENDPOINT, $request_body );

		if ( isset( $request['type'] ) && $request['type'] === 'ExInvalidParam' ) {
			$data['errors'] = $request;
		} else if ( isset( $request['label']['receiverDueAmount'] ) ) {
			$data['price'] = woo_bg_tax_based_price( $request['label']['receiverDueAmount'] );
		}

		return $data;
	}

	private function generate_label() {
		$label = array(
			'senderClient' => $this->generate_sender_data(),
			'senderAgent' => $this->generate_sender_auth(),
			'senderAddress' => $this->generate_sender_address(),
		);

		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $send_from == 'office' ) {
			$label['senderOfficeCode'] = $this->generate_sender_office_code();
		}

		if ( $this->cookie_data ) {
			if ( isset( $this->cookie_data['billing_to_company'] ) ) {
				$label['receiverAgent'] = $this->generate_receiver_data();
				$label['receiverClient'] = $this->generate_receiver_data();
				$label['receiverClient']['juridicalEntity'] = true;
				$label['receiverClient']['molName'] = $this->cookie_data['billing_company_mol'];
				
				if ( !empty( $this->cookie_data['billing_company'] ) ) {
					$label['receiverClient']['name'] = $this->cookie_data['billing_company'];
				}

				if ( $this->cookie_data['billing_vat_number'] ) {
					$vat_number = $this->cookie_data['billing_vat_number'];
					$label['receiverClient']['ein'] = substr( $vat_number, 2 );
					$label['receiverClient']['ddsEin'] = $label['receiverClient']['ein'];
					$label['receiverClient']['ddsEinPrefix'] = str_replace( $label['receiverClient']['ein'], '', $vat_number );
				}
			} else {
				$label['receiverClient'] = $this->generate_receiver_data();
				$label['receiverAgent'] = $this->generate_receiver_data();
			}

			if ( $this->cookie_data['type'] === 'address' ) {
				$label['receiverAddress'] = $this->generate_receiver_address();
			} else if ( $this->cookie_data['type'] === 'office' ) {
				$label['receiverOfficeCode'] = $this->generate_receiver_office_code();
			}
		}

		$cart_data = $this->generate_cart_data();
		$other_data = $this->generate_other_data();
		$payment_by_data = $this->generate_payment_by_data();

		return array_merge( $label, $cart_data, $other_data, $payment_by_data );
	}

	private function generate_sender_auth() {
		$client = $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['client'];

		return array(
			'name' => woo_bg_get_option( 'econt', 'name' ),
			'phones' => [ woo_bg_get_option( 'econt', 'phone' ) ],
		);
	}

	private function generate_sender_data() {
		return $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['client'];
	}

	private function generate_sender_address() {
		$address = '';
		$id = woo_bg_get_option( 'econt_send_from', 'address' );
		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $id !== '' && $send_from === 'address' ) {
			$profile_addresses = $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['addresses'];

			$address = $profile_addresses[ $id ];
		}

		return $address;
	}

	private function generate_sender_office_code() {
		$office = woo_bg_get_option( 'econt_send_from', 'office' );
		
		return str_replace( 'officeID-', '', $office );
	}

	private function generate_receiver_data() {
		return array(
			'name' => $this->cookie_data[ 'receiver' ],
			'phones' => array( $this->cookie_data[ 'phone' ] ),
		);
	}

	private function generate_receiver_address() {
		$states = woo_bg_return_bg_states();
		$state = $states[ $this->cookie_data['state'] ];
		$cities = $this->container[ Client::ECONT_CITIES ]->get_filtered_cities( $this->cookie_data['city'], $state );
		$city_key = $cities['city_key'];
		$type = ( !empty( $this->cookie_data['selectedAddress']['type'] ) ) ? $this->cookie_data['selectedAddress']['type'] :'';

		if ( empty( $cities['cities'][ $city_key ] ) ) {
			return [];
		}

		$receiver_address = array(
			'city' => $cities['cities'][ $city_key ],
		);

		if ( $type === 'streets' ) {
			$num_parts = explode( ' ', $this->cookie_data['streetNumber'] );
			$receiver_address['street'] = $this->cookie_data['selectedAddress']['label'];
			$receiver_address['num'] = array_shift( $num_parts );
			$this->cookie_data['other'] = '';
			$this->cookie_data['streetNumber'] = $receiver_address['num'];

			if ( !empty( $num_parts ) ) {
				$receiver_address['other'] = implode( ' ', $num_parts );
			}

			if ( !empty( $this->cookie_data['otherField'] ) ) {
				$receiver_address['other'] .= $this->cookie_data['otherField'];
			}

			if ( isset( $receiver_address['other'] ) ) {
				$this->cookie_data['other'] = $receiver_address['other'];
			}
		} else if ( $type === 'quarters' ) {
			$receiver_address['quarter'] = $this->cookie_data['selectedAddress']['label'];
			$receiver_address['other'] = $this->cookie_data['other'];
		}

		return $receiver_address;
	}

	private function generate_receiver_office_code() {
		return ( isset( $this->cookie_data['selectedOffice'] ) ) ? $this->cookie_data['selectedOffice'] : '';
	}

	private function generate_cart_data() {
		$names = array();
		$cart = array(
			'packCount' => 1,
			'shipmentType' => 'PACK',
			'weight' => 0,
		);

		$os_value = 0;

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			$_product = wc_get_product( $item[ 'product_id' ] );
			$product_os_value = $_product->get_meta( '_woo_bg_os_value' );

			if ( is_numeric( $product_os_value ) && is_numeric( $item['quantity'] ) ) {
				$os_value += $product_os_value * absint( $item['quantity'] );
			}
			
			if ( $item['data']->get_weight() ) {
				$cart['weight'] += wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];
			}

			if ( $cart['weight'] > 0 && $cart['weight'] < 0.100 ) {
				$cart['weight'] = 0.100;
			}

			$names[] = $item['data']->get_name();
		}

		if ( !$cart['weight'] ) {
			$cart['weight'] = apply_filters( 'woo_bg/econt/label/weight', 1, $this->package, $this );
		}

		$cart['shipmentDescription'] = implode( ', ', $names );

		if ( $this->cookie_data['payment'] === 'cod' ) {
			$cart['services']['cdType'] = 'get';
			$cart['services']['cdAmount'] = $this->get_package_total();
			$cart['services']['cdCurrency'] = get_woocommerce_currency();

			$cd_pay_option = woo_bg_get_option( 'econt', 'pay_options' );
			
			if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
				$cart['services']['cdPayOptionsTemplate'] = $cd_pay_option;
			}
		}

		if ( $os_value && empty( $this->cookie_data['selectedOfficeIsAPS'] ) ) {
			$cart[ 'services' ]['declaredValueAmount'] = number_format( $os_value, 2 );
			$cart[ 'services' ]['declaredValueCurrency'] = 'BGN';
		}

		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $send_from === 'office' ) {
			$offices = $this->container[ Client::ECONT_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'econt_send_from', 'office_city' ) );
			$office = woo_bg_get_option( 'econt_send_from', 'office' );

			if ( !empty( $offices['aps'] ) && array_key_exists( $office, $offices['aps'] ) ) {
				unset( $cart[ 'services' ]['declaredValueAmount'] );
				unset( $cart[ 'services' ]['declaredValueCurrency'] );
			}
		}

		if ( $this->sms === 'yes' ) {
			$cart['services']['smsNotification'] = true;
		}


		return $cart;
	}

	private function generate_other_data() {
		$other_data = [];
		
		if ( empty( $this->cookie_data['selectedOfficeIsAPS'] ) ) {
			if ( $this->test == 'review' ) {
				$other_data['payAfterAccept'] = true;
			} else if ( $this->test == 'test' ) {
				$other_data['payAfterAccept'] = true;
				$other_data['payAfterTest'] = true;
			}
		}

		return $other_data;
	}
	
	private function generate_payment_by_data() {
		$payment_by_data = array(
			'paymentReceiverMethod' => 'cash',
		);

		if ( !empty( $this->fixed_price ) ) {
			$payment_by_data['paymentSenderMethod'] = $this->container[ Client::ECONT_PROFILE ]->get_sender_payment_method();
			$payment_by_data['paymentReceiverMethod'] = 'cash';
			$payment_by_data['paymentReceiverAmount'] = $this->fixed_price;
		}

		if ( 
			woo_bg_has_free_shipping_coupon_in_cart() || 
			( !empty( $this->free_shipping_over ) && $this->get_package_total() > $this->free_shipping_over )
		) {
			$this->free_shipping = true;

			unset( $payment_by_data[ 'paymentReceiverMethod' ] );
			unset( $payment_by_data[ 'paymentReceiverAmount' ] );

			$payment_by_data['paymentSenderMethod'] = $this->container[ Client::ECONT_PROFILE ]->get_sender_payment_method();
		}

		return $payment_by_data;
	}

	protected function get_package_total() {
		$total = floatval( WC()->cart->get_cart_contents_total() ) + floatval( WC()->cart->get_cart_contents_tax() );

		return number_format( $total, 2, '.', '' );
	}

	public static function validate_econt_method( $fields, $errors ){
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_econt' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];

				if ( $data->method_id === 'woo_bg_econt' && ! $data->meta_data['validated'] ) {
					$meta_data = $data->get_meta_data();

					if ( !empty( $meta_data['errors'] ) && !empty( array_filter( $meta_data['errors'] ) ) ) {
						$message = array_merge( array( __( 'Econt - ', 'woo-bg' ) ) , woo_bg()->container()[ Client::ECONT ]::add_error_message( $meta_data['errors'] ) );
						$errors->add( 'validation', implode( ' ', $message ) );
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'woo-bg' ) );
					}
				}

				$cookie_data = self::get_cookie_data();

				if ( $cookie_data['type'] === 'office' && empty( $cookie_data['selectedOffice'] ) ) {
					$errors->add( 'validation', __( 'Please choose a office.', 'woo-bg' ) );
				}
			}
		}
	}

	public static function save_label_data_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( !empty( $order->get_items( 'shipping' ) ) ) {
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_econt' ) {
					$cookie_data = '';

					foreach ( $shipping->get_meta_data() as $meta_data ) {
						$data = $meta_data->get_data();

						if ( $data['key'] == 'cookie_data' ) {
							$cookie_data = $data['value'];
						}
					}

					if ( $cookie_data ) {
						$order->update_meta_data( 'woo_bg_econt_cookie_data', $cookie_data );
					}
					
					if ( WC()->session->get( 'woo-bg-econt-label' ) ) {
						$label = WC()->session->get( 'woo-bg-econt-label' );

						if ( isset( $cookie_data['payment'] ) && $cookie_data['payment'] !== 'cod' ) {
							$container = woo_bg()->container();
							
							unset( 
								$label['label']['paymentReceiverMethod'],
								$label['label']['paymentReceiverAmount'],
								$label['label']['paymentSenderMethod'],
							);
							
							$label['label']['paymentSenderMethod'] = $container[ Client::ECONT_PROFILE ]->get_sender_payment_method();
						}

						$order->update_meta_data( 'woo_bg_econt_label', $label );
						WC()->session->__unset( 'woo-bg-econt-label' );
					}

					$order->save();
					break;
				}
			}
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-econt',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'econt-frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);
		
		wp_localize_script( 'woo-bg-js-econt', 'wooBg_econt_address', array(
			'i18n' => Address::get_i18n(),
		) );

		wp_localize_script( 'woo-bg-js-econt', 'wooBg_econt', array(
			'i18n' => Office::get_i18n(),
		) );

		wp_enqueue_style(
			'woo-bg-css-econt',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'econt-frontend.css' )
		);
	}

	public static function add_label_number_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		$email_ids_to_send = [];

		if ( woo_bg_get_option( 'econt', 'label_after_checkout' ) === 'yes' ) {
			$email_ids_to_send[] = 'customer_processing_order';
		}
		
		$email_ids_to_send = apply_filters( 'woo_bg/econt/emails_to_send_label_number', $email_ids_to_send );

		if ( !in_array( $email->id, $email_ids_to_send ) ) {
			return;
		}

		$label = $order->get_meta( 'woo_bg_econt_label' );

		if ( !isset( $label['label']['shipmentNumber'] ) ) {
			return;
		}

		$number = $label['label']['shipmentNumber'];
		$url = 'https://www.econt.com/services/track-shipment/' . $number;

		$track_number_text = sprintf( 
			__( 'Label number: %s. %s', 'woo-bg' ), 
			$number, 
			sprintf( '<a href="%s" target="_blank">%s</a>',
				$url,
				__( 'Track your order.' , 'woo-bg' )
			)
		);

		$track_number_text = apply_filters( 'woo_bg/econt/track_number_text_in_email', $track_number_text, $url, $order );

		echo wp_kses_post( wpautop( $track_number_text ) );
	}
}