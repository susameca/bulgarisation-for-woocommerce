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

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$ids[] = $refund->get_id();
			}
		}
		?>
		<ul class="wpo_wcpdf-actions">
			<?php foreach ( $ids as $id ): 
				$order = wc_get_order( $id );
			?>
				<li>
					<?php if ( $order_pdf = $order->get_meta( 'woo_bg_order_document' ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $order_pdf ); ?>" class="button">
							<?php _e('Order PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $invoice_pdf = $order->get_meta( 'woo_bg_invoice_document' ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $invoice_pdf ); ?>" class="button">
							<?php _e('Invoice PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $refunded_order_pdf = $order->get_meta( 'woo_bg_refunded_order_document' ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_order_pdf ); ?>" class="button">
							<?php _e('Refunded Order PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $refunded_invoice_pdf = $order->get_meta( 'woo_bg_refunded_invoice_document' ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_invoice_pdf ); ?>" class="button">
							<?php _e('Refunded Invoice PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>
				</li>
			<?php endforeach ?>
		</ul>
		<?php
	}
}
