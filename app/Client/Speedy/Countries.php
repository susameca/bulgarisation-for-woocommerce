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

    protected function load_countries( $nameEn ) {
        if ( ! is_dir( $this->container[ Client::SPEEDY ]::CACHE_FOLDER ) ) {
            wp_mkdir_p( $this->container[ Client::SPEEDY ]::CACHE_FOLDER );
        }

        $countries_by_nameEn_file = $this->container[ Client::SPEEDY ]::CACHE_FOLDER . 'countries-by-name-in-english.json';

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

            $countries_by_nameEn = array_filter($csv->to_array(), function ( $country ) use ( $nameEn ) {
                return ( $country['isoAlpha2'] === $nameEn );
            });



            $countries_by_nameEn = wp_json_encode( $countries_by_nameEn );

            File::put_to_file( $countries_by_nameEn_file, $countries_by_nameEn );
        } else {
            $this->container[Client::SPEEDY]::clear_cache_folder();

            return [];
        }

        $countries = json_decode( $countries_by_nameEn, 1 );

        $this->set_countries( $countries );

        return $countries;
    }

    //Getters
    public function get_countries_by_nameEn( $country ) {
        if ( empty( $this->countries ) ) {
            $this->load_countries( $country );
        }

        return $this->countries_by_nameEn;
    }

    public function get_country_id( $key ) {
        $this->get_countries_by_nameEn( $key );

        return array_values($this->countries)[0]['id'];
    }

    //Setters
    private function set_countries( $countries_by_nameEn ) {
        $this->countries = $countries_by_nameEn;
    }
}
