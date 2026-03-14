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

			self::maybe_generate_invoice_copy( $order );
		}

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				self::generate_refunded_documents( $refund->get_id(), $refund->get_parent_id() );
			}
		}
	}

	public static function generate_proforma( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( self::maybe_generate_proforma( $order ) && $order->get_payment_method() === 'bacs' ) {
			( new Document\Proforma( $order ) )->generate_file();
		}
	}

	public static function maybe_generate_invoice( $order ) {
		$invoice_generation = woo_bg_get_option( 'invoice', 'invoices' );

		$generate_invoice = ( 
			$order->get_meta( 'woo_bg_invoice_document' ) ||
			$invoice_generation === 'always' ||
			( $invoice_generation === 'only_for_company' && $order->get_meta('_billing_to_company') )
		);

		return apply_filters( 'woo_bg/admin/order/maybe_generate_invoice', $generate_invoice, $order );
	}

	public static function maybe_generate_proforma( $order ) {
		return apply_filters( 'woo_bg/admin/order/maybe_generate_proforma', self::maybe_generate_invoice( $order ), $order );
	}

	public static function generate_refunded_documents( $refund_id, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ) {
			( new Document\NRARefunded( $refund_id ) )->generate_file();
		}

		if ( self::maybe_generate_invoice( $order ) ) {
			( new Document\CreditNotice( $refund_id ) )->generate_file();

			self::maybe_generate_credit_notice_copy( $refund_id, $order_id );
		}
	}

	public static function maybe_generate_invoice_copy( $order ) {
		if ( woo_bg_get_option( 'invoice', 'separate_copy_documents' ) !== 'yes' ) {
			return;
		}

		$copy = new Document\InvoiceCopy( $order );
		$copy->generate_file();

		self::send_accountant_email( $order->get_id(), $copy );
	}

	public static function maybe_generate_credit_notice_copy( $refund_id, $order_id ) {
		if ( woo_bg_get_option( 'invoice', 'separate_copy_documents' ) !== 'yes' ) {
			return;
		}

		$copy = new Document\CreditNoticeCopy( $refund_id );
		$copy->generate_file();

		self::send_accountant_email( $order_id, $copy );
	}

	public static function send_accountant_email( $order_id, $document ) {
		$accountant_email = woo_bg_get_option( 'invoice', 'accountant_email' );

		if ( ! $accountant_email ) {
			return;
		}

		$attach_id = $document->woo_order->get_meta( $document->meta );

		if ( ! $attach_id ) {
			return;
		}

		$file_path = get_attached_file( $attach_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		WC()->mailer();
		$email = new Accountant_Email();
		$email->trigger( $order_id, array( $file_path ) );
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
					'meta' => 'woo_bg_order_document',
					'name' => __( 'Order', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $order_pdf ),
				);
			}

			if ( $invoice_pdf = $order->get_meta( 'woo_bg_invoice_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_invoice_document',
					'name' => __( 'Invoice', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $invoice_pdf ),
				);
			}

			if ( $invoice_copy_pdf = $order->get_meta( 'woo_bg_invoice_copy_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_invoice_copy_document',
					'name' => __( 'Invoice Copy', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $invoice_copy_pdf ),
				);
			}

			if ( $refunded_order_pdf = $order->get_meta( 'woo_bg_refunded_order_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_refunded_order_document',
					'name' => __( 'Refunded Order', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $refunded_order_pdf ),
				);
			}

			if ( $refunded_invoice_pdf = $order->get_meta( 'woo_bg_refunded_invoice_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_refunded_invoice_document',
					'name' => __( 'Refunded Invoice', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $refunded_invoice_pdf ),
				);
			}

			if ( $refunded_invoice_copy_pdf = $order->get_meta( 'woo_bg_refunded_invoice_copy_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_refunded_invoice_copy_document',
					'name' => __( 'Refunded Invoice Copy', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $refunded_invoice_copy_pdf ),
				);
			}

			if ( $proform_pdf = $order->get_meta( 'woo_bg_proform_document' ) ) {
				$files[] = array(
					'meta' => 'woo_bg_proform_document',
					'name' => __( 'Pro forma', 'bulgarisation-for-woocommerce' ),
					'file_url' => wp_get_attachment_url( $proform_pdf ),
				);
			}
		}

		return apply_filters( 'woo_bg/admin/order/documents', $files, $main_order );
	}
}
