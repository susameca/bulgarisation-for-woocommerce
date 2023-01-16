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
		add_action( 'wp_ajax_nopriv_woo_bg_cvc_generate_label', array( __CLASS__, 'generate_label' ) );

		add_action( 'wp_ajax_woo_bg_cvc_delete_label', array( __CLASS__, 'delete_label' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_cvc_delete_label', array( __CLASS__, 'delete_label' ) );
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
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_cvc', __( 'CVC Delivery', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
						'cookie_data' => $cookie_data,
						'offices' => self::get_offices( $cookie_data ),
						'streets' => self::get_streets( $cookie_data ),
						'orderId' => $theorder->get_id(),
						'testsOptions' => woo_bg_return_array_for_select( woo_bg_get_shipping_tests_options() ),
						'testOption' => self::get_test_option( $label_data ),
						'i18n' => self::get_i18n(),
					) );
					break;
				}
			}
		}
	}

	protected static function get_i18n() {
		return array(
			'updateShipmentStatus' => __( 'Update shipment status', 'woo-bg' ),
			'updateLabel' => __( 'Update label', 'woo-bg' ),
			'generateLabel' => __( 'Generate label', 'woo-bg' ),
			'deleteLabel' => __( 'Delete label', 'woo-bg' ),
			'label' => __( 'Label', 'woo-bg' ), 
			'default' => __( 'Default', 'woo-bg' ),
			'selected' => __( 'Selected', 'woo-bg' ),
			'choose' => __( 'Choose', 'woo-bg' ),
			'deliveryPayedBy' => __( 'Delivery is payed by', 'woo-bg' ),
			'cd' => __( 'Cash on delivery', 'woo-bg' ), 
			'packCount' => __( 'Pack count', 'woo-bg' ),
			'shipmentType' => __( 'Shipment type', 'woo-bg' ),
			'other' => __( 'Other', 'woo-bg' ),
			'blVhEt' => __( 'bl. vh. et.', 'woo-bg' ),
			'streetNumber' => __( 'Street number', 'woo-bg' ),
			'streetQuarter' => __( 'Street/Quarter', 'woo-bg' ),
			'office' => __( 'Office', 'woo-bg' ),
			'address' => __( 'Address', 'woo-bg' ),
			'deliveryType' => __( 'Delivery type', 'woo-bg' ),
			'labelData' => __( 'Label data', 'woo-bg' ),
			'buyer' => __( 'Buyer', 'woo-bg' ),
			'sender' => __( 'Sender', 'woo-bg' ),
			'contract' => __( 'Contract', 'woo-bg' ),
			'weight' => __( 'Weight', 'woo-bg' ),
			'fixedPrice' => __( 'Fixed price', 'woo-bg' ),
			'copyLabelData' => __( 'Copy Label Data', 'woo-bg' ),
			'copyLabelDataMessage' => __( 'You just copied the label data used for the cvc API.', 'woo-bg' ),
			'shipmentStatus' => __( 'Shipment status', 'woo-bg' ),
			'time' => __( 'Time:', 'woo-bg' ),
			'event' => __( 'Event:', 'woo-bg' ),
			'details' => __( 'Details:', 'woo-bg' ),
			'reviewAndTest' => __( 'Review and test', 'woo-bg' ),
			'declaredValue' => __( 'Declared value', 'woo-bg' ),
		);
	}

	protected static function get_offices( $cookie_data ) {
		$country = sanitize_text_field( $cookie_data['country'] );
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$raw_city = sanitize_text_field( $cookie_data['city'] );
		$state_id = self::$container[ Client::CVC_CITIES ]->get_state_id( sanitize_text_field( $cookie_data['state'] ), $country_id );
		$city = self::$container[ Client::CVC_CITIES ]->search_for_city( $raw_city, $state_id, $country_id );

		return self::$container[ Client::CVC_OFFICES ]->get_offices( $city[0]['zip'] );
	}

	protected static function get_streets( $cookie_data ) {
		$country = sanitize_text_field( $cookie_data['country'] );
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$raw_city = sanitize_text_field( $cookie_data['city'] );
		$state_id = self::$container[ Client::CVC_CITIES ]->get_state_id( sanitize_text_field( $cookie_data['state'] ), $country_id );
		$cities_data = self::$container[ Client::CVC_CITIES ]->get_filtered_cities( $raw_city, $state_id, $country_id );

		$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		$streets = woo_bg_return_array_for_select( Address::get_streets_for_query( $city_id, ' ' ), 1, array( 'type' => 'streets' ) );
		$quarters = woo_bg_return_array_for_select( Address::get_quarters_for_query( $city_id, ' ' ), 1, array( 'type' => 'quarters' ) );

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
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$shipment_status = $_REQUEST['shipmentStatus'];
		$order = wc_get_order( $order_id );
		
		$response = $container[ Client::CVC ]->api_call( $container[ Client::CVC ]::CANCEL_LABELS_ENDPOINT, array(
			'wb' => $shipment_status['wb'],
		) );

		$order->update_meta_data( 'woo_bg_cvc_shipment_status', '' );
		$order->save();
		
		wp_send_json_success( $response );
		wp_die();
	}

	public static function generate_label() {
		$order_id = $_REQUEST['orderId'];
		$label = $_REQUEST['label_data'];

		$label = self::update_receiver_address( $label );
		$label = self::update_payment_by( $label, $order_id );
		$label = self::update_test_options( $label );
		$label = self::update_shipment_description( $label, $order_id );
		$label = self::update_phone_and_names( $label );
		$label = self::update_os_value( $label );

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

		return $data;
	}

	protected static function update_receiver_address( $label ) {
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$type = $_REQUEST['type'];
		$cookie_data = $_REQUEST['cookie_data'];
		$cookie_data['type'] = $type['id'];

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


		if ( $type['id'] === 'address' ) {
			$label['rec']["street"] = $_REQUEST['street']['label'] . ' ' . $_REQUEST['streetNumber'] . ' ' . $_REQUEST[ 'other' ]; 

			if ( $_REQUEST['street']['type'] === 'streets' ) {
				$label['rec']["street_id"] = str_replace('street-', '', $_REQUEST['street']['orig_key'] ); 
				$label['rec']["num"] = $_REQUEST['streetNumber'];
			} else if ( $_REQUEST['street']['type'] === 'quarters' ) {
				$label['rec']["qt_id"] = str_replace('qtr-', '', $_REQUEST['street']['orig_key'] );
			}
		} else if ( $type['id'] === 'office' ) {
			$label['rec']["office_id"] = $_REQUEST['office']['id'];
		}

		$order->update_meta_data( 'woo_bg_cvc_cookie_data', $cookie_data );
		$order->save();

		return $label;
	}

	protected static function update_payment_by( $label, $order_id ) {
		$payment_by = $_REQUEST['paymentBy'];

		if ( $payment_by['id'] === 'fixed'  ) {
			$label['payer'] = 'sender';

			$label = self::update_order_shipping_price( $label, $order_id, $label );
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

	protected static function update_os_value( $label ) {
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

		if ( $payment_by = $_REQUEST['paymentBy'] ) {
			$cookie_data = $_REQUEST['cookie_data'];

			if ( $payment_by['id'] == 'rec' ) {
				$price = $response['price_with_vat'];
			} else if ( $payment_by['id'] == 'fixed' ) {
				$price = $cookie_data['fixed_price'];
				$request_body['cod_amount'] += $cookie_data['fixed_price'];
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
