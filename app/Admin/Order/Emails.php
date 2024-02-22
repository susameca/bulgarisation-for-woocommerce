<?php
namespace Woo_BG\Admin\Order;

defined( 'ABSPATH' ) || exit;

class Emails {
	function __construct() {
		add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'attach_invoice_to_mail' ), 10, 4 );
		add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'attach_refund_pdfs_to_mail' ), 10, 4 );
	}

	public static function attach_invoice_to_mail( $attachments, $email_id, $order, $email ) {
		if ( !is_object( $order ) || !is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$order = wc_get_order( $order->get_id() );

		$order_documents_trigger = woo_bg_get_option( 'invoice', 'trigger' );

		if ( !$order_documents_trigger || $order_documents_trigger === 'order_created' ) {
			$email_ids = array( 'customer_processing_order', 'customer_on_hold_order' );
		} else if ( $order_documents_trigger === 'order_completed' ) {
			$email_ids = array( 'customer_completed_order' );
		}

		$email_ids[] = 'customer_invoice';

		if ( in_array ( $email_id, $email_ids ) ) {
			$order_doc_id = $order->get_meta( 'woo_bg_order_document' );
			$proform_id = $order->get_meta( 'woo_bg_proform_document' );
			$invoice_id = $order->get_meta( 'woo_bg_invoice_document' );

			if ( $order_doc_id ) {
				$attachments[] = get_attached_file( $order_doc_id );
			}
			
			if ( $email_id === 'customer_on_hold_order' && $proform_id ) {
				$attachments[] = get_attached_file( $proform_id );
			} else if ( $invoice_id ) {
				$attachments[] = get_attached_file( $invoice_id );
			}
		}

		return $attachments;
	}

	public static function attach_refund_pdfs_to_mail( $attachments, $email_id, $order, $email ) {
		if ( !is_object( $order ) || !is_a( $order, 'WC_Order' ) ) {
			return;
		}
		
		$order = wc_get_order( $order->get_id() );

		$email_ids = array( 'customer_refunded_order', 'customer_invoice' );

		if ( in_array ( $email_id, $email_ids ) ) {
			$ids = [ $order->get_id() ];

			if ( $refunds = $order->get_refunds() ) {
				foreach ( $refunds as $refund ) {
					$ids[] = $refund->get_id();
				}
			}

			foreach ( $ids as $id ) {
				$order = wc_get_order( $id );
				if ( $invoice_id = $order->get_meta( 'woo_bg_refunded_order_document' ) ) {
					$attachments[] = get_attached_file( $invoice_id );
				}

				if ( $invoice_id = $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
					$attachments[] = get_attached_file( $invoice_id );
				}
			}
		}

		return $attachments;
	}
}
