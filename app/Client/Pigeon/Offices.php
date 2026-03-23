<?php
namespace Woo_BG\Client\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Offices {
    const OFFICES_ENDPOINT = '/offices';

    private $offices = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_offices( $city_id, $country_code = 'BG' ) {
		if ( ! is_dir( $this->container[ Client::PIGEON ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::PIGEON ]::CACHE_FOLDER );
		}

		$offices_file = $this->container[ Client::PIGEON ]::CACHE_FOLDER . 'offices-' . $city_id . '.json';
		$offices = File::get_file( $offices_file );

		if ( !$offices ) {
			$args = apply_filters( 'woo_bg/pigeon/offices/api_call_args', array(
				'city_id' => $city_id,
				'type' => 'office',
			), $city_id, $country_code );
			
			$page = 1;
			$all_offices = [];
			$data = $this->get_page( $page, $args );
			
			if ( isset( $data['success'] ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$all_offices = array_merge( $all_offices, $data['data'] );
				$current_page = $data['meta']['current_page'];
				$total_pages = $data['meta']['last_page'];

				while ( $current_page < $total_pages ) {
					$page++;
					$data = $this->get_page( $page, $args );

					if ( isset( $data['success'] ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
						$all_offices = array_merge( $all_offices, $data['data'] );
						$current_page = $data['meta']['current_page'];
						$total_pages = $data['meta']['last_page'];
					} else {
						break;
					}
				}
			}

			if ( !empty( $all_offices ) ) {
				$offices = wp_json_encode( $all_offices );
				
				File::put_to_file( $offices_file, $offices );
			}
		}

		$offices = json_decode( $offices, 1 );

		$this->set_offices( $city_id, $offices );

		return $offices;
	}

	//Getters
	public function get_page( $page = 1, $args = array() ) {
		return $this->container[ Client::PIGEON ]->api_call( self::OFFICES_ENDPOINT, array_merge( $args, [
			'page' => $page,
		] ) );
	}

	public function get_offices( $city_id, $country_code = 'BG' ) {
		if ( empty( $this->offices[ $city_id ] ) ) {
			$this->load_offices( $city_id, $country_code );
		}

		return $this->offices[ $city_id ];
	}

	//Setters
	private function set_offices( $city_id, $offices ) {
		$this->offices[ $city_id ] = $offices;
	}

	public function get_formatted_offices( $city, $country_code = 'BG' ) {
		$offices = $this->get_offices( str_replace( 'cityID-', '', $city ), $country_code );
		$data = [];

		if ( !empty( $offices ) ) {
			foreach ( $offices as $office ) {
				$data[ 'officeID-' . $office['id']] = [
					'id' => 'officeID-' . $office['id'],
					'label' => $office['name'] . ' (' . $office['address'] . ')',
				];
			}
		}

		return $data;
	}
}
