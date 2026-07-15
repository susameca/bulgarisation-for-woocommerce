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
		$query = self::normalize_api_query( sanitize_text_field( $_POST['query'] ) );

		$raw_city = Transliteration::latin2cyrillic( sanitize_text_field( $_POST['city'] ) );
		$raw_state = sanitize_text_field( $_POST['state'] );
		$cities_data = self::$container[ Client::PIGEON_CITIES ]->get_filtered_cities( $raw_city, $raw_state );

		if ( in_array( $cities_data['city'], $cities_data['cities_only_names'] ) ) {
			$city_id = $cities_data['cities_search_names'][ $cities_data['city_key'] ][ 'id' ];
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
			$city_id = $cities_data['cities_search_names'][ $cities_data['city_key'] ][ 'id' ];

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
			$streets = self::rank_names( $streets, $query );
		} else {
			$streets = [];
		}

		return $streets;
	}

	private static function normalize_api_query( $query ) {
		$query = woo_bg_strip_street_prefix( trim( (string) $query ) );
		$query = Transliteration::latin2cyrillic( $query );

		return trim( preg_replace( '/[\s\p{Zs}]+/u', ' ', $query ) );
	}

	private static function normalize_match_text( $text ) {
		$text = woo_bg_strip_street_prefix( trim( (string) $text ) );
		$text = mb_strtolower( $text );
		$text = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );

		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	private static function rank_names( $names, $query ) {
		$query = self::normalize_match_text( $query );

		if ( $query === '' ) {
			return $names;
		}

		$tokens = explode( ' ', $query );
		$ranked = [];
		$order = 0;

		foreach ( $names as $key => $name ) {
			$normalized_name = self::normalize_match_text( $name );
			$position = mb_strpos( $normalized_name, $query );
			$all_tokens_match = true;

			foreach ( $tokens as $token ) {
				if ( mb_strpos( $normalized_name, $token ) === false ) {
					$all_tokens_match = false;
					break;
				}
			}

			$rank = $normalized_name === $query ? 0 : ( $position === 0 ? 1 : ( $position !== false ? 2 : ( $all_tokens_match ? 3 : 4 ) ) );
			$ranked[] = [ 'key' => $key, 'name' => $name, 'rank' => $rank, 'order' => $order++ ];
		}

		usort( $ranked, function( $a, $b ) {
			return $a['rank'] === $b['rank'] ? $a['order'] <=> $b['order'] : $a['rank'] <=> $b['rank'];
		} );

		$sorted = [];
		foreach ( $ranked as $match ) {
			$sorted[ $match['key'] ] = $match['name'];
		}

		return $sorted;
	}
}
