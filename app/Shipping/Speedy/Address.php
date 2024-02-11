<?php
namespace Woo_BG\Shipping\Speedy;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Address {
	private static $container = null;

	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_speedy_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_speedy_load_streets', array( __CLASS__, 'load_streets' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_speedy_load_streets', array( __CLASS__, 'load_streets' ) );

		add_action( 'wp_ajax_woo_bg_speedy_search_address', array( __CLASS__, 'search_address' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_speedy_search_address', array( __CLASS__, 'search_address' ) );
	}

	public static function delivery_with_speedy_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'address' ) {
				echo '<div data-cache="' . rand() . '" id="woo-bg-speedy-shipping-to--address" class="woo-bg-additional-fields" data-type="address"></div>';
			}
		}
	}

	public static function get_i18n() {
		return array(
			'selected' => __( 'Selected', 'woo-bg' ),
			'choose' => __( 'Choose', 'woo-bg' ),
			'searchAddress' => __( 'Search address', 'woo-bg' ), 
			'select' => __( 'Select', 'woo-bg' ), 
			'noResult' => __( 'No results was found for this city', 'woo-bg' ), 
			'noOptions' => __( 'Start typing street or quarter', 'woo-bg' ), 
			'streetNumber' => __( 'Street number', 'woo-bg' ), 
			'blVhEt' => __( 'bl. vh. et.', 'woo-bg' ),
			'mysticQuarter' => __( 'Street or quarter', 'woo-bg' ),
		);
	}

	public static function search_address() {
		self::$container = woo_bg()->container();
		$args = [];
		$query = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['query'] ) );
		$raw_city = sanitize_text_field( $_POST['city'] );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

			$streets = [];
			$streets = woo_bg_return_array_for_select( self::get_streets_for_query( $city_id, $query ), 0, array( 'type' => 'streets' ) );
			$quarters = woo_bg_return_array_for_select( self::get_quarters_for_query( $city_id, $query ), 0, array( 'type' => 'quarters' ) );

			$args[ 'streets' ] = self::merge_options( $streets, $quarters );
			$args[ 'has_any' ] = !empty( array_merge( self::get_quarters_for_query( $city_id, ' ' ), self::get_streets_for_query( $city_id, ' ' ) ) );
		} else {
			$args[ 'cities' ] = woo_bg_return_array_for_select( $cities_data['cities_only_names'], 1, array( 'type' => 'city' ) );
		}

		wp_send_json_success( $args );
		wp_die();
	}

	public static function load_streets() {
		self::$container = woo_bg()->container();
		$args = [];
		$raw_city = sanitize_text_field( $_POST['city'] );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$cities_data = self::$container[ Client::SPEEDY_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$args[ 'cities' ] = woo_bg_return_array_for_select( $cities_data['cities_only_names_dropdowns'], 1, array( 'type'=>'city' ) );
			$args[ 'status' ] = 'invalid-city';
		} else {
			$city_id = $cities_data['cities'][ $cities_data['city_key'] ][ 'id' ];

			$streets = woo_bg_return_array_for_select( self::get_streets_for_query( $city_id, ' ' ), 1, array( 'type' => 'streets' ) );
			$quarters = woo_bg_return_array_for_select( self::get_quarters_for_query( $city_id, ' ' ), 1, array( 'type' => 'quarters' ) );

			$args[ 'streets' ] = self::merge_options( $streets, $quarters );
			$args[ 'has_any' ] = ( !empty( self::get_quarters_for_query( $city_id, ' ' ) ) || !empty( self::get_streets_for_query( $city_id, ' ' ) ) );
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}

	public static function get_streets_for_query( $city_id, $query ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}
		
		$streets = self::$container[ Client::SPEEDY_STREETS ]->get_streets_by_city( $city_id, $query );

		if ( !empty( $streets ) ) {
			$streets = self::$container[ Client::SPEEDY_STREETS ]->format_streets( $streets );
		} else {
			$streets = [];
		}

		return $streets;
	}

	public static function get_quarters_for_query( $city_id, $query  ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}
		
		$quarters = self::$container[ Client::SPEEDY_QUARTERS ]->get_quarters_by_city( $city_id, $query );
		
		if ( !empty( $quarters ) ) {
			$quarters = self::$container[ Client::SPEEDY_QUARTERS ]->format_quarters( $quarters );
		} else {
			$quarters = [];
		}

		return $quarters;
	}

	public static function merge_options( $streets, $quarters ) {
		$test_streets = array_map( function( $street ) {
  			return mb_strtolower( str_replace( ' ', '', $street['label'] ) );
		}, $streets );

		foreach ( $quarters as $quarter ) {
			$test_label = mb_strtolower( str_replace( ' ', '', $quarter['label'] ) );

			if ( in_array( $test_label, $test_streets ) ) {				
				$key = array_search( $test_label, $test_streets );
				$streets[ $key ] = $quarter;
			} else {
				$streets[] = $quarter;
			}
		}

		return $streets;
	}
}
