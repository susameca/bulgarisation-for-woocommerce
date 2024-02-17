<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Streets {
	const STREETS_ENDPOINT = 'location/street';

	private $streets = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_streets_by_city( $city_id, $query ) {
		if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
		}

		$hash = md5( $query );
		$streets_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'streets-' . $city_id . '-' . $hash . '.json';
		$streets = '';

		if ( file_exists( $streets_file ) ) {
			$streets = file_get_contents( $streets_file );
		}

		if ( !$streets ) {
			$api_call = $this->container[ Client::SPEEDY ]->api_call( self::STREETS_ENDPOINT, array( 'siteId' => $city_id, 'name' => $query ) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::SPEEDY ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['streets'] ) ) {
						$streets = wp_json_encode( $api_call['streets'] );
						
						file_put_contents( $streets_file, $streets );
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
			$formatted[ 'street-' . $street['id'] ] = $street['type'] . " " . $street['name'];
		}
		
		return $formatted;
	}
}
