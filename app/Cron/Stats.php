<?php
namespace Woo_BG\Cron;
use Woo_BG\Container\Client;
use Woo_BG\Plugin;

defined( 'ABSPATH' ) || exit;

class Stats {
	const SERVER_URL = 'https://license.bulgarisation.bg/';
	const ACTIVITY_REQUEST_LOCK = 'woo_bg_activity_request_lock';

	function __construct() {
		add_action( 'wp', array( __CLASS__, 'cron_schedule' )  );
		add_action( 'woo_bg/submit_stats', array( __CLASS__, 'submit_stats' ) );
	}

	public static function cron_schedule() {
		if ( ! wp_next_scheduled( 'woo_bg/submit_stats' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'woo_bg/submit_stats' );
		}
	}

	public static function get_args() {
		$container = woo_bg()->container();
		
		$args = apply_filters( 'woo_bg/stats/args', [
			'site_url' => esc_url( home_url( '/' ) ),
			'has_pro' => woo_bg_is_pro_activated(),
			'has_econt' => ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ),
			'has_speedy' => ( woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' ), 
			'has_boxnow' => ( woo_bg_get_option( 'apis', 'enable_boxnow' ) === 'yes' ), 
			'has_pigeon' => ( woo_bg_get_option( 'apis', 'enable_pigeon' ) === 'yes' ), 
			'valid_boxnow' => ( $container[ Client::BOXNOW ]->get_access_token() && woo_bg_get_option( 'boxnow', 'env' ) === 'live' ), 
			'has_cvc' => ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ),
			'has_nekorekten' => ( woo_bg_get_option( 'reports', 'enable_nekorekten' ) === 'yes' ),
			'has_connectix' => ( woo_bg_get_option( 'reports', 'enable_connectix' ) === 'yes' ),
			'has_nepostop' => ( woo_bg_get_option( 'reports', 'enable_nepostop' ) === 'yes' ),
			'has_nra' => ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' && woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ),
			'version' => Plugin::VERSION,
		] );

		return $args;
	}

	public static function submit_stats() {
		if ( woo_bg_get_option( 'apis', 'enable_stats' ) !== 'yes' ) {
			return;
		}

		if ( ! self::acquire_activity_request_lock() ) {
			return;
		}

		wp_remote_post( self::SERVER_URL . 'wp-json/woo-bg/v1/activity/', [
			'body' => self::get_args(),
		] );
	}

	public static function acquire_activity_request_lock( $force = false ) {
		if ( ! $force && false !== get_transient( self::ACTIVITY_REQUEST_LOCK ) ) {
			return false;
		}

		set_transient( self::ACTIVITY_REQUEST_LOCK, time(), HOUR_IN_SECONDS );

		return true;
	}
}
