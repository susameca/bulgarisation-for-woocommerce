<?php
namespace Woo_BG\Shipping\Econt;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Address {
	private static $container = null;

	public function __construct() {
		add_filter( 'woocommerce_after_shipping_rate', array( __CLASS__, 'delivery_with_econt_render_form_button' ), 20, 2 );

		add_action( 'wp_ajax_woo_bg_econt_load_streets', array( __CLASS__, 'load_streets' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_econt_load_streets', array( __CLASS__, 'load_streets' ) );

		add_action( 'wp_ajax_woo_bg_econt_search_address', array( __CLASS__, 'search_address' ) );
		add_action( 'wp_ajax_nopriv_woo_bg_econt_search_address', array( __CLASS__, 'search_address' ) );
	}

	public static function delivery_with_econt_render_form_button( $method, $index ) {
		if ( $method->get_method_id() === Method::METHOD_ID ) {
			if ( $method->meta_data['delivery_type'] === 'address' ) {
				echo wp_kses_post( '<div data-cache="' . wp_rand() . '" id="woo-bg-econt-shipping-to--address" class="woo-bg-additional-fields" data-type="address"></div>' );
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
		);
	}

	public static function search_address() {
		self::$container = woo_bg()->container();
		$args = [];
		$query = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['query'] ) );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$states = woo_bg_return_bg_states();
		$state = $states[ $raw_state ];
		$raw_city = sanitize_text_field( $_POST['city'] );
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $raw_city, $state );

		if ( in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$streets = woo_bg_return_array_for_select( 
				self::get_streets_for_query( $query, $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type'=>'streets' ) 
			);

			$quarters = woo_bg_return_array_for_select( 
				self::get_quarters_for_query( $query, $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type'=>'quarters' ) 
			);

			$args[ 'streets' ] = array_merge( $streets, $quarters );
		} else {
			$args[ 'cities' ] = woo_bg_return_array_for_select( self::get_cities_for_query( $query, $cities_data['cities_only_names'] ), 1 );
		}

		wp_send_json_success( $args );

		wp_die();
	}

	public static function load_streets() {
		self::$container = woo_bg()->container();
		$args = [];

		$raw_state = sanitize_text_field( $_POST['state'] );
		$states = woo_bg_return_bg_states();
		$state = $states[ $raw_state ];

		$raw_city = sanitize_text_field( $_POST['city'] );
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $raw_city, $state );

		if ( !in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$args[ 'cities' ] = woo_bg_return_array_for_select( $cities_data['cities_only_names_dropdowns'], 1, array( 'type'=>'city' ) );
			$args[ 'status' ] = 'invalid-city';
		} else {
			$streets = woo_bg_return_array_for_select( 
				self::get_streets_for_query( '', $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type' => 'streets' ) 
			);

			$quarters = woo_bg_return_array_for_select( 
				self::get_quarters_for_query( '', $cities_data['city_key'], $cities_data['cities'] ), 
				1, 
				array( 'type' => 'quarters' ) 
			);

			$args[ 'streets' ] = array_merge( $streets, $quarters );
			$args[ 'status' ] = 'valid-city';
		}

		wp_send_json_success( $args );

		wp_die();
	}

	public static function get_streets_for_query( $query, $city_key, $cities ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}
		
		$streets = self::$container[ Client::ECONT_STREETS ]->get_streets_by_city( $cities[ $city_key ]['id'] );
		$streets = ( !empty( $streets['streets'] ) ) ? $streets['streets'] : [];

		if ( $cities[ $city_key ]['country']['code2'] == 'BG' ) {
			$streets_only_names = array_map( function( $street ) {
				return $street['name'];
			}, $streets );
		} else {
			$streets_only_names = array_map( function( $street ) {
				return $street['nameEn'];
			}, $streets );
		}

		if ( !empty( $query ) ) {
			$streets_only_names = array_filter( $streets_only_names, function( $street ) use ( $query ) {
				if ( strpos( mb_strtolower( $street ), mb_strtolower( $query ) ) !== false ) {
					return true;
				}
			} );

			$streets_only_names = array_values( $streets_only_names );
		}

		return $streets_only_names;
	}

	public static function get_quarters_for_query( $query, $city_key, $cities ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}

		$quarters = self::$container[ Client::ECONT_QUARTERS ]->get_quarters_by_city( $cities[ $city_key ]['id'] );
		$quarters = ( !empty( $quarters['quarters'] ) ) ? $quarters['quarters'] : [];

		$quarters_only_names = array_map( function( $quarter ) {
			return $quarter['name'];
		}, $quarters );

		if ( !empty( $query ) ) {
			$quarters_only_names = array_filter( $quarters_only_names, function( $quarter ) use ( $query ) {
				if ( strpos( mb_strtolower( $quarter ), mb_strtolower( $query ) ) !== false ) {
					return true;
				}
			} );

			$quarters_only_names = array_values( $quarters_only_names );
		}

		return $quarters_only_names;
	}

	public static function get_cities_for_query( $query, $cities ) {
		$cities_filtered = [];

		if ( !empty( $query ) ) {
			$cities_filtered = array_filter( $cities, function( $city ) use ( $query ) {
				if ( strpos( mb_strtolower( $city ), mb_strtolower( $query ) ) !== false ) {
					return true;
				}
			} );

			$cities_filtered = array_values( $cities_filtered );
		}

		return $cities_filtered;
	}
}
