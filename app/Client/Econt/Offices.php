<?php
namespace Woo_BG\Client\Econt;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Offices {
    const OFFICES_ENDPOINT = 'Nomenclatures/NomenclaturesService.getOffices.json';

    private $offices = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_offices( $city_id ) {
		if ( ! is_dir( $this->container[ Client::ECONT ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::ECONT ]::CACHE_FOLDER );
		}

		$offices_file = $this->container[ Client::ECONT ]::CACHE_FOLDER . 'offices-' . $city_id . '.json';
		$offices = Cache::get_file( $offices_file );

		if ( !$offices ) {
			$api_call = $this->container[ Client::ECONT ]->api_call( self::OFFICES_ENDPOINT, array( 
				'countryCode' => 'BGR', 
				'cityID' => $city_id, 
			) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::ECONT ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['offices'] ) ) {
						$offices = wp_json_encode( $api_call );
						
						Cache::put_to_file( $offices_file, $offices );
					}
				}
			}
		}

		$offices = json_decode( $offices, 1 );

		$this->set_offices( $city_id, $offices );

		return $offices;
	}

	//Getters
	public function get_offices( $city_id ) {
		if ( empty( $this->offices[ $city_id ] ) ) {
			$this->load_offices( $city_id );
		}

		return $this->offices[ $city_id ];
	}

	//Setters
	private function set_offices( $city_id, $offices ) {
		$this->offices[ $city_id ] = $offices;
	}

	public function get_formatted_offices( $city ) {
		$offices = $this->get_offices( str_replace( 'cityID-', '', $city ) );
		$shops = [];
		$aps = [];

		if ( !empty( $offices['offices'] ) ) {
			foreach ( $offices['offices'] as $office ) {
				$data = [
					'id' => 'officeID-' . $office['code'],
					'label' => $office['name'] . ' (' . $office['address']['fullAddress'] . ')',
				];

				if ( $office['isAPS'] ) {
					$aps[ 'officeID-' . $office['code'] ] = $data;
				} else {
					$shops[ 'officeID-' . $office['code'] ] = $data;
				}
			}
		}

		return array( 'shops' => $shops, 'aps' => $aps );
	}
}
