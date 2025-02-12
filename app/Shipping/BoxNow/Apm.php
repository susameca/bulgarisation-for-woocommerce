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
			'selected' => __( 'Selected', 'woo-bg' ),
			'choose' => __( 'Choose', 'woo-bg' ),
			'searchApm' => __( 'Search Automat', 'woo-bg' ), 
			'select' => __( 'Select', 'woo-bg' ), 
			'noResult' => __( 'No results was found', 'woo-bg' ),
			'noOptions' => __( 'Start typing Automat', 'woo-bg' ), 
			'apmLocator' => __( 'Automat locator', 'woo-bg' ),
			'toApm' => __( 'To Automat: ', 'woo-bg' ),
		);
	}

	public static function load_apms() {
		self::$container = woo_bg()->container();
		$apm_size = Method::get_allowed_apm_size();
		$apms = self::$container[ Client::BOXNOW_DESTINATIONS ]->get_destinations( $apm_size['max_size'] );

		$args[ 'apms' ] = apply_filters( 'woo_bg/shipping_method/boxnow/apms', $apms );

		wp_send_json_success( $args );

		wp_die();
	}
}
