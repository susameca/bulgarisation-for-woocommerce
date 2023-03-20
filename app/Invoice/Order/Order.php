<?php
namespace Woo_BG\Invoice\Order;

defined( 'ABSPATH' ) || exit;

class Order {
	public $woo_order;
	private $vat_group, $vat_percentages, $_tax, $vat, $order_taxes;

	function __construct( $order ) {
		$this->woo_order = $order;

		$this->set_vat_and_taxes();
	}

	private function set_vat_and_taxes() {
		$this->vat_group = woo_bg_get_option( 'shop', 'vat_group' );
		$this->vat_percentages = woo_bg_get_vat_groups();
		$this->_tax = new \WC_Tax();
		$this->vat = $this->vat_percentages[ $this->vat_group ];
		$this->order_taxes = $this->woo_order->get_taxes();
	}

	public function get_items() {
		$items = array();
		$order_items = array_merge( $this->woo_order->get_items(), $this->woo_order->get_items('fee') );

		if ( empty( $order_items ) ) {
			$parent_order = wc_get_order( $this->woo_order->get_parent_id() );
			$this->woo_order = $parent_order;
			$order_items = array_merge( $parent_order->get_items(), $parent_order->get_items('fee') );
			$this->order_taxes = $this->woo_order->get_taxes();
		}

		foreach ( $order_items as $key => $item ) {
			$items[] = array(
				'name' => apply_filters( 'woo_bg/invoice/order/item_name', $item->get_name(), $item ),
				'quantity' => abs( $item->get_quantity() ), 
				'vat_rate' => $this->get_item_vat_rate( $item, $this->woo_order ) . "%", 
				'price' => wc_price( abs( $item->get_total() / $item->get_quantity() ), array( 'currency' => $this->woo_order->get_currency() ) ),
				'total' => wc_price( abs( $item->get_total() ), array( 'currency' => $this->woo_order->get_currency() ) )
			);
		}

		$shipping_vat = woo_bg_get_order_shipping_vat( $this->woo_order );

		foreach ( $this->woo_order->get_items( 'shipping' ) as $item ) {
			$item_price = $item->get_total() / $item->get_quantity();
			$item_vat = $this->vat;
			$item_tax = $item->get_total_tax();

			if ( wc_tax_enabled() ) {
				if ( $item->get_total_tax() ) {
					$item_vat = $shipping_vat;
				} else {
					$item_tax = $this->_tax::calc_tax(  $item_price, array( array('compound' => 'yes', 'rate' => $this->vat ) ), true )[0];
					$item_price = $item_price - $item_tax;
				}
			}

			$item_total = $item_price * $item->get_quantity();

			$items[] = array(
				'name' => apply_filters( 'woo_bg/invoice/order/item_name', sprintf( __('Shipping: %s', 'woo-bg'), $item->get_name() ), $item ),
				'quantity' => abs( $item->get_quantity() ), 
				'vat_rate' => $item_vat . "%", 
				'price' => wc_price( abs( $item_price ), array( 'currency' => $this->woo_order->get_currency() ) ),
				'total' => wc_price( abs( $item_total ), array( 'currency' => $this->woo_order->get_currency() ) )
			);
		}

		return apply_filters( 'woo_bg/invoice/order/items', $items, $this );
	}

	public function get_total_items() {
		$items = array(
			'subtotal' => array(
				'label' => __( "Total", 'woo-bg' ),
				'value' => wc_price( abs( $this->woo_order->get_subtotal() + $this->woo_order->get_shipping_total() + $this->woo_order->get_total_fees() ) , array( 'currency' => $this->woo_order->get_currency() ) ),
			),
		);

		if ( 0 < $this->woo_order->get_total_discount() ) {
			$items['discount'] = array(
				'label' => __( "Discount", 'woo-bg' ),
				'value' => wc_price( abs( $this->woo_order->get_total_discount() ), array( 'currency' => $this->woo_order->get_currency() ) ),
			);
		}

		if ( wc_tax_enabled() ) {
			foreach ( $this->woo_order->get_tax_totals() as $code => $tax_total ) {
				$items['tax-' . sanitize_title_with_dashes( $code )] = array(
					'label' => esc_html( $tax_total->label ),
					'value' => wc_price( wc_round_tax_total( abs( $tax_total->amount ) ), array( 'currency' => $this->woo_order->get_currency() ) ),
				);
			}
		}

		$items['total'] = array(
			'label' => __( "Total due", 'woo-bg' ),
			'value' => wc_price( abs( $this->woo_order->get_total() ), array( 'currency' => $this->woo_order->get_currency() ) ),
		);

		return apply_filters( 'woo_bg/invoice/order/total_items', $items, $this );
	}

	public function get_woo_order() {
		return $this->woo_order;
	}

	public function get_item_vat_rate( $item ) {
		$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
		$rate = $this->vat;

		if ( $tax_data ) {
			$founded = false;

			foreach ( $this->order_taxes as $tax_item ) {
				$tax_item_id       = $tax_item->get_rate_id();
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
					
				if ( '' !== $tax_item_total ) {
					$rate = $tax_item->get_rate_percent();
					$founded = true;
					break;
				}
			}

			if ( !$founded ) {
				$rate = 0;
			}
		}

		return $rate;
	}
}