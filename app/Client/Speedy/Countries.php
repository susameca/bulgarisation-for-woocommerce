<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;
use \Carbon_CSV\CsvFile;
use \Carbon_CSV\Exception as CsvException;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Countries {
    const COUNTRIES_ENDPOINT = 'location/country/csv/';

    private $countries = [];
    private $container;

    public function __construct( $container ) {
        $this->container = $container;
    }

    protected function load_countries($name_en ) {
        if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
            wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
        }

        $countries_by_name_en_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'countries-by-name-in-english.json';

        $countries_file = $this->container[Client::SPEEDY]::CACHE_FOLDER . 'countries.csv';
        $countries = '';
        $error = false;

        if ( file_exists( $countries_file ) && filesize( $countries_file ) > 0 ) {
            $csv = new CsvFile( $countries_file );
        } else {
            $api_call = $this->container[Client::SPEEDY]->api_call(self::COUNTRIES_ENDPOINT, array(), 1);
            File::put_to_file($countries_file, $api_call);
        }

        clearstatcache();

        if (filesize($countries_file) > 0) {
            $csv = new CsvFile($countries_file);

            $csv->use_first_row_as_header();

            $countries_by_name_en = array_filter($csv->to_array(), function ( $country ) use ( $name_en ) {
                return ( $country['isoAlpha2'] === $name_en );
            });



            $countries_by_name_en = wp_json_encode( $countries_by_name_en );

            File::put_to_file( $countries_by_name_en_file, $countries_by_name_en );
        } else {
            $this->container[Client::SPEEDY]::clear_cache_folder();

            return [];
        }

        $countries = json_decode( $countries_by_name_en, 1 );

        $this->set_countries( $countries );

        return $countries;
    }

    //Getters
    public function get_countries_by_name_en( $country ) {
        if ( empty( $this->countries ) ) {
            $this->load_countries( $country );
        }

        return $this->countries;
    }

    public function get_country_id( $key ) {
        $this->get_countries_by_name_en( $key );

        return array_values($this->countries)[0]['id'];
    }

    //Setters
    private function set_countries($countries_by_name_en ) {
        $this->countries = $countries_by_name_en;
    }
}
