<?php
namespace Woo_BG\Client\Econt;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Countries {
	const COUNTRIES_ENDPOINT = 'Nomenclatures/NomenclaturesService.getCountries.json';

	private $countries = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_countries() {
		if ( ! is_dir( $this->container[ Client::ECONT ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::ECONT ]::CACHE_FOLDER );
		}

		$countries_file = $this->container[ Client::ECONT ]::CACHE_FOLDER . 'countries.json';
		$countries = '';

		if ( file_exists( $countries_file ) ) {
			$countries = file_get_contents( $countries_file );
		}

		if ( !$countries ) {
			$api_call = $this->container[ Client::ECONT ]->api_call( self::COUNTRIES_ENDPOINT, array( 'GetCountriesRequest' => '' ) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::ECONT ]::validate_access( $api_call ) ) {
					$countries = wp_json_encode( $api_call );
					
					file_put_contents( $countries_file, $countries );
				}
			}
		}

		$countries = json_decode( $countries, 1 );

		$this->set_countries( $countries );

		return $countries;
	}

	//Getters
	public function get_countries() {
		if ( empty( $this->countries ) ) {
			$this->load_countries();
		}

		return $this->countries;
	}

	//Setters
	private function set_countries( $countries ) {
		$this->countries = $countries;
	}
}
