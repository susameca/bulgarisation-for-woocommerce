<?php
namespace Woo_BG\Shipping\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Locker {
	private static $container = null;
	
	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_pigeon_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_pigeon_load_lockers', array( __CLASS__, 'load_lockers' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_pigeon_load_lockers', array( __CLASS__, 'load_lockers' ) );
	}

	public static function delivery_with_pigeon_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'locker' ) {
				echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-pigeon-shipping-to--locker" class="woo-bg-additional-fields" data-type="locker"></div>', [
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
			'searchAPS' => __( 'Search APS', 'bulgarisation-for-woocommerce' ), 
			'select' => __( 'Select', 'bulgarisation-for-woocommerce' ), 
			'noResult' => __( 'No results was found for this city', 'bulgarisation-for-woocommerce' ),
			'noOptions' => __( 'Start typing APS', 'bulgarisation-for-woocommerce' ), 
			'apsLocator' => __( 'APS locator', 'bulgarisation-for-woocommerce' ),
			'toAPS' => __( 'To APS: ', 'bulgarisation-for-woocommerce' ),
		);
	}

	public static function load_lockers() {
		self::$container = woo_bg()->container();
		$args = [];
		$raw_state = sanitize_text_field( $_POST['state'] );
		$states = self::$container[ Client::PIGEON_CITIES ]->get_regions();
		$state = $states[ $raw_state ];
		$raw_city = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['city'] ) );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) || !$raw_city ) {
			$args[ 'status' ] = 'invalid-city';
			
			if ( !$state ) {
				$args[ 'error' ] = __( 'Please select region.', 'bulgarisation-for-woocommerce' );
			} elseif ( empty( $raw_city ) ) {
				$args[ 'error' ] = sprintf( __( 'Please enter a city.', 'bulgarisation-for-woocommerce' ), $raw_city, $state );
			} else {
				$args[ 'error' ] = sprintf( __( '%s is not found in %s region.', 'bulgarisation-for-woocommerce' ), $raw_city, $state );
			}
		} else {
			$lockers = self::$container[ Client::PIGEON_LOCKERS ]->get_lockers( $cities_data['cities'][ $cities_data['city_key'] ]['id'] );

			if ( empty( $lockers ) ) {
				$lockers = [];
				$args[ 'error' ] = sprintf( __( 'No lockers were found at %s.', 'bulgarisation-for-woocommerce' ), $raw_city );
			} else {
				$lockers = $lockers;
			}

			$args[ 'lockers' ] = apply_filters( 'woo_bg/shipping_method/pigeon/lockers', $lockers );
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}
}
