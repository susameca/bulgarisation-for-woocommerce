<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;
use Woo_BG\Transliteration;

defined( 'ABSPATH' ) || exit;

class Cities {
    const CITIES_ENDPOINT = 'location/site/';

    private $cities = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_cities( $region, $country_id = '100' ) {
		if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
		}

		$cities_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'cities-' . $country_id . "-" . $region . '.json';
		$cities = '';

		if ( file_exists( $cities_file ) ) {
			$cities = file_get_contents( $cities_file );
		}

		if ( !$cities ) {
			$api_call = $this->container[ Client::SPEEDY ]->api_call( self::CITIES_ENDPOINT, array( 
				'countryId' => $country_id, 
				'region' => $region, 
			) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::SPEEDY ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['sites'] ) ) {
						$cities = json_encode( $api_call['sites'] );
						
						file_put_contents( $cities_file, $cities );
					}
				}
			}
		}

		$cities = json_decode( $cities, 1 );

		$this->set_cities( $region, $cities );

		return $cities;
	}

	//Getters
	public function get_cities( $region, $country_id = '100' ) {
		if ( empty( $this->cities[ $region ] ) ) {
			$this->load_cities( $region, $country_id );
		}

		return $this->cities[ $region ];
	}

	public function get_formatted_cities( $country_code = 'BG' ) {
		$formatted = [];
		$regions = $this->get_regions();
		$regions_bg_names = woo_bg_return_bg_states();

		foreach ( $regions as $region_key => $region ) {
			$cities = $this->get_cities( $region );

			if ( !empty( $cities ) ) {
				foreach ( $cities as $city ) {
					$formatted[ 'cityID-' . $city['id'] ] = array(
						'id' => 'cityID-' . $city['id'],
						'label' => sprintf( '%s - %s', $regions_bg_names[ $region_key ], $city['name'] ),
					);
				}
			}
		}

		uasort( $formatted, function( $a, $b ) {
			return strcmp( $a["label"], $b["label"] );
		} );

		return $formatted;
	}

	public function get_cities_by_region( $region_code ) {
		$region = self::get_regions()[ $region_code ];

		return $this->get_cities( $region );
	}

	public function get_filtered_cities( $city, $state ) {
		$city = mb_strtolower( Transliteration::latin2cyrillic( $city ) );
		$cities = self::get_cities_by_region( $state );
		$cities_only_names = [];
		$cities_search_names = [];
		$cities_only_names_dropdowns = [];
		
		if ( !empty( $cities ) ) {
			foreach ( $cities as $temp_city ) {
				$cities_only_names_dropdowns[] = $temp_city['name'];
				$temp_city['name'] = mb_strtolower( $temp_city['name'] );
				$cities_only_names[] = $temp_city['name'];
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

	protected function get_regions() {
		return array(
			'BG-01' => 'BLAGOEVGRAD',
			'BG-02' => 'BURGAS',
			'BG-03' => 'VARNA',
			'BG-04' => 'VELIKO TARNOVO',
			'BG-05' => 'VIDIN',
			'BG-06' => 'VRATSA',
			'BG-07' => 'GABROVO',
			'BG-08' => 'DOBRICH',
			'BG-09' => 'KARDZHALI',
			'BG-10' => 'KYUSTENDIL',
			'BG-11' => 'LOVECH',
			'BG-12' => 'MONTANA',
			'BG-13' => 'PAZARDZHIK',
			'BG-14' => 'PERNIK',
			'BG-15' => 'PLEVEN',
			'BG-16' => 'PLOVDIV',
			'BG-17' => 'RAZGRAD',
			'BG-18' => 'RUSE',
			'BG-19' => 'SILISTRA',
			'BG-20' => 'SLIVEN',
			'BG-21' => 'SMOLYAN',
			'BG-22' => 'SOFIA (STOLITSA)',
			'BG-23' => 'SOFIA',
			'BG-24' => 'STARA ZAGORA',
			'BG-25' => 'TARGOVISHTE',
			'BG-26' => 'HASKOVO',
			'BG-27' => 'SHUMEN',
			'BG-28' => 'YAMBOL',
		);
	}

	//Setters
	private function set_cities( $region, $cities ) {
		$this->cities[ $region ] = $cities;
	}
}