<?php
namespace Woo_BG\Client;
use Woo_BG\File;
use Woo_BG\Cron\Stats;

defined( 'ABSPATH' ) || exit;

class BoxNow {
	const LIVE_URL = 'https://api-production.boxnow.bg';
    const DEMO_URL = 'https://api-stage.boxnow.bg';
    const AUTH_ENDPOINT = '/api/v1/auth-sessions';
    const CREATE_LABELS_ENDPOINT = '/api/v1/delivery-requests';

	const CACHE_FOLDER = File::CACHE_FOLDER . 'boxnow' . DIRECTORY_SEPARATOR;

	private $env = '';
	private $client_id = '';
	private $client_secret = '';
	private $base_endpoint;
	private $access_token;

	public function __construct() {
		$this->set_env( woo_bg_get_option( 'boxnow', 'env' ) );
		$this->load_client_id();
		$this->load_client_secret();
		$this->load_base_endpoint();

		$this->load_access_token( true );
	}

	public function api_call( $endpoint, $args, $method = 'POST', $return_plain = false ) {
		if ( ! $this->get_access_token() ) {
			return;
		}

		$url = $this->get_base_endpoint() . $endpoint;
		
		$request_args = array(
			'timeout' => 15,
			'headers' => array(
				'content-type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
			'body' => wp_json_encode( $args ),
		);

		if ( $method === 'GET' ) {
			unset( $request_args['body'] );

			$request = wp_remote_get( add_query_arg( $args, $url ), $request_args );
		} else {
			$request = wp_remote_post( $url, $request_args );
		}

		if ( $return_plain ) {
			return wp_remote_retrieve_body( $request );
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), 1 );

		if ( isset( $response['code'] ) ) {
			$response['message'] = self::get_message( $response['code'] );

			if ( !empty( $response['jsonSchemaErrors'] ) ) {
				$response['message'] .= " " . implode('. ', $response['jsonSchemaErrors'] );
			}
		}

		return $response;
	}

	public function load_access_token( $forced = false ) {
		if ( $forced ) {
			$this->load_client_id();
			$this->load_client_secret();
		}

		$request = wp_remote_post( $this->get_base_endpoint() . self::AUTH_ENDPOINT, array(
			'headers' => array( 
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( array(
				'grant_type' => 'client_credentials',
				'client_id' => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
			) ),
		) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
			$this->set_access_token( null );
			return;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		$this->set_access_token( $response[ 'access_token' ] );
	}

	//Loaders
	public function load_client_id() {
		$this->set_client_id( woo_bg_get_option( 'boxnow', 'client_id' ) );
	}

	public function load_client_secret() {
		$this->set_client_secret( woo_bg_get_option( 'boxnow', 'client_secret' ) );
	}

	public function load_base_endpoint() {
		$base_endpoint = self::DEMO_URL;

		if ( $this->get_env() == 'live' ) {
			$base_endpoint = self::LIVE_URL;
		}

		$this->set_base_endpoint( $base_endpoint );
	}

	//Getters

	public function get_client_id() {
		return $this->client_id;
	}

	public function get_client_secret() {
		return $this->client_secret;
	}

	public function get_env() {
		return $this->env;
	}

	public function get_base_endpoint() {
		return $this->base_endpoint;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	//Setters

	private function set_client_id( $user ) {
		$this->client_id = $user;
	}

	private function set_client_secret( $password ) {
		$this->client_secret = $password;
	}

	public function set_env( $env ) {
		$this->env = $env;
	}

	private function set_base_endpoint( $endpoint ) {
		$this->base_endpoint = $endpoint;
	}

	private function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}

	public static function clear_cache_folder() {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();
		global $wp_filesystem;

		$files = $wp_filesystem->dirlist( self::CACHE_FOLDER );

		if ( !empty( $files ) ) {
			foreach ( $files as $file ) {
				$wp_filesystem->delete( self::CACHE_FOLDER . DIRECTORY_SEPARATOR . $file['name'] );
			}
		}

		Stats::submit_stats();
	}
}
