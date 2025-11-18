<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\Econt\Address;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class Econt {
	private static $container = null;

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_woo_bg_econt_generate_label', array( __CLASS__, 'generate_label' ) );
		add_action( 'wp_ajax_woo_bg_econt_delete_label', array( __CLASS__, 'delete_label' ) );
		add_action( 'wp_ajax_woo_bg_econt_update_shipment_status', array( __CLASS__, 'update_shipment_status' ) );
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-econt',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'econt-admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-econt',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'econt-admin.css' )
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
				if ( $shipping['method_id'] === 'woo_bg_econt' ) {
					$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
					? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
					: array( 'shop_order', 'shop_subscription' );

					$screen = array_filter( $screen );

					add_meta_box( 'woo_bg_econt', __( 'Econt Delivery', 'bulgarisation-for-woocommerce' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
				if ( $shipping['method_id'] === 'woo_bg_econt' ) {
					echo '<div id="woo-bg--econt-admin"></div>';

					$label_data = array();
					$cookie_data = $theorder->get_meta( 'woo_bg_econt_cookie_data' );
					$shipment_status = $theorder->get_meta( 'woo_bg_econt_shipment_status' );

					if ( $label = $theorder->get_meta( 'woo_bg_econt_label' ) ) {
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
					
					wp_localize_script( 'woo-bg-js-admin', 'wooBg_econt', array(
						'useInvoiceNumber' => self::use_invoice_number(),
						'invoiceNumber' => self::get_invoice_number( $theorder->get_id() ),
						'label' => $label_data['label'],
						'sendFrom' => self::get_send_from_data(),
						'shipmentStatus' => $shipment_status,
						'cookie_data' => $cookie_data,
						'paymentType' => $theorder->get_payment_method(),
						'shipmentTypes' => self::get_shipment_types(),
						'offices' => self::get_offices( $cookie_data, $theorder ),
						'streets' => self::get_streets( $cookie_data, $theorder ),
						'orderId' => $theorder->get_id(),
						'testsOptions' => woo_bg_return_array_for_select( woo_bg_get_shipping_tests_options() ),
						'testOption' => self::get_test_option( $label_data['label'] ),
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
			'weight' => __( 'Weight', 'bulgarisation-for-woocommerce' ),
			'fixedPrice' => __( 'Fixed price', 'bulgarisation-for-woocommerce' ),
			'copyLabelData' => __( 'Copy Label Data', 'bulgarisation-for-woocommerce' ),
			'copyLabelDataMessage' => __( 'You just copied the label data used for the Econt API.', 'bulgarisation-for-woocommerce' ),
			'shipmentStatus' => __( 'Shipment status', 'bulgarisation-for-woocommerce' ),
			'time' => __( 'Time:', 'bulgarisation-for-woocommerce' ),
			'event' => __( 'Event:', 'bulgarisation-for-woocommerce' ),
			'details' => __( 'Details:', 'bulgarisation-for-woocommerce' ),
			'reviewAndTest' => __( 'Review and test', 'bulgarisation-for-woocommerce' ),
			'declaredValue' => __( 'Declared value', 'bulgarisation-for-woocommerce' ),
			'description' => __( 'Description', 'bulgarisation-for-woocommerce' ),
			'invoiceNum' => __( 'Invoice number', 'bulgarisation-for-woocommerce' ),
			'partialDelivery' => __( 'Partial delivery', 'bulgarisation-for-woocommerce' ),
			'sendFrom' => __('Send From', 'bulgarisation-for-woocommerce'),
			'length' => __('Length', 'bulgarisation-for-woocommerce'),
			'width' => __('Width', 'bulgarisation-for-woocommerce'),
			'height' => __('Height', 'bulgarisation-for-woocommerce'),
		);
	}

	protected static function get_shipment_types() {
		return woo_bg_return_array_for_select( array(
			'PACK' => __('Pack', 'bulgarisation-for-woocommerce'),
			'DOCUMENT' => __('Document', 'bulgarisation-for-woocommerce'),
			'PALLET' => __('Pallet', 'bulgarisation-for-woocommerce'),
			'CARGO' => __('Cargo', 'bulgarisation-for-woocommerce'),
			'DOCUMENTPALLET' => __('Document-Pallet', 'bulgarisation-for-woocommerce'),
		) );
	}

	protected static function get_send_from_data() {
		$container = woo_bg()->container();
		$type = woo_bg_get_option( 'econt', 'send_from' );
		$data = [
			'type' => $type,
		];

		$data['currentOffice'] = woo_bg_get_option( 'econt_send_from', 'office' );
		$data['offices'] = ( woo_bg_get_option( 'econt_send_from', 'office_city' ) ) ? self::$container[ Client::ECONT_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'econt_send_from', 'office_city' ) ) : [];

		if ( !empty( $data['offices'] ) ) {
			$data['offices'] = array_merge( $data['offices']['shops'], $data['offices']['aps'] );
		}
		
		$data['currentAddress'] = woo_bg_get_option( 'econt_send_from', 'address' );
		$data['addresses'] = $container[ Client::ECONT_PROFILE ]->get_formatted_addresses();


		return $data;
	}

	protected static function get_offices( $cookie_data, $order ) {
		$offices = [];
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();

		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $state ];
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $city, $state, $country );

		if ( $cities_data['city_key'] !== false ) {
			$offices = self::$container[ Client::ECONT_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'], $country );
			$offices = ( isset( $offices['offices'] ) ) ? $offices['offices'] : [];
		}

		return $offices;
	}

	protected static function get_streets( $cookie_data, $order ) {
		$streets = [];
		$quarters = [];
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $state ];

		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $city, $state, $country );

		if ( $cities_data['city_key'] !== false ) {
			$streets = woo_bg_return_array_for_select( 
				Address::get_streets_for_query( '', $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type' => 'streets' ) 
			);

			$quarters = woo_bg_return_array_for_select( 
				Address::get_quarters_for_query( '', $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type' => 'quarters' ) 
			);
		}

		return array_merge( $streets, $quarters );
	}

	protected static function get_test_option( $label ) {
		$option = 'no';

		if ( !empty( $label['payAfterAccept'] ) && !empty( $label['payAfterTest'] ) ) {
			$option = 'test';
		} else if ( !empty( $label['payAfterAccept'] ) ) {
			$option = 'review';
		}

		return $option;
	}

	public static function delete_label() {
		woo_bg_check_admin_label_actions();

		$container = woo_bg()->container();
		$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$shipment_status = map_deep( $_REQUEST['shipmentStatus'], 'sanitize_text_field' );
		$order = wc_get_order( $order_id );

		if ( isset( $shipment_status['label']['shipmentNumber'] ) ) {
			$response = $container[ Client::ECONT ]->api_call( $container[ Client::ECONT ]::DELETE_LABELS_ENDPOINT, array(
				'shipmentNumbers' => [ $shipment_status['label']['shipmentNumber'] ]
			) );

			$order->update_meta_data( 'woo_bg_econt_shipment_status', '' );
			$order->save();
		} else {
			$response = [ 'message' => 'Няма намерена товарителница' ];
		}
		
		wp_send_json_success( $response );
		wp_die();
	}

	public static function update_shipment_status() {
		woo_bg_check_admin_label_actions();
		
		$container = woo_bg()->container();
		$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$order = wc_get_order( $order_id );
		$shipment_status = map_deep( $_REQUEST['shipmentStatus'], 'sanitize_text_field' );
		$data = array();
		$order_shipment_status = $order->get_meta( 'woo_bg_econt_shipment_status' );

		$response = $container[ Client::ECONT ]->api_call( $container[ Client::ECONT ]::SHIPMENT_STATUS_ENDPOINT, array(
			'shipmentNumbers' => [ $shipment_status['label']['shipmentNumber'] ]
		) );

		$status = array_shift( $response );
		$status = array_shift( $status );
		$data['shipmentStatus'] = $status['status'];
		$order_shipment_status['label'] = $data['shipmentStatus'];
		
		$order->update_meta_data( 'woo_bg_econt_shipment_status', $order_shipment_status );
		$order->save();

		wp_send_json_success( $data );
		wp_die();
	}

	public static function generate_label() {
		woo_bg_check_admin_label_actions();

		$order_id = $order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$label = map_deep( $_REQUEST['label_data'], 'sanitize_text_field' );

		$label = self::update_sender( $label );
		$label = self::update_receiver_address( $label );
		$label = self::update_label_pay_options( $label, $order_id );
		$label = self::update_payment_by( $label, $order_id );
		$label = self::update_test_options( $label );
		$label = self::update_shipment_type( $label );
		$label = self::update_phone_and_names( $label );
		$label = self::update_os_value( $label );

		$data = self::send_label_to_econt( $label, $order_id );

		wp_send_json_success( $data );
		wp_die();
	}

	public static function generate_label_after_order_generated( $order_id ) {
		$order = wc_get_order( $order_id );
		$label = $order->get_meta( 'woo_bg_econt_label' );

		if ( !$label ) {
			return;
		}

		$label = $label['label'];
		$label = self::update_sender( $label );
		$label = self::update_label_pay_options( $label, $order_id );
		$label = self::update_phone_and_names( $label, $order_id );

		self::send_label_to_econt( $label, $order_id );
	}

	public static function send_label_to_econt( $label, $order_id ) {
		$data = [];
		$order = wc_get_order( $order_id );

		$generated_data = self::generate_response( $label, $order_id );
		$response = $generated_data['response'];
		$request_body = $generated_data['request_body'];

		if ( 
			isset( $response['type'] ) && 
			( strpos( $response['type'], 'ExInvalid' ) !== false || strpos( $response['type'], 'ExException' ) !== false )
		) {
			$errors = woo_bg()->container()[ Client::ECONT ]::add_error_message( $response );

			$data['message'] = implode('', $errors );
		} else if ( isset( $response['label'] ) ) {
			$request_body['label']['shipmentNumber'] = $response['label']['shipmentNumber'];
			$data['shipmentStatus'] = $response;
			$data['label'] = $request_body['label'];

			$order->update_meta_data( 'woo_bg_econt_label', $request_body );
			$order->update_meta_data( 'woo_bg_econt_shipment_status', $response );
			$order->save();

			self::update_order_shipping_price( $response, $order_id, $label );
		}

		do_action( 'woo_bg/econt/after_send_label', $data, $order );

		return $data;
	}

	public static function update_sender( $label ) {
		$container = woo_bg()->container();

		$label['senderClient'] = $container[ Client::ECONT_PROFILE ]->get_profile_data()['client'];
		$label['senderAgent'] = array(
			'name' => woo_bg_get_option( 'econt', 'name' ),
			'phones' => [ woo_bg_get_option( 'econt', 'phone' ) ],
		);
		$label['senderAddress'] = self::generate_sender_address();
		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $send_from == 'office' ) {
			$office = woo_bg_get_option( 'econt_send_from', 'office' );
			$label['senderOfficeCode'] = str_replace( 'officeID-', '', $office );
		}

		$new_send_from = map_deep( $_REQUEST['send_from'], 'sanitize_text_field' );
		$new_send_from_type = sanitize_text_field( $_REQUEST['send_from_type'] );

		if ( !empty( $new_send_from ) && !empty( $new_send_from_type ) ) {
			switch ( $new_send_from_type ) {
				case 'address':
					$profile_addresses = $container[ Client::ECONT_PROFILE ]->get_profile_data()['addresses'];

					$label['senderAddress'] = $profile_addresses[ $new_send_from ];
					unset( $label['senderOfficeCode'] );
					break;
				case 'office':
					$label['senderOfficeCode'] = str_replace( 'officeID-', '', $new_send_from );
					break;
			}
		}

		return $label;
	}

	public static function generate_sender_address() {
		$container = woo_bg()->container();
		$address = '';
		$id = woo_bg_get_option( 'econt_send_from', 'address' );
		$send_from = woo_bg_get_option( 'econt', 'send_from' );

		if ( $id !== '' && $send_from === 'address' ) {
			$profile_addresses = $container[ Client::ECONT_PROFILE ]->get_profile_data()['addresses'];

			$address = $profile_addresses[ $id ];
		}

		return $address;
	}

	protected static function update_receiver_address( $label ) {
		$container = woo_bg()->container();
		$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$order = wc_get_order( $order_id );
		$type = map_deep( $_REQUEST['type'], 'sanitize_text_field' );
		$cookie_data = map_deep( $_REQUEST['cookie_data'], 'sanitize_text_field' );
		$cookie_data['type'] = $type['id'];
		$country = ( $order->get_shipping_country() ) ? $order->get_shipping_country() : $order->get_billing_country();

		unset( $label[ 'receiverOfficeCode' ] );
		unset( $label[ 'receiverAddress' ] );

		if ( $type['id'] === 'office' ) {
			$label[ 'receiverDeliveryType' ] = 'office';
			$office = map_deep( $_REQUEST['office'], 'sanitize_text_field' );
			$label['receiverOfficeCode'] = $office['code'];
			$cookie_data['selectedOffice'] = $office['code'];
		} else {
			$label[ 'receiverDeliveryType' ] = 'door';
			$cookie_data['selectedAddress'] = map_deep( $_REQUEST['street'], 'sanitize_text_field' );
			$cookie_data['streetNumber'] = sanitize_text_field( $_REQUEST['streetNumber'] );
			$cookie_data['other'] = sanitize_text_field( $_REQUEST['other'] );

			$states = $container[ Client::ECONT_CITIES ]->get_regions( $country );
			$state = $states[ $cookie_data['state'] ];

			$cities_data = $container[ Client::ECONT_CITIES ]->get_filtered_cities( $cookie_data['city'], $state, $country );
			$cities = $cities_data['cities'];
			$city_key = $cities_data['city_key'];

			$type = ( !empty( $cookie_data['selectedAddress']['type'] ) ) ? $cookie_data['selectedAddress']['type'] :'';

			$receiver_address = array(
				'city' => $cities[ $city_key ],
			);
			
			unset( $receiver_address['city']['servingOffices'] );

			if ( $type === 'streets' ) {
				$receiver_address['street'] = $cookie_data['selectedAddress']['label'];
				$receiver_address['num'] = $cookie_data['streetNumber'];
				if ( $cookie_data['other'] ) {
					$receiver_address['other'] = $cookie_data['other'];
				}
			} else if ( $type === 'quarters' ) {
				$receiver_address['quarter'] = $cookie_data['selectedAddress']['label'];
				$receiver_address['other'] = $cookie_data['other'] . " " . $cookie_data[ 'otherField' ];
			}

			$label['receiverAddress'] = $receiver_address;
		}

		$order->update_meta_data( 'woo_bg_econt_cookie_data', $cookie_data );
		$order->save();

		return $label;
	}

	protected static function update_label_pay_options( $label, $order_id ) {
		$order = wc_get_order( $order_id );
		$cookie_data = $order->get_meta( 'woo_bg_econt_cookie_data' );
		
		unset( $label['services']['cdPayOptionsTemplate'] );
		unset( $label['packingListType'] );
		unset( $label['packingList'] );
		unset( $label['instructions'] );
		
		if ( $cookie_data['payment'] === 'cod' ) {			
			$cd_pay_option = woo_bg_get_option( 'econt', 'pay_options' );

			if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
				$packing_list_or_invoice = woo_bg_get_option( 'econt', 'invoice_or_packing_list' );
				$label['services']['cdPayOptionsTemplate'] = $cd_pay_option;

				if ( $packing_list_or_invoice === 'packing_list' ) {
					unset( $label['services']['invoiceNum'] );
					$label["packingListType"] = 'digital';
					$label["packingList"] = [];
					
					foreach ( $order->get_items() as $key => $item ) {
						if ( $item->get_total() <= 0 ) {
							continue;
						}
						/*
						/ Count is added to the name for a reason.
						/ When you have discount with more than 1 quantity, the label cannot be generated because of differences in the cents.
						*/
						
						$item_weight = ( $item->get_product()->get_weight() ) ? wc_get_weight( $item->get_product()->get_weight(), 'kg' ) * $item['quantity'] : 0.100;

						$label["packingList"][] = [
							'inventoryNum' => $key,
							'description' => $item->get_name() . " x " . $item->get_quantity(),
							'weight' => number_format( $item_weight, 3 ),
							'count' => 1,
							'price' => number_format( $item->get_total() + $item->get_total_tax(), 2, '.', '' ),
						];
					}

					if ( !empty( $order->get_items( 'fee' ) ) && apply_filters('woo_bg/shipping/package_total_includes_fees', true ) ) {
						foreach ( $order->get_items( 'fee' ) as $key => $item ) {
							if ( $item->get_total() <= 0 ) {
								continue;
							}

							$key++;
							
							$rate = woo_bg_get_order_item_vat_rate( $item, $order );

							$label["packingList"][] = [
								'inventoryNum' => $key,
								'description' => $item->get_name() . " x " . $item->get_quantity(),
								'weight' => 0.05,
								'count' => 1,
								'price' => number_format( $item->get_total() + $item->get_total_tax(), 2, '.', '' ),
							];
						}
					}
				} else if ( ( empty( $packing_list_or_invoice ) || $packing_list_or_invoice === 'invoice' ) && empty( $label['services']['invoiceNum'] ) ) {
					$label['services']['invoiceNum'] = self::get_invoice_number( $order_id );
				}
			}
		}
		
		return $label;
	}

	public static function get_invoice_number( $order_id ) {
		$order = wc_get_order( $order_id );
		$cookie_data = $order->get_meta( 'woo_bg_econt_cookie_data' );
		$invoice_num = '';

		if ( !empty( $cookie_data['payment'] ) && $cookie_data['payment'] === 'cod' ) {
			$cd_pay_option = woo_bg_get_option( 'econt', 'pay_options' );

			if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
				$packing_list_or_invoice = woo_bg_get_option( 'econt', 'invoice_or_packing_list' );

				if ( self::use_invoice_number() ) {
					$cd_pay_options = woo_bg()->container()[ Client::ECONT_PROFILE ]->get_profile_data()['cdPayOptions'];

					foreach ( $cd_pay_options as $option ) {
						if ( $option['num'] === $cd_pay_option && $option['method'] != 'office' ) {
							$order = wc_get_order( $order_id );
							$document_number = $order->get_meta( 'woo_bg_order_number' );

							if ( !$document_number ) {
								$document_number = str_pad( $order->get_id(), 10, '0', STR_PAD_LEFT );
							}

							$invoice_num = $document_number . '/' . gmdate( 'd.m.y', strtotime( $order->get_date_created() ) );
						}
					}
				}
			}
		}

		return $invoice_num;
	}

	public static function use_invoice_number() {
		$cd_pay_option = woo_bg_get_option( 'econt', 'pay_options' );
		$use = false;

		if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
			$packing_list_or_invoice = woo_bg_get_option( 'econt', 'invoice_or_packing_list' );

			$use = ( !$packing_list_or_invoice || $packing_list_or_invoice === 'invoice' );
		}

		return $use;
	}

	protected static function update_payment_by( $label, $order_id ) {
		$payment_by = map_deep( $_REQUEST['paymentBy'], 'sanitize_text_field' );
		$fixed_price = ( isset( $label['paymentReceiverAmount'] ) ) ? $label['paymentReceiverAmount'] : 0;
		$sender_method = woo_bg()->container()[ Client::ECONT_PROFILE ]->get_sender_payment_method();

		unset( 
			$label['paymentReceiverMethod'],
			$label['paymentSenderMethod'],
			$label['paymentReceiverAmount'],
		);

		if ( $payment_by['id'] == 'buyer' ) {
			$label['paymentReceiverMethod'] = 'cash';
		} elseif ( $payment_by['id'] == 'sender' ) {
			$label['paymentSenderMethod'] = $sender_method;
		} elseif ( $payment_by['id'] == 'fixed' ) {
			$label['paymentSenderMethod'] = $sender_method;
			$label['paymentReceiverMethod'] = 'cash';
			$label['paymentReceiverAmount'] = $fixed_price;

			self::update_order_shipping_price( $label, $order_id, $label );
		}
		
		return $label;
	}

	protected static function update_test_options( $label ) {
		$payment_by = map_deep( $_REQUEST['testOption'], 'sanitize_text_field' );
		$label['payAfterAccept'] = false;
		$label['payAfterTest'] = false;

		if ( isset( $label['partialDelivery'] ) ) {
			unset( $label['partialDelivery'] );
		}

		if ( $payment_by['id'] == 'review' ) {
			$label['payAfterAccept'] = true;
		} else if ( $payment_by['id'] == 'test' ) {
			$label['payAfterAccept'] = true;
			$label['payAfterTest'] = true;

			if ( wc_string_to_bool( sanitize_text_field( $_REQUEST['partialDelivery'] ) ) ) {
				$label['partialDelivery'] = true;
			}

		}
		
		return $label;
	}

	protected static function update_shipment_type( $label ) {
		$shipment_type = map_deep( $_REQUEST['shipmentType'], 'sanitize_text_field' );
		$label['shipmentType'] = strtolower( $shipment_type['id'] );
		
		return $label;
	}

	protected static function update_phone_and_names( $label, $order_id = null ) {
		if ( !$order_id ) {
			$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		}

		if ( !$order_id ) {
			return $label;
		}

		$order = wc_get_order( $order_id );
		$phone = [];
		$name = '';

		unset( $label['receiverClient'] );
		unset( $label['receiverAgent'] );

		if ( $order->get_shipping_first_name() && $order->get_shipping_last_name() ) {
			$name = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		} else {
			$name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		}

		if ( $order->get_shipping_phone() ) {
			$phone = [ $order->get_shipping_phone() ];
		} else {
			$phone = [ $order->get_billing_phone() ];
		}
		
		$label['receiverClient'] = array();
		$label['receiverClient']['name'] = $name;
		$label['receiverClient']['email'] = $order->get_billing_email();
		$label['receiverClient']['phones'] = $phone;
		$label['receiverAgent'] = $label['receiverClient'];
		
		if ( $order->get_meta( '_billing_to_company' ) ) {
			$label['receiverClient']['juridicalEntity'] = true;
			$label['receiverClient']['molName'] = $order->get_meta( '_billing_company_mol' );
			$label['receiverClient']['name'] = $order->get_billing_company();
			
			if ( $order->get_meta( '_billing_vat_number' ) ) {
				$label['receiverClient']['ein'] = substr( $order->get_meta( '_billing_vat_number' ), 2 );
				$label['receiverClient']['ddsEin'] = $label['receiverClient']['ein'];
				$label['receiverClient']['ddsEinPrefix'] = str_replace( $label['receiverClient']['ein'], '', $order->get_meta( '_billing_vat_number' ) );
			}
		}

		return $label;
	}

	protected static function update_os_value( $label ) {
		if ( isset( $label[ 'services' ]['declaredValueAmount'] ) ) {
			unset( $label[ 'services' ]['declaredValueAmount'] );
			unset( $label[ 'services' ]['declaredValueCurrency'] );
		}

		if ( $_REQUEST['declaredValue'] ) {
			$label[ 'services' ]['declaredValueAmount'] = sanitize_text_field( $_REQUEST['declaredValue'] );
			$label[ 'services' ]['declaredValueCurrency'] = 'BGN';
		}

		return $label;
	}

	protected static function update_order_shipping_price( $response, $order_id, $request_body = [] ) {
		if ( !isset( $_REQUEST['paymentBy'] ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$price = 0;
		$cookie_data = $order->get_meta( 'woo_bg_econt_cookie_data' );

		if ( $order->get_payment_method() !== 'cod' ) { 
			return;
		}

		$payment_by = map_deep( $_REQUEST['paymentBy'], 'sanitize_text_field' );

		if ( !empty( $payment_by ) ) {
			if ( $payment_by['id'] == 'buyer' ) {
				$price = woo_bg_tax_based_price( $response['label']['receiverDueAmount'] );
			} else if ( $payment_by['id'] == 'fixed' && !empty( $request_body['paymentReceiverAmount'] ) ) {
				$price = woo_bg_tax_based_price( $request_body['paymentReceiverAmount'] );
			}
		}

		$price = apply_filters( 'woo_bg/admin/econt/price_for_update', $price, $order, $_REQUEST, $response, $request_body );

		foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$item->set_total( $price );
			$item->calculate_taxes();
			$item->save();
		}

		$order->calculate_shipping();
		$order->calculate_totals();
		$order->save();
	}

	protected static function generate_response( $label, $order_id ) {
		$container = woo_bg()->container();
		$order = wc_get_order( $order_id );
		$shipment_status = $order->get_meta( 'woo_bg_econt_shipment_status' );

		$label['holidayDeliveryDay'] = 'workday';

		if ( $shipment_status ) {
			if ( !$label['shipmentNumber'] ) {
				$label['shipmentNumber'] = $shipment_status['label']['shipmentNumber'];
			}

			$request_body = apply_filters( 'woo_bg/econt/update_label', array(
				'label' => $label,
			), $order );

			$response = $container[ Client::ECONT ]->api_call( $container[ Client::ECONT ]::UPDATE_LABELS_ENDPOINT, $request_body );
		} else {
			unset( $label['shipmentNumber'] );
			
			$request_body = apply_filters( 'woo_bg/econt/create_label', array(
				'label' => $label,
				'mode' => 'create',
			), $order );

			$response = $container[ Client::ECONT ]->api_call( $container[ Client::ECONT ]::LABELS_ENDPOINT, $request_body );
		}

		return [
			'response' => $response,
			'request_body' => $request_body,
		];
	}
}
