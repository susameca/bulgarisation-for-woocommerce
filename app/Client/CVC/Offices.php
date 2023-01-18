<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Offices {
    const OFFICES_ENDPOINT = 'get_offices';
    const HUBS_ENDPOINT = 'get_hubs';

    private $all_offices = [];
    private $offices = [];
    private $hubs = [];
    private $client, $container;
    private $method = 'GET';

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_offices( $city_zip, $country_id = '100' ) {
		$this->load_all_offices( $country_id );

		$offices = [];

		foreach ( $this->all_offices as $office ) {
			if ( $office['zip'] === $city_zip ) {
				$offices[] = $office;
			}
		}

		$this->set_offices( $city_zip, $offices );

		return $offices;
	}

	protected function load_all_offices( $country_id = '100' ) {
		if ( ! empty( $this->all_offices ) ) {
			return;
		}

		if ( ! is_dir( $this->container[ Client::CVC ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::CVC ]::CACHE_FOLDER );
		}

		$offices_file = $this->container[ Client::CVC ]::CACHE_FOLDER . 'offices.json';
		$offices = null;

		if ( file_exists( $offices_file ) ) {
			$offices = file_get_contents( $offices_file );
		}

		if ( !$offices ) {
			$api_call = $this->container[ Client::CVC ]->api_call( self::OFFICES_ENDPOINT, array( 'country_id' => $country_id ), $this->method );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['offices'] ) ) {
						$offices = json_encode( $api_call['offices'] );
						
						file_put_contents( $offices_file, $offices );
					}
				}
			}
		}

		$offices = json_decode( $offices, 1 );

		$this->set_all_offices( $offices );
	}

	//Getters
	public function get_offices( $city_zip, $country_id = '100' ) {
		if ( empty( $this->offices[ $city_zip ] ) ) {
			$this->load_offices( $city_zip );
		}

		return $this->offices[ $city_zip ];
	}

	public function get_all_offices( $country_id ) {
		if ( empty( $this->all_offices ) ) {
			$this->load_all_offices( $country_id );
		}

		return $this->all_offices;
	}

	//Setters
	private function set_offices( $city_zip, $offices ) {
		$this->offices[ $city_zip ] = $offices;
	}

	private function set_all_offices( $offices ) {
		$this->all_offices = $offices;
	}

	public function get_formatted_offices( $city ) {
		$shops = [];
		
		if ( $zip = $this->container[ Client::CVC_CITIES ]->get_city_zip_by_id( str_replace( 'cityID-', '', $city ) ) ) {
			$offices = $this->get_offices( $zip );

			foreach ( $offices as $office ) {
				$data = [
					'id' => 'officeID-' . $office['id'],
					'label' => $office['name_bg'],
				];

				$shops[ 'officeID-' . $office['id'] ] = $data;
			}
		}

		return $shops;
	}
}
