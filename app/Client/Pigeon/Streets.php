<?php
namespace Woo_BG\Client\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Streets {
	const STREETS_ENDPOINT = '/cities/{cityID}/streets';

	private $streets = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_streets_by_city( $city_id, $query = '' ) {
		if ( ! is_dir( $this->container[ Client::PIGEON ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::PIGEON ]::CACHE_FOLDER );
		}

		$hash = md5( $query );
		$streets_file = $this->container[ Client::PIGEON ]::CACHE_FOLDER . 'streets-' . $city_id . '-' . $hash . '.json';
		$streets = File::get_file( $streets_file );

		if ( !$streets ) {
			$page = 1;
			$all_streets = [];
			$data = $this->get_page( $city_id, $page, $query );
			
			if ( $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$all_streets = array_merge( $all_streets, $data['data'] );
				$current_page = $data['meta']['current_page'];
				$total_pages = $data['meta']['last_page'];

				while ( $current_page < $total_pages ) {
					$page++;
					$data = $this->get_page( $city_id, $page, $query );

					if ( $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
						$all_streets = array_merge( $all_streets, $data['data'] );
						$current_page = $data['meta']['current_page'];
						$total_pages = $data['meta']['last_page'];
					} else {
						break;
					}
				}
			}

			if ( !empty( $all_streets ) ) {
				$streets = wp_json_encode( $all_streets );
				
				File::put_to_file( $streets_file, $streets );
			}
		}

		$streets = json_decode( $streets, 1 );

		$this->set_streets( $streets, $city_id, $hash );

		return $streets;
	}

	//Getters
	public function get_page( $city_id, $page = 1, $query = '' ) {
		$endpoint = str_replace( '{cityID}', $city_id, self::STREETS_ENDPOINT );
		$args = [
			'city_id' => $city_id,
			'page' => $page,
		];

		if ( !empty( $query ) ) {
			$args['name'] = $query;
		}

		return $this->container[ Client::PIGEON ]->api_call( $endpoint, $args );
	}

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
			$formatted[ 'street-' . $street['id'] ] = self::format_type( $street['type'] ) . " " . woo_bg_title_case_bg( $street['name'] );
		}
		
		return $formatted;
	}

	public static function format_type( $type ) {
		if ( !$type ) {
			return '';
		}

		return str_replace( 
			[ 'улица', 'булевард', 'площад', 'жилищен комплекс', 'квартал', 'район', 'курортен комплекс'], 
			[ 'ул.', 'бул.', 'пл.', 'ж.к.', 'кв.', 'р-н', 'к.к.'], 
			$type 
		);
	}
}
