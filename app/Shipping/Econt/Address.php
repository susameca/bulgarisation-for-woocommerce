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
				echo wp_kses( '<div data-cache="' . wp_rand() . '" id="woo-bg-econt-shipping-to--address" class="woo-bg-additional-fields" data-type="address"></div>', [
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
		);
	}

	public static function search_address() {
		self::$container = woo_bg()->container();
		$args = [];
		$raw_state = sanitize_text_field( $_POST['state'] );
		$country = sanitize_text_field( $_POST[ 'country' ] );
		$query = self::normalize_search_text( sanitize_text_field( $_POST['query'] ), $country === 'BG' );
		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $raw_state ];
		$raw_city = sanitize_text_field( $_POST['city'] );
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $raw_city, $state, $country );

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
		$country = sanitize_text_field( $_POST[ 'country' ] );
		$states = self::$container[ Client::ECONT_CITIES ]->get_regions( $country );
		$state = $states[ $raw_state ];

		$raw_city = sanitize_text_field( $_POST['city'] );
		$cities_data = self::$container[ Client::ECONT_CITIES ]->get_filtered_cities( $raw_city, $state, $country );

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
		
		$city_id = $cities[ $city_key ]['id'];
		$streets = self::$container[ Client::ECONT_STREETS ]->get_streets_by_city( $city_id );
		$streets_only_names = ( !empty( $streets['streets'] ) ) ? self::$container[ Client::ECONT_STREETS ]->format_streets( $streets['streets'], $city_id ) : [];
		unset( $streets );

		return self::filter_and_rank_names( $streets_only_names, $query );
	}

	public static function get_quarters_for_query( $query, $city_key, $cities ) {
		if ( !self::$container ) {
			self::$container = woo_bg()->container();
		}

		$city_id = $cities[ $city_key ]['id'];
		$quarters = self::$container[ Client::ECONT_QUARTERS ]->get_quarters_by_city( $city_id );
		$quarters_only_names = ( !empty( $quarters['quarters'] ) ) ? self::$container[ Client::ECONT_QUARTERS ]->format_quarters( $quarters['quarters'], $city_id ) : [];
		unset( $quarters );

		return self::filter_and_rank_names( $quarters_only_names, $query );
	}

	public static function get_cities_for_query( $query, $cities ) {
		return array_values( self::filter_and_rank_names( $cities, $query ) );
	}

	private static function normalize_search_text( $text, $transliterate = true ) {
		$text = woo_bg_strip_street_prefix( trim( (string) $text ) );

		if ( $transliterate ) {
			$text = Transliteration::latin2cyrillic( $text );
		}

		$text = mb_strtolower( $text );
		$text = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );

		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	private static function filter_and_rank_names( $names, $query ) {
		$query = self::normalize_search_text( $query, false );

		if ( $query === '' ) {
			return $names;
		}

		$tokens = explode( ' ', $query );
		$ranked = [];
		$order = 0;

		foreach ( $names as $key => $name ) {
			$normalized_name = self::normalize_search_text( $name, false );
			$position = mb_strpos( $normalized_name, $query );
			$all_tokens_match = true;

			foreach ( $tokens as $token ) {
				if ( mb_strpos( $normalized_name, $token ) === false ) {
					$all_tokens_match = false;
					break;
				}
			}

			if ( $position === false && !$all_tokens_match ) {
				continue;
			}

			$rank = $normalized_name === $query ? 0 : ( $position === 0 ? 1 : ( $position !== false ? 2 : 3 ) );
			$ranked[] = [ 'key' => $key, 'name' => $name, 'rank' => $rank, 'order' => $order++ ];
		}

		usort( $ranked, function( $a, $b ) {
			return $a['rank'] === $b['rank'] ? $a['order'] <=> $b['order'] : $a['rank'] <=> $b['rank'];
		} );

		$filtered = [];
		foreach ( $ranked as $match ) {
			$filtered[ $match['key'] ] = $match['name'];
		}

		return $filtered;
	}
}
