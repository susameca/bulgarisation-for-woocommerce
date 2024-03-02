<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Offices {
    const OFFICES_ENDPOINT = 'location/office/';

    private $offices = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_offices( $city_id, $country_id = '100' ) {
		if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
		}

		$offices_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'offices-' . $city_id . '.json';
		$offices = File::get_file( $offices_file );

		if ( !$offices ) {
			$api_call = $this->container[ Client::SPEEDY ]->api_call( self::OFFICES_ENDPOINT, array(
                'countryId' => $country_id,
				'siteId' => $city_id, 
			) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::SPEEDY ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['offices'] ) ) {
						$offices = wp_json_encode( $api_call );
						
						File::put_to_file( $offices_file, $offices );
					}
				}
			}
		}

		$offices = json_decode( $offices, 1 );

		$this->set_offices( $city_id, $offices );

		return $offices;
	}

	//Getters
	public function get_offices( $city_id, $country_id = '100' ) {
		if ( empty( $this->offices[ $city_id ] ) ) {
			$this->load_offices( $city_id, $country_id );
		}

		return $this->offices[ $city_id ];
	}

	//Setters
	private function set_offices( $city_id, $offices ) {
		$this->offices[ $city_id ] = $offices;
	}

	public function get_formatted_offices( $city, $country_id = '100' ) {
		$offices = $this->get_offices( str_replace( 'cityID-', '', $city ), $country_id );
		$shops = [];
		$aps = [];

		if ( !empty( $offices['offices'] ) ) {
			foreach ( $offices['offices'] as $office ) {
				$data = [
					'id' => 'officeID-' . $office['id'],
					'label' => $office['name'] . ' (' . $office['address']['fullAddressString'] . ')',
				];

				if ( $office['type'] === 'APT' ) {
					$aps[ 'officeID-' . $office['id'] ] = $data;
				} else {
					$shops[ 'officeID-' . $office['id'] ] = $data;
				}
			}
		}

		return array( 'shops' => $shops, 'aps' => $aps );
	}
}
