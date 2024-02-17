<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Cities {
	const CITIES_ENDPOINT = 'get_counties';
	const SEARCH_CITIES_ENDPOINT = 'search_cities';

	private $cities = [];
	private $munis = [];
	private $container;
	private $method = 'GET';

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_cities( $country_id = '100' ) {
		if ( ! is_dir( $this->container[ Client::CVC ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::CVC ]::CACHE_FOLDER );
		}

		$cities_file = $this->container[ Client::CVC ]::CACHE_FOLDER . 'cities-' . $country_id . '.json';
		$cities = Cache::get_file( $cities_file );

		if ( !$cities ) {
			$api_call = $this->container[ Client::CVC ]->api_call( self::CITIES_ENDPOINT, array( 'country_id' => $country_id ), $this->method );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['counties'] ) ) {
						$cities = wp_json_encode( $api_call );
						
						Cache::put_to_file( $cities_file, $cities );
					}
				}
			}
		}

		$cities = json_decode( $cities, 1 );

		$this->set_cities( $cities, $country_id );

		return $cities;
	}

	public function get_formatted_cities( $country_id = '100' ) {
		$cities = $this->container[ Client::CVC_CITIES ]->get_cities( $country_id );
		$formatted = [];

		foreach ( $cities['counties'] as $city ) {
			$label = $city['name_bg'];

			$formatted[ 'cityID-' . $city['id'] ] = array(
				'id' => 'cityID-' . $city['id'],
				'label' => $label,
			);
		}

		uasort( $formatted, function( $a, $b ) {
			return strcmp( $a["label"], $b["label"] );
		} );

		return $formatted;
	}

	//Getters
	public function get_cities( $country_id = '100' ) {
		if ( empty( $this->cities[ $country_id ] ) ) {
			$this->load_cities( $country_id );
		}

		return $this->cities[ $country_id ];
	}

	public function search_for_city( $city, $state, $country_id = '100' ) {
		$city = Transliteration::latin2cyrillic( $city );

		$api_call = $this->container[ Client::CVC ]->api_call( self::SEARCH_CITIES_ENDPOINT, array( 'search_for' => $city, 'county_id' => $state, 'country_id' => $country_id ), $this->method );

		if ( $this->container[ Client::CVC ]::validate_access( $api_call ) ) {
			return $api_call['cities'];
		}

		return [];
	}

	public function get_state_name( $state_code, $country_id = '100' ) {
		$states = woo_bg_return_bg_states();

		return $states[ $state_code ];
	}

	public function get_state_id( $state_code, $country_id = '100' ) {
		$state_name = $this->get_state_name( $state_code );
		$cities = $this->get_cities( $country_id )[ 'counties' ];
		$key = array_search( $state_name, array_column( $cities, 'name_bg' ) );

		return $cities[ $key ][ 'id' ];
	}

	public function get_city_zip_by_id( $id, $country_id = '100' ) {
		$cities = $this->get_cities( $country_id )[ 'counties' ];

		foreach ( $cities as $city ) {
			if ( $city['id'] === $id ) {
				$founded_city = $city;
				break;
			}
		}

		if ( !empty( $founded_city ) ) {
			$city = $this->search_for_city( $founded_city['name_bg'], $founded_city['id'], $country_id );

			if ( $city ) {
				return $city[0]['zip'];
			}
		}

		return;
	}

	//Setters
	private function set_cities( $cities, $country_id = '100' ) {
		$this->cities[ $country_id ] = $cities;
	}

	public function get_filtered_cities( $city, $state, $country_id ) {
		$city = mb_strtolower( Transliteration::latin2cyrillic( $city ) );
		$cities = self::search_for_city( $city, $state, $country_id );
		$cities_only_names = [];
		$cities_search_names = [];
		$cities_only_names_dropdowns = [];
		
		if ( !empty( $cities ) ) {
			foreach ( $cities as $temp_city ) {
				$cities_only_names_dropdowns[] = $temp_city['name_bg'];
				$temp_city['name_bg'] = mb_strtolower( $temp_city['name_bg'] );
				$cities_only_names[] = $temp_city['name_bg'];
				$cities_search_names[] = $temp_city;
			}
		}

		$city_key = array_search( $city, array_column( $cities_search_names, 'name_bg' ) );

		return [
			'city' => $city,
			'cities' => $cities,
			'cities_only_names' => $cities_only_names,
			'cities_search_names' => $cities_search_names,
			'cities_only_names_dropdowns' => $cities_only_names_dropdowns,
			'city_key' => $city_key,
		];
	}
}
