<?php
namespace Woo_BG\Client\BoxNow;
use Woo_BG\Container\Client;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class Origins {
    const ORIGINS_ENDPOINT = '/api/v1/origins';

    private $origins = [];
    private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_origins() {
		if ( ! is_dir( $this->container[ Client::BOXNOW ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::BOXNOW ]::CACHE_FOLDER );
		}

		$origins_file = $this->container[ Client::BOXNOW ]::CACHE_FOLDER . 'origins.json';
		$origins = File::get_file( $origins_file );

		if ( !$origins ) {
			$api_call = $this->container[ Client::BOXNOW ]->api_call( self::ORIGINS_ENDPOINT, [], 'GET' );

			if ( is_array( $api_call ) ) {
				if ( !empty( $api_call['data'] ) ) {
					$origins = wp_json_encode( $api_call['data'] );
					
					File::put_to_file( $origins_file, $origins );
				}
			}
		}

		$origins = json_decode( $origins, 1 );

		$this->set_origins( $origins );

		return $origins;
	}

	//Getters
	public function get_origins() {
		if ( empty( $this->origins ) ) {
			$this->load_origins();
		}

		return $this->origins;
	}

	//Setters
	private function set_origins( $origins ) {
		$this->origins = $origins;
	}

	public function get_formatted_origins() {
		$origins = $this->get_origins();
		$data = [];

		if ( !empty( $origins ) ) {
			foreach ( $origins as $origin ) {
				$data[ 'originID-' . $origin['id'] ] = [
					'id' => 'originID-' . $origin['id'],
					'type' => $origin['type'],
					'label' => $origin['name'] . ' (' . str_replace( ' ', '', $origin['addressLine2'] ) . ' ' . $origin['addressLine1'] . ')',
				];
			}
		}

		return $data;
	}
}
