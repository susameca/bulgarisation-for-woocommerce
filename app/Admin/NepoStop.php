<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class NepoStop {
	public function __construct() {
		add_action( 'woo_bg/econt/after_send_label', array( __CLASS__, 'send_econt_tracking_code' ), 20, 2 );
		add_action( 'woo_bg/speedy/after_send_label', array( __CLASS__, 'send_speedy_tracking_code'), 20, 2 );
	}

    public static function send_tracking_code( $tracking_code, $phone ) {
        $container = woo_bg()->container();

        $container[ Client::NEPOSTOP ]->api_call( $container[ Client::NEPOSTOP ]::ADD_SHIPMENT_ENDPOINT, [ 'shipping_number' => $tracking_code, 'phone_number' => woo_bg_format_phone( $phone ) ] );
    }

    public static function send_econt_tracking_code( $data, $order ) {
        $tracking_code = $data['label']['shipmentNumber'];
        $phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();

        if ( !empty( $tracking_code ) && !empty( $phone ) ) {
            self::send_tracking_code( $tracking_code, $phone );
        }
    }

    public static function send_speedy_tracking_code( $data, $order ) {
        $tracking_code = $data['shipmentStatus']['id'];
        $phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();

        if ( !empty( $tracking_code ) && !empty( $phone ) ) {
            self::send_tracking_code( $tracking_code, $phone );
        }
    }
}
