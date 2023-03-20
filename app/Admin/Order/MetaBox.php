<?php
namespace Woo_BG\Admin\Order;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class MetaBox {
	function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ) )
		: array( 'shop_order' );

		add_meta_box( 'woo_bg_pdf-box', __( 'PDF Files', 'woo-bg' ), array( __CLASS__, 'pdf_actions_meta_box' ), $screen, 'side', 'high' );
	}

	public static function pdf_actions_meta_box( $post ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order = $post;
			$ids = [ $order->get_id() ];
		} else {
			$ids = [ $post->ID ];
			$order = wc_get_order( $post->ID );
		}

		$files = Documents::get_order_documents( $order );
		?>
		<ul class="wpo_wcpdf-actions">
			<?php foreach ( $files as $file ): ?>
				<li>
					<a target="_blank" href="<?php echo $file[ 'file_url' ]; ?>" class="button">
						<div class="wp-menu-image dashicons-before dashicons-pdf"></div>
						
						<?php echo $file['name'] ?>
					</a>
				</li>
			<?php endforeach ?>
		</ul>
		<?php
	}
}
