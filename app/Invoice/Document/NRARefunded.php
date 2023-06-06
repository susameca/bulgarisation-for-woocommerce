<?php
namespace Woo_BG\Invoice\Document;
use Woo_BG\Invoice\Order\Order;
use \Automattic\WooCommerce\Admin\Overrides\OrderRefund;

defined( 'ABSPATH' ) || exit;

class NRARefunded extends BaseDocument {
	public $title, $parent_order, $return_methods, $return_method;

	function __construct( $refund_id ) {
		$order = new OrderRefund( $refund_id );
		parent::__construct( $order );
		$this->parent_order = wc_get_order( $this->woo_order->get_parent_id() );
		$this->order = new Order( $order );
		$this->return_methods = woo_bg_get_return_methods();
		$this->return_method = woo_bg_get_option( 'shop', 'return_method' );
		$this->payment_method = $this->return_methods[ $this->return_method ]['label'];

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/order_document_title_refunded', __( 'Refunded Order - Original', 'woo-bg' ) ) );
		$this->meta = 'woo_bg_refunded_order_document';
	}

	public function get_document_number() {
		$document_number = $this->woo_order->get_meta( 'woo_bg_refunded_order_number' );
		
		if ( !$document_number ) {
			$document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $document_number + 1, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->update_meta_data( 'woo_bg_refunded_order_number', str_pad( $document_number, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->save();
		}

		return $document_number;
	}

	public function get_head_items() {
		return apply_filters( 'woo_bg/invoice/head_items', array(
			'reference' => array(
				'label' => '№',
				'value' => str_pad( $this->get_document_number(), 10, '0', STR_PAD_LEFT ),
			),
			'date' => array(
				'label' => __( 'Order date', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'date-of-tax' => array(
				'label' => __( 'Date of tax. event', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'due-date' => array(
				'label' => __( 'Due date', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'address' => array(
				'label' => __( 'Place of transaction', 'woo-bg' ),
				'value' => $this->city,
			),
			'order-number' => array(
				'label' => __( 'Order Number', 'woo-bg' ),
				'value' => $this->parent_order->get_order_number(),
			),
		), $this );
	}

	public function get_to_items() {
		if ( $this->parent_order->get_meta('_billing_to_company') === '1' ) {
			$items = apply_filters( 'woo_bg/invoice/set_to_company', array(
				$this->parent_order->get_billing_company(),
				sprintf( __('EIK: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_company_eik') ),
				sprintf( __('VAT Number: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_vat_number') ),
				sprintf( __('MOL: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_company_mol') ),
				$this->parent_order->get_meta('_billing_company_address'),
				$this->parent_order->get_meta('_billing_company_settlement'),
				'&nbsp;',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->parent_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->parent_order->get_billing_email() ),
			), $this->parent_order );
		} else {
			$items = apply_filters( 'woo_bg/invoice/set_to_person', array(
				$this->parent_order->get_billing_first_name() . ' ' . $this->parent_order->get_billing_last_name(),
				$this->parent_order->get_billing_address_1() . " " . $this->parent_order->get_billing_address_2(),
				$this->parent_order->get_billing_city() .", " . $this->parent_order->get_billing_postcode(),
				'&nbsp;',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->parent_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->parent_order->get_billing_email() ),
			), $this->parent_order );
		}

		return apply_filters( 'woo_bg/invoice/to_items', $items, $this );
	}

	public function get_additional_items() {
		$items = array();

		if ( $this->payment_method ) {
			$items[] = $this->payment_method;
		}

		if ( $this->transaction_id ) {
			$items[] = $this->transaction_id;
		}

		$items[] = $this->prepared_by . " " . $this->identification_code;
		$items[] = $this->parent_order->get_billing_first_name() . ' ' . $this->parent_order->get_billing_last_name();

		return apply_filters( 'woo_bg/invoice/additional_items', $items, $this );
	}
}