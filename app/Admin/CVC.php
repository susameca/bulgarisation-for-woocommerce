<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\CVC\Address;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class CVC {
	private static $container = null;

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_woo_bg_cvc_generate_label', array( __CLASS__, 'generate_label' ) );
		add_action( 'wp_ajax_woo_bg_cvc_delete_label', array( __CLASS__, 'delete_label' ) );
		add_action( 'wp_ajax_woo_bg_cvc_update_actions', array( __CLASS__, 'update_actions' ) );
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-cvc',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'cvc-admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-cvc',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'cvc-admin.css' )
		);
	}

	public static function add_meta_boxes() {
		global $post, $theorder;

		self::$container = woo_bg()->container();

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		if ( empty( $theorder ) ) {
			return;
		}

		if ( !empty( $theorder->get_items( 'shipping' ) ) ) {
			foreach ( $theorder->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_cvc' ) {
					$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
					? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
					: array( 'shop_order', 'shop_subscription' );

					$screen = array_filter( $screen );

					add_meta_box( 'woo_bg_cvc', __( 'CVC Delivery', 'bulgarisation-for-woocommerce' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
					break;
				}
			}
		}
	}

	public static function meta_box() {
		global $post, $theorder;

		self::$container = woo_bg()->container();

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		} else {
			$order = $theorder;
		}

		if ( !empty( $theorder->get_items( 'shipping' ) ) ) {
			foreach ( $theorder->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_cvc' ) {
					echo '<div id="woo-bg--cvc-admin"></div>';

					$label_data = array();
					$cookie_data = $theorder->get_meta( 'woo_bg_cvc_cookie_data' );
					$shipment_status = $theorder->get_meta( 'woo_bg_cvc_shipment_status' );
					$actions = $theorder->get_meta( 'woo_bg_cvc_actions' );

					if ( $label = $theorder->get_meta( 'woo_bg_cvc_label' ) ) {
						$label_data = $label;
					}

					if ( !$cookie_data ) {
						foreach ( $shipping->get_meta_data() as $meta_data ) {
							$data = $meta_data->get_data();

							if ( $data['key'] == 'cookie_data' ) {
								$cookie_data = $data['value'];
							}
						}	
					}
					
					wp_localize_script( 'woo-bg-js-admin', 'wooBg_cvc', array(
						'label' => $label_data,
						'shipmentStatus' => $shipment_status,
						'actions' => $actions,
						'paymentType' => $theorder->get_payment_method(),
						'cookie_data' => $cookie_data,
						'offices' => self::get_offices( $cookie_data ),
						'streets' => self::get_streets( $cookie_data ),
						'orderId' => $theorder->get_id(),
						'testsOptions' => woo_bg_return_array_for_select( woo_bg_get_shipping_tests_options() ),
						'testOption' => self::get_test_option( $label_data ),
						'i18n' => self::get_i18n(),
						'nonce' => wp_create_nonce( 'woo_bg_admin_label' ),
					) );
					break;
				}
			}
		}
	}

	protected static function get_i18n() {
		return array(
			'updateShipmentStatus' => __( 'Update shipment status', 'bulgarisation-for-woocommerce' ),
			'updateLabel' => __( 'Update label', 'bulgarisation-for-woocommerce' ),
			'generateLabel' => __( 'Generate label', 'bulgarisation-for-woocommerce' ),
			'deleteLabel' => __( 'Delete label', 'bulgarisation-for-woocommerce' ),
			'label' => __( 'Label', 'bulgarisation-for-woocommerce' ), 
			'default' => __( 'Default', 'bulgarisation-for-woocommerce' ),
			'selected' => __( 'Selected', 'bulgarisation-for-woocommerce' ),
			'choose' => __( 'Choose', 'bulgarisation-for-woocommerce' ),
			'deliveryPayedBy' => __( 'Delivery is payed by', 'bulgarisation-for-woocommerce' ),
			'cd' => __( 'Cash on delivery', 'bulgarisation-for-woocommerce' ), 
			'packCount' => __( 'Pack count', 'bulgarisation-for-woocommerce' ),
			'shipmentType' => __( 'Shipment type', 'bulgarisation-for-woocommerce' ),
			'other' => __( 'Other', 'bulgarisation-for-woocommerce' ),
			'blVhEt' => __( 'bl. vh. et.', 'bulgarisation-for-woocommerce' ),
			'streetNumber' => __( 'Street number', 'bulgarisation-for-woocommerce' ),
			'streetQuarter' => __( 'Street/Quarter', 'bulgarisation-for-woocommerce' ),
			'office' => __( 'Office', 'bulgarisation-for-woocommerce' ),
			'address' => __( 'Address', 'bulgarisation-for-woocommerce' ),
			'deliveryType' => __( 'Delivery type', 'bulgarisation-for-woocommerce' ),
			'labelData' => __( 'Label data', 'bulgarisation-for-woocommerce' ),
			'buyer' => __( 'Buyer', 'bulgarisation-for-woocommerce' ),
			'sender' => __( 'Sender', 'bulgarisation-for-woocommerce' ),
			'contract' => __( 'Contract', 'bulgarisation-for-woocommerce' ),
			'weight' => __( 'Weight', 'bulgarisation-for-woocommerce' ),
			'fixedPrice' => __( 'Fixed price', 'bulgarisation-for-woocommerce' ),
			'copyLabelData' => __( 'Copy Label Data', 'bulgarisation-for-woocommerce' ),
			'copyLabelDataMessage' => __( 'You just copied the label data used for the cvc API.', 'bulgarisation-for-woocommerce' ),
			'shipmentStatus' => __( 'Shipment status', 'bulgarisation-for-woocommerce' ),
			'time' => __( 'Time:', 'bulgarisation-for-woocommerce' ),
			'event' => __( 'Event:', 'bulgarisation-for-woocommerce' ),
			'details' => __( 'Details:', 'bulgarisation-for-woocommerce' ),
			'reviewAndTest' => __( 'Review and test', 'bulgarisation-for-woocommerce' ),
			'declaredValue' => __( 'Declared value', 'bulgarisation-for-woocommerce' ),
			'description' => __( 'Description', 'bulgarisation-for-woocommerce' ),
		);
	}

	protected static function get_offices( $cookie_data ) {
		$country = ( !empty( $cookie_data['country'] ) ) ? $cookie_data['country'] : 100;
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );

		return self::$container[ Client::CVC_OFFICES ]->get_all_offices( $country_id ); 
	}

	protected static function get_streets( $cookie_data ) {
		$country = $country = ( !empty( $cookie_data['country'] ) ) ? $cookie_data['country'] : 100;
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$raw_city = sanitize_text_field( $cookie_data['city'] );
		
		if ( empty( $raw_city ) ) {
			return [];
		}

		$state_id = self::$container[ Client::CVC_CITIES ]->get_state_id( sanitize_text_field( $cookie_data['state'] ), $country_id );
		$cities_data = self::$container[ Client::CVC_CITIES ]->get_filtered_cities( $raw_city, $state_id, $country_id );

		$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		$streets = woo_bg_return_array_for_select( Address::get_streets_for_query( $city_id, $cookie_data['selectedAddress']['label'] ), 1, array( 'type' => 'streets' ) );

		$quarters = woo_bg_return_array_for_select( Address::get_quarters_for_query( $city_id, $cookie_data['selectedAddress']['label'] ), 1, array( 'type' => 'quarters' ) );

		return array_merge( $streets, $quarters );
	}

	protected static function get_test_option( $label ) {
		$option = 'no';

		if ( !empty( $label['is_observe'] ) && !empty( $label['is_test'] ) ) {
			$option = 'test';
		} else if ( !empty( $label['is_observe'] ) ) {
			$option = 'review';
		}

		return $option;
	}

	public static function delete_label() {
		woo_bg_check_admin_label_actions();

		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$shipment_status = $_REQUEST['shipmentStatus'];
		$order = wc_get_order( $order_id );
		
		$response = $container[ Client::CVC ]->api_call( $container[ Client::CVC ]::CANCEL_LABELS_ENDPOINT, array(
			'wb' => $shipment_status['wb'],
		) );

		$order->update_meta_data( 'woo_bg_cvc_shipment_status', '' );
		$order->update_meta_data( 'woo_bg_cvc_actions', '' );
		$order->save();
		
		wp_send_json_success( $response );
		wp_die();
	}

	public static function update_actions() {
		woo_bg_check_admin_label_actions();

		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$shipment_status = $_REQUEST['shipmentStatus'];

		$response = $container[ Client::CVC ]->api_call( $container[ Client::CVC ]::ACTIONS_ENDPOINT, array(
			'wb' => $shipment_status['wb'],
		), 'GET' );

		$order->update_meta_data( 'woo_bg_cvc_actions', $response['history'] );
		$order->save();

		wp_send_json_success( array(
			'actions' => $response['history'],
		) );
		wp_die();
	}

	public static function generate_label() {
		woo_bg_check_admin_label_actions();

		$order_id = $_REQUEST['orderId'];
		$label = $_REQUEST['label_data'];

		$label = self::update_sender( $label );
		$label = self::update_receiver_address( $label );
		$label = self::update_payment_by( $label, $order_id );
		$label = self::update_test_options( $label );
		$label = self::update_phone_and_names( $label );
		$label = self::update_os_value_and_cod( $label );

		$data = self::send_label_to_cvc( $label, $order_id );

		wp_send_json_success( $data );
		wp_die();
	}

	public static function generate_label_after_order_generated( $order_id ) {
		$order = wc_get_order( $order_id );
		$label = $order->get_meta( 'woo_bg_cvc_label' );

		if ( !$label ) {
			return;
		}

		$label = self::update_sender( $label );
		$label = self::update_shipment_description( $label, $order_id );

		self::send_label_to_cvc( $label, $order_id );
	}

	public static function send_label_to_cvc( $label, $order_id ) {
		$data = [];
		$order = wc_get_order( $order_id );

		$generated_data = self::generate_response( $label, $order_id );
		$response = $generated_data['response'];
		$request_body = $generated_data['request_body'];

		if ( ! $response['success'] ) {
			$data['message'] = $response['error'];
		} else {
			$request_body['wb'] = $response['wb'];
			$data['shipmentStatus'] = $response;
			$data['label'] = $request_body;

			$order->update_meta_data( 'woo_bg_cvc_label', $request_body );
			$order->update_meta_data( 'woo_bg_cvc_shipment_status', $response );
			$order->save();

			self::update_order_shipping_price( $response, $order_id );
		}

		do_action( 'woo_bg/cvc/after_send_label', $data, $order );

		return $data;
	}

	public static function update_sender( $label ) {
		$container = woo_bg()->container();
		$city = str_replace( 'cityID-', '', woo_bg_get_option( 'cvc_sender', 'city' ) );

		$sender_data = array( 
			"name" => woo_bg_get_option( 'cvc_sender', 'name' ), 
			"phone" => woo_bg_get_option( 'cvc_sender', 'phone' ), 
			"email" => woo_bg_get_option( 'cvc_sender', 'email' ), 
			"country_id" => 100, 
			"city_id" => $city,
			"zip" => $container[ Client::CVC_CITIES ]->get_city_zip_by_id( $city ),
		);

		$send_from = woo_bg_get_option( 'cvc_sender', 'send_from' );

		if ( $send_from === 'address' ) {
			$sender_data[ "custom_location_id" ] = woo_bg_get_option( 'cvc_sender', 'address' );
		} else if ( $send_from === 'office' ) {
			$sender_data[ "hub_id" ] = str_replace( 'hubID-', '', woo_bg_get_option( 'cvc_sender', 'office' ) );
		}

		$label['pickup_date'] = date_i18n( 'Y-m-d' );
		$label['sender'] = $sender_data;

		return $label;
	}

	protected static function update_receiver_address( $label ) {
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$type = $_REQUEST['type'];
		$cookie_data = $_REQUEST['cookie_data'];
		$cookie_data['type'] = $type['id'];

		$label = self::unset_rec_address_fields( $label );

		if ( $type['id'] === 'address' ) {
			if ( $_REQUEST['street']['type'] === 'streets' ) {
				$label['rec']["street_id"] = str_replace('street-', '', $_REQUEST['street']['orig_key'] ); 
				$label['rec']["num"] = $_REQUEST['streetNumber'];
			} else if ( $_REQUEST['street']['type'] === 'quarters' ) {
				$label['rec']["qt_id"] = str_replace('qtr-', '', $_REQUEST['street']['orig_key'] );
			}

			$label = self::update_rec_other_fields( $label );
		} else if ( $type['id'] === 'office' ) {
			$label['rec']["office_id"] = $_REQUEST['office']['id'];
		}

		$order->update_meta_data( 'woo_bg_cvc_cookie_data', $cookie_data );
		$order->save();

		return $label;
	}

	protected static function unset_rec_address_fields( $label ) {
		if ( isset( $label['rec'][ 'office_id' ] ) ) {
			unset( $label['rec'][ 'office_id' ] );
		}

		if ( isset( $label['rec'][ 'street' ] ) ) {
			unset( $label['rec'][ 'street' ] );
		}

		if ( isset( $label['rec'][ 'street_id' ] ) ) {
			unset( $label['rec'][ 'street_id' ] );
		}

		if ( isset( $label['rec'][ 'num' ] ) ) {
			unset( $label['rec'][ 'num' ] );
		}
		
		if ( isset( $label['rec'][ 'qt_id' ] ) ) {
			unset( $label['rec'][ 'qt_id' ] );
		}

		if ( isset( $label['rec']["block"] ) ) {
			unset( $label['rec']["block"] );
		}

		if ( isset( $label['rec']["entr"] ) ) {
			unset( $label['rec']["entr"] );
		}

		if ( isset( $label['rec']["floor"] ) ) {
			unset( $label['rec']["floor"] );
		}

		if ( isset( $label['rec']["ap"] ) ) {
			unset( $label['rec']["ap"] );
		}

		if ( isset( $label['rec']["notes"] ) ) {
			unset( $label['rec']["notes"] );
		}

		return $label;
	}

	protected static function update_rec_other_fields( $label ) {
		if ( !empty( $_REQUEST[ 'other' ] ) ) {
			$parts = explode( ' ', $_REQUEST[ 'other' ] );

			$label['rec']["block"] = $parts[0];

			if ( isset( $parts[1] ) ) {
				$label['rec']["entr"] = $parts[1];
			}

			if ( isset( $parts[2] ) ) {
				$label['rec']["floor"] = $parts[2];
			}

			if ( isset( $parts[3] ) ) {
				$label['rec']["ap"] = $parts[3];
			}

			if ( isset( $parts[4] ) ) {
				unset( $parts[0], $parts[1], $parts[2], $parts[3] );

				$label['rec']["notes"] = implode( ' ', $parts ) ;
			}
		}

		return $label;
	}

	protected static function update_payment_by( $label, $order_id ) {
		$payment_by = $_REQUEST['paymentBy'];

		if ( $payment_by['id'] === 'fixed'  ) {
			$label['payer'] = 'sender';

			self::update_order_shipping_price( '', $order_id, $label );
		} else {
			$label['payer'] = $payment_by['id'];
		}

		if ( woo_bg_get_option( 'cvc_sender', 'contract_pay' ) === 'yes' ) {
			$label['payer'] = 'contract';
		}
		
		return $label;
	}

	protected static function update_test_options( $label ) {
		$test_option = $_REQUEST['testOption']['id'];

		if ( isset( $label['is_observe'] ) ) {
			unset( $label['is_observe'] );
		}

		if ( isset( $label['is_test'] ) ) {
			unset( $label['is_test'] );
		}

		if ( $test_option == 'review' ) {
			$label['is_observe'] = 1;
		} else if ( $test_option == 'test' ) {
			$label['is_observe'] = 1;
			$label['is_test'] = 1;
		}
		
		return $label;
	}

	protected static function update_shipment_description( $label, $order_id ) {
		$force = woo_bg_get_option( 'cvc_sender', 'force_variations_in_desc' );
		$order = wc_get_order( $order_id );
		$names = [];

		foreach ( $order->get_items() as $item ) {
			$name = $item->get_name();

			if ( $force === 'yes' ) {
				if ( $attributes = $item->get_meta_data() ) {
					$name .= ' - ' .implode( ',', wp_list_pluck( $attributes, 'value' ) );
				}
			}

			$names[] = $name;
		}

		$label[ 'description' ] = implode( ', ', $names );

		return $label;
	}

	protected static function update_phone_and_names( $label ) {
		$order = wc_get_order( $_REQUEST['orderId'] );
		$phone = [];
		$name = '';

		if ( $order->get_shipping_first_name() && $order->get_shipping_last_name() ) {
			$name = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		} else {
			$name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		}

		if ( $order->get_shipping_phone() ) {
			$phone = $order->get_shipping_phone();
		} else {
			$phone = $order->get_billing_phone();
		}

		$label['rec']['name'] = $name;
		$label['rec']['phone'] = $phone;

		return $label;
	}

	protected static function update_os_value_and_cod( $label ) {
		$cookie_data = $_REQUEST['cookie_data'];
		$payment_by = $_REQUEST['paymentBy'];

		if ( isset( $label['cod_amount'] ) ) {
			if ( !$label['cod_amount'] ) {
				unset( $label['cod_amount'] );
			} else if ( 
				isset( $label['cod_amount'] ) && 
				$cookie_data['fixed_price'] && 
				$payment_by['id'] == 'fixed'
			) {
				$label['cod_amount'] += number_format( $cookie_data['fixed_price'], 2 );
				$label['cod_amount'] = number_format( $label['cod_amount'], 2 );
			}
		}

		if ( isset( $label[ 'os_value' ] ) ) {
			unset( $label[ 'os_value' ] );
		}

		if ( $_REQUEST['declaredValue'] ) {
			$label[ 'os_value' ] = $_REQUEST['declaredValue'];
		}

		return $label;
	}

	protected static function update_order_shipping_price( $response, $order_id, $request_body = [] ) {
		if ( !isset( $_REQUEST['paymentBy'] ) ) {
			return;
		}
		
		$order = wc_get_order( $order_id );
		$price = 0;

		if ( $order->get_payment_method() !== 'cod' ) { 
			return;
		}

		if ( $payment_by = $_REQUEST['paymentBy'] ) {
			$cookie_data = $_REQUEST['cookie_data'];

			if ( $payment_by['id'] == 'rec' ) {
				$price = ( wc_tax_enabled() ) ? $response['price'] : $response['price_with_vat'];
			} else if ( $payment_by['id'] == 'fixed' && $cookie_data['fixed_price'] ) {
				$request_body['cod_amount'] += $cookie_data['fixed_price'];
				$price = woo_bg_tax_based_price( $cookie_data['fixed_price'] );
			}
		}

		foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$item->set_total( $price );
			$item->calculate_taxes();
			$item->save();
		}

		$order->calculate_shipping();
		$order->calculate_totals();
		$order->save();

		if ( !empty( $request_body ) ) {
			return $request_body;
		}
	}

	protected static function generate_response( $label, $order_id ) {
		$container = woo_bg()->container();
		$order = wc_get_order( $order_id );

		$request_body = apply_filters( 'woo_bg/cvc/create_label', $label, $order );

		$response = $container[ Client::CVC ]->api_call( $container[ Client::CVC ]::CREATE_LABELS_ENDPOINT, $request_body );

		return [
			'response' => $response,
			'request_body' => $request_body,
		];
	}
}
