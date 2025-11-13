<?php
namespace Woo_BG\Shipping\CVC;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Office {
	private static $container = null;
	
	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_cvc_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_cvc_load_offices', array( __CLASS__, 'load_offices' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_cvc_load_offices', array( __CLASS__, 'load_offices' ) );
	}

	public static function delivery_with_cvc_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'office' ) {
				echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-cvc-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>', [
					'div' => [
						'data-cache' => [],
						'data-type' => [],
						'class' => [],
						'id' => [],
					]
				] );
			}
		}
	}

	public static function get_i18n() {
		return array(
			'selected' => __( 'Selected', 'bulgarisation-for-woocommerce' ),
			'choose' => __( 'Choose', 'bulgarisation-for-woocommerce' ),
			'searchOffice' => __( 'Search office', 'bulgarisation-for-woocommerce' ), 
			'select' => __( 'Select', 'bulgarisation-for-woocommerce' ), 
			'noResult' => __( 'No results was found for this city', 'bulgarisation-for-woocommerce' ),
			'noOptions' => __( 'Start typing office', 'bulgarisation-for-woocommerce' ), 
			'officeLocator' => __( 'Office locator', 'bulgarisation-for-woocommerce' ),
			'toOffice' => __( 'To Office: ', 'bulgarisation-for-woocommerce' ),
		);
	}

	public static function load_offices() {
		self::$container = woo_bg()->container();
		$args = [];
		$country = sanitize_text_field( $_POST['country'] );
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$args[ 'offices' ] = apply_filters( 'woo_bg/shipping_method/cvc/offices', self::$container[ Client::CVC_OFFICES ]->get_all_offices( $country_id ) );
		$args[ 'status' ] = 'valid-city';

		wp_send_json_success( $args );

		wp_die();
	}
}
