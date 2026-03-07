<?php
namespace Woo_BG\Client;

defined( 'ABSPATH' ) || exit;

class NepoStop {
	const LIVE_URL = 'https://nepostop.com/api/';
    const ADD_SHIPMENT_ENDPOINT = "v1/shipments";
    const CHECK_ENDPOINT = 'v1/phone-check';

    private $api_key = '';

	public function __construct() {
		$this->load_api_key();
	}

	public function api_call( $endpoint, $args ) {
		if ( empty( $this->get_api_key() ) ) {
	        return;
	    }
	    
		$request = wp_remote_post( self::LIVE_URL . $endpoint, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-API-Key' => $this->get_api_key(),
			),
			'body' => wp_json_encode( $args ),
		) );

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

    public function validate_access() {
        $has_access = false;

		$request = wp_remote_post( self::LIVE_URL . self::CHECK_ENDPOINT, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-API-Key' => $this->get_api_key(),
			),
			'body' => wp_json_encode( [ 'phone_number' => '0888000001' ] ),
		) );

        if ( wp_remote_retrieve_response_code( $request ) !== 401 ) {
            $has_access = true;
        }

        return $has_access;
	}

	//Loaders
	public function load_api_key() {
		$this->set_api_key( woo_bg_get_option( 'nepostop', 'api_key' ) );
	}

	//Getters
	public function get_api_key() {
		return $this->api_key;
	}

	//Setters
	private function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}
}
