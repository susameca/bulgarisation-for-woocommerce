<?php
namespace Woo_BG\Export\Delta;


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
		foreach ( $this->order_ids as $key => $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			$names = [];
			foreach ( $order->get_items() as $item ) {
				$names[] = $item->get_name();
			}

			$temp_order = $order;
			$company = '';
			$eik = '';
			$vat_number = '';
			$mol = '';
			$address = '';
			$city = '';

			if ( is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) || is_a( $order, 'WC_Order_Refund' ) ) {
				$temp_order = wc_get_order( $order->get_parent_id() );
			}

			if ( $temp_order->get_meta('_billing_to_company') === '1' ) {
				$company = $temp_order->get_billing_company();
				$eik = $temp_order->get_meta('_billing_company_eik');
				$vat_number = $temp_order->get_meta('_billing_vat_number');
				$mol = $temp_order->get_meta('_billing_company_mol');
				$city = $temp_order->get_meta('_billing_company_settlement');
				$address = $temp_order->get_meta('_billing_company_address');
			} else {
				$company = $temp_order->get_billing_first_name() . ' ' . $temp_order->get_billing_last_name();
				$address = $temp_order->get_billing_address_1() . " " . $temp_order->get_billing_address_2();
				$city = $temp_order->get_billing_city();
			}

			$order_number = $order->get_meta( 'woo_bg_order_number' );
			if ( woo_bg_get_option( 'invoice', 'next_invoice_separate_number' ) ) {
				$order_number = $order->get_meta( 'woo_bg_order_invoice_number' );
			}

			if ( $order->get_meta( 'woo_bg_invoice_document' ) ) {
				$total_due = $order->get_total();
				

				$this->documents[] = apply_filters( 'woo_bg/admin/export/microinvest-order', array(
					'2', 
					date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) ),
					$order_number,
					'Ф-ра',
					$total_due,
					'16',
					$company,
					$mol,
					$city,
					$address,
					$vat_number,
					$eik,
					'',
					'Продажба',
					implode( ', ', $names ),
					'-1'
				), $order, $temp_order );
			}

			if ( $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
				$total_due = $order->get_total();
				$this->documents[] = apply_filters( 'woo_bg/admin/export/microinvest-order', array(
					'2', 
					date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) ),
					$order_number,
					'КИ',
					$total_due,
					'16',
					$company,
					$mol,
					$city,
					$address,
					$vat_number,
					$eik,
					'',
					'Продажба',
					implode( ', ', $names ),
					'-1'
				), $order, $temp_order );
			}

			unset( $order, $temp_order );

			if ( 0 === ( ( $key + 1 ) % 100 ) && function_exists( 'wp_cache_flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}
		}

		if ( has_filter( 'woo_bg/admin/delta_export/documents' ) ) {
			$this->woo_orders = array_filter( array_map( 'wc_get_order', $this->order_ids ) );
			$this->documents = apply_filters( 'woo_bg/admin/delta_export/documents', $this->documents, $this->woo_orders );
		}

		return $this->upload_file();
	}

	protected function upload_file() {
		$name = uniqid( wp_rand(), true );
		$content = '';

		foreach ( $this->documents as $index => $document ) {
			if ( $index > 0 ) {
				$content .= "\n";
			}

			$content .= implode( '|', $document );
		}

		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$txt = wp_upload_bits( $name . '.txt', null, $content );
		remove_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		unset( $content );

		if ( is_wp_error( $txt ) ) {
			return;
		}

		$attachment = array(
			'guid' => $txt[ 'file' ], 
			'post_mime_type' => $txt['type'],
			'post_title' => $name,
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $txt[ 'file' ] );

		return array(
			'file' => wp_get_attachment_url( $attach_id ),
		);
	}
}
