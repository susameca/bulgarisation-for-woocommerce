<?php
namespace Woo_BG\Admin\Order;

use Woo_BG\Admin\Speedy;
use Woo_BG\Admin\Econt;
use Woo_BG\Admin\CVC;
use Woo_BG\Admin\BoxNow;
use Woo_BG\Admin\Admin_Menus;

defined( 'ABSPATH' ) || exit;

class Labels {
	public static function generate_label_from_listing_page_callback() {
		woo_bg_check_admin_label_actions();

		$order_id = sanitize_text_field( $_REQUEST['orderId'] );
		$method = sanitize_text_field( $_REQUEST['method'] );
		$order = wc_get_order( $order_id );

		switch ( $method ) {
			case 'woo_bg_speedy':
				Speedy::generate_label_after_order_generated( $order_id );

				break;
			case 'woo_bg_econt':
				Econt::generate_label_after_order_generated( $order_id );

				break;
			case 'woo_bg_cvc':
				CVC::generate_label_after_order_generated( $order_id );

				break;
			case 'woo_bg_boxnow':
				BoxNow::generate_label( $order_id );

				break;
		}

		$data = woo_bg_get_order_label( $order_id );
		$html = '';

		if ( !empty( $data ) ) {
			ob_start();
			Admin_Menus::render_fragment( 'label-column', [ 
				'data' => $data,
				'order' => $order,
			] );
			$html = ob_get_clean();
		}

		wp_send_json( [ 'html' => $html ] );
		wp_die;
	}
}
