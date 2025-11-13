<?php
namespace Woo_BG\Invoice\Document;

use Woo_BG\Image_Uploader;
use chillerlan\QRCode\QRCode;

defined( 'ABSPATH' ) || exit;

class NRA extends BaseDocument {
	function __construct( $order ) {
		parent::__construct( $order );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/order_document_title', __( 'Order - Original', 'bulgarisation-for-woocommerce' ) ) );
		$this->meta = 'woo_bg_order_document';
		$this->generate_QR_code();
	}

	public function generate_QR_code() {
		$qr_code_pieces = array(
			$this->nap_number,
			$this->woo_order->get_order_number(),
			$this->woo_order->get_transaction_id(),
			$this->woo_order->get_date_created()->date_i18n('Y-m-d'),
			$this->woo_order->get_date_created()->date_i18n('G:i:s'),
			$this->woo_order->get_total(),
		);

		$qr_code = new QRCode();
		ob_start();
		?>
		<img src="<?php echo $qr_code->render( implode( '*', $qr_code_pieces ) ); ?>">
		<?php

		$this->logo = ob_get_clean();
	}
}