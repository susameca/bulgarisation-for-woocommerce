<?php
namespace Woo_BG\Shipping\Econt;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Office {
	private static $container = null;
	
	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_econt_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_econt_load_offices', array( __CLASS__, 'load_offices' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_econt_load_offices', array( __CLASS__, 'load_offices' ) );
	}

	public static function delivery_with_econt_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'office' ) {
				echo '<div data-cache="' . rand() . '" id="woo-bg-econt-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>';
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
		$raw_state = sanitize_text_field( $_POST['state'] );
		$states = woo_bg_return_bg_states();
		$state = $states[ $raw_state ];
		$raw_city = sanitize_text_field( $_POST['city'] );

		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $raw_city, $state );

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$args[ 'status' ] = 'invalid-city';
			
			if ( !$state ) {
				$args[ 'error' ] = __( 'Please select region.', 'woo-bg' );
			} elseif ( empty( $raw_city ) ) {
				$args[ 'error' ] = sprintf( __( 'Please enter a city.', 'woo-bg' ), $raw_city, $state );
			} else {
				$args[ 'error' ] = sprintf( __( '%s is not found in %s region.', 'woo-bg' ), $raw_city, $state );
			}
		} else {
			$offices = self::$container[ Client::ECONT_OFFICES ]->get_offices( $cities_data['cities'][ $cities_data['city_key'] ]['id'] );

			if ( empty( $offices ) ) {
				$offices = [];
				$args[ 'error' ] = sprintf( __( 'No offices were found at %s.', 'woo-bg' ), $raw_city );
			} else {
				$offices = $offices['offices'];
			}

			$args[ 'offices' ] = $offices;
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}
}
