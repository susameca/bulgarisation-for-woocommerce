<?php
namespace Woo_BG\Client;
use Woo_BG\File;
use Woo_BG\Cron\Stats;

defined( 'ABSPATH' ) || exit;

class Econt {
	const LIVE_URL = 'http://ee.econt.com/services/';
    const DEMO_URL = 'http://demo.econt.com/ee/services/';
    const SHIPMENT_STATUS_ENDPOINT = 'Shipments/ShipmentService.getShipmentStatuses.json';
    const CACHE_FOLDER = File::CACHE_FOLDER . 'econt' . DIRECTORY_SEPARATOR;

    private $env = '';
    private $user = '';
    private $password = '';
    private $base_endpoint;
    public static $skip_cached_files = array(
		'cities-BG.json',
	);

	public function __construct() {	
		$this->set_env( woo_bg_get_option( 'econt', 'env' ) );
		$this->load_user();
		$this->load_password();
		$this->load_base_endpoint();
	}

	public function api_call( $endpoint, $args ) {
		if ( empty( $this->get_user() ) || empty( $this->get_password() ) ) {
	        return;
	    }
	    
		$request = wp_remote_post( $this->get_base_endpoint() . $endpoint, array(
			'headers' => array(
				'content-type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->get_user() . ':' . $this->get_password() ),
			),
			'body' => wp_json_encode( $args ),
			'timeout' => 60,
		) );

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

	public function label_request( $type, $request_body ) {
		if ( empty( $this->get_user() ) || empty( $this->get_password() ) ) {
			return;
		}

		$api_filters = woo_bg_remove_api_filters();

		try {
			$request = wp_remote_post( 'https://api.bulgarisation.bg/wp-json/woo-bg/v1/econt/label_request/', array(
				'body' => array(
					'client' => esc_url( home_url( '/' ) ),
					'type' => $type,
					'env' => $this->get_env(),
					'user' => $this->get_user(),
					'password' => $this->get_password(),
					'request_body' => $request_body,
				),
				'timeout' => 15,
			) );
		} finally {
			woo_bg_restore_api_filters( $api_filters );
		}

		return json_decode( wp_remote_retrieve_body( $request ), 1 );
	}

	public static function validate_access( $api_call ) {
		if ( is_array( $api_call ) ) {
			return !( isset( $api_call['type'] ) && $api_call['type'] == 'ExInvalidParam' );
		}
	}

	//Loaders
	public function load_user() {
		$this->set_user( woo_bg_get_option( 'econt', 'user' ) );
	}

	public function load_password() {
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

	public static function clear_cache_folder( $skip_cached_files = false ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();
		global $wp_filesystem;

		$files = $wp_filesystem->dirlist( self::CACHE_FOLDER );

		if ( !empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( $skip_cached_files && in_array( $file['name'], self::$skip_cached_files ) ) {
					continue;
				}

				$wp_filesystem->delete( self::CACHE_FOLDER . DIRECTORY_SEPARATOR . $file['name'] );
			}
		}

		Stats::submit_stats();
	}

	public static function clear_profile_data() {
		woo_bg_set_option( 'econt', 'profile_data', '' );
	}
}
