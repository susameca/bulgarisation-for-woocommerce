<?php
namespace Woo_BG\Shipping\CVC;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_cvc";
	private $container = '';
	private $cookie_data = '';
	private $package = '';
	private $free_shipping = false;

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = 'woo_bg_cvc'; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - CVC', 'woo-bg' );  // Title shown in admin
		$this->method_description = __( 'Enables CVC delivery and automatically calculate shipping price.', 'woo-bg' ); // Description shown in admin
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
		$this->is_sat               = $this->get_option( 'is_sat' );
		$this->is_ppp               = $this->get_option( 'is_ppp' );
		$this->sms                  = $this->get_option( 'sms' );
		$this->tax_status           = 'none';

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
		$this->cookie_data = $this->get_cookie_data();
		$this->package = $package;

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
		);

		$rate['meta_data']['delivery_type'] = $this->delivery_type;
		$rate['meta_data']['validated'] = false;
		$payment_by_data = $this->generate_payment_by_data();

		if ( 
			isset( $this->cookie_data['type'] ) && 
			$this->cookie_data['type'] === $this->delivery_type && 
			( 
				( isset( $this->cookie_data['other'] ) && $this->cookie_data['other'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['streetNumber']) && $this->cookie_data['streetNumber'] && $this->cookie_data['selectedAddress'] ) || 
				( isset( $this->cookie_data['selectedOffice'] ) && $this->cookie_data['selectedOffice'] ) 
			) 
		) {
			$this->cookie_data['fixed_price'] = $this->fixed_price;
			$request_data = $this->calculate_shipping_price_from_api();
			$rate['meta_data']['validated'] = true;
			$rate['meta_data']['cookie_data'] = $this->cookie_data;

			if ( isset( $request_data['errors'] ) ) {
				$rate['meta_data']['validated'] = false;
				$rate['meta_data']['errors'] = $request_data['errors'];
			} elseif ( isset( $request_data['price'] )  ) {
				$rate['cost'] = $request_data['price'];
			}

			if ( 
				( !$rate['cost'] && !isset( $request_data['errors'] ) ) || 
				( empty( $this->fixed_price ) && $payment_by_data[ 'payer' ] !== 'rec' )
			) {
				$this->free_shipping = true;
			}
		}

		if ( $this->free_shipping ) {
			$rate['label'] = sprintf( __( '%s: Free shipping', 'woo-bg' ), $rate['label'] );
			$rate[ 'cost' ] = 0;
		} 

		if ( !$this->free_shipping && !empty( $this->fixed_price ) ) {
			$rate[ 'cost' ] = $this->fixed_price;
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
			'is_sat'    => array(
				'title'             => __( 'Saturday Delivery', 'woo-bg' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => array(
					'no' => __( 'No', 'woo-bg' ),
					'yes' => __( 'Yes', 'woo-bg' ),
				),
				'description' => __( 'Saturday delivery/pickup.', 'woo-bg' ),
			),
			'is_ppp'    => array(
				'title'             => __( 'Cash on delivery as PPP.', 'woo-bg' ),
				'type'              => 'select',
				'css'               => 'width: 400px;',
				'default'           => 'no',
				'options'           => array(
					'no' => __( 'No', 'woo-bg' ),
					'yes' => __( 'Yes', 'woo-bg' ),
				),
				'description' => __( 'Cash on delivery to be paid with PPP.', 'woo-bg' ),
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
				'description' => __( 'Enter a fixed price that will be payed by you, and will be included in the order.', 'woo-bg' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	public function is_available( $package ) {
		//$countries = $this->container[ Client::CVC_COUNTRIES ]->get_countries();
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
		return ( isset( $_COOKIE[ 'woo-bg--cvc-address' ] ) ) ? json_decode( stripslashes( urldecode( $_COOKIE[ 'woo-bg--cvc-address' ] ) ), 1 ) : '';
	}

	public function calculate_shipping_price_from_api() {
		$data = array(
			'price' => '0',
		);
		
		$request_body = apply_filters( 'woo_bg/cvc/calculate_label', $this->generate_label(), $this );
		
		WC()->session->set( 'woo-bg-cvc-label' , $request_body );

		$request = $this->container[ Client::CVC ]->api_call( $this->container[ Client::CVC ]::CALC_LABELS_ENDPOINT, $request_body );

		if ( !isset( $request ) ) {
			$data['errors'] = __( 'Calculation failed. Please try again.', 'woo-bg' );
		} else if ( !$request['success'] ) {
			$data['errors'] = $request['error'];
		} else if ( isset( $request['price'] ) ) {
			$data['price'] = $request['price_with_vat'];
			$data['price_without_vat'] = $request['price'];
			$data['vat'] = $request['price_with_vat'] - $request['price'];
		}

		return $data;
	}

	private function generate_label() {
		$label = array(
			"pickup_date" => date_i18n( 'Y-m-d' ), // "2020-10-27
			"total_parcels" => '1', // "2020-10-27
			"sender" => $this->generate_sender_data(),
		);

		if ( $this->cookie_data ) {
			$label['rec'] = $this->generate_receiver_data();
		}

		$cart_data = $this->generate_cart_data();
		$other_data = $this->generate_other_data();
		$payment_by_data = $this->generate_payment_by_data();

		return array_merge( $label, $cart_data, $other_data, $payment_by_data );
	}

	private function generate_sender_data() {
		$city = str_replace( 'cityID-', '', woo_bg_get_option( 'cvc_sender', 'city' ) );

		$sender_data = array( 
			"name" => woo_bg_get_option( 'cvc_sender', 'name' ), 
			"phone" => woo_bg_get_option( 'cvc_sender', 'phone' ), 
			"email" => woo_bg_get_option( 'cvc_sender', 'email' ), 
			"country_id" => 100, 
			"city_id" => $city,
			"zip" => $this->container[ Client::CVC_CITIES ]->get_city_zip_by_id( $city ),
		);

		$send_from = woo_bg_get_option( 'cvc_sender', 'send_from' );

		if ( $send_from === 'address' ) {
			$sender_data[ "custom_location_id" ] = woo_bg_get_option( 'cvc_sender', 'address' );
		} else if ( $send_from === 'office' ) {
			$sender_data[ "hub_id" ] = str_replace( 'officeID-', '', woo_bg_get_option( 'cvc_sender', 'office' ) );
		}

		return $sender_data;
	}

	private function generate_receiver_data() {
		if ( !isset( $_POST['country'] ) ) {
			return( [] );
		}

		$country = sanitize_text_field( $_POST['country'] );
		$country_id = $this->container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$raw_city = sanitize_text_field( $_POST['city'] );
		$state_id = $this->container[ Client::CVC_CITIES ]->get_state_id( sanitize_text_field( $_POST['state'] ), $country_id );
		$city = $this->container[ Client::CVC_CITIES ]->search_for_city( $raw_city, $state_id, $country_id );


		if ( empty( $city ) ) {
			return( [] );
		}
		
		$rec = array(
			"name" => $this->cookie_data['receiver'], 
			"phone" => $this->cookie_data['phone'],
			"country_id" => $country_id, 
			"city_id" => $city[0]['id'],
			"zip" => $city[0]['zip'],
		);

		if ( $this->cookie_data['type'] === 'address' ) {
			$rec["street"] = $this->cookie_data['selectedAddress']['label'] . ' ' . $this->cookie_data['streetNumber'] . ' ' . $this->cookie_data[ 'other' ]; 

			if ( $this->cookie_data['selectedAddress']['type'] === 'streets' ) {
				$rec["street_id"] = str_replace('street-', '', $this->cookie_data['selectedAddress']['orig_key'] ); 
				$rec["num"] = $this->cookie_data['streetNumber'];
			} else if ( $this->cookie_data['selectedAddress']['type'] === 'quarters' ) {
				$rec["qt_id"] = str_replace('qtr-', '', $this->cookie_data['selectedAddress']['orig_key'] );
			}
		} else if ( $this->cookie_data['type'] === 'office' ) {
			$rec["office_id"] = $this->cookie_data['selectedOffice'];
		}

		return $rec;
	}

	private function generate_cart_data() {
		global $woocommerce;
		$names = array();
		$cart = array(
			'parcel_type' => 'parcel',
			'total_kgs' => 0,
			'reject_payer' => 'rec',
		);

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			if ( $item['data']->get_weight() ) {
				$cart['total_kgs'] += wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];
			}

			$names[] = $item['data']->get_name();
		}

		if ( !$cart['total_kgs'] ) {
			$cart['total_kgs'] = apply_filters( 'woo_bg/cvc/label/weight', 1, $this->package, $this );
		}

		$cart['description'] = implode( ', ', $names );

		if ( $this->cookie_data['payment'] === 'cod' ) {
			$cart['cod_amount'] = $this->get_package_total();

			if ( $this->is_ppp === 'yes' ) {
				$cart['is_cod_ppp'] = 1;
			}
		}

		$os_value = 0;

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			$_product = wc_get_product( $item[ 'product_id' ] );

			if ( $product_os_value = $_product->get_meta( '_woo_bg_os_value' ) ) {
				$os_value += $product_os_value * $item['quantity'];

				if ( $_product->get_meta( '_woo_bg_fragile' ) === 'on' ) {
					$cart['is_fragile'] = 1;
				}
			} 
		}

		if ( $os_value ) {
			$cart[ 'os_value' ] = $os_value;
		}

		return $cart;
	}

	private function generate_other_data() {
		$other_data = array();

		if ( $this->sms === 'yes' ) {
			$other_data['is_sms'] = 1;
		}

		if ( $this->is_sat === 'yes' ) {
			$other_data['is_sat'] = 1;
		}

		if ( $this->test == 'review' ) {
			$other_data['is_observe'] = 1;
		} else if ( $this->test == 'test' ) {
			$other_data['is_observe'] = 1;
			$other_data['is_test'] = 1;
		}

		return $other_data;
	}
	
	private function generate_payment_by_data() {
		$payment_by_data = array(
			'payer' => 'rec',
			'reject_payer' => 'rec', 
		);

		if ( !empty( $this->fixed_price ) && $payment_by_data[ 'payer' ] === 'rec' ) {
			$payment_by_data[ 'payer' ] = 'sender';
		}

		if ( !empty( $this->free_shipping_over ) && $this->get_package_total() > $this->free_shipping_over ) {
			$this->free_shipping = true;

			if ( $payment_by_data[ 'payer' ] === 'rec' ) {
				$payment_by_data[ 'payer' ] = 'sender';
			}
		}

		if ( $payment_by_data[ 'payer' ] === 'sender' && woo_bg_get_option( 'cvc_sender', 'contract_pay' ) === 'yes' ) {
			$payment_by_data[ 'payer' ] = 'contract';
		}

		return $payment_by_data;
	}

	private function get_package_total() {
		$total = WC()->cart->total;

		if ( WC()->cart->shipping_total ) {
			$total -= WC()->cart->shipping_total;
		}

		return $total;
	}

	public static function validate_cvc_method( $fields, $errors ){
		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_cvc' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];

				if ( $data->method_id === 'woo_bg_cvc' && ! $data->meta_data['validated'] ) {
					$meta_data = $data->get_meta_data();

					if ( !empty( $meta_data['errors'] ) ) {
						$errors->add( 'validation', sprintf( __( 'CVC - %s', 'woo-bg' ), $meta_data['errors'] ) );
					} else {
						$errors->add( 'validation', __( 'Please choose delivery option!', 'woo-bg' ) );
					}
				}
			}
		}
	}

	public static function save_label_data_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( WC()->session->get( 'woo-bg-cvc-label' ) ) {
			$order->update_meta_data( 'woo_bg_cvc_label', WC()->session->get( 'woo-bg-cvc-label' ) );
			$order->save();
			WC()->session->__unset( 'woo-bg-cvc-label' );
		}

		if ( !empty( $order->get_items( 'shipping' ) ) ) {
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_cvc' ) {
					$cookie_data = '';

					foreach ( $shipping->get_meta_data() as $meta_data ) {
						$data = $meta_data->get_data();

						if ( $data['key'] == 'cookie_data' ) {
							$cookie_data = $data['value'];
						}
					}

					if ( $cookie_data ) {
						$order->update_meta_data( $order_id, 'woo_bg_cvc_cookie_data', $cookie_data );
						$order->save();
					}
					break;
				}
			}
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-cvc',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'cvc-frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_localize_script( 'woo-bg-js-cvc', 'wooBg_cvc_address', array(
			'i18n' => Address::get_i18n(),
		) );

		wp_localize_script( 'woo-bg-js-cvc', 'wooBg_cvc', array(
			'i18n' => Office::get_i18n(),
		) );

		wp_enqueue_style(
			'woo-bg-css-cvc',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'cvc-frontend.css' )
		);
	}

	public static function add_label_number_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $email->id !== 'customer_processing_order' ) {
			return;
		}
		
		$label = $order->get_meta( 'woo_bg_cvc_label' );

		if ( !isset( $label['wb'] ) ) {
			return;
		}

		$number = $label['wb'];
		$url = 'https://my.e-cvc.bg/track?wb=' . $number;

		$track_number_text = sprintf( 
			__( 'Label number: %s. %s', 'woo-bg' ), 
			$number, 
			sprintf( '<a href="%s" target="_blank">%s</a>',
				$url,
				__( 'Track your order.' , 'woo-bg' )
			)
		);

		$track_number_text = apply_filters( 'woo_bg/cvc/track_number_text_in_email', $track_number_text, $url, $order );

		echo wpautop( $track_number_text );
	}
}
