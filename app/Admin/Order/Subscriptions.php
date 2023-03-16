<?php
namespace Woo_BG\Admin\Order;

defined( 'ABSPATH' ) || exit;

class Subscriptions {
	function __construct() {
		add_action( 'woocommerce_checkout_subscription_created', array( __CLASS__, 'subscriptions_created' ), 20, 2 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', array( __CLASS__, 'subscriptions_created' ), 20, 2 );
	}

	public static function subscriptions_created( $subscription, $order ) {
		self::increase_order_doc_number( $order );

		Documents::generate_documents( $order->get_id() );
	}

	public static function increase_order_doc_number( $order ) {
		$order_document_number = $order->get_meta( 'woo_bg_order_number' );

		if ( $order_document_number ) {
			$order_document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $order_document_number + 1, 10, '0', STR_PAD_LEFT ) );
			$order->update_meta_data( 'woo_bg_order_number', str_pad( $order_document_number, 10, '0', STR_PAD_LEFT ) );
			$order->save();
		}
	}
}
