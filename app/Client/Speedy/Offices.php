<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Offices {
    const OFFICES_ENDPOINT = 'location/office/';

    private $offices = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_offices( $city_id ) {
		if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
		}

		$offices_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'offices-' . $city_id . '.json';
		$offices = '';

		if ( file_exists( $offices_file ) ) {
			$offices = file_get_contents( $offices_file );
		}

		if ( !$offices ) {
			$api_call = $this->container[ Client::SPEEDY ]->api_call( self::OFFICES_ENDPOINT, array( 
				'siteId' => $city_id, 
			) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::SPEEDY ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['offices'] ) ) {
						$offices = json_encode( $api_call );
						
						file_put_contents( $offices_file, $offices );
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

		return array( 'shops' => $shops, 'aps' => $aps );
	}
}