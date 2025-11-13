<?php
namespace Woo_BG\Cron;
use Woo_BG\Container\Client;
use Woo_BG\Plugin;

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

	public static function get_args() {
		$container = woo_bg()->container();
		
		$args = apply_filters( 'woo_bg/stats/args', [
			'site_url' => esc_url( home_url( '/' ) ),
			'has_pro' => woo_bg_is_pro_activated(),
			'has_econt' => ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ),
			'has_speedy' => ( woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' ), 
			'has_boxnow' => ( woo_bg_get_option( 'apis', 'enable_boxnow' ) === 'yes' ), 
			'valid_boxnow' => ( $container[ Client::BOXNOW ]->get_access_token() && woo_bg_get_option( 'boxnow', 'env' ) === 'live' ), 
			'has_cvc' => ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ),
			'has_nekorekten' => ( woo_bg_get_option( 'apis', 'enable_nekorekten' ) === 'yes' ),
			'has_nra' => ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' && woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ),
			'version' => Plugin::VERSION,
		] );

		return $args;
	}

	public static function submit_stats() {
		if ( woo_bg_get_option( 'apis', 'enable_stats' ) !== 'yes' ) {
			return;
		}

		wp_remote_post( self::SERVER_URL . 'wp-json/woo-bg/v1/activity/', [
			'body' => self::get_args(),
		] );
	}
}
