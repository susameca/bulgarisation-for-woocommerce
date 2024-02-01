<?php
namespace Woo_BG\Export\Nra;

use Woo_BG\Admin\Tabs\Nra_Tab;
use Woo_BG\Admin\Order\Documents;

defined( 'ABSPATH' ) || exit;

class Export {
	public $status, $payment_methods, $woo_orders, $not_included_orders, $options, $orders_ids, $generate_files, $date, $xml_shop, $xml_orders;

	function __construct( $date, $generate_files ) {
		$this->status = apply_filters( 'woo_bg/admin/export/orders_statuses', array( 'wc-completed' ) );
		$this->payment_methods = woo_bg_get_payment_types_for_meta();
		$this->generate_files = $generate_files;
		$this->date = $date;
		$this->load_woo_orders();
		$this->load_options();
	}

	protected function load_woo_orders() {
		$this->woo_orders = apply_filters( 'woo_bg/admin/export/orders', wc_get_orders( array(
			'date_created' => strtotime( 'first day of ' . $this->date ) . '...' . strtotime( 'last day of ' . $this->date . ' 23:59' ),
			'status' => $this->status,
			'limit' => -1,
		) ), $this );

		$this->orders_ids = array();

		foreach ( $this->woo_orders as $order ) {
			$this->orders_ids[] = $order->get_id();
		}
	}

	protected function load_options() {
		$settings = new Nra_Tab();
		$settings->load_fields();
		$this->options = $settings->get_localized_fields();
	}

	protected function load_xml_shop() {
		$this->xml_shop = new Xml\Shop(
			$this->options['nap']['eik']['value'], 
			$this->options['nap']['nap_number']['value'],
			$this->options['nap']['domain']['value'],
			new \DateTime(), 
			false, 
			date('Y', strtotime( $this->date ) ), 
			date('m', strtotime( $this->date ) )
		);
	}

	public function get_xml_file() {
		$this->load_xml_shop();

		foreach ( $this->woo_orders as $woo_order ) {
			if ( is_a( $woo_order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) || is_a( $woo_order, 'WC_Order_Refund' ) ) {
				$refunded_order = new RefundedOrder( $woo_order );

				$this->xml_shop->addReturnedOrder( $refunded_order->get_xml_order() );

				if ( !$woo_order->get_items() || !in_array( $woo_order->get_parent_id(), $this->orders_ids ) ) {
					if ( !in_array( $woo_order->get_parent_id(), $this->orders_ids ) ) {
						$this->orders_ids[] = $woo_order->get_parent_id(); 
					}

					$woo_order = wc_get_order( $woo_order->get_parent_id() );
				} else {
					continue;
				}
			}

			if ( !$woo_order ) {
				continue;
			}

			$xml_order = new Order( $woo_order, $this->generate_files );

			if ( ! $xml_order->payment_method_type ) {
				$this->not_included_orders[] = $xml_order->order_id_to_show;
				continue;
			}

			$this->xml_shop->addOrder( $xml_order->get_xml_order() );
		}

		return $this->upload_xml();
	}

	protected function upload_xml() {
		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$name = uniqid( rand(), true );
		$xml = wp_upload_bits( $name . '.xml', null, Xml\XmlConverter::convert( $this->xml_shop ) );
		remove_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );

		if ( is_wp_error( $xml ) ) {
			return;
		}

		$attachment = array(
			 'guid' => $xml[ 'file' ], 
			 'post_mime_type' => $xml['type'],
			 'post_title' => $name,
			 'post_content' => '',
			 'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $xml[ 'file' ] );

		return array(
			'file' => wp_get_attachment_url( $attach_id ),
			'not_included_orders' => $this->not_included_orders,
			'totals' => $this->calculate_totals_message(),
		);
	}

	protected function calculate_totals_message() {
		$message = sprintf( 
			__( 'Total: %s | Total vat: %s | Returned Total: %s', 'woo-bg' ), 
			wc_price( $this->xml_shop->getOrdersTotal() ), 
			wc_price( $this->xml_shop->getOrdersTotalVat() ),
			wc_price( $this->xml_shop->getTotalAmountReturnedOrders() ) 
		);

		return $message;
	}
}
