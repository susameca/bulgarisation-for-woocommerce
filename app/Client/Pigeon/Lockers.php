<?php
namespace Woo_BG\Client\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Lockers {
    const LOCKERS_ENDPOINT = '/offices';

    private $lockers = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_lockers( $city_id, $country_code = 'BG' ) {
		if ( ! is_dir( $this->container[ Client::PIGEON ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::PIGEON ]::CACHE_FOLDER );
		}

		$lockers_file = $this->container[ Client::PIGEON ]::CACHE_FOLDER . 'lockers-' . $city_id . '.json';
		$lockers = File::get_file( $lockers_file );

		if ( !$lockers ) {
			$args = apply_filters( 'woo_bg/pigeon/lockers/api_call_args', array(
				'city_id' => $city_id,
				'type' => 'locker',
			), $city_id, $country_code );
			
			$page = 1;
			$all_lockers = [];
			$data = $this->get_page( $page, $args );
			
			if ( isset( $data['success'] ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$all_lockers = array_merge( $all_lockers, $data['data'] );
				$current_page = $data['meta']['current_page'];
				$total_pages = $data['meta']['last_page'];

				while ( $current_page < $total_pages ) {
					$page++;
					$data = $this->get_page( $page, $args );

					if ( isset( $data['success'] ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
						$all_lockers = array_merge( $all_lockers, $data['data'] );
						$current_page = $data['meta']['current_page'];
						$total_pages = $data['meta']['last_page'];
					} else {
						break;
					}
				}
			}

			if ( !empty( $all_lockers ) ) {
				$lockers = wp_json_encode( $all_lockers );
				
				File::put_to_file( $lockers_file, $lockers );
			}
		}

		$lockers = json_decode( $lockers, 1 );

		$this->set_lockers( $city_id, $lockers );

		return $lockers;
	}

	//Getters
	public function get_page( $page = 1, $args = array() ) {
		return $this->container[ Client::PIGEON ]->api_call( self::LOCKERS_ENDPOINT, array_merge( $args, [
			'page' => $page,
		] ) );
	}

	public function get_lockers( $city_id, $country_code = 'BG' ) {
		if ( empty( $this->lockers[ $city_id ] ) ) {
			$this->load_lockers( $city_id, $country_code );
		}

		return $this->lockers[ $city_id ];
	}

	//Setters
	private function set_lockers( $city_id, $lockers ) {
		$this->lockers[ $city_id ] = $lockers;
	}

	public function get_formatted_lockers( $city, $country_code = 'BG' ) {
		$lockers = $this->get_lockers( str_replace( 'cityID-', '', $city ), $country_code );
		$data = [];

		if ( !empty( $lockers ) ) {
			foreach ( $lockers as $locker ) {
				$data[ 'lockerID-' . $locker['id']] = [
					'id' => 'lockerID-' . $locker['id'],
					'label' => $locker['name'] . ' (' . $locker['address'] . ')',
				];
			}
		}

		return $data;
	}
}
