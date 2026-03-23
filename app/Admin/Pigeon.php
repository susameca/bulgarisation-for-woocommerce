<?php
namespace Woo_BG\Admin;
use Woo_BG\Container\Client;
use Woo_BG\Shipping\Pigeon\Method;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class Pigeon {
	private static $container = null;

	public function __construct() {
	}

	public static function message_dismiss_callback() {
		update_option( 'woo_bg_pigeon_express_message_dismiss', true );
	}
}