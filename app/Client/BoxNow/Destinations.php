<?php
namespace Woo_BG\Client\BoxNow;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Destinations {
    const DESTINATIONS_ENDPOINT = '/api/v1/destinations';

    private $destinations = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_destinations( $size ) {
		if ( ! is_dir( $this->container[ Client::BOXNOW ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::BOXNOW ]::CACHE_FOLDER );
		}

		$destinations_file = $this->container[ Client::BOXNOW ]::CACHE_FOLDER . 'destinations-' . $size . '.json';
		$destinations = File::get_file( $destinations_file );

		if ( !$destinations ) {
			$args = [];

			if ( $size && $size != 'all' ) {
				$args['requiredSize'] = $size;
			}

			$api_call = $this->container[ Client::BOXNOW ]->api_call( self::DESTINATIONS_ENDPOINT, $args, 'GET' );

			if ( is_array( $api_call ) ) {
				if ( !empty( $api_call['data'] ) ) {
					$destinations = wp_json_encode( $api_call['data'] );
					
					File::put_to_file( $destinations_file, $destinations );
				}
			}
		}

		$destinations = json_decode( $destinations, 1 );

		$this->set_destinations( $size, $destinations );

		return $destinations;
	}

	//Getters
	public function get_destinations( $size = '' ) {
		$size = ( !$size ) ? 'all' : $size;

		if ( empty( $this->destinations[ $size ] ) ) {
			$this->load_destinations( $size );
		}

		return $this->destinations[ $size ];
	}

	//Setters
	private function set_destinations( $size, $destinations ) {
		$this->destinations[ $size ] = $destinations;
	}

	public function get_formatted_destinations( $size = '' ) {
		$destinations = $this->get_destinations( $size );
		$data = [];

		if ( !empty( $destinations ) ) {
			foreach ( $destinations as $destionation ) {
				$data[ 'destionationID-' . $destionation['id'] ] = [
					'id' => 'destionationID-' . $destionation['id'],
					'type' => $destionation['type'],
					'label' => $destionation['name'] . ' (' . str_replace( ' ', '', $destionation['addressLine2'] ) . ' ' . $destionation['addressLine1'] . ')',
				];
			}
		}

		return $data;
	}
}
