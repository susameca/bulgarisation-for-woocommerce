<?php
namespace Woo_BG\Client;

defined( 'ABSPATH' ) || exit;

class CVC {
	const BASE_ENDPOINT = 'https://lox.e-cvc.bg/';
	const CALC_LABELS_ENDPOINT = 'calculate_wb';
	const CREATE_LABELS_ENDPOINT = 'create_wb';
	const CANCEL_LABELS_ENDPOINT = 'cancel_wb';
    const CACHE_FOLDER = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woo-bg' . DIRECTORY_SEPARATOR . 'cvc' . DIRECTORY_SEPARATOR;

    private $token = '';

	public function __construct() {
		$this->load_token();
	}

	public function api_call( $endpoint, $args, $method = '' ) {
		$url = self::BASE_ENDPOINT . $endpoint;
		$request_args = array(
			'timeout' => 15,
			'headers' => array(
				'content-type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_token(),
			),
			'body' => json_encode( $args ),
		);

		if ( $method === 'GET' ) {
			unset( $request_args['body'] );

			$request = wp_remote_get( add_query_arg( $args, $url ), $request_args );
		} else {
			$request = wp_remote_post( $url, $request_args );
		}

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

	public static function validate_access( $api_call ) {
		return $api_call['success'];
	}

	//Loaders
	public function load_token() {
		$this->set_token( woo_bg_get_option( 'cvc', 'token' ) );
	}

	//Getters
	public function get_token() {
		return $this->token;
	}

	//Setters
	private function set_token( $token ) {
		$this->token = $token;
	}

	public static function add_error_message( $error ) {
		$message = [];

		return array_filter( $message );
	}

	public static function clear_cache_folder() {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;
		$wp_filesystem->rmdir( self::CACHE_FOLDER, true );
	}

	public static function clear_profile_data() {
		woo_bg_set_option( 'cvc', 'profile_data', '' );
	}
}
