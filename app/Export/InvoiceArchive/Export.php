<?php
namespace Woo_BG\Export\InvoiceArchive;
use ZipArchive;

defined( 'ABSPATH' ) || exit;

class Export {
	public $documents = array(), $woo_orders = array(), $order_ids = array(), $date;

	function __construct( $date ) {
		$this->date = $date;
		$this->load_woo_orders();
	}

	protected function load_woo_orders() {
		$this->order_ids = wc_get_orders( array(
			'date_created' => strtotime( 'first day of ' . $this->date . ' ' . wp_timezone_string() ) . '...' . strtotime( 'last day of ' . $this->date . ' 23:59:59 ' . wp_timezone_string() ),
			'limit' => -1,
			'return' => 'ids',
		) );
	}

	public function get_file() {
		if ( ! class_exists( 'ZipArchive' ) ) {
	        return new \WP_Error( 'zip_missing', 'PHP ZipArchive extension is not available.' );
	    }

		foreach ( $this->order_ids as $key => $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			if ( $order->get_meta( 'woo_bg_invoice_document' ) ) {
				$attachment_id = $order->get_meta( 'woo_bg_invoice_document' );
				$this->documents[] = [
					'file_name' => $order->get_id() . '.pdf',
					'file' => wp_get_attachment_url( $attachment_id ),
					'path' => get_attached_file( $attachment_id ),
				];
			}

			if ( $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
				$attachment_id = $order->get_meta( 'woo_bg_refunded_invoice_document' );
				$this->documents[] = [
					'file_name' => $order->get_parent_id() . "-refund-" . $order->get_id() . '.pdf',
					'file' => wp_get_attachment_url( $attachment_id ),
					'path' => get_attached_file( $attachment_id ),
				];
			}

			unset( $order );

			if ( 0 === ( ( $key + 1 ) % 100 ) && function_exists( 'wp_cache_flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}
		}

		if ( has_filter( 'woo_bg/admin/delta_export/documents' ) ) {
			$this->woo_orders = array_filter( array_map( 'wc_get_order', $this->order_ids ) );
			$this->documents = apply_filters( 'woo_bg/admin/delta_export/documents', $this->documents, $this->woo_orders );
		}

		return $this->generate_zip();
	}

	protected function generate_zip() {
		$zip_file_name = 'invoice-export-' . $this->date . '.zip';
		$zip = new \ZipArchive();
		$tmp_file = wp_tempnam( $zip_file_name );
		$opened = $zip->open( $tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $opened ) {
			return new \WP_Error( 'zip_open_failed', 'Could not create the invoice archive.' );
		}

		foreach ( $this->documents as $document ) {
			if ( ! empty( $document['path'] ) && is_readable( $document['path'] ) ) {
				$zip->addFile( $document['path'], $document['file_name'] );
			} else if ( ! empty( $document['file'] ) ) {
				$file_contents = file_get_contents( $document['file'] );

				if ( false !== $file_contents ) {
					$zip->addFromString( $document['file_name'], $file_contents );
				}

				unset( $file_contents );
			}
		}

		$zip->close();

		foreach ( $this->documents as &$document ) {
			unset( $document['path'] );
		}
		unset( $document );

		return [
			'documents' => $this->documents,
			'zip' => $zip,
			'file_name' => $zip_file_name,
			'temp_file' => $tmp_file,
		];
	}
}
