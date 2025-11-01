<?php
namespace Woo_BG\Export\InvoiceArchive;
use ZipArchive;

defined( 'ABSPATH' ) || exit;

class Export {
	public $documents, $woo_orders, $date;

	function __construct( $date ) {
		$this->date = $date;
		$this->load_woo_orders();
	}

	protected function load_woo_orders() {
		$this->woo_orders = wc_get_orders( array(
			'date_created' => strtotime( 'first day of ' . $this->date . ' ' . wp_timezone_string() ) . '...' . strtotime( 'last day of ' . $this->date . ' 23:59:59 ' . wp_timezone_string() ),
			'limit' => -1,
		) );
	}

	public function get_file() {
		if ( ! class_exists( 'ZipArchive' ) ) {
	        return new \WP_Error( 'zip_missing', 'PHP ZipArchive extension is not available.' );
	    }

		foreach ( $this->woo_orders as $key => $order ) {
			if ( is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) || is_a( $order, 'WC_Order_Refund' ) ) {
				$temp_order = wc_get_order( $order->get_parent_id() );
			}

			$order_number = $order->get_meta( 'woo_bg_order_number' );

			if ( $order->get_meta( 'woo_bg_invoice_document' ) ) {
				$this->documents[] = [
					'file_name' => $order->get_id() . '.pdf',
					'file' => wp_get_attachment_url( $order->get_meta( 'woo_bg_invoice_document' ) ),
				];
			}

			if ( $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
				$this->documents[] = [
					'file_name' => $order->get_parent_id() . "-refund-" . $order->get_id() . '.pdf',
					'file' => wp_get_attachment_url( $order->get_meta( 'woo_bg_refunded_invoice_document' ) ),
				];
			}
		}

		$this->documents = apply_filters( 'woo_bg/admin/delta_export/documents', $this->documents, $this->woo_orders );

		return $this->generate_zip();
	}

	protected function generate_zip() {
		$zip_file_name = 'invoice-export-' . $this->date . '.zip';
		$zip = new \ZipArchive();
		$tmp_file = tempnam('.','');
		$zip->open( $tmp_file, ZipArchive::CREATE );

		foreach ( $this->documents as $document ) {
			$file_contents = file_get_contents( $document['file'] );
			$zip->addFromString( $document['file_name'], $file_contents );
		}

		$zip->close();

		return [
			'documents' => $this->documents,
			'zip' => $zip,
			'file_name' => $zip_file_name,
			'temp_file' => $tmp_file,
		];
	}
}
