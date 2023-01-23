<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Hubs {
    const HUBS_ENDPOINT = 'get_hubs';

    private $all_hubs = [];
    private $hubs = [];
    private $container;
    private $method = 'GET';

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_hubs( $city_zip, $country_id = '100' ) {
		$this->load_all_hubs( $country_id );

		$hubs = [];

		foreach ( $this->all_hubs as $hub ) {
			if ( $hub['zip'] === $city_zip ) {
				$hubs[] = $hub;
			}
		}

		$this->set_hubs( $city_zip, $hubs );

		return $hubs;
	}

	protected function load_all_hubs( $country_id = '100' ) {
		if ( ! empty( $this->all_hubs ) ) {
			return;
		}

		if ( ! is_dir( $this->container[ Client::CVC ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::CVC ]::CACHE_FOLDER );
		}

		$hubs_file = $this->container[ Client::CVC ]::CACHE_FOLDER . 'hubs.json';
		$hubs = null;

		if ( file_exists( $hubs_file ) ) {
			$hubs = file_get_contents( $hubs_file );
		}

		if ( !$hubs ) {
			$api_call = $this->container[ Client::CVC ]->api_call( self::HUBS_ENDPOINT, array( 'country_id' => $country_id ), $this->method );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['hubs'] ) ) {
						$hubs = json_encode( $api_call['hubs'] );
						
						file_put_contents( $hubs_file, $hubs );
					}
				}
			}
		}

		$hubs = json_decode( $hubs, 1 );

		$this->set_all_hubs( $hubs );
	}


	//Getters
	public function get_hubs( $city_zip, $country_id = '100' ) {
		if ( empty( $this->hubs[ $city_zip ] ) ) {
			$this->load_hubs( $city_zip );
		}

		return $this->hubs[ $city_zip ];
	}


	//Setters
	private function set_hubs( $city_zip, $hubs ) {
		$this->hubs[ $city_zip ] = $hubs;
	}

	private function set_all_hubs( $hubs ) {
		$this->all_hubs = $hubs;
	}

	public function get_formatted_hubs( $city ) {
		$shops = [];
		
		if ( $zip = $this->container[ Client::CVC_CITIES ]->get_city_zip_by_id( str_replace( 'cityID-', '', $city ) ) ) {
			$hubs = $this->get_hubs( $zip );

			foreach ( $hubs as $hub ) {
				$data = [
					'id' => 'hubID-' . $hub['id'],
					'label' => $hub['name_bg'],
				];

				$shops[ 'hubID-' . $hub['id'] ] = $data;
			}
		}

		return $shops;
	}
}
