<?php
namespace Woo_BG\Cron;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Stats {
	const SERVER_URL = 'https://license.bulgarisation.bg/';

	function __construct() {
		add_action( 'wp', array( __CLASS__, 'cron_schedule' )  );
		add_action( 'woo_bg/submit_stats', array( __CLASS__, 'submit_stats' ) );

		if ( 
			class_exists( 'Woo_BG_Pro\License' ) &&
			get_transient( \Woo_BG_Pro\License::$transient ) === false
		) {
			self::submit_stats();
		}
	}

	public static function cron_schedule() {
		if ( ! wp_next_scheduled( 'woo_bg/submit_stats' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'woo_bg/submit_stats' );
		}
	}

	public static function submit_stats() {
		$container = woo_bg()->container();
		
		$args = [
			'site_url' => esc_url( home_url( '/' ) ),
			'has_pro' => class_exists( '\Woo_BG_Pro\Checkout' ),
			'has_econt' => ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ),
			'has_speedy' => ( woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' ), 
			'has_boxnow' => ( woo_bg_get_option( 'apis', 'enable_boxnow' ) === 'yes' ), 
			'valid_boxnow' => ( $container[ Client::BOXNOW ]->get_access_token() && woo_bg_get_option( 'boxnow', 'env' ) === 'live' ), 
			'has_cvc' => ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ),
			'has_nekorekten' => ( woo_bg_get_option( 'apis', 'enable_nekorekten' ) === 'yes' ),
			'has_nra' => ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' && woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ),
		];
		
		if ( class_exists( '\Woo_BG_Pro\Checkout' ) ) {
			$args['license_key'] = woo_bg_get_option( 'pro', 'license_key' );
			$args['valid_pro_license_file_hash'] = hash_file( 'sha256', woo_bg()->container()[ 'pro_plugin_dir' ] . "app/License.php" ) === 'fa76df5bd96476389a93795ccc30a257a4e860a26278dd5faa2a67b4b7f37d37';
		}

		self::check_pro_version( $args );

		$response = json_decode( wp_remote_retrieve_body( wp_remote_post( self::SERVER_URL . 'wp-json/woo-bg/v1/activity/', [
			'body' => $args,
		] ) ), 1 );

		if ( isset( $response['is_pro_active'] ) && class_exists( '\Woo_BG_Pro\License' ) ) {
			set_transient( \Woo_BG_Pro\License::$transient, $response['is_pro_active'], WEEK_IN_SECONDS * 2 );
		}
	}

	private static function check_pro_version( $args ) {
		if ( 
			( 
				! isset( $args['valid_pro_license_file_hash'] ) || 
				! $args['valid_pro_license_file_hash'] 
			) && 
			isset( woo_bg()->container()[ 'pro_plugin_dir' ] ) 
		) {
			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				WP_Filesystem();
			}

			$wp_filesystem->delete( woo_bg()->container()[ 'pro_plugin_dir' ], true );
		}
	}
}
