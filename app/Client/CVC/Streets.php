<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Streets {
	const STREETS_ENDPOINT = 'search_streets';

	private $streets = [];
	private $container;
	private $method = 'GET';

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_streets_by_city( $city_id, $query ) {
		if ( ! is_dir( $this->container[ Client::CVC ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::CVC ]::CACHE_FOLDER );
		}

		$hash = md5( $query );
		$streets_file = $this->container[ Client::CVC ]::CACHE_FOLDER . 'streets-' . $city_id . '-' . $hash . '.json';
		$streets = Cache::get_file( $streets_file );


		if ( !$streets ) {
			$api_call = $this->container[ Client::CVC ]->api_call( self::STREETS_ENDPOINT, array( 'country_id' => '100', 'city_id' => $city_id, 'search_for' => urlencode( $query ) ), $this->method );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['streets'] ) ) {
						$streets = wp_json_encode( $api_call );
						
						Cache::put_to_file( $streets_file, $streets );
					}
				}
			}
		}

		$streets = json_decode( $streets, 1 );

		$this->set_streets( $streets, $city_id, $hash );

		return $streets;
	}

	//Getters
	public function get_streets_by_city( $city_id, $query ) {
		$hash = md5( $query );

		if ( empty( $this->streets[ $city_id ][ $hash ] ) ) {
			$this->load_streets_by_city( $city_id, $query );
		}

		return $this->streets[ $city_id ][ $hash ];
	}

	//Setters
	private function set_streets( $streets, $city_id, $hash ) {
		$this->streets[ $city_id ][ $hash ] = $streets;
	}

	public function format_streets( $streets ) {
		$formatted = [];

		foreach ( $streets as $street ) {
			$formatted[ 'street-' . $street['id'] ] = $street['name_bg'];
		}
		
		return $formatted;
	}
}
