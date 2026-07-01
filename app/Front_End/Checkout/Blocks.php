<?php
namespace Woo_BG\Front_End\Checkout;

use Woo_BG\Front_End\Multi_Currency;

defined( 'ABSPATH' ) || exit;

/**
 * Multi_Currency::display_price_in_multiple_currencies() filters wc_price(),
 * which the WooCommerce Cart/Checkout blocks never call — block prices are
 * rendered client-side from the Store API. This class enqueues a script that
 * reproduces the same dual BGN/EUR price display for block-based cart/checkout.
 */
class Blocks {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! function_exists( 'WC' ) || ! wp_script_is( 'wc-blocks-checkout', 'registered' ) ) {
			return;
		}

		$asset_file = woo_bg()->plugin_dir_path() . 'blocks-frontend/build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_register_script(
			'woo-bg-blocks-checkout',
			woo_bg()->plugin_dir_url() . 'blocks-frontend/build/index.js',
			// wc-blocks-checkout is WooCommerce's WP handle for @woocommerce/blocks-checkout.
			// It is not listed in index.asset.php because @wordpress/dependency-extraction-webpack-plugin
			// does not handle @woocommerce/* packages -- it is declared as an external in
			// blocks-frontend/webpack.config.js instead.
			array_merge( $asset['dependencies'], array( 'wc-blocks-checkout' ) ),
			$asset['version'],
			true
		);

		wp_set_script_translations( 'woo-bg-blocks-checkout', 'bulgarisation-for-woocommerce', woo_bg()->plugin_dir_path() . 'languages' );

		wp_enqueue_style(
			'woo-bg-blocks-checkout',
			woo_bg()->plugin_dir_url() . 'blocks-frontend/build/index.css',
			array(),
			$asset['version']
		);

		wp_localize_script(
			'woo-bg-blocks-checkout',
			'wooBgBlocksCheckout',
			array(
				'locale' => get_locale(),
				'rate'   => Multi_Currency::get_eur_rate(),
				'debug'  => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);

		wp_enqueue_script( 'woo-bg-blocks-checkout' );
	}
}
