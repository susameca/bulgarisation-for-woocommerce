<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\Pigeon\Method;
use Woo_BG\Shipping\Pigeon\Address;
use Woo_BG\Transliteration;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

use function PHPSTORM_META\map;

defined( 'ABSPATH' ) || exit;

class Pigeon {
	private static $container = null;

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_woo_bg_pigeon_generate_label', array( __CLASS__, 'generate_label' ) );
		add_action( 'wp_ajax_woo_bg_pigeon_delete_label', array( __CLASS__, 'delete_label' ) );
		add_action( 'wp_ajax_woo_bg_pigeon_update_shipment_status', array( __CLASS__, 'update_shipment_status' ) );
		add_action( 'wp_ajax_woo_bg_pigeon_print_labels', array( __CLASS__, 'print_labels_endpoint' ) );
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-pigeon',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'pigeon-admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-pigeon',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'pigeon-admin.css' )
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
				if ( $shipping['method_id'] === 'woo_bg_pigeon' ) {
					$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
					? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
					: array( 'shop_order', 'shop_subscription' );

					$screen = array_filter( $screen );

					add_meta_box( 'woo_bg_pigeon', __( 'Pigeon Express', 'bulgarisation-for-woocommerce' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
		}

		if ( !empty( $theorder->get_items( 'shipping' ) ) ) {
			foreach ( $theorder->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_pigeon' ) {
					echo '<div id="woo-bg--pigeon-admin"></div>';

					$label_data = array();
					$cookie_data = $theorder->get_meta( 'woo_bg_pigeon_cookie_data' );
					//$shipment_status = $theorder->get_meta( 'woo_bg_pigeon_shipment_status' );
					//$operations = $theorder->get_meta( 'woo_bg_pigeon_operations' );

					if ( $label = $theorder->get_meta( 'woo_bg_pigeon_label' ) ) {
						$label_data = $label;
					}

					if ( !$cookie_data ) {
						foreach ( $shipping->get_meta_data() as $meta_data ) {
							$data = $meta_data->get_data();

							if ( $data['key'] == 'woo_bg_pigeon_cookie_data' ) {
								$cookie_data = $data['value'];
							}
						}	
					}
					
					wp_localize_script( 'woo-bg-js-admin', 'wooBg_pigeon', array(
						'label' => $label_data,
						//'shipmentStatus' => $shipment_status,
						//'operations' => $operations,
						'cookie_data' => $cookie_data,
						'paymentType' => $theorder->get_payment_method(),
						'sendFrom' => self::get_send_from_data(),
						'offices' => self::get_offices( $theorder ),
						'lockers' => self::get_lockers( $theorder ),
						'streets' => self::get_streets( $cookie_data, $theorder ),
						'orderId' => $theorder->get_id(),
						'testOption' => self::get_test_option( $label_data ),
						'i18n' => self::get_i18n(),
						'nonce' => wp_create_nonce( 'woo_bg_admin_label' ),
					) );
					break;
				}
			}
		}
	}

	protected static function get_send_from_data() {
		$container = woo_bg()->container();
		$type = woo_bg_get_option( 'pigeon', 'send_from' );
		$data = [
			'type' => $type,
		];

		$data['currentOffice'] = woo_bg_get_option( 'pigeon_send_from', 'office' );
		$data['offices'] = ( woo_bg_get_option( 'pigeon_send_from', 'city' ) ) ? $container[ Client::PIGEON_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'pigeon_send_from', 'city' ) ) : [];
		
		$data['currentAddress'] = woo_bg_get_option( 'pigeon_send_from', 'address' );

		return $data;
	}

	private static function get_city_id( $order ) {
		$city_id = false;
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$city = Transliteration::latin2cyrillic( $city );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $city, $state );

		if ( $cities_data['city_key'] !== false ) {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ]['id'];
		}

		return $city_id;
	}

	protected static function get_offices( $order ) {
		$offices = [];
		$city_id = self::get_city_id( $order );

		if ( !( $city_id === false || $city_id === null ) ) {
			$offices = self::$container[ Client::PIGEON_OFFICES ]->get_formatted_offices( $city_id );
		}

		return $offices;
	}

	protected static function get_lockers( $order ) {
		$lockers = [];
		$city_id = self::get_city_id( $order );

		if ( !( $city_id === false || $city_id === null ) ) {
			$lockers = self::$container[ Client::PIGEON_LOCKERS ]->get_formatted_lockers( $city_id );
		}

		return $lockers;
	}

	protected static function get_streets( $cookie_data, $order ) {
		$streets = [];
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$city = Transliteration::latin2cyrillic( $city );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $city, $state );

		if ( $cities_data['city_key'] !== false ) {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

			$streets = woo_bg_return_array_for_select( Address::get_streets_for_query( $city_id, '' ), 1, array( 'type' => 'streets' ) );
		}

		return $streets;
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
			'locker' => __( 'Locker', 'bulgarisation-for-woocommerce' ),
			'deliveryType' => __( 'Delivery type', 'bulgarisation-for-woocommerce' ),
			'labelData' => __( 'Label data', 'bulgarisation-for-woocommerce' ),
			'buyer' => __( 'Buyer', 'bulgarisation-for-woocommerce' ),
			'sender' => __( 'Sender', 'bulgarisation-for-woocommerce' ),
			'weight' => __( 'Weight', 'bulgarisation-for-woocommerce' ),
			'fixedPrice' => __( 'Fixed price', 'bulgarisation-for-woocommerce' ),
			'copyLabelData' => __( 'Copy Label Data', 'bulgarisation-for-woocommerce' ),
			'copyLabelDataMessage' => __( 'You just copied the label data used for the Pigeon Express API.', 'bulgarisation-for-woocommerce' ),
			'shipmentStatus' => __( 'Shipment status', 'bulgarisation-for-woocommerce' ),
			'time' => __( 'Time:', 'bulgarisation-for-woocommerce' ),
			'event' => __( 'Event:', 'bulgarisation-for-woocommerce' ),
			'details' => __( 'Details:', 'bulgarisation-for-woocommerce' ),
			'reviewAndTest' => __( 'Review and test', 'bulgarisation-for-woocommerce' ),
			'declaredValue' => __( 'Declared value', 'bulgarisation-for-woocommerce' ),
			'mysticQuarter' => __( 'Street or quarter', 'bulgarisation-for-woocommerce' ),
			'description' => __( 'Description', 'bulgarisation-for-woocommerce' ),
			'sendFrom' => __('Send From', 'bulgarisation-for-woocommerce'),
			'length' => __('Length', 'bulgarisation-for-woocommerce'),
			'width' => __('Width', 'bulgarisation-for-woocommerce'),
			'height' => __('Height', 'bulgarisation-for-woocommerce'),
			'pack' => __('Pack', 'bulgarisation-for-woocommerce'),
			'addPack' => __('Add Pack', 'bulgarisation-for-woocommerce'),
			'removePack' => __('Remove Pack', 'bulgarisation-for-woocommerce'),
			'returnAtMyExpense' => __('Return at my expense', 'bulgarisation-for-woocommerce'),
			'note' => __('Note for courier', 'bulgarisation-for-woocommerce'),
		);
	}

	protected static function get_test_option( $label ) {
		return ( !empty( $label['service_codes']['shipment_test_before_payment'] ) ) ? 'yes' : 'no';
	}

	public static function message_dismiss_callback() {
		update_option( 'woo_bg_pigeon_express_message_dismiss', true );
	}

	public static function generate_label() {
		woo_bg_check_admin_label_actions();

		$order = wc_get_order( sanitize_text_field( $_REQUEST['orderId'] ) );
		$label = map_deep( $_REQUEST['label_data'], 'sanitize_text_field' );

		$label = self::update_sender( $label );
		$label = self::update_receiver_data( $label );
		$label = self::update_services( $label );
		$label = self::update_items( $label, $order );
		$label = self::update_payment_by( $label, $order );

		$data = self::send_label_to_pigeon( $label, $order );

		wp_send_json_success( $data );
		wp_die();
	}

	public static function update_sender( $label ) {
		$send_from = woo_bg_get_option( 'pigeon', 'send_from' );
		$label['pickup_type'] = $send_from;

		if ( $send_from == 'office' ) {
			$label['pickup_office_id'] = self::update_sender_office_code();
		} else {
			$label['pickup_address'] = [
				'city_id' => str_replace( 'cityID-', '', woo_bg_get_option( 'pigeon_send_from', 'city' ) ),
				'additional_info' => woo_bg_get_option( 'pigeon_send_from', 'address' ),
			];
		}

		if ( isset( $_REQUEST['send_from'] ) && isset( $_REQUEST['send_from_type'] ) ) {
			$new_send_from = map_deep( $_REQUEST['send_from'], 'sanitize_text_field' );
			$new_send_from_address = sanitize_text_field( $_REQUEST['send_from_address'] );
			$new_send_from_type = sanitize_text_field( $_REQUEST['send_from_type'] );

			if ( !empty( $new_send_from ) && !empty( $new_send_from_type ) ) {
				switch ( $new_send_from_type ) {
					case 'address':
						$label['pickup_type'] = 'address';
						$label['pickup_address'] = [
							'city_id' => str_replace( 'cityID-', '', woo_bg_get_option( 'pigeon_send_from', 'city' ) ),
							'additional_info' => $new_send_from_address,
						];

						unset( $label['pickup_office_id'] );
						break;
					case 'office':
						$label['pickup_type'] = 'office';
						$label['pickup_office_id'] = str_replace( 'officeID-', '', $new_send_from );

						unset( $label['pickup_address'] );
						break;
				}
			}	
		}

		return $label;
	}

	public static function update_receiver_data( $label ) {
		$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$order = wc_get_order( $order_id );
		$type = map_deep( $_REQUEST['type'], 'sanitize_text_field' );

		$phone = '';
		$name = '';

		if ( $order->get_shipping_first_name() && $order->get_shipping_last_name() ) {
			$name = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		} else {
			$name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		}

		if ( $order->get_shipping_phone() ) {
			$phone = woo_bg_format_phone( $order->get_shipping_phone(), $order->get_shipping_country() );
		} else {
			$phone = woo_bg_format_phone( $order->get_billing_phone(), $order->get_billing_country() );
		}

		$label['delivery_type'] = $type['id'];
		$label['receiver_name'] = $name;
		$label['receiver_phone'] = $phone;
		$label['receiver_email'] = $order->get_billing_email();

		unset( $label['delivery_address'] );
		unset( $label['delivery_office_id'] );

		if ( $label['delivery_type'] === 'address' ) {
			$label['delivery_address'] = self::generate_receiver_address( $order );
		} else if ( $label['delivery_type'] === 'office' ) {
			$label['delivery_office_id'] = absint( self::generate_receiver_office_code() );
		} else if ( $label['delivery_type'] === 'locker' ) {
			$label['delivery_office_id'] = absint( self::generate_receiver_locker_code() );
		}

		return $label;
	}

	private static function generate_receiver_address( $order) {
		$street = map_deep( $_REQUEST['street'], 'sanitize_text_field' );

		$receiver_address = [
			'city_id' => self::get_city_id( $order ),
			'street_id' => str_replace( 'street-', '', $street['orig_key'] ),
			'additional_info' => sanitize_text_field( $_REQUEST['other'] ),
		];

		return $receiver_address;
	}

	private static function generate_receiver_office_code() {
		$office = map_deep( $_REQUEST['office'], 'sanitize_text_field' );

		return str_replace( 'officeID-', '', $office['id'] );
	}

	private static function generate_receiver_locker_code() {
		$locker = map_deep( $_REQUEST['locker'], 'sanitize_text_field' );

		return str_replace( 'lockerID-', '', $locker['id'] );
	}

	private static function update_payment_by( $label, $order ) {
		$payment_by = map_deep( $_REQUEST['paymentBy'], 'sanitize_text_field' );
		$cookie_data = map_deep( $_REQUEST['cookie_data'], 'sanitize_text_field' );
		
		if ( $payment_by['id'] === 'fixed' ) {
			$label['who_pays'] = 'sender';

			if ( $order->get_payment_method() === 'cod' ) {
				$label['service_codes']['cod_amount'] += number_format( $cookie_data['fixed_price'], 2, '.', '');
			}

			self::update_order_shipping_price( $cookie_data['fixed_price'], $order );
		} else {
			$label['who_pays'] = $payment_by['id'];
		}

		return $label;
	}

	private static function update_order_shipping_price( $price, $order ) {
		foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$item->set_total( $price );
			$item->calculate_taxes();
			$item->save();
		}

		$order->calculate_shipping();
		$order->calculate_totals();
		$order->save();
	}

	private static function update_services( $label ) {
		$type = map_deep( $_REQUEST['type'], 'sanitize_text_field' );
		$declared_value = sanitize_text_field( $_REQUEST['declaredValue'] );
		$test = sanitize_text_field( $_REQUEST['testOption'] );

		$is_fragile = wc_string_to_bool( woo_bg_get_option( 'pigeon_services', 'declared_value' ) );
	
		if ( $declared_value && $is_fragile ) {
			$label['service_codes']['declared_value'] = $declared_value;
		} else {
			unset( $label['service_codes']['declared_value'] );
		}
		
		if ( woo_bg_get_option( 'pigeon_services', 'paper_return_receipt' ) === 'yes' ) {
			$label['service_codes']['paper_return_receipt'] = true;
		} else if ( isset( $label['service_codes']['paper_return_receipt'] ) ) {
			unset( $label['service_codes']['paper_return_receipt'] );
		}

		if ( woo_bg_get_option( 'pigeon_services', 'return_receipt' ) === 'yes' ) {
			$label['service_codes']['return_receipt'] = true;
		} else if ( isset( $label['service_codes']['return_receipt'] ) ) {
			unset( $label['service_codes']['return_receipt'] );
		}
		
		if ( woo_bg_get_option( 'pigeon_services', 'service_return_documents' ) === 'yes' ) {
			$label['service_codes']['service_return_documents'] = true;
		} else if ( isset( $label['service_codes']['service_return_documents'] ) ) {
			unset( $label['service_codes']['service_return_documents'] );
		}
		
		if ( woo_bg_get_option( 'pigeon_services', 'id_verification_and_document_signature' ) === 'yes' ) {
			$label['service_codes']['ID_verification_and_document_signature'] = true;
		} else if ( isset( $label['service_codes']['ID_verification_and_document_signature'] ) ) {
			unset( $label['service_codes']['ID_verification_and_document_signature'] );
		}
		
		if ( woo_bg_get_option( 'pigeon_services', 'cod_card_fee' ) === 'yes' ) {
			$label['service_codes']['cod_card_fee'] = true;
		} else if ( isset( $label['service_codes']['cod_card_fee'] ) ) {
			unset( $label['service_codes']['cod_card_fee'] );
		}
		
		if ( $type !== 'locker' ) {
			if ( $test === 'yes' ) {
				$label['service_codes']['shipment_test_before_payment'] = true;
			} else if ( isset( $label['service_codes']['shipment_test_before_payment'] ) ) {
				unset( $label['service_codes']['shipment_test_before_payment'] );
			}
		} else if ( isset( $label['service_codes']['shipment_test_before_payment'] ) ) {
			unset( $label['service_codes']['shipment_test_before_payment'] );
		}

		return $label;
	}

	private static function update_items( $label, $order ) {
		$label['inventory_items'] = array();

		foreach ( $order->get_items() as $item ) {
			$label['inventory_items'][] = array(
				'description' => $item->get_name(),
				'quantity' => $item['quantity'],
			);
		}

		return $label;
	}

	public static function send_label_to_pigeon( $label, $order ) {
		$data = [];
		$container = woo_bg()->container();
		$request_body = apply_filters( 'woo_bg/pigeon/create_label', $label, $order );
		$response = $container[ Client::PIGEON ]->api_call( $container[ Client::PIGEON ]::CREATE_LABEL_ENDPOINT, $request_body, 'POST' );
		
		if ( isset( $response['success'] ) && !$response['success'] ) {
			$errors = isset( $response['errors'] ) ? $response['errors'] : [ [ $response['message'] ] ];
			$data['message'] = wp_list_pluck( $errors, 0 );
		} else {
			$data['price'] = woo_bg_tax_based_price( $response['data']['total_price'] );
			$data['label'] = $request_body;
			$data['shipmentStatus'] = $response;

			$order->update_meta_data( 'woo_bg_pigeon_label', $request_body );
			$order->update_meta_data( 'woo_bg_pigeon_shipment_status', $response );
			$order->save();
		}

		do_action( 'woo_bg/pigeon/after_send_label', $data, $order );

		return $data;
	}
}