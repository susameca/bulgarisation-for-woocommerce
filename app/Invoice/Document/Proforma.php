<?php
namespace Woo_BG\Invoice\Document;

defined( 'ABSPATH' ) || exit;

class Proforma extends BaseDocument {
	function __construct( $order ) {
		parent::__construct( $order );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/order_document_title', __( 'Pro forma', 'woo-bg' ) ) );
		$this->meta = 'woo_bg_proform_document';
	}
}