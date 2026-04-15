<?php
namespace Woo_BG\Client\Pigeon;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Cities {
	const CITIES_ENDPOINT = '/cities';

	private $cities = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_cities( $country_code = 'BG' ) {
		if ( ! is_dir( $this->container[ Client::PIGEON ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::PIGEON ]::CACHE_FOLDER );
		}

		$cities_file = $this->container[ Client::PIGEON ]::CACHE_FOLDER . 'cities-' . $country_code . '.json';
		$cities = File::get_file( $cities_file );

		if ( !$cities ) {
			$page = 1;
			$all_cities = [];
			$data = $this->get_page( $page );

			if ( is_array( $data ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$all_cities = array_merge( $all_cities, $data['data'] );
				$current_page = $data['meta']['current_page'];
				$total_pages = $data['meta']['last_page'];

				while ( $current_page < $total_pages ) {
					$page++;
					$data = $this->get_page( $page );

					if ( is_array( $data ) && $data['success'] && isset( $data['data'] ) && is_array( $data['data'] ) ) {
						$all_cities = array_merge( $all_cities, $data['data'] );
						$current_page = $data['meta']['current_page'];
						$total_pages = $data['meta']['last_page'];
					} else {
						break;
					}
				}
			}

			if ( !empty( $all_cities ) ) {
				$cities = wp_json_encode( [ 'cities' => $all_cities ] );
				
				File::put_to_file( $cities_file, $cities );
			}
		}

		$cities = json_decode( $cities, 1 );

		$this->set_cities( $cities, $country_code );

		return $cities;
	}

	function get_page( $page = 1 ) {
		return $this->container[ Client::PIGEON ]->api_call( self::CITIES_ENDPOINT, [
			'page' => $page,
		] );
	}

	public function find_city( $query ) {
		$city_search = null;

		$hash = md5( $query );
		$city_file = $this->container[ Client::PIGEON ]::CACHE_FOLDER . 'city-search-' . $hash . '.json';
		$city_search = File::get_file( $city_file );

		if ( !$city_search ) {
			$request = $this->container[ Client::PIGEON ]->api_call( self::CITIES_ENDPOINT, [
				'page' => 1,
				'name' => $query,
			] );

			if ( isset( $request['success'] ) && $request['success'] && isset( $request['data'] ) && is_array( $request['data'] ) ) {
				$city_search = wp_json_encode( $request['data'] );

				File::put_to_file( $city_file, $city_search );
			}	
		}
		
		$city_search = json_decode( $city_search, 1 );

		return $city_search;
	}

	public function get_formatted_cities( $country_code = 'BG' ) {
		$cities = $this->get_cities( $country_code );
		$formatted = [];

		if ( is_array( $cities ) && isset( $cities['cities'] ) && is_array( $cities['cities'] ) ) {
			foreach ( $cities['cities'] as $city ) {
				$label = $city['name'];
	
				if ( $city['district'] && $city['district'] !== $city['name'] ) {
					$label = $city['district'] . " - " . $label;
				}
	
				if ( $city['name'] && $city['district'] ) {
					$formatted[ 'cityID-' . $city['id'] ] = array(
						'id' => 'cityID-' . $city['id'],
						'label' => $label,
					);
				}
			}
	
			uasort( $formatted, function( $a, $b ) {
				return strcmp( $a["label"], $b["label"] );
			} );
		}

		return $formatted;
	}

	//Getters
	public function get_cities( $country_code = 'BG' ) {
		if ( empty( $this->cities[ $country_code ] ) ) {
			$this->load_cities( $country_code );
		}

		return $this->cities[ $country_code ];
	}

	public function get_state_name( $state_code, $country_code = 'BG' ) {
		$states = $this->get_regions( $country_code );

		return $states[ $state_code ];
	}

	public function get_filtered_cities( $query, $state ) {
		if ( empty( $state ) ) {
			return [
				'city' => '',
				'cities' => [],
				'cities_only_names' => [],
				'cities_search_names' => [],
				'cities_only_names_dropdowns' => [],
				'city_key' => null,
			];
		}

		$city = mb_strtolower( Transliteration::latin2cyrillic( trim( $query ) ) );
		$state_name = $this->get_state_name( $state );
		$cities = $this->find_city( $query );

		$cities_only_names = [];
		$cities_search_names = [];
		$cities_only_names_dropdowns = [];
		
		if ( !empty( $cities ) ) {
			foreach ( $cities as $temp_city ) {
				if ( $temp_city['district'] !== $state_name ) {
					continue;
				}
				
				$cities_only_names_dropdowns[] = $temp_city[ 'name' ];
				$temp_city[ 'name' ] = mb_strtolower( $temp_city[ 'name' ] );
				$cities_only_names[] = $temp_city[ 'name' ];
				$cities_search_names[] = $temp_city;
			}
		}

		$city_key = array_search( $city, array_column( $cities_search_names, 'name' ) );

		return [
			'city' => $city,
			'cities' => $cities,
			'cities_only_names' => $cities_only_names,
			'cities_search_names' => $cities_search_names,
			'cities_only_names_dropdowns' => $cities_only_names_dropdowns,
			'city_key' => $city_key,
		];
	}

	public function get_regions( $country_code = 'BG' ) {
		$states = [ 
			'BG' => [
				'BG-01' => 'Благоевград',
				'BG-02' => 'Бургас',
				'BG-03' => 'Варна',
				'BG-04' => 'Велико Търново',
				'BG-05' => 'Видин',
				'BG-06' => 'Враца',
				'BG-07' => 'Габрово',
				'BG-08' => 'Добрич',
				'BG-09' => 'Кърджали',
				'BG-10' => 'Кюстендил',
				'BG-11' => 'Ловеч',
				'BG-12' => 'Монтана',
				'BG-13' => 'Пазарджик',
				'BG-14' => 'Перник',
				'BG-15' => 'Плевен',
				'BG-16' => 'Пловдив',
				'BG-17' => 'Разград',
				'BG-18' => 'Русе',
				'BG-19' => 'Силистра',
				'BG-20' => 'Сливен',
				'BG-21' => 'Смолян',
				'BG-22' => 'София (столица)',
				'BG-23' => 'София',
				'BG-24' => 'Стара Загора',
				'BG-25' => 'Търговище',
				'BG-26' => 'Хасково',
				'BG-27' => 'Шумен',
				'BG-28' => 'Ямбол',
			] 
		];

		return $states[ $country_code ];
	}
	
	//Setters
	private function set_cities( $cities, $country_code = 'BG' ) {
		$this->cities[ $country_code ] = $cities;
	}
}
