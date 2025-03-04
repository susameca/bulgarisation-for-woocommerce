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
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_econt', __( 'Econt Delivery', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
			'copyLabelDataMessage' => __( 'You just copied the label data used for the Econt API.', 'woo-bg' ),
			'shipmentStatus' => __( 'Shipment status', 'woo-bg' ),
			'time' => __( 'Time:', 'woo-bg' ),
			'event' => __( 'Event:', 'woo-bg' ),
			'details' => __( 'Details:', 'woo-bg' ),
			'reviewAndTest' => __( 'Review and test', 'woo-bg' ),
			'declaredValue' => __( 'Declared value', 'woo-bg' ),
			'description' => __( 'Description', 'woo-bg' ),
			'invoiceNum' => __( 'Invoice number', 'woo-bg' ),
		);
	}

	protected static function get_shipment_types() {
		return woo_bg_return_array_for_select( array(
			'PACK' => __('Pack', 'woo-bg'),
			'DOCUMENT' => __('Document', 'woo-bg'),
			'PALLET' => __('Pallet', 'woo-bg'),
			'CARGO' => __('Cargo', 'woo-bg'),
			'DOCUMENTPALLET' => __('Document-Pallet', 'woo-bg'),
		) );
	}

	protected static function get_offices( $cookie_data, $order ) {
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();

		if ( $order->get_shipping_country() ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $state ];
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $city, $state, $country );

		return self::$container[ Client::ECONT_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'], $country )['offices'];
	}

	protected static function get_streets( $cookie_data, $order ) {
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( $order->get_shipping_country() ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $state ];

		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $city, $state, $country );

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
		$order_id = $_REQUEST['orderId'];
		$shipment_status = $_REQUEST['shipmentStatus'];
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
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$shipment_status = $_REQUEST['shipmentStatus'];
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

		$order_id = $_REQUEST['orderId'];
		$label = $_REQUEST['label_data'];

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
		$label = self::update_shipment_description( $label, $order_id );
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
		$order_id = $_REQUEST['orderId'];
		$order = wc_get_order( $order_id );
		$type = $_REQUEST['type'];
		$cookie_data = $_REQUEST['cookie_data'];
		$cookie_data['type'] = $type['id'];
		$country = ( $order->get_shipping_country() ) ? $order->get_shipping_country() : $order->get_billing_country();

		unset( $label[ 'receiverOfficeCode' ] );
		unset( $label[ 'receiverAddress' ] );

		if ( $type['id'] === 'office' ) {
			$label[ 'receiverDeliveryType' ] = 'office';
			$office = $_REQUEST['office'];
			$label['receiverOfficeCode'] = $office['code'];
			$cookie_data['selectedOffice'] = $office['code'];
		} else {
			$label[ 'receiverDeliveryType' ] = 'door';
			$cookie_data['selectedAddress'] = $_REQUEST['street'];
			$cookie_data['streetNumber'] = $_REQUEST['streetNumber'];
			$cookie_data['other'] = $_REQUEST['other'];

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
		$payment_by = $_REQUEST['paymentBy'];
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
		$payment_by = $_REQUEST['testOption'];
		$label['payAfterAccept'] = false;
		$label['payAfterTest'] = false;

		if ( $payment_by['id'] == 'review' ) {
			$label['payAfterAccept'] = true;
		} else if ( $payment_by['id'] == 'test' ) {
			$label['payAfterAccept'] = true;
			$label['payAfterTest'] = true;
		}
		
		return $label;
	}

	protected static function update_shipment_type( $label ) {
		$shipment_type = $_REQUEST['shipmentType'];
		$label['shipmentType'] = strtolower( $shipment_type['id'] );
		
		return $label;
	}

	protected static function update_shipment_description( $label, $order_id ) {
		$force = woo_bg_get_option( 'econt', 'force_variations_in_desc' );
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

		$label[ 'shipmentDescription' ] = implode( ', ', $names );

		return $label;
	}

	protected static function update_phone_and_names( $label, $order_id = null ) {
		if ( !$order_id ) {
			$order_id = $_REQUEST['orderId'];
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
			$label[ 'services' ]['declaredValueAmount'] = $_REQUEST['declaredValue'];
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

		if ( $payment_by = $_REQUEST['paymentBy'] ) {
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
