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
		}

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				self::generate_refunded_documents( $refund->get_id(), $refund->get_parent_id() );
			}
		}
	}

	public static function maybe_generate_invoice( $order ) {
		$invoice_generation = woo_bg_get_option( 'invoice', 'invoices' );

		return (
			$order->get_meta( 'woo_bg_invoice_document' ) ||
			$invoice_generation === 'always' ||
			( $invoice_generation === 'only_for_company' && $order->get_meta('_billing_to_company') )
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
}