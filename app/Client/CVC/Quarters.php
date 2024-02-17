<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Quarters {
	const QUARTERS_ENDPOINT = 'search_qts';

	private $quarters = [];
	private $container;
	private $method = 'GET';

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_quarters_by_city( $city_id, $query ) {
		if ( ! is_dir( $this->container[ Client::CVC ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::CVC ]::CACHE_FOLDER );
		}

		$hash = md5( $query );
		$quarters_file = $this->container[ Client::CVC ]::CACHE_FOLDER . 'quarters-' . $city_id . '-' . $hash . '.json';
		$quarters = Cache::get_file( $quarters_file );

		if ( !$quarters ) {
			$api_call = $this->container[ Client::CVC ]->api_call( self::QUARTERS_ENDPOINT, array( 'country_id' => '100', 'city_id' => $city_id, 'search_for' => urlencode( $query ) ), $this->method );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['qts'] ) ) {
						$quarters = wp_json_encode( $api_call );
						
						Cache::put_to_file( $quarters_file, $quarters );
					}
				}
			}
		}

		$quarters = json_decode( $quarters, 1 );

		$this->set_quarters( $quarters, $city_id, $hash );

		return $quarters;
	}

	//Getters
	public function get_quarters_by_city( $city_id, $query ) {
		$hash = md5( $query );

		if ( empty( $this->quarters[ $city_id ][ $hash ] ) ) {
			$this->load_quarters_by_city( $city_id, $query );
		}

		return $this->quarters[ $city_id ][ $hash ];
	}

	//Setters
	private function set_quarters( $quarters, $city_id, $hash ) {
		$this->quarters[ $city_id ][ $hash ] = $quarters;
	}

	public function format_quarters( $quarters ) {
		$formatted = [];

		foreach ( $quarters as $qtr ) {
			$formatted[ 'qtr-' . $qtr['id'] ] = $qtr['name_bg'];
		}

		return $formatted;
	}
}
