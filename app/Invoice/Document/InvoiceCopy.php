<?php
namespace Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class InvoiceCopy extends BaseDocument {
	public $title;

	function __construct( $order ) {
		parent::__construct( $order );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/invoice_title_copy', __( 'Sale Invoice - Copy', 'bulgarisation-for-woocommerce' ) ) );
		$this->meta = 'woo_bg_invoice_copy_document';

		if ( woo_bg_get_option( 'invoice', 'next_invoice_separate_number' ) ) {
			$this->document_number_meta = 'woo_bg_order_invoice_number';
			$this->document_number_option = 'next_invoice_separate_number';
		}
	}

	public function get_document_number() {
		return $this->woo_order->get_meta( $this->document_number_meta );
	}

	public function get_document_date() {
		return $this->woo_order->get_meta( 'woo_bg_invoice_document_date' ) ?: $this->woo_order->get_date_created();
	}

	public function get_document_due_date() {
		$date = $this->get_document_date();

		if ( $due_days = woo_bg_get_option( 'invoice', 'due_days' ) ) {
			$date = date_i18n( 'd-m-Y', strtotime( $date . " + " . $due_days . " days" ) );
		}

		return $date;
	}
}
