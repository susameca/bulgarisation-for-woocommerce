<?php
namespace Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class CreditNoticeCopy extends NRARefunded {
	public $title, $parent_order, $return_methods, $return_method;

	function __construct( $refund_id ) {
		parent::__construct( $refund_id );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/credit_notice_title_copy', __( 'Credit Notice - Copy', 'bulgarisation-for-woocommerce' ) ) );
		$this->meta = 'woo_bg_refunded_invoice_copy_document';

		if ( woo_bg_get_option( 'invoice', 'next_invoice_separate_number' ) ) {
			$this->document_number_meta = 'woo_bg_order_invoice_number';
			$this->document_number_option = 'next_invoice_separate_number';
		}
	}

	public function get_head_items() {
		return apply_filters( 'woo_bg/invoice/head_items', array(
			'parent_reference' => array(
				'label' => __('To invoice №', 'bulgarisation-for-woocommerce'),
				'value' => $this->get_parent_reference(),
			),
			'reference' => array(
				'label' => '№',
				'value' => str_pad( $this->get_document_number(), 10, '0', STR_PAD_LEFT ),
			),
			'date' => array(
				'label' => __( 'Order date', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'date-of-tax' => array(
				'label' => __( 'Date of tax. event', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'due-date' => array(
				'label' => __( 'Due date', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'address' => array(
				'label' => __( 'Place of transaction', 'bulgarisation-for-woocommerce' ),
				'value' => $this->city,
			),
			'order-number' => array(
				'label' => __( 'Order Number', 'bulgarisation-for-woocommerce' ),
				'value' => $this->parent_order->get_order_number(),
			),
		), $this );
	}

	public function get_document_number() {
		return $this->woo_order->get_meta( $this->document_number_meta );
	}

	public function get_parent_reference() {
		$meta = 'woo_bg_order_number';

		if ( woo_bg_get_option( 'invoice', 'next_invoice_separate_number' ) ) {
			$meta = 'woo_bg_order_invoice_number';
		}

		return str_pad( $this->parent_order->get_meta( $meta ), 10, '0', STR_PAD_LEFT );
	}

	public function get_document_date() {
		return $this->woo_order->get_meta( 'woo_bg_credit_notice_document_date' ) ?: $this->woo_order->get_date_created();
	}
}
