<?php
namespace Woo_BG\Shipping\Econt;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_econt";
	private $container = '';
	private $free_shipping = false;

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
		$cookie_data = $this->get_cookie_data();

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
		);

		$rate['meta_data']['delivery_type'] = $this->delivery_type;
		$rate['meta_data']['validated'] = false;

		if ( 
			isset( $cookie_data['type'] ) && 
			$cookie_data['type'] === $this->delivery_type && 
			( 
				( isset( $cookie_data['streetNumber']) && $cookie_data['streetNumber'] ) || 
				( isset( $cookie_data['selectedOffice'] ) && $cookie_data['selectedOffice'] ) 
			) 
		) {
			$request_data = $this->calculate_shipping_price_from_api( $package );

			$rate['meta_data']['validated'] = true;
			$rate['meta_data']['cookie_data'] = $cookie_data;

			if ( isset( $request_data['errors'] ) ) {
				$rate['meta_data']['validated'] = false;
				$rate['meta_data']['errors'] = $request_data['errors'];
			} elseif ( $request_data['price']  ) {
				$rate['cost'] = $request_data['price'];
			}

		}

		if ( $this->free_shipping ) {
			$rate['label'] = sprintf( __( '%s: Free shipping', 'woo-bg' ), $rate['label'] );
		}


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
				'options'           => array(
					'no' => __( 'No', 'woo-bg' ),
					'review' => __( 'Review only', 'woo-bg' ),
					'test' => __( 'Review and test', 'woo-bg' ),
				),
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

	public function get_cookie_data() {
		return ( isset( $_COOKIE[ 'woo-bg--econt-address' ] ) ) ? json_decode( stripslashes( $_COOKIE[ 'woo-bg--econt-address' ] ), 1 ) : '';
	}

	public function calculate_shipping_price_from_api( $package ) {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/econt/calculate_label', array(
			'label' => $this->generate_label( $package ),
			'mode' => 'calculate',
		) );

		WC()->session->set( 'woo-bg-econt-label' , $request_body );

		$request = $this->container[ Client::ECONT ]->api_call( $this->container[ Client::ECONT ]::LABELS_ENDPOINT, $request_body );

		//var_dump( $request );
		if ( isset( $request['innerErrors'] ) ) {
			$data['errors'] = $request['innerErrors'];
		} else if ( isset( $request['label']['receiverDueAmount'] ) ) {
			$data['price'] = $request['label']['receiverDueAmount'];
		}

		return $data;
	}

	private function generate_label( $package ) {
		$label = array(
			'senderClient' => $this->generate_sender_data(),
			'senderAgent' => $this->generate_sender_auth(),
			'senderAddress' => $this->generate_sender_address(),
		);

		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $send_from == 'office' ) {
			$label['senderOfficeCode'] = $this->generate_sender_office_code();
		}

		$cookie_data = $this->get_cookie_data();

		if ( isset( $_COOKIE[ 'woo-bg--econt-address' ] ) ) {
			$label['receiverClient'] = $this->generate_receiver_data( $cookie_data );

			if ( $cookie_data['type'] === 'address' ) {
				$label['receiverAddress'] = $this->generate_receiver_address( $cookie_data );
			} else if ( $cookie_data['type'] === 'office' ) {
				$label['receiverOfficeCode'] = $this->generate_receiver_office_code( $cookie_data );
			}
		}

		$cart_data = $this->generate_cart_data( $cookie_data, $package );
		$other_data = $this->generate_other_data( $cookie_data, $package );
		$payment_by_data = $this->generate_payment_by_data( $cookie_data, $package );

		return array_merge( $label, $cart_data, $other_data, $payment_by_data );
	}

	private function generate_sender_auth() {
		$client = $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['profiles'][0]['client'];

		return array(
			'name' => woo_bg_get_option( 'econt', 'name' ),
			'phones' => [ woo_bg_get_option( 'econt', 'phone' ) ],
		);
	}

	private function generate_sender_data() {
		return $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['profiles'][0]['client'];
	}

	private function generate_sender_address() {
		$id = woo_bg_get_option( 'econt_send_from', 'address' );

		$profile_addresses = $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['profiles'][0]['addresses'];

		$address = $profile_addresses[ $id ];

		return $address;
	}

	private function generate_sender_office_code() {
		$office = woo_bg_get_option( 'econt_send_from', 'office' );
		
		return str_replace( 'officeID-', '', $office );
	}

	private function generate_receiver_data( $cookie_data ) {
		return array(
			'name' => $cookie_data[ 'receiver' ],
			'phones' => array( $cookie_data[ 'phone' ] ),
		);
	}

	private function generate_receiver_address( $cookie_data ) {
		$country = $cookie_data['country'];
		$state = $this->container[ Client::ECONT_CITIES ]->get_state_name( sanitize_text_field( $cookie_data['state'] ), $country );
		$cities = $this->container[ Client::ECONT_CITIES ]->get_cities_by_region( $state );
		$city_key = array_search( $cookie_data['city'], array_column( $cities, 'name' ) );
		$type = ( !empty( $cookie_data['selectedAddress']['type'] ) ) ? $cookie_data['selectedAddress']['type'] :'';

		$receiver_address = array(
			'city' => $cities[ $city_key ],
		);

		if ( $type === 'streets' ) {
			$receiver_address['street'] = $cookie_data['selectedAddress']['label'];
			$receiver_address['num'] = $cookie_data['streetNumber'];
			if ( $cookie_data['otherField'] ) {
				$receiver_address['other'] = $cookie_data['otherField'];
			}
		} else if ( $type === 'quarters' ) {
			$receiver_address['quarter'] = $cookie_data['selectedAddress']['label'];
			$receiver_address['other'] = $cookie_data['other'] . " " . $cookie_data[ 'otherField' ];
		}
		
		return $receiver_address;
	}

	private function generate_receiver_office_code( $cookie_data ) {
		return ( isset( $cookie_data['selectedOffice'] ) ) ? $cookie_data['selectedOffice'] : '';
	}

	private function generate_cart_data( $cookie_data, $package ) {
		global $woocommerce;
		$names = array();
		$cart = array(
			'packCount' => 1,
			'shipmentType' => 'PACK',
			'weight' => 0,
		);

		foreach ( $package[ 'contents' ] as $key => $item ) {
			if ( $item['data']->get_weight() ) {
				$cart['weight'] += wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];

				$names[] = $item['data']->get_name();
			}
		}

		$cart['shipmentDescription'] = implode( ', ', $names );

		if ( $cookie_data['payment'] === 'cod' ) {
			$cart['services']['cdType'] = 'get';
			$cart['services']['cdAmount'] = $package['cart_subtotal'];
			$cart['services']['cdCurrency'] = get_woocommerce_currency();

			$cd_pay_option = woo_bg_get_option( 'econt', 'pay_options' );
			if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
				$cd_pay_options = woo_bg()->container()[ Client::ECONT_PROFILE ]->get_profile_data()['profiles'][0]['cdPayOptions'];
				foreach ( $cd_pay_options as $option ) {
					if ( $option['num'] === $cd_pay_option ) {
						$cart['services']['cdPayOptions'] = $option;
					}
				}
			}
		}

		if ( $this->sms === 'yes' ) {
			$cart['services']['smsNotification'] = true;
		}

		return $cart;
	}

	private function generate_other_data( $cookie_data, $package ) {
		$other_data = [];

		if ( $this->test == 'review' ) {
			$other_data['payAfterAccept'] = true;
		} else if ( $this->test == 'test' ) {
			$other_data['payAfterAccept'] = true;
			$other_data['payAfterTest'] = true;
		}

		return $other_data;
	}
	
	private function generate_payment_by_data( $cookie_data, $package ) {
		$payment_by_data = array(
			'paymentReceiverMethod' => 'cash',
		);

		if ( !empty( $this->fixed_price ) ) {
			$payment_by_data['paymentSenderMethod'] = $this->container[ Client::ECONT_PROFILE ]->get_sender_payment_method();
			$payment_by_data['paymentReceiverMethod'] = 'cash';
			$payment_by_data['paymentReceiverAmount'] = $this->fixed_price;
		}

		if ( !empty( $this->free_shipping_over ) && $package['cart_subtotal'] > $this->free_shipping_over ) {
			$this->free_shipping = true;

			unset( $payment_by_data[ 'paymentReceiverMethod' ] );
			unset( $payment_by_data[ 'paymentReceiverAmount' ] );

			$payment_by_data['paymentSenderMethod'] = $this->container[ Client::ECONT_PROFILE ]->get_sender_payment_method();
		}

		return $payment_by_data;
	}

	public static function validate_econt_method( $fields, $errors ){
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_econt' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];

				if ( $data->method_id === 'woo_bg_econt' && ! $data->meta_data['validated'] ) {
					if ( !empty( array_filter( $data->meta_data['errors'] ) ) ) {
						foreach ( $data->meta_data['errors'] as $error ) {
							$message = array_merge( array( __( 'Econt - ', 'woo-bg' ) ) , woo_bg()->container()[ Client::ECONT ]::add_error_message( $error ) );

							$errors->add( 'validation', implode( ' ', $message ) );
						}
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'woo-bg' ) );
					}
				}
			}
		}
	}

	public static function save_label_data_to_order( $post ) {
		if ( WC()->session->get( 'woo-bg-econt-label' ) ) {
			update_post_meta( $post, 'woo_bg_econt_label', WC()->session->get( 'woo-bg-econt-label' ) );
			
			WC()->session->__unset( 'woo-bg-econt-label' );
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

		wp_enqueue_style(
			'woo-bg-css-econt',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'econt-frontend.css' )
		);
	}
}
