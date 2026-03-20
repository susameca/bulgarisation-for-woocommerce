<?php
namespace Woo_BG\Shipping\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Address {
	private static $container = null;

	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_pigeon_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_pigeon_load_streets', array( __CLASS__, 'load_streets' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_pigeon_load_streets', array( __CLASS__, 'load_streets' ) );

		add_action( 'wp_ajax_woo_bg_pigeon_search_address', array( __CLASS__, 'search_address' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_pigeon_search_address', array( __CLASS__, 'search_address' ) );
	}

	public static function delivery_with_pigeon_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'address' ) {
				echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-pigeon-shipping-to--address" class="woo-bg-additional-fields" data-type="address"></div>', [
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
			'searchAddress' => __( 'Search address', 'bulgarisation-for-woocommerce' ), 
			'select' => __( 'Select', 'bulgarisation-for-woocommerce' ), 
			'noResult' => __( 'No results was found for this city', 'bulgarisation-for-woocommerce' ), 
			'noOptions' => __( 'Start typing street or quarter', 'bulgarisation-for-woocommerce' ), 
			'streetNumber' => __( 'Street number', 'bulgarisation-for-woocommerce' ), 
			'blVhEt' => __( 'bl. vh. et.', 'bulgarisation-for-woocommerce' ),
			'mysticQuarter' => __( 'Street or quarter', 'bulgarisation-for-woocommerce' ),
		);
	}

	public static function search_address() {
		self::$container = woo_bg()->container();
		$args = [];
		$query = woo_bg_strip_street_prefix( sanitize_text_field( $_POST['query'] ) );
		$query = Transliteration::latin2cyrillic( $query );

		$raw_city = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['city'] ) );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];
			$args[ 'streets' ] = woo_bg_return_array_for_select( self::get_streets_for_query( $city_id, $query ), 0, array( 'type' => 'streets' ) );
			$args[ 'has_any' ] = !empty( self::get_streets_for_query( $city_id, '' ) );
			$args[ 'status' ] = 'valid-city';
		} else {
			$args[ 'cities' ] = woo_bg_return_array_for_select( $cities_data['cities_only_names'], 1, array( 'type' => 'city' ) );
		}

		wp_send_json_success( $args );
		wp_die();
	}

	public static function load_streets() {
		self::$container = woo_bg()->container();
		$args = [];

		$raw_city = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['city'] ) );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $raw_city, $raw_state) ;

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$args[ 'cities' ] = woo_bg_return_array_for_select( $cities_data['cities_only_names_dropdowns'], 1, array( 'type'=>'city' ) );
			$args[ 'status' ] = 'invalid-city';
		} else {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

			$args[ 'streets' ] = woo_bg_return_array_for_select( self::get_streets_for_query( $city_id, '' ), 1, array( 'type' => 'streets' ) );
			$args[ 'has_any' ] = !empty( self::get_streets_for_query( $city_id, '' ) );
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}

	public static function get_streets_for_query( $city_id, $query ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}
		
		$streets = self::$container[ Client::PIGEON_STREETS ]->get_streets_by_city( $city_id, $query );

		if ( !empty( $streets ) ) {
			$streets = self::$container[ Client::PIGEON_STREETS ]->format_streets( $streets );
		} else {
			$streets = [];
		}

		return $streets;
	}
}
