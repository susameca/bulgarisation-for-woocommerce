<?php
namespace Woo_BG\Admin;

defined( 'ABSPATH' ) || exit;

class Ajax {
	public function __construct() {
		add_action( 'wp_ajax_woo_bg_save_settings', array( 'Woo_BG\Admin\Tabs\Settings_Tab', 'woo_bg_save_settings_callback' ) );
		add_action( 'wp_ajax_woo_bg_export_nap', array( 'Woo_BG\Admin\Tabs\Export_Tab', 'woo_bg_export_nap_callback' ) );
		add_action( 'wp_ajax_woo_bg_send_request', array( 'Woo_BG\Admin\Tabs\Help_Tab', 'woo_bg_send_request_callback') );
	}
}
