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
				echo '<div data-cache="' . wp_rand() . '" id="woo-bg-cvc-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>';
			}
		}
	}

	public static function get_i18n() {
		return array(
			'selected' => __( 'Selected', 'woo-bg' ),
			'choose' => __( 'Choose', 'woo-bg' ),
			'searchOffice' => __( 'Search office', 'woo-bg' ), 
			'select' => __( 'Select', 'woo-bg' ), 
			'noResult' => __( 'No results was found for this city', 'woo-bg' ),
			'noOptions' => __( 'Start typing office', 'woo-bg' ), 
			'officeLocator' => __( 'Office locator', 'woo-bg' ),
			'toOffice' => __( 'To Office: ', 'woo-bg' ),
		);
	}

	public static function load_offices() {
		self::$container = woo_bg()->container();
		$args = [];
		$country = sanitize_text_field( $_POST['country'] );
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$args[ 'offices' ] = self::$container[ Client::CVC_OFFICES ]->get_all_offices( $country_id );
		$args[ 'status' ] = 'valid-city';

		wp_send_json_success( $args );

		wp_die();
	}
}
