<?php
namespace Woo_BG\Shipping\BoxNow;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Apm {
	private static $container = null;
	
	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_boxnow_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_boxnow_load_apms', array( __CLASS__, 'load_apms' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_boxnow_load_apms', array( __CLASS__, 'load_apms' ) );
	}

	public static function delivery_with_boxnow_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-boxnow-shipping-to--apm" class="woo-bg-additional-fields" data-type="APM"></div>', [
					'div' => [
						'data-cache' => [],
						'data-type' => [],
						'class' => [],
						'id' => [],
					]
				] );
		}
	}

	public static function get_i18n() {
		return array(
			'selected' => __( 'Selected', 'bulgarisation-for-woocommerce' ),
			'choose' => __( 'Choose', 'bulgarisation-for-woocommerce' ),
			'searchApm' => __( 'Search Automat', 'bulgarisation-for-woocommerce' ), 
			'select' => __( 'Select', 'bulgarisation-for-woocommerce' ), 
			'noResult' => __( 'No results was found', 'bulgarisation-for-woocommerce' ),
			'noOptions' => __( 'Start typing Automat', 'bulgarisation-for-woocommerce' ), 
			'apmLocator' => __( 'Automat locator', 'bulgarisation-for-woocommerce' ),
			'toApm' => __( 'To Automat: ', 'bulgarisation-for-woocommerce' ),
		);
	}

	public static function load_apms() {
		self::$container = woo_bg()->container();
		$apm_size = Method::get_allowed_apm_size();
		$apms = self::$container[ Client::BOXNOW_DESTINATIONS ]->get_destinations( $apm_size['max_size'] );

		$apms = array_filter( $apms, function( $apm ) {
			return ( $apm['country'] === 'BG' );
		} );

		$apms = array_values( $apms );

		$args[ 'apms' ] = apply_filters( 'woo_bg/shipping_method/boxnow/apms', $apms );

		wp_send_json_success( $args );

		wp_die();
	}
}
