<?php
namespace Woo_BG\Shipping\Speedy;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Office {
	private static $container = null;
	
	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_speedy_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_speedy_load_offices', array( __CLASS__, 'load_offices' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_speedy_load_offices', array( __CLASS__, 'load_offices' ) );
	}

	public static function delivery_with_speedy_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'office' ) {
				echo '<div id="woo-bg-speedy-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>';
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
		$raw_state = sanitize_text_field( $_POST['state'] );
		$raw_city = sanitize_text_field( $_POST['city'] );
		$states = woo_bg_return_bg_states();
		$state = $states[ $raw_state ];

		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$args[ 'status' ] = 'invalid-city';
			$args[ 'error' ] = sprintf( __( '%s is not found in %s region.', 'woo-bg' ), $raw_city, $state );
		} else {
			$args[ 'offices' ] = self::$container[ Client::SPEEDY_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'] )['offices'];
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}
}
