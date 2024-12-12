<?php
namespace Woo_BG\Cron;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class CVC {
	function __construct() {
		add_action( 'wp', array( __CLASS__, 'cron_schedule' )  );
		add_action( 'woo_bg/cvc/clear_cache_folder', array( __CLASS__, 'clear_cache_folder' ) );
	}

	public static function cron_schedule() {
		if ( ! wp_next_scheduled( 'woo_bg/cvc/clear_cache_folder' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'woo_bg/cvc/clear_cache_folder' );
		}
	}

	public static function clear_cache_folder() {
		woo_bg()->container()[ Client::CVC ]::clear_cache_folder();
	}
}
