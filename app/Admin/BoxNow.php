<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\BoxNow\Method;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class BoxNow {
	private static $container = null;

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_woo_bg_boxnow_generate_label', array( __CLASS__, 'generate_label' ) );
		add_action( 'wp_ajax_woo_bg_boxnow_print_label', array( __CLASS__, 'print_label_endpoint' ) );
		add_action( 'wp_ajax_woo_bg_boxnow_delete_label', array( __CLASS__, 'delete_label' ) );
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-boxnow',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'boxnow-admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-boxnow',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'boxnow-admin.css' )
		);
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_boxnow', __( 'BoxNow Delivery', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
	}

	public static function meta_box() {
		global $post, $theorder;

		self::$container = woo_bg()->container();

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		if ( !empty( $theorder->get_items( 'shipping' ) ) ) {
			foreach ( $theorder->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_boxnow' ) {
					echo '<div id="woo-bg--boxnow-admin"></div>';

					$label = self::generate_label_data( $theorder->get_id() );
					$cookie_data = $theorder->get_meta( 'woo_bg_boxnow_cookie_data' );
					$sender_data = self::generate_sender_data();
					$shipment_status = $theorder->get_meta( 'woo_bg_boxnow_shipment_status' );
					//$operations = $theorder->get_meta( 'woo_bg_boxnow_operations' );

					if ( !$cookie_data ) {
						foreach ( $shipping->get_meta_data() as $meta_data ) {
							$data = $meta_data->get_data();

							if ( $data['key'] == 'cookie_data' ) {
								$cookie_data = $data['value'];
							}
						}	
					}
					
					wp_localize_script( 'woo-bg-js-admin', 'wooBg_boxnow', array(
						'label' => $label,
						'shipmentStatus' => $shipment_status,
						//'operations' => $operations,
						'cookie_data' => $cookie_data,
						'paymentType' => $theorder->get_payment_method(),
						'destinations' => self::get_destinations(),
						'origin' => $sender_data['locationId'],
						'origins' => self::get_origins(),
						'orderId' => $theorder->get_id(),
						'allowReturn' => wc_string_to_bool( woo_bg_get_option( 'boxnow_send_from', 'allow_return' ) ),
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
			'sendTo' => __('Send to', 'woo-bg'),
			'sendFrom' => __('Send From', 'woo-bg'),
			'warehouseApm' => __('Warehouse/APM', 'woo-bg'),
			'apm' => __('APM', 'woo-bg'),
			'total' => __('Total price', 'woo-bg'),
			'allowReturn' => __('Allow returns', 'woo-bg'),
			'updateShipmentStatus' => __( 'Update shipment status', 'woo-bg' ),
			'generateLabel' => __( 'Generate label', 'woo-bg' ),
			'deleteLabel' => __( 'Delete label', 'woo-bg' ),
			'label' => __( 'Label', 'woo-bg' ), 
			'selected' => __( 'Selected', 'woo-bg' ),
			'choose' => __( 'Choose', 'woo-bg' ),
			'labelData' => __( 'Label data', 'woo-bg' ),
			'shipmentStatus' => __( 'Shipment status', 'woo-bg' ),





			'time' => __( 'Time:', 'woo-bg' ),
			'event' => __( 'Event:', 'woo-bg' ),
			'details' => __( 'Details:', 'woo-bg' ),
		);
	}

	public static function generate_label_data( $order_id ) {
		$order = wc_get_order( $order_id );
		$cookie_data = $order->get_meta( 'woo_bg_boxnow_cookie_data' );

		if ( !$cookie_data ) {
			foreach ( $shipping->get_meta_data() as $meta_data ) {
				$data = $meta_data->get_data();

				if ( $data['key'] == 'cookie_data' ) {
					$cookie_data = $data['value'];
				}
			}	
		}

		$label_data = [
			'orderNumber' => strval( mt_rand() ),
			'origin' => self::generate_sender_data(),
			'destination' => self::generate_receiver_data( $order, $cookie_data ),
			'items' => self::generate_items( $order ),
			'allowReturn' => wc_string_to_bool( woo_bg_get_option( 'boxnow_send_from', 'allow_return' ) ),
			'invoiceValue' => '0',
			'paymentMode' => 'prepaid',
			'amountToBeCollected' => '0',
		];

		if ( $order->get_payment_method() === 'cod' ) {
			$label_data[ 'paymentMode' ] = $order->get_payment_method();
			$label_data[ 'invoiceValue' ] = $order->get_total();
			$label_data[ 'amountToBeCollected' ] = $order->get_total();
		}

		return $label_data;
	}

	public static function generate_sender_data() {
		return [
			'contactName' => woo_bg_get_option( 'boxnow_send_from', 'name' ),
			'contactNumber' => self::format_phone( woo_bg_format_phone( woo_bg_get_option( 'boxnow_send_from', 'phone' ) ) ),
			'contactEmail' => woo_bg_get_option( 'boxnow_send_from', 'email' ),
			'locationId' => str_replace( [ 'originID-', 'destionationID-' ], '', woo_bg_get_option( 'boxnow_send_from', 'location' ) ),
		];
	}

	public static function generate_receiver_data( $order, $cookie_data ) {
		if ( $order->get_shipping_phone() ) {
			$phone = $order->get_shipping_phone();
		} else {
			$phone = $order->get_billing_phone();
		}

		if ( $order->get_shipping_first_name() && $order->get_shipping_last_name() ) {
			$name = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		} else {
			$name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		}

		return [
			'contactNumber' => self::format_phone( woo_bg_format_phone( $phone ) ),
			'contactEmail' => $order->get_billing_email(),
			'contactName' => $name,
			'locationId' => $cookie_data['selectedApm'],
		];
	}

	public static function format_phone( $phone ) {
		$phone = substr( $phone, 1 );
		$phone = "+359" . $phone;

		return $phone;
	}

	public static function generate_items( $order ) {
		$items = [];

		foreach ( $order->get_items() as $order_item ) {
			$item_weight = ( $order_item->get_product()->get_weight() ) ? wc_get_weight( $order_item->get_product()->get_weight(), 'kg' ) * $order_item['quantity'] : 1;
			$data = [
				'name' => $order_item->get_name() . " x " . $order_item['quantity'],
				'weight' => $item_weight,
				'value' => number_format( $order_item->get_total() + $order_item->get_total_tax(), 2, '.', '' ),
			];

			$item_sizes = Method::determine_item_size( $order_item->get_product() );

			if ( $item_sizes['item_size'] ) {
				$data[ 'compartmentSize' ] = $item_sizes['item_size'];
			}

			$items[] = $data;
		}

		return $items;
	}

	public static function get_destinations() {
		$container = woo_bg()->container();

		return $container[ Client::BOXNOW_DESTINATIONS ]->get_destinations();
	}

	public static function get_origins() {
		$container = woo_bg()->container();

		return array_values( $container[ Client::BOXNOW_ORIGINS ]->get_origins() ); 
	}

	public static function generate_label( $order_id = '' ) {
		if ( isset( $_REQUEST['action'] ) ) {
			woo_bg_check_admin_label_actions();
		}

		$order_id = ( isset( $_REQUEST['orderId'] ) ) ? $_REQUEST['orderId'] : $order_id;

		if ( !$order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$label_data = self::generate_label_data( $order->get_id() );

		if ( isset( $_REQUEST['origin'] ) ) {
			$label_data['origin']['locationId'] = $_REQUEST['origin']['id'];
		}

		if ( isset( $_REQUEST['destination'] ) ) {
			$label_data['destination']['locationId'] = $_REQUEST['destination']['id'];
		}

		if ( isset( $_REQUEST['declaredValue'] ) ) {
			if ( $_REQUEST['declaredValue'] > 0  ) {
				$total = number_format( $_REQUEST['declaredValue'], 2, '.', '' );

				$label_data[ 'paymentMode' ] = 'cod';
				$label_data[ 'invoiceValue' ] = $total;
				$label_data[ 'amountToBeCollected' ] = $total;
			} else {
				$label_data[ 'paymentMode' ] = 'prepaid';
				unset( $label_data[ 'invoiceValue' ] );
				unset( $label_data[ 'amountToBeCollected' ] );
			}
		}

		if ( isset( $_REQUEST[ 'allowReturn' ] ) ) {
			$label_data['allowReturn'] = wc_string_to_bool( $_REQUEST[ 'allowReturn' ] );
		}

		$data = self::send_label_to_boxnow( $label_data, $order );

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'woo_bg_boxnow_generate_label' ) {
			wp_send_json_success( $data );
			wp_die();
		} else {
			return $data;
		}
	}

	public static function send_label_to_boxnow( $label, $order ) {
		$data = [];
		$container = woo_bg()->container();
		$request_body = apply_filters( 'woo_bg/boxnow/create_label', $label, $order );

		$response = $container[ Client::BOXNOW ]->api_call( $container[ Client::BOXNOW ]::CREATE_LABELS_ENDPOINT, $request_body );

		if ( isset( $response['message'] ) ) {
			$data['message'] = $response['message'];
		} else {
			$data['label'] = $request_body;
			$data['shipmentStatus'] = $response;

			$order->update_meta_data( 'woo_bg_boxnow_label', $request_body );
			$order->update_meta_data( 'woo_bg_boxnow_shipment_status', $response );
			$order->save();
		}

		do_action( 'woo_bg/boxnow/after_send_label', $data, $order );

		return $data;
	}

	public static function delete_label() {
		woo_bg_check_admin_label_actions();

		$container = woo_bg()->container();
		$order_id = $_REQUEST['orderId'];
		$shipment_status = $_REQUEST['shipmentStatus'];
		$order = wc_get_order( $order_id );
		
		foreach ( $shipment_status['parcels'] as $parcel ) {
			$response = $container[ Client::BOXNOW ]->api_call( '/api/v1/parcels/' . $parcel['id'] . ":cancel", [] );
		}

		$order->update_meta_data( 'woo_bg_boxnow_shipment_status', '' );
		$order->update_meta_data( 'woo_bg_boxnow_operations', '' );
		$order->save();
		
		wp_send_json_success( $response );
		wp_die();
	}

	public static function print_label_endpoint() {
		$container = woo_bg()->container();
		$parcel = sanitize_text_field( $_REQUEST['parcel'] );

		$url = "/api/v1/parcels/" . $parcel . "/label.pdf";

		$pdf_escaped = $container[ Client::BOXNOW ]->api_call( $url, [], 'GET', true );

		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $pdf_escaped ) );
		header('Content-disposition: inline; filename="' . $parcel . '.pdf"');

		echo $pdf_escaped;

		wp_die();
	}















	

	public static function update_shipment_status() {
		woo_bg_check_admin_label_actions();

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
}