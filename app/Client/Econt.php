<?php
namespace Woo_BG\Client;

defined( 'ABSPATH' ) || exit;

class Econt {
	const LIVE_URL = 'http://ee.econt.com/services/';
    const DEMO_URL = 'http://demo.econt.com/ee/services/';
    const LABELS_ENDPOINT = 'Shipments/LabelService.createLabel.json';
    const UPDATE_LABELS_ENDPOINT = 'Shipments/LabelService.updateLabel.json';
    const DELETE_LABELS_ENDPOINT = 'Shipments/LabelService.deleteLabels.json';
    const SHIPMENT_STATUS_ENDPOINT = 'Shipments/ShipmentService.getShipmentStatuses.json';
    const CACHE_FOLDER = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woo-bg' . DIRECTORY_SEPARATOR . 'econt' . DIRECTORY_SEPARATOR;

    private $env = '';
    private $user = '';
    private $password = '';
    private $base_endpoint;

	public function __construct() {	
		$this->set_env( woo_bg_get_option( 'econt', 'env' ) );
		$this->load_user();
		$this->load_password();
		$this->load_base_endpoint();
	}

	public function api_call( $endpoint, $args ) {
		$request = wp_remote_post( $this->get_base_endpoint() . $endpoint, array(
			'headers' => array(
				'content-type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->get_user() . ':' . $this->get_password() ),
			),
			'body' => json_encode( $args ),
		) );

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

	public static function validate_access( $api_call ) {
		return !( isset( $api_call['type'] ) && $api_call['type'] == 'ExAccessDenied' );
	}

	//Loaders
	public function load_user() {
		if ( ! woo_bg_get_option( 'econt', 'user' ) ) {
			woo_bg_set_option( 'econt', 'user', 'iasp-dev' );
		}

		$this->set_user( woo_bg_get_option( 'econt', 'user' ) );
	}

	public function load_password() {
		if ( ! woo_bg_get_option( 'econt', 'password' ) ) {
			woo_bg_set_option( 'econt', 'password', 'iasp-dev' );
		}

		$this->set_password( woo_bg_get_option( 'econt', 'password' ) );
	}

	public function load_base_endpoint() {
		$base_endpoint = self::DEMO_URL;

		if ( $this->get_env() == 'live' ) {
			$base_endpoint = self::LIVE_URL;
		}

		$this->set_base_endpoint( $base_endpoint );
	}

	//Getters

	public function get_user() {
		return $this->user;
	}

	public function get_password() {
		return $this->password;
	}

	public function get_env() {
		return $this->env;
	}

	public function get_base_endpoint() {
		return $this->base_endpoint;
	}

	//Setters

	private function set_user( $user ) {
		$this->user = $user;
	}

	private function set_password( $password ) {
		$this->password = $password;
	}

	public function set_env( $env ) {
		$this->env = $env;
	}

	private function set_base_endpoint( $endpoint ) {
		$this->base_endpoint = $endpoint;
	}

	public static function add_error_message( $error ) {
		$message = [];

		if ( $error['message'] && $error['message'] !== ' ' ) {
			$message[] = $error['message'];
		}

		if ( !empty( $error['innerErrors'] ) ) {
			foreach ( $error['innerErrors'] as $error ) {
				$message = array_merge( $message, self::add_error_message( $error ) );
			}
		}

		return array_filter( $message );
	}

	public static function clear_cache_folder() {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;
		$wp_filesystem->rmdir( self::CACHE_FOLDER, true );
	}

	public static function clear_profile_data() {
		woo_bg_set_option( 'econt', 'profile_data', '' );
	}
}
