<?php
namespace Woo_BG\Cron;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Stats {
	const SERVER_URL = 'https://license.bulgarisation.bg/';

	function __construct() {
		add_action( 'wp', array( __CLASS__, 'cron_schedule' )  );
		add_action( 'woo_bg/submit_stats', array( __CLASS__, 'submit_stats' ) );
	}

	public static function cron_schedule() {
		if ( ! wp_next_scheduled( 'woo_bg/submit_stats' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'woo_bg/submit_stats' );
		}
	}

	public static function submit_stats() {
		$args = [
			'site_url' => esc_url( home_url( '/' ) ),
			'has_pro' => class_exists( '\Woo_BG_Pro\Checkout' ),
			'has_econt' => ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ),
			'has_speedy' => ( woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' ), 
			'has_cvc' => ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ),
			'has_nekorekten' => ( woo_bg_get_option( 'apis', 'enable_nekorekten' ) === 'yes' ),
			'has_nra' => ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' && woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ),
		];

		if ( class_exists( '\Woo_BG_Pro\Checkout' ) ) {
			$args['license_key'] = woo_bg_get_option( 'pro', 'license_key' );
			$args['valid_pro_license_file_hash'] = hash_file( 'sha256', woo_bg()->container()[ 'pro_plugin_dir' ] . "app/License.php" ) === 'fa76df5bd96476389a93795ccc30a257a4e860a26278dd5faa2a67b4b7f37d37';
		}

		$response = json_decode( wp_remote_retrieve_body( wp_remote_post( self::SERVER_URL . 'wp-json/woo-bg/v1/activity/', [
			'body' => $args,
		] ) ), 1 );

		if ( isset( $response['is_pro_active'] ) ) {
			set_transient( \Woo_BG_Pro\License::$transient, $response['is_pro_active'], WEEK_IN_SECONDS * 2 );
		}
	}
}
