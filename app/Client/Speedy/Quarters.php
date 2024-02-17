<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Quarters {
	const QUARTERS_ENDPOINT = 'location/complex/';

	private $quarters = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_quarters_by_city( $city_id, $query ) {
		if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
		}

		$hash = md5( $query );
		$quarters_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'quarters-' . $city_id . '-' . $hash . '.json';
		$quarters = Cache::get_file( $quarters_file );

		if ( !$quarters ) {
			$api_call = $this->container[ Client::SPEEDY ]->api_call( self::QUARTERS_ENDPOINT, array( 'siteId' => $city_id, 'name' => $query ) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::SPEEDY ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['complexes'] ) ) {
						$quarters = wp_json_encode( $api_call['complexes'] );
						
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
			$formatted[ 'qtr-' . $qtr['id'] ] = $qtr['type'] . " " . $qtr['name'];
		}

		return $formatted;
	}
}
