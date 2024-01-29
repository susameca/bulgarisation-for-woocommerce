<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\Speedy\Address;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class Speedy {
	private static $container = null;

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_woo_bg_speedy_generate_label', array( __CLASS__, 'generate_label' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_speedy_generate_label', array( __CLASS__, 'generate_label' ) );

		add_action( 'wp_ajax_woo_bg_speedy_delete_label', array( __CLASS__, 'delete_label' ) );
		add_action( 'wp_ajax_woo_bg_speedy_update_shipment_status', array( __CLASS__, 'update_shipment_status' ) );
		add_action( 'wp_ajax_woo_bg_speedy_print_labels', array( __CLASS__, 'print_labels_endpoint' ) );
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-speedy',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'speedy-admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-speedy',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'speedy-admin.css' )
		);
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_speedy', __( 'Speedy Delivery', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
				if ( $shipping['method_id'] === 'woo_bg_speedy' ) {
					echo '<div id="woo-bg--speedy-admin"></div>';

					$label_data = array();
					$cookie_data = $theorder->get_meta( 'woo_bg_speedy_cookie_data' );
					$shipment_status = $theorder->get_meta( 'woo_bg_speedy_shipment_status' );
					$operations = $theorder->get_meta( 'woo_bg_speedy_operations' );

					if ( $label = $theorder->get_meta( 'woo_bg_speedy_label' ) ) {
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
					
					wp_localize_script( 'woo-bg-js-admin', 'wooBg_speedy', array(
						'label' => $label_data,
						'shipmentStatus' => $shipment_status,
						'operations' => $operations,
						'cookie_data' => $cookie_data,
						'paymentType' => $theorder->get_payment_method(),
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
			'weight' => __( 'Weight', 'woo-bg' ),
			'fixedPrice' => __( 'Fixed price', 'woo-bg' ),
			'copyLabelData' => __( 'Copy Label Data', 'woo-bg' ),
			'copyLabelDataMessage' => __( 'You just copied the label data used for the Speedy API.', 'woo-bg' ),
			'shipmentStatus' => __( 'Shipment status', 'woo-bg' ),
			'time' => __( 'Time:', 'woo-bg' ),
			'event' => __( 'Event:', 'woo-bg' ),
			'details' => __( 'Details:', 'woo-bg' ),
			'reviewAndTest' => __( 'Review and test', 'woo-bg' ),
			'declaredValue' => __( 'Declared value', 'woo-bg' ),
			'mysticQuarter' => __( 'Street or quarter', 'woo-bg' ),
		);
	}

	protected static function get_offices( $cookie_data ) {
		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $cookie_data['city'], $cookie_data['state'] );
		$offices = ( !empty( self::$container[ Client::SPEEDY_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'] ) ) ) ? self::$container[ Client::SPEEDY_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'] )['offices'] : [];
		return $offices;
	}

	protected static function get_streets( $cookie_data ) {
		$query = ' ';

		if ( !empty( $cookie_data['selectedAddress'] ) ) {
			$query = explode(' ', $cookie_data['selectedAddress']['label'] );
			unset( $query[0] );
			$query = implode( ' ', $query );
		}

		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $cookie_data['city'], $cookie_data['state'] );
		$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		$streets = woo_bg_return_array_for_select( Address::get_streets_for_query( $city_id, $query ), 1, array( 'type' => 'streets' ) );
		$quarters = woo_bg_return_array_for_select( Address::get_quarters_for_query( $city_id, $query ), 1, array( 'type' => 'quarters' ) );

		return array_merge( $streets, $quarters );
	}

	protected static function get_test_option( $label ) {
		$option = 'no';

		if ( !empty( $label['service']['additionalServices']['obpd']['option'] ) ) {
			if ( $label['service']['additionalServices']['obpd']['option'] === 'OPEN' ) {
				$option = 'review';
			} else if ( $label['service']['additionalServices']['obpd']['option'] === 'TEST' ) {
				$option = 'test';
			}
		}

		return $option;
	}

	public static function delete_label() {
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$shipment_status = $_REQUEST['shipmentStatus'];
		$order = wc_get_order( $order_id );
		
		$response = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::DELETE_LABELS_ENDPOINT, array(
			'shipmentId' => $shipment_status['id'],
			'comment' => 'Нулиране'
		) );

		$order->update_meta_data( 'woo_bg_speedy_shipment_status', '' );
		$order->update_meta_data( 'woo_bg_speedy_operations', '' );
		$order->save();
		
		wp_send_json_success( $response );
		wp_die();
	}

	public static function update_shipment_status() {
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$shipment_status = $_REQUEST['shipmentStatus'];
		$data = array();

		$response = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::TRACK_ENDPOINT, array(
			'parcels' => [ [ 'id' => $shipment_status['id'] ] ]
		) );

		$order_shipment_status = $response['parcels'][0]['operations'];
		
		$order->update_meta_data( 'woo_bg_speedy_operations', $order_shipment_status );
		$order->save();

		wp_send_json_success( array( 'operations' => $order_shipment_status ) );
		wp_die();
	}

	public static function generate_label() {
		$order = wc_get_order( $_REQUEST['orderId'] );
		$label = $_REQUEST['label_data'];

		$label = self::update_recipient_data( $label );
		$label = self::update_payment_by( $label, $order );
		$label = self::update_services( $label );
		$label = self::update_shipment_description( $label, $order );

		$data = self::send_label_to_speedy( $label, $order );

		wp_send_json_success( $data );
		wp_die();
	}

	public static function generate_label_after_order_generated( $order_id ) {
		$order = wc_get_order( $order_id );
		$label = $order->get_meta( 'woo_bg_speedy_label' );
		if ( !$label ) {
			return;
		}

		$label = self::update_shipment_description( $label, $order );
		$label = self::update_cod( $label, $order );

		self::send_label_to_speedy( $label, $order );
	}

	public static function update_cod( $label, $order ) {
		$cookie_data = $order->get_meta( 'woo_bg_speedy_cookie_data' );

		if ( isset( $label['service']['additionalServices']['cod']['amount'] ) ) {
			if ( 
				isset( $label['service']['additionalServices']['cod']['amount'] ) && 
				$cookie_data['fixed_price']
			) {
				$label['service']['additionalServices']['cod']['amount'] += number_format( $cookie_data['fixed_price'], 2 );
				$label['service']['additionalServices']['cod']['amount'] = number_format( $label['service']['additionalServices']['cod']['amount'], 2 );
			}
		}

		return $label;
	}

	public static function send_label_to_speedy( $label, $order ) {
		$data = [];
		$order_id = $order->get_id();
		$generated_data = self::generate_response( $label, $order );
		$response = $generated_data['response'];
		$request_body = $generated_data['request_body'];

		if ( isset( $response['error'] ) ) {
			$data['message'] = $response['error']['message'];
		} else {
			$request_body['id'] = $response['id'];
			$data['shipmentStatus'] = $response;
			$data['label'] = $request_body;

			$order->update_meta_data( 'woo_bg_speedy_label', $request_body );
			$order->update_meta_data( 'woo_bg_speedy_shipment_status', $response );
			$order->save();

			self::update_order_shipping_price( $response, $order_id );
		}

		return $data;
	}

	protected static function update_recipient_data( $label ) {
		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$type = $_REQUEST['type'];
		$cookie_data = $_REQUEST['cookie_data'];
		$cookie_data['type'] = $type['id'];

		$label = self::update_phone_and_names( $label );

		unset( $label[ 'recipient' ][ 'addressLocation' ] );
		unset( $label[ 'recipient' ][ 'address' ] );
		unset( $label[ 'recipient' ][ 'pickupOfficeId' ] );

		if ( $cookie_data['type'] === 'address' ) {
			$label[ 'recipient' ][ 'addressLocation' ] = self::generate_recipient_address();
			$label[ 'recipient' ][ 'address' ] = self::generate_recipient_address();
		} else if ( $cookie_data['type'] === 'office' ) {
			$label[ 'recipient' ][ 'pickupOfficeId' ] = $_REQUEST['office']['id'];
		}

		$order->update_meta_data( 'woo_bg_speedy_cookie_data', $cookie_data );
		$order->save();

		return $label;
	}

	protected static function generate_recipient_address() {
		$container = woo_bg()->container();
		$cookie_data = $cookie_data = $_REQUEST['cookie_data'];
		$cities_data = $container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $cookie_data['city'], $cookie_data['state'] );
		$address['siteId'] = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		if ( !empty( $_REQUEST['street']['type'] ) && $_REQUEST['street']['type'] === 'streets' ) {
			$address["streetId"] = str_replace('street-', '', $_REQUEST['street']['orig_key'] ); 
			$parts = explode( ',', $_REQUEST[ 'streetNumber' ] );
			$address["streetNo"] = array_shift( $parts );

			if ( !empty( $parts ) ) {
				$address["addressNote"] = implode( ' ', $parts );
			}
		} else if ( 
			!empty( $_REQUEST['street']['type'] ) && $_REQUEST['street']['type'] === 'quarters' || 
			!empty( $_REQUEST['cookie_data']['mysticQuarter'] )
		) {
			if ( !empty( $_REQUEST['cookie_data']['mysticQuarter'] ) ) {
				$address["addressNote"] = $_REQUEST['cookie_data']['mysticQuarter'] . ' ' . $_REQUEST[ 'other' ];
			} else {
				$address["complexId"] = str_replace('qtr-', '', $_REQUEST['street']['orig_key'] );

				if ( !empty( $_REQUEST[ 'other' ] ) ) {
					$parts = explode( ' ', $_REQUEST[ 'other' ] );

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

						$address["addressNote"] = implode( ' ', $parts );
					}
				}
			}
		}

		return $address;
	}

	protected static function update_payment_by( $label, $order ) {
		unset( $label['payment'] );
		$order_id = $order->get_id();

		$payment_by = $_REQUEST['paymentBy'];
		$cookie_data = $_REQUEST['cookie_data'];

		if ( isset( $label['service']['additionalServices']['cod']['amount'] ) && $label['service']['additionalServices']['cod']['amount'] ) {
			$payment[ 'declaredValuePayer' ] = 'RECIPIENT';
			$payment[ 'packagePayer' ] = 'RECIPIENT';
		}

		if ( $payment_by['id'] === 'fixed'  ) {
			$payment[ 'courierServicePayer' ] = 'SENDER';

			self::update_order_shipping_price( $label, $order_id, $label );
		} else {
			$payment[ 'courierServicePayer' ] = $payment_by['id'];
		}

		if ( $payment[ 'courierServicePayer' ] === 'SENDER' ) {
			$payment[ 'declaredValuePayer' ] = 'SENDER';
			$payment[ 'packagePayer' ] = 'SENDER';
		}

		$label['payment'] = $payment;

		return $label;
	}

	protected static function update_services( $label ) {
		$cookie_data = $_REQUEST['cookie_data'];
		$payment_by = $_REQUEST['paymentBy'];
		if ( isset( $label['service']['additionalServices']['declaredValue'] ) ) {
			unset( $label['service']['additionalServices']['declaredValue'] );
		}

		if ( isset( $label['service']['additionalServices']['obpd'] ) ) {
			unset( $label['service']['additionalServices']['obpd'] );
		}

		if ( isset( $label['service']['additionalServices']['cod']['amount'] ) ) {
			if ( 
				isset( $label['service']['additionalServices']['cod']['amount'] ) && 
				$cookie_data['fixed_price'] && 
				$payment_by['id'] == 'fixed'
			) {
				$label['service']['additionalServices']['cod']['amount'] += number_format( $cookie_data['fixed_price'], 2 );
				$label['service']['additionalServices']['cod']['amount'] = number_format( $label['service']['additionalServices']['cod']['amount'], 2 );
			}
		}

		if ( $_REQUEST['declaredValue'] ) {
			$label['service']['additionalServices']['declaredValue'] = array(
				'amount' => $_REQUEST['declaredValue'], 
				'fragile' => true, 
				"ignoreIfNotApplicable" => true 
			);
		}

		$test_option = $_REQUEST['testOption']['id'];

		$test = '';

		if ( $test_option == 'review' ) {
			$test = 'OPEN';
		} else if ( $test_option == 'test' ) {
			$test = 'TEST';
		}

		if ( $test ) {
			$label['service']['additionalServices']['obpd'] = array(
				'option' => $test, 
				'returnShipmentServiceId' => 505, 
				'returnShipmentPayer' => 'SENDER' 
			);
		}

		if ( empty( $label['service']['additionalServices'] ) ) {
		    unset( $label['service']['additionalServices'] );
		}
			
		return $label;
	}

	protected static function update_shipment_description( $label, $order ) {
		$force = woo_bg_get_option( 'speedy', 'force_variations_in_desc' );
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
		
		$label['content']['contents'] =  mb_substr( implode( ',', $names ), 0, 100 );

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
			$phone = [ 'number' => woo_bg_format_phone( $order->get_shipping_phone() ) ];
		} else {
			$phone = [ 'number' => woo_bg_format_phone( $order->get_billing_phone() ) ];
		}

		$label['recipient']['clientName'] = $name;
		$label['recipient']['phone1'] = $phone;

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

			if ( $payment_by['id'] == 'RECIPIENT' ) {
				$price = ( wc_tax_enabled() ) ? $response['price']['amount'] : $response['price']['total'];
			} else if ( $payment_by['id'] == 'fixed' && $cookie_data['fixed_price'] ) {
				$price = woo_bg_tax_based_price( $cookie_data['fixed_price'] );
			}
		}

		$price = number_format( $price, 2 );

		foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$item->set_total( $price );
			$item->calculate_taxes();
			$item->save();
		}

		$order->calculate_shipping();
		$order->calculate_totals();
		$order->save();
	}

	protected static function generate_response( $label, $order ) {
		$container = woo_bg()->container();
		$shipment_status = $order->get_meta( 'woo_bg_speedy_shipment_status' );

		if ( $shipment_status ) {
			if ( !$label['id'] ) {
				$label['id'] = $shipment_status['id'];
			}

			$request_body = apply_filters( 'woo_bg/speedy/update_label', $label, $order );

			$response = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::UPDATE_LABELS_ENDPOINT, $request_body );
		} else {
			unset( $label['id'] );
			
			$request_body = apply_filters( 'woo_bg/speedy/create_label', $label, $order );

			$response = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::CREATE_LABELS_ENDPOINT, $request_body );
		}

		return [
			'response' => $response,
			'request_body' => $request_body,
		];
	}

	public static function print_labels_endpoint() {
		$container = woo_bg()->container();
		$labels = explode( '|', $_REQUEST['parcels'] );
		$parcels = array();

		foreach ( $labels as $label ) {
			$parcels[] = array(
				'parcel' => array(
					'id' => $label,
				),
			);
		}

		$request_body = array(
			'paperSize' => 'A6',
			'parcels' => $parcels,
		);

		$pdf = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::PRINT_LABELS_ENDPOINT, $request_body, 1 );

		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $pdf ));
		header('Content-disposition: inline; filename="' . $labels[0] . '.pdf"');

		echo $pdf;
		wp_die();
	}
}