<?php
/*
Plugin Name:  Bulgarisation for WooCommerce
Description:  Everything necessary for your online store to work in Bulgaria and according to Bulgarian regulations. Includes a light regime for Ordinance - H-18 and Econt shipping method.
Author:       Autopolis.bg
Version:      2.2.6
Author URI:   https://autopolis.bg/
Requires PHP: 7.4.0
WC requires at least: 4.0
WC tested up to: 6.6.1
Text Domain:  woo-bg
License:      GPLv3 or later
*/

define( 'WOO_BG_PHP_MINIMUM_VERSION', '7.4' );
define( 'WOO_BG_WP_MINIMUM_VERSION', '4.8' );

defined( 'ABSPATH' ) || exit;

// Start the plugin
add_action( 'plugins_loaded', 'woo_bg_init', 1, 0 );

/**
 * @return \Woo_BG\Plugin
 */
function woo_bg_init() {
	load_plugin_textdomain( 'woo-bg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( version_compare( PHP_VERSION, WOO_BG_PHP_MINIMUM_VERSION, '<' ) || version_compare( get_bloginfo( 'version' ), WOO_BG_WP_MINIMUM_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() {
			$message = sprintf( esc_html__( 'WooCommerce - Bulgarisation requires PHP version %s+ and WP version %s+, plugin is currently NOT RUNNING.', 'woo-bg' ), WOO_BG_PHP_MINIMUM_VERSION, WOO_BG_WP_MINIMUM_VERSION );
			echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
		} );

		return;
	} 

	if ( !class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function() {
			$message = sprintf( esc_html__( 'Bulgarisation for WooCommerce - Please enable WooCommerce first.', 'woo-bg' ) );
			echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
		} );
		
		return;
	}

	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		add_action( 'admin_notices', function() {
			$message = sprintf( esc_html__( 'Please run `composer install` in the plugin folder `woocommerce-bulgarisation`', 'woo-bg' ) );
			echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
		} );
		return;
	}

	$container = new \Pimple\Container( [ 'plugin_file' => __FILE__ ] );
	$plugin    = \Woo_BG\Plugin::instance( $container );
	$plugin->init();

	/**
	 * Fires after the plugin has initialized
	 *
	 * @param \Woo_BG\Plugin $plugin    The global instance of the plugin controller
	 * @param \Pimple\Container   $container The plugin's dependency injection container
	 */
	do_action( 'woo_bg/init', $plugin, $container );

	return $plugin;
}

function woo_bg() {
	try {
		return \Woo_BG\Plugin::instance();
	} catch ( \Exception $e ) {
		return woo_bg_init();
	}
}