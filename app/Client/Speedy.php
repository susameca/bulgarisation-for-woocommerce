<?php
namespace Woo_BG\Client;

defined( 'ABSPATH' ) || exit;

class Speedy {
	const BASE_ENDPOINT = 'https://api.speedy.bg/v1/';
    const LABELS_ENDPOINT = 'Shipments/LabelService.createLabel.json';
    const UPDATE_LABELS_ENDPOINT = 'Shipments/LabelService.updateLabel.json';
    const DELETE_LABELS_ENDPOINT = 'Shipments/LabelService.deleteLabels.json';
    const SHIPMENT_STATUS_ENDPOINT = 'Shipments/ShipmentService.getShipmentStatuses.json';
    const CACHE_FOLDER = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woo-bg' . DIRECTORY_SEPARATOR . 'speedy' . DIRECTORY_SEPARATOR;

    private $env = '';
    private $user = '';
    private $password = '';
    private $base_endpoint;

	public function __construct() {
		$this->load_user();
		$this->load_password();
	}

	public function api_call( $endpoint, $args ) {
		$args = array_merge(
			array(
				"userName" => $this->get_user(),
    			"password" => $this->get_password(),
			),
			$args
		);

		$request = wp_remote_post( self::BASE_ENDPOINT . $endpoint, array(
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode( $args ),
		) );

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

	public static function validate_access( $api_call ) {
		return !( isset( $api_call['error'] ) && $api_call['error']['code'] === 1 );
	}

	//Loaders
	public function load_user() {
		$this->set_user( woo_bg_get_option( 'speedy', 'user' ) );
	}

	public function load_password() {
		$this->set_password( woo_bg_get_option( 'speedy', 'password' ) );
	}

	//Getters

	public function get_user() {
		return $this->user;
	}

	public function get_password() {
		return $this->password;
	}

	//Setters

	private function set_user( $user ) {
		$this->user = $user;
	}

	private function set_password( $password ) {
		$this->password = $password;
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
		woo_bg_set_option( 'speedy', 'profile_data', '' );
		woo_bg_set_option( 'speedy', 'clients', '' );
	}
}
