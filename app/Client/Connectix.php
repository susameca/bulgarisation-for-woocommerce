<?php
namespace Woo_BG\Client;

defined( 'ABSPATH' ) || exit;

class Connectix {
	const LIVE_URL = 'https://api.connectix.bg/';
    const SANDBOX_URL = 'https://api-sandbox.connectix.bg/';
    const INTEGRATION_ENDPOINT = "integration/register";
    const CHECK_ENDPOINT = 'shopper/check';
	const TRACKING_ENDPOINT = 'trackings';

    private $env = '';
    private $token = '';
    private $integration_token = '';
    private $base_endpoint;

	public function __construct() {	
		$this->set_env( woo_bg_get_option( 'connectix', 'env' ) );
		$this->load_token();
		$this->load_base_endpoint();
	}

	public function api_call( $endpoint, $args ) {
		if ( empty( $this->get_token() ) ) {
	        return;
	    }
	    
		$request = wp_remote_post( $this->get_base_endpoint() . $endpoint, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => $this->get_token(),
			),
			'body' => wp_json_encode( $args ),
		) );

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

    public function validate_access() {
        $has_access = false;

		$response = $this->api_call( self::INTEGRATION_ENDPOINT, [
            "type" => "ecommerce",
            "platform" => "Bulgarisation for WooCommerce",
            "secret" => $this->get_secret(),
        ] );

        if ( !empty( $response ) && !empty( $response['token'] ) ) {
            $this->set_integration_token( $response['token'] );
            $has_access = true;
        }

        return $has_access;
	}

	//Loaders
	public function load_token() {
		$this->set_token( woo_bg_get_option( 'connectix', 'token' ) );
	}

	public function load_base_endpoint() {
		$base_endpoint = self::SANDBOX_URL;

		if ( $this->get_env() == 'live' ) {
			$base_endpoint = self::LIVE_URL;
		}

		$this->set_base_endpoint( $base_endpoint );
	}

	//Getters
	public function get_token() {
		return $this->token;
	}

    public function get_integration_token() {
        if ( !$this->integration_token ) {
            $this->validate_access();
        }

        return $this->integration_token;
    }

	public function get_env() {
		return $this->env;
	}

	public function get_base_endpoint() {
		return $this->base_endpoint;
	}

    public function get_secret() {
        $secret = woo_bg_get_option( 'connectix', 'secret' );
        
        if ( !$secret ) {
            $secret = md5( uniqid( ( string ) mt_rand(), true) );
            $secret = woo_bg_set_option( 'connectix', 'secret', $secret );
        }

        return $secret;
    }

	public static function get_levels_of_probability() {
		return [
			[ 
				'key' => 'no_info',
				'label' => __('No Info', 'bulgarisation-for-woocommerce'),
			],
			[ 
				'key' => 'low',
				'label' => __('Low', 'bulgarisation-for-woocommerce'),
			],
			[ 
				'key' => 'medium',
				'label' => __('Medium', 'bulgarisation-for-woocommerce'),
			],
			[ 
				'key' => 'high',
				'label' => __('High', 'bulgarisation-for-woocommerce'),
			],
			[ 
				'key' => 'ultra_high',
				'label' => __('Ultra high', 'bulgarisation-for-woocommerce'),
			],
		];
	}

	//Setters
	private function set_token( $token ) {
		$this->token = $token;
	}

    private function set_integration_token( $token ) {
		$this->integration_token = $token;
	}

	public function set_env( $env ) {
		$this->env = $env;
	}

	private function set_base_endpoint( $endpoint ) {
		$this->base_endpoint = $endpoint;
	}
}
