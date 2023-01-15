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
				echo '<div id="woo-bg-cvc-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>';
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
		);
	}

	public static function load_offices() {
		self::$container = woo_bg()->container();
		$args = [];
		$country = sanitize_text_field( $_POST['country'] );
		$country_id = self::$container[ Client::CVC_COUNTRIES ]->get_country_id( $country );
		$raw_city = sanitize_text_field( $_POST['city'] );
		$state_id = self::$container[ Client::CVC_CITIES ]->get_state_id( sanitize_text_field( $_POST['state'] ), $country_id );
		$city = self::$container[ Client::CVC_CITIES ]->search_for_city( $raw_city, $state_id, $country_id );

		if ( empty( $city ) ) {
			$args[ 'status' ] = 'invalid-city';
			$args[ 'error' ] = sprintf( __( '%s is not found in %s region.', 'woo-bg' ), $raw_city, woo_bg_return_bg_states()[ $_POST['state'] ] );
		} else {
			$args[ 'offices' ] = self::$container[ Client::CVC_OFFICES ]->get_offices( $city[0]['zip'] );
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}
}