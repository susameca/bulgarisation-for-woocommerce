<?php
namespace Woo_BG\Export\Nra;

use Woo_BG\Admin\Tabs\Nra_Tab;

defined( 'ABSPATH' ) || exit;

class RefundedOrder {
	public $options, $parent_order, $return_methods, $woo_order;

	function __construct( $woo_order ) {
		$this->woo_order = $woo_order;
		$this->return_methods = array(
			'1' => Xml\ReturnMethods\IBAN::class, //1
			'2' => Xml\ReturnMethods\Card::class, //2
			'3' => Xml\ReturnMethods\Cash::class, //3
			'4' => Xml\ReturnMethods\Other::class, //4
		);
		$this->load_options();


		if ( $this->woo_order->get_parent_id() ) {
			$this->parent_order = wc_get_order( $this->woo_order->get_parent_id() );
		} else {
			$this->parent_order = $this->woo_order;
		}
	}

	public function get_xml_order() {
		$total = abs( $this->woo_order->get_total() );

		if ( 
			woo_bg_maybe_remove_shipping( $this->parent_order ) === 'yes' &&
			abs( $this->parent_order->get_total() ) === abs( $total )
		) {
			$total = $total - ( abs( $this->parent_order->get_shipping_total() ) + abs( $this->parent_order->get_shipping_tax() ) );
		}

		return new Xml\ReturnedOrder(
			apply_filters( 'woo_bg/admin/export/refunded_order_id', $this->parent_order->get_order_number(), $this->woo_order ), 
			abs( $total ), 
			new \DateTime( $this->woo_order->get_date_created() ), 
			$this->return_methods[ $this->options[ 'shop' ][ 'return_method' ][ 'value' ]['id'] ]
		);
	}

	protected function load_options() {
		$settings = new Nra_Tab();
		$settings->load_fields();
		$this->options = $settings->get_localized_fields();
	}
}
