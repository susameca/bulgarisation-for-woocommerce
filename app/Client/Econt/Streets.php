<?php
namespace Woo_BG\Client\Econt;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Streets {
	const STREETS_ENDPOINT = 'Nomenclatures/NomenclaturesService.getStreets.json';

	private $streets = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_streets_by_city( $city_id ) {
		if ( ! is_dir( $this->container[ Client::ECONT ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::ECONT ]::CACHE_FOLDER );
		}

		$streets_file = $this->container[ Client::ECONT ]::CACHE_FOLDER . 'streets-' . $city_id . '.json';
		$streets = Cache::get_file( $streets_file );

		if ( !$streets ) {
			$api_call = $this->container[ Client::ECONT ]->api_call( self::STREETS_ENDPOINT, array( 'cityID' => $city_id ) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::ECONT ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['streets'] ) ) {
						$streets = wp_json_encode( $api_call );
						
						Cache::put_to_file( $streets_file, $streets );
					}
				}
			}
		}

		$streets = json_decode( $streets, 1 );

		$this->set_streets( $streets, $city_id );

		return $streets;
	}

	//Getters
	public function get_streets_by_city( $city_id ) {
		if ( empty( $this->streets[ $city_id ] ) ) {
			$this->load_streets_by_city( $city_id );
		}

		return $this->streets[ $city_id ];
	}

	//Setters
	private function set_streets( $streets, $city_id ) {
		$this->streets[ $city_id ] = $streets;
	}
}
