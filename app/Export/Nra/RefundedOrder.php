<?php
namespace Woo_BG\Export\Nra;

use Woo_BG\Admin\Tabs\Nra_Tab;

defined( 'ABSPATH' ) || exit;

class RefundedOrder {
	public $options, $return_methods, $woo_order, $export_date;

	function __construct( $woo_order, $export_date ) {
		$this->woo_order = $woo_order;
		$this->export_date = $export_date;

		$this->load_options();
	}

	public function get_order_data() {
		$total = 0;
		$refunds = $this->woo_order->get_refunds();

		foreach ( $refunds as $refund ) {
			if ( date_i18n( 'Y-m', strtotime( $refund->get_date_created()->__toString() ) ) === $this->export_date  ) {
				$refund_date = $refund->get_date_created()->format('Y-m-d');
				$total += apply_filters( 'woo_bg/admin/export/refunded_total', $refund->get_amount(), $refund );
			}
		}

		return [
			'order_id' => apply_filters( 'woo_bg/admin/export/refunded_order_id', $this->woo_order->get_order_number(), $this->woo_order ), 
			'total' => abs( $total ), 
			'date' => $refund_date, 
			'return_method' => $this->options[ 'shop' ][ 'return_method' ][ 'value' ]['id'],
		];
	}

	protected function load_options() {
		$settings = new Nra_Tab();
		$settings->load_fields();
		$this->options = $settings->get_localized_fields();
	}
}
