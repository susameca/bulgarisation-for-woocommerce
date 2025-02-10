<?php
namespace Woo_BG\Front_End\Checkout;
use Woo_BG\Admin\Nekorekten_Com;
defined( 'ABSPATH' ) || exit;

class Nekorekten_Com_Checkout {
	const SESSION_NAME = 'nekorekten_found';

	public function __construct() {
		if ( woo_bg_get_option( 'nekorekten', 'check_on_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_payment_methods' ) );
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'unset_session' ), 30 );
		}
	}

	public static function filter_payment_methods( $available_gateways ) {    
	    if( 
	    	is_admin() || 
	    	!isset( $_POST['post_data'] ) || 
	    	empty( $available_gateways['cod'] ) 
	    ) {
	        return $available_gateways;
	    }

	    parse_str( $_POST['post_data'], $data );

	    $phone = $data['billing_phone'];

		$reports_by_phone = self::check_in_session( $phone );

		$phone_counts = ( isset( $reports_by_phone['count'] ) ) ? $reports_by_phone['count'] : 0;

		if ( $phone_counts ) {
	    	unset(  $available_gateways['cod'] );
		}
	     
	    return $available_gateways;
	}

	public static function check_in_session( $phone ) {
		$phones = [];

		if ( $cached = WC()->session->get( 'nekorekten_found' ) ) {
			$phones = $cached;
		}


		if ( ! isset( $phones[ md5( $phone ) ] ) ) {
			$report = Nekorekten_Com::api_call( Nekorekten_Com::REPORTS_URL, [ 'phone' => $phone ] );

			if ( isset( $report['server']['httpCode'] ) && $report['server']['httpCode'] === 200 ) {
				$phones[ md5( $phone ) ] = $report;
			}

			WC()->session->set( 'nekorekten_found', $phones );
		}
        
        if ( !empty( $phones[ md5( $phone ) ] ) ) {
        	return $phones[ md5( $phone ) ];
        }
	}

	public static function unset_session( $order_id ) {
		WC()->session->__unset( 'nekorekten_found' );
	}
}