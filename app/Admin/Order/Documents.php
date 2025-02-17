<?php
namespace Woo_BG\Admin\Order;
use Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class Documents {
	public static function generate_documents( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ) {
			( new Document\NRA( $order ) )->generate_file();
		}

		if ( self::maybe_generate_invoice( $order ) ) {
			( new Document\Invoice( $order ) )->generate_file();
			
			if ( $order->get_payment_method() === 'bacs' ) {
				( new Document\Proforma( $order ) )->generate_file();
			}
		}

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				self::generate_refunded_documents( $refund->get_id(), $refund->get_parent_id() );
			}
		}
	}

	public static function generate_proforma( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( self::maybe_generate_invoice( $order ) && $order->get_payment_method() === 'bacs' ) {
			( new Document\Proforma( $order ) )->generate_file();
		}
	}

	public static function maybe_generate_invoice( $order ) {
		$invoice_generation = woo_bg_get_option( 'invoice', 'invoices' );

		return apply_filters('woo_bg_maybe_generate_invoice', 
			$order->get_meta( 'woo_bg_invoice_document' ) ||
			$invoice_generation === 'always' ||
			( $invoice_generation === 'only_for_company' && $order->get_meta('_billing_to_company') ),
			$order
		);
	}

	public static function generate_refunded_documents( $refund_id, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ) {
			( new Document\NRARefunded( $refund_id ) )->generate_file();
		}

		if ( self::maybe_generate_invoice( $order ) ) {
			( new Document\CreditNotice( $refund_id ) )->generate_file();
		}
	}

	public static function get_order_documents( $main_order ) {
		$files = array();
		$ids = [ $main_order->get_id() ];

		if ( $refunds = $main_order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$ids[] = $refund->get_id();
			}
		}

		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );

			if ( $order_pdf = $order->get_meta( 'woo_bg_order_document' ) ) {
				$files[] = array(
					'name' => __( 'Order', 'woo-bg' ),
					'file_url' => wp_get_attachment_url( $order_pdf ),
				);
			}

			if ( $invoice_pdf = $order->get_meta( 'woo_bg_invoice_document' ) ) {
				$files[] = array(
					'name' => __( 'Invoice', 'woo-bg' ),
					'file_url' => wp_get_attachment_url( $invoice_pdf ),
				);
			}

			if ( $refunded_order_pdf = $order->get_meta( 'woo_bg_refunded_order_document' ) ) {
				$files[] = array(
					'name' => __( 'Refunded Order', 'woo-bg' ),
					'file_url' => wp_get_attachment_url( $refunded_order_pdf ),
				);
			}

			if ( $refunded_invoice_pdf = $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
				$files[] = array(
					'name' => __( 'Refunded Invoice', 'woo-bg' ),
					'file_url' => wp_get_attachment_url( $refunded_invoice_pdf ),
				);
			}

			if ( $proform_pdf = $order->get_meta( 'woo_bg_proform_document' ) ) {
				$files[] = array(
					'name' => __( 'Pro forma', 'woo-bg' ),
					'file_url' => wp_get_attachment_url( $proform_pdf ),
				);
			}
		}

		return apply_filters( 'woo_bg/admin/order/documents', $files, $main_order );
	}
}
