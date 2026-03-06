<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Connectix {
	public function __construct() {
		add_action( 'woo_bg/econt/after_send_label', array( __CLASS__, 'send_econt_tracking_code' ), 20, 2 );
		add_action( 'woo_bg/speedy/after_send_label', array( __CLASS__, 'send_speedy_tracking_code'), 20, 2 );
	}

    public static function send_tracking_code( $tracking_code ) {
        $container = woo_bg()->container();
        $integration_token = $container[ Client::CONNECTIX ]->get_integration_token();

        if ( $integration_token ) {
            $endpoint = add_query_arg( 'integration', $integration_token, $container[ Client::CONNECTIX ]::TRACKING_ENDPOINT );

            $container[ Client::CONNECTIX ]->api_call( $endpoint, [ 'trackingNumber' => $tracking_code ] );
        }
    }

    public static function send_econt_tracking_code( $data, $order ) {
        $tracking_code = $data['label']['shipmentNumber'];

        if ( !empty( $tracking_code ) ) {
            self::send_tracking_code( $tracking_code );
        }
    }

    public static function send_speedy_tracking_code( $data, $order ) {
        $tracking_code = $data['shipmentStatus']['id'];

        if ( !empty( $tracking_code ) ) {
            self::send_tracking_code( $tracking_code );
        }
    }
}
