<?php
namespace Woo_BG\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Woo_BG\Admin\Tabs;

/**
 * Setup menus in WP admin.
 *
 * @package Woo_BG\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class Admin_Menus {
	public $tabs;

	public function __construct() {
		add_filter( 'woocommerce_navigation_is_connected_page', array( __CLASS__, 'is_connect_woo_bg_pages' ), 9, 2 );
		add_action( 'wp_loaded', array(  __CLASS__, 'after_setup_theme' ) );
		add_action( 'woocommerce_screen_ids', array( __CLASS__, 'add_screen_id' ), 0);
		add_filter( 'upload_mimes', array( __CLASS__, 'allow_upload_xml' ) );
	}

	public static function add_screen_id( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_woo-bg';
		
		return $screen_ids;
	}

	public static function after_setup_theme() {
		new Ajax();

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	/**
	 * Add menu items.
	 */
	public static function admin_menu() {
		add_submenu_page( 
			'woocommerce', 
			__( 'Bulgarisation', 'bulgarisation-for-woocommerce' ), 
			__( 'Bulgarisation', 'bulgarisation-for-woocommerce' ), 
			'edit_others_shop_orders', 
			'bulgarisation-for-woocommerce', 
			array( __CLASS__, 'settings_page_init' ) 
		);
	}

	public static function is_connect_woo_bg_pages( $is_connected, $current_page ) {
		if ( false === $is_connected && false === $current_page ) {
			$screen_id        = PageController::get_instance()->get_current_screen_id();
			$pages_to_connect = apply_filters( 'woo_bg/admin/register_pages', array(
				'bulgarisation-for-woocommerce'  => array(
					'title' => __( 'Bulgarisation', 'bulgarisation-for-woocommerce' ),
				)
			) );

			foreach ( $pages_to_connect as $page_id => $page_data ) {
				if ( preg_match( "/^woocommerce_page_{$page_id}/", $screen_id ) ) {
					add_filter( 'woocommerce_navigation_get_breadcrumbs', array( __CLASS__, 'connect_to_breadcrumbs' ) );
					add_filter( 'admin_body_class', array( __CLASS__, 'add_body_class' ) );

					return true;
				}
			}
		}

		return $is_connected;
	}

	public static function add_body_class( $classes ) {
		$classes .= ' woocommerce_page_wc-settings';

		return $classes;
	}

	public static function connect_to_breadcrumbs( $breadcrumbs ) {
		return apply_filters( 'woo_bg/admin/register_breadcrumbs', array(
			array(
				'admin.php?page=wc-admin',
				__( 'WooCommerce', 'bulgarisation-for-woocommerce' ),
			),

			__( 'Bulgarisation', 'bulgarisation-for-woocommerce' ),
		) );
	}

	/**
	 * Loads gateways and shipping methods into memory for use within settings.
	 */
	public static function settings_page_init() {
		self::render_fragment( 'tabs', array(
			'tabs' => self::get_tabs(),
		) );
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public static function get_tabs() {
		$tabs = array(
			new Tabs\Settings_Tab(),
		);

		if ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' ) {
			$tabs[] = new Tabs\Nra_Tab();
			$tabs[] = new Tabs\Export_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' ) {
			$tabs[] = new Tabs\Econt_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' ) {
			$tabs[] = new Tabs\Speedy_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_boxnow' ) === 'yes' ) {
			$tabs[] = new Tabs\BoxNow_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' ) {
			$tabs[] = new Tabs\CVC_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_nekorekten' ) === 'yes' ) {
			$tabs[] = new Tabs\Nekorekten_Com_Tab();
		}

		if ( woo_bg_get_option( 'apis', 'enable_multi_currency' ) === 'yes' ) {
			$tabs[] = new Tabs\Multi_Currency_Tab();
		}

		$tabs = apply_filters( 'woo_bg/admin/get_tabs_items', $tabs );
		$tabs[] = new Tabs\Pro_Tab();
		$tabs[] = new Tabs\Help_Tab();

		return $tabs;
	}

	public static function render_fragment( $fragment, $atts = array() ) {
		$fragment_dir = __DIR__ . DIRECTORY_SEPARATOR ."fragments" . DIRECTORY_SEPARATOR . $fragment . ".php";

		if ( ! is_readable( $fragment_dir ) ) {
			return;
		}

		extract( $atts );

		include( $fragment_dir );
	}

	public static function allow_upload_xml( $mimes ) {
	    $mimes = array_merge( $mimes, array( 'xml' => 'application/xml' ) );

	    return $mimes;
	}
}
