<?php
namespace Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class NRA extends BaseDocument {
	function __construct( $order ) {
		parent::__construct( $order );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/order_document_title', __( 'Order - Original', 'woo-bg' ) ) );
		$this->meta = 'woo_bg_order_document';
	}
}