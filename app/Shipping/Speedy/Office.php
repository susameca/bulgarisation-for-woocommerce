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
				echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-speedy-shipping-to--office" class="woo-bg-additional-fields" data-type="office"></div>', [
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
		$raw_city = sanitize_text_field( $_POST['city'] );
		$country_id = self::$container[ Client::SPEEDY_COUNTRIES ]->get_country_id( sanitize_text_field( $_POST[ 'country' ] ) );
		$states = self::$container[ Client::SPEEDY_CITIES ]->get_regions( $country_id );
		$state = $states[ $raw_state ];
		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state, $country_id );

		if ( $country_id !== '300' && !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) || !$raw_city ) {
			$args[ 'status' ] = 'invalid-city';

			if ( !$state ) {
				$args[ 'error' ] = __( 'Please select region.', 'woo-bg' );
			} elseif ( empty( $raw_city ) ) {
				$args[ 'error' ] = sprintf( __( 'Please enter a city.', 'woo-bg' ), $raw_city, $state );
			} else {
				/* translators: %1$s is the city and %2$s is the state */
				$args[ 'error' ] = sprintf( __( '%1$s is not found in %2$s region.', 'woo-bg' ), $raw_city, $state );
			}
		} else {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ]['id'];

			if ( $country_id === '300' ) {
				$city_id = 0;
			}

			$offices = self::$container[ Client::SPEEDY_OFFICES ]->get_offices( $city_id, $country_id );
			
			if ( empty( $offices ) ) {
				$offices = [];
				/* translators: %s is the city */
				$args[ 'error' ] = sprintf( __( 'No offices were found at %s.', 'woo-bg' ), $raw_city );
			} else {
				$offices = $offices['offices'];

				if ( woo_bg_get_option( 'speedy', 'disable_apt' ) === 'yes' ) {
					$offices = array_values( array_filter( $offices, function( $office ) {
						return ( $office['type'] !== 'APT' );
					} ) );
				}
			}

			$args[ 'offices' ] = apply_filters( 'woo_bg/shipping_method/speedy/offices', $offices );
			$args[ 'status' ] = 'valid-city';
			$args[ 'cityId' ] = $cities_data['cities'][ $cities_data['city_key'] ]['id'];
		}

		wp_send_json_success( $args );

		wp_die();
	}
}
