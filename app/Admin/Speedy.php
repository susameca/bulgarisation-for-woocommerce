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
				if ( $shipping['method_id'] === 'woo_bg_speedy' ) {
					$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
					? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
					: array( 'shop_order', 'shop_subscription' );

					$screen = array_filter( $screen );

					add_meta_box( 'woo_bg_speedy', __( 'Speedy Delivery', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
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
						'offices' => self::get_offices( $theorder ),
						'streets' => self::get_streets( $cookie_data, $theorder ),
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
			'description' => __( 'Description', 'woo-bg' ),
			'a4WithCopy' => __( 'A4 with copy on same page', 'woo-bg' ),
			'a4OnSingle' => __( 'A4 with copy on single page', 'woo-bg' ),
		);
	}

	protected static function get_offices( $order ) {
		$offices = [];
		$city_id = false;
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$country_id = self::$container[ Client::SPEEDY_COUNTRIES ]->get_country_id( $country );

		if ( $country_id === '300' ) {
			$city_id = 0;
		} else {
			$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $city, $state, $country_id );

			if ( $cities_data['city_key'] !== false ) {
				$city_id = $cities_data['cities'][ $cities_data['city_key'] ]['id'];
			}
		}

		if ( !( $city_id === false || $city_id === null ) ) {
			$offices = self::$container[ Client::SPEEDY_OFFICES ]->get_offices( $city_id );
			$offices = ( isset( $offices['offices'] ) ) ? $offices['offices'] : [];
		}

		return $offices;
	}

	protected static function get_streets( $cookie_data, $order ) {
		$streets = [];
		$quarters = [];
		$query = ' ';
		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$city = $order->get_billing_city();
		
		if ( woo_bg_is_different_shipping_address( $order ) ) {
			$country = $order->get_shipping_country();
			$state = $order->get_shipping_state();
			$city = $order->get_shipping_city();
		}

		$country_id = self::$container[ Client::SPEEDY_COUNTRIES ]->get_country_id( $country );

		if ( !empty( $cookie_data['selectedAddress'] ) ) {
			$query = explode(' ', $cookie_data['selectedAddress']['label'] );
			unset( $query[0] );
			$query = implode( ' ', $query );
		}

		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $city, $state, $country_id );

		if ( $cities_data['city_key'] !== false ) {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

			$streets = woo_bg_return_array_for_select( Address::get_streets_for_query( $city_id, $query ), 1, array( 'type' => 'streets' ) );
			$quarters = woo_bg_return_array_for_select( Address::get_quarters_for_query( $city_id, $query ), 1, array( 'type' => 'quarters' ) );
		}

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
		woo_bg_check_admin_label_actions();

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

	public static function generate_label() {
		woo_bg_check_admin_label_actions();

		$order = wc_get_order( $_REQUEST['orderId'] );
		$label = $_REQUEST['label_data'];

		$label = self::update_sender( $label );
		$label = self::update_recipient_data( $label );
		$label = self::update_payment_by( $label, $order );
		$label = self::update_services( $label, $order );
		$label = self::update_fiscal_items( $label, $order );

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

		$label = self::update_sender( $label );
		$label = self::update_cod( $label, $order );

		self::send_label_to_speedy( $label, $order );
	}

	public static function update_sender( $label ) {
		$container = woo_bg()->container();
		$send_from = woo_bg_get_option( 'speedy', 'send_from' );
		$sender = array(
			'clientId' => $container[ Client::SPEEDY_PROFILE ]->get_profile_data()['clientId'],
			'contactName' => woo_bg_get_option( 'speedy', 'name' ),
			'phone1' => array(
				'number' => woo_bg_get_option( 'speedy', 'phone' ),
			),
		);

		if ( $send_from === 'office' ) {
			$sender['dropoffOfficeId'] = str_replace( 'officeID-', '', woo_bg_get_option( 'speedy_send_from', 'office' ) );
		}

		$label['sender'] = $sender;

		return $label;
	}

	public static function update_fiscal_items( $label, $order ) {
		$cookie_data = $order->get_meta( 'woo_bg_speedy_cookie_data' );

		if ( isset( $label['service']['additionalServices']['cod']['fiscalReceiptItems'] ) && wc_string_to_bool( woo_bg_get_option( 'speedy', 'kb' ) ) && wc_tax_enabled() ) {
			$label['service']['additionalServices']['cod']['fiscalReceiptItems'] = array();

			foreach ( $order->get_items() as $item ) {
				$rate = woo_bg_get_order_item_vat_rate( $item, $order );

				$label['service']['additionalServices']['cod']['fiscalReceiptItems'][] = [
					'description' => mb_substr( $item->get_name(), 0, 50 ),
					'vatGroup' => woo_bg_get_vat_group_from_rate( $rate ),
					'amount' => number_format( $item->get_total(), 2, '.', '' ),
					'amountWithVat' => number_format( $item->get_total() + $item->get_total_tax(), 2, '.', '' ),
				];
			}

			if ( !empty( $cookie_data['fixed_price'] ) ) {
				$label['service']['additionalServices']['cod']['fiscalReceiptItems'][] = [
					'description' => mb_substr( 'Доставка', 0, 50 ),
					'vatGroup' => woo_bg_get_vat_group_from_rate( 20 ),
					'amount' => woo_bg_tax_based_price( $cookie_data['fixed_price'] ),
					'amountWithVat' => number_format( $cookie_data['fixed_price'], 2, '.', '' ),
				];
			}
		}

		return $label;
	}

	public static function update_cod( $label, $order ) {
		$cookie_data = $order->get_meta( 'woo_bg_speedy_cookie_data' );

		if ( isset( $label['service']['additionalServices']['cod']['amount'] ) ) {
			if ( 
				isset( $label['service']['additionalServices']['cod']['amount'] ) && 
				$cookie_data['fixed_price']
			) {
				$label['service']['additionalServices']['cod']['amount'] += number_format( $cookie_data['fixed_price'], 2, '.', '' );
				$label['service']['additionalServices']['cod']['amount'] = number_format( $label['service']['additionalServices']['cod']['amount'], 2, '.', '' );
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

		do_action( 'woo_bg/speedy/after_send_label', $data, $order );

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
			$label[ 'recipient' ][ 'addressLocation' ] = self::generate_recipient_address( $order );
			$label[ 'recipient' ][ 'address' ] = self::generate_recipient_address( $order );
		} else if ( $cookie_data['type'] === 'office' ) {
			$label[ 'recipient' ][ 'pickupOfficeId' ] = $_REQUEST['office']['id'];
		}

		$order->update_meta_data( 'woo_bg_speedy_cookie_data', $cookie_data );
		$order->save();

		return $label;
	}

	protected static function generate_recipient_address( $order ) {
		$container = woo_bg()->container();
		$cookie_data = $cookie_data = $_REQUEST['cookie_data'];
		$country_id = $container[ Client::SPEEDY_COUNTRIES ]->get_country_id( $order->get_billing_country() );
		$cities_data = $container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $cookie_data['city'], $cookie_data['state'], $country_id );
		$address['countryId'] = $country_id;
		$address['siteId'] = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

		if ( !empty( $_REQUEST['street']['type'] ) && $_REQUEST['street']['type'] === 'streets' ) {
			$address["streetId"] = str_replace('street-', '', $_REQUEST['street']['orig_key'] ); 
			$parts = explode( ',', $_REQUEST[ 'streetNumber' ] );
			$address["streetNo"] = array_shift( $parts );

			if ( !empty( $parts ) ) {
				$address["addressLine1"] = implode( ' ', $parts );
				$address["addressNote"] = implode( ' ', $parts );
			}
		} else if ( 
			!empty( $_REQUEST['street']['type'] ) && $_REQUEST['street']['type'] === 'quarters' || 
			!empty( $_REQUEST['cookie_data']['mysticQuarter'] )
		) {
			if ( !empty( $_REQUEST['cookie_data']['mysticQuarter'] ) ) {
				if ( $country_id == '300' ) {
					$address["addressLine1"] = $_REQUEST['cookie_data']['mysticQuarter'] . ' ' . $_REQUEST[ 'other' ];
					$address["postCode"] = $order->get_billing_postcode();
				}
				
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

		$recipient_country = '';
		$bulgarian_id = 'BG';
		
		if ( isset( $label['recipient']['addressLocation']['countryId'] ) ){
			$recipient_country = $label['recipient']['addressLocation']['countryId'];
			$bulgarian_id = '100';
		} else {
			$recipient_country = $label['recipient']['country'];
		}

		if ( isset( $label['service']['additionalServices']['cod']['amount'] ) && $label['service']['additionalServices']['cod']['amount'] && $recipient_country == $bulgarian_id ) {
			$payment[ 'declaredValuePayer' ] = 'RECIPIENT';
			$payment[ 'packagePayer' ] = 'RECIPIENT';
		} else {
			$payment[ 'declaredValuePayer' ] = 'SENDER';
			$payment[ 'packagePayer' ] = 'SENDER';
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

	protected static function update_services( $label, $order ) {
		$cookie_data = $_REQUEST['cookie_data'];
		$payment_by = $_REQUEST['paymentBy'];
		$country = ( $order->get_shipping_country() ) ? $order->get_shipping_country() : $order->get_billing_country();

		$service_id = '505';
		if ( $country !== 'BG' ) {
			$service_id = '202';
		}

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
				$label['service']['additionalServices']['cod']['amount'] += number_format( $cookie_data['fixed_price'], 2, '.', '' );
				$label['service']['additionalServices']['cod']['amount'] = number_format( $label['service']['additionalServices']['cod']['amount'], 2, '.', '' );
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
				'returnShipmentServiceId' => $service_id, 
				'returnShipmentPayer' => 'SENDER' 
			);
		}

		if ( empty( $label['service']['additionalServices'] ) ) {
		    unset( $label['service']['additionalServices'] );
		}
			
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
			$phone = [ 'number' => woo_bg_format_phone( $order->get_shipping_phone(), $order->get_shipping_country() ) ];
		} else {
			$phone = [ 'number' => woo_bg_format_phone( $order->get_billing_phone(), $order->get_billing_country() ) ];
		}

		unset( $label['recipient']['clientName'] );
		unset( $label['recipient']['contactName'] );

		$label['recipient']['privatePerson'] = true;
		$label['recipient']['clientName'] = $name;
		$label['recipient']['phone1'] = $phone;
		$label['recipient']['email'] = $order->get_billing_email();

		if ( $order->get_meta( '_billing_to_company' ) ) {
			$label['recipient']['privatePerson'] = false;
			$label['recipient']['contactName'] = $label['recipient']['clientName'];
			$label['recipient']['clientName'] = $order->get_billing_company();
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

			if ( $payment_by['id'] == 'RECIPIENT' ) {
				$price = ( wc_tax_enabled() ) ? $response['price']['amount'] : $response['price']['total'];
			} else if ( $payment_by['id'] == 'fixed' && $cookie_data['fixed_price'] ) {
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
		$labels = explode( '|', sanitize_text_field( $_REQUEST['parcels']) );
		$size = sanitize_text_field( $_REQUEST['size'] );
		$parcels = array();

		foreach ( $labels as $label ) {
			$parcels[] = array(
				'parcel' => array(
					'id' => $label,
				),
			);
		}

		$request_body = array(
			'paperSize' => $size,
			'parcels' => $parcels,
		);

		if ( !empty( $_REQUEST['awbsc'] ) ) {
			$request_body['additionalWaybillSenderCopy'] = sanitize_text_field( $_REQUEST['awbsc'] );
		}

		$pdf_escaped = $container[ Client::SPEEDY ]->api_call( $container[ Client::SPEEDY ]::PRINT_LABELS_ENDPOINT, $request_body, 1 );

		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $pdf_escaped ));
		header('Content-disposition: inline; filename="' . $labels[0] . '.pdf"');

		echo $pdf_escaped;

		wp_die();
	}
}