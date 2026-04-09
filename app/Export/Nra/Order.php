<?php
namespace Woo_BG\Export\Nra;

use Woo_BG\Admin\Tabs\Nra_Tab;
use Woo_BG\Admin\Order\Documents;

defined( 'ABSPATH' ) || exit;

class Order {
	public $payment_method_type, $order_id_to_show, $options, $woo_order, $order_document_number, $payment_method, $pos_number, $identifier, $vat_group, $vat_groups, $_tax;

	function __construct( $woo_order, $generate_files ) {
		$this->woo_order = $woo_order;
		$this->order_id_to_show = apply_filters( 'woo_bg/admin/export/order_id', $this->woo_order->get_order_number(), $this->woo_order );
		$this->load_options();
		$this->load_order_number( $generate_files );
		$this->load_vat_groups();
		$this->load_payment_method();
	}

	protected function load_vat_groups() {
		$this->vat_group = woo_bg_get_option( 'shop', 'vat_group' );
		$this->vat_groups = woo_bg_get_vat_groups();
		$this->_tax = new \WC_Tax();
	}

	protected function load_order_number( $generate_files ) {
		$order_document_number = $this->woo_order->get_meta( 'woo_bg_order_number' );

		if ( $generate_files == 'true' && !$order_document_number ) {
			Documents::generate_documents( $this->woo_order->get_id() );
			$this->woo_order = wc_get_order( $this->woo_order->get_id() );
			$order_document_number = $this->woo_order->get_meta( 'woo_bg_order_number' );
		}

		$this->order_document_number = $order_document_number;
	}

	protected function load_options() {
		$settings = new Nra_Tab();
		$settings->load_fields();
		$this->options = $settings->get_localized_fields();
	}

	protected function load_payment_method() {
		$this->payment_method = $this->woo_order->get_meta( 'woo_bg_payment_method' );
		$this->payment_method_type = null;

		if ( empty( $this->payment_method ) ) {
			$this->payment_method = ( !empty( $this->options[ $this->woo_order->get_payment_method() ] ) ) ? $this->options[ $this->woo_order->get_payment_method() ] : null;
			
			$this->pos_number = ( !empty( $this->payment_method['virtual_pos_number'] ) ) ? $this->payment_method['virtual_pos_number']['value'] : null;
			$this->identifier = ( !empty( $this->payment_method['identifier'] ) ) ? $this->payment_method['identifier']['value'] : null;

			if ( $this->payment_method ) {
				$this->payment_method_type = ( !empty( $this->payment_method[ 'payment_type' ]['value']['id'] ) ) ? $this->payment_method[ 'payment_type' ]['value']['id'] : null;
			}
		} else {
			$this->pos_number = $this->payment_method['pos_number'];
			$this->identifier = $this->payment_method['identifier'];
			$this->payment_method_type = $this->payment_method['type'];
		}
	}

	protected function get_items_from_order() {
		$items = array();
		$shipping_vat = woo_bg_get_order_shipping_vat( $this->woo_order );
		$order_items = array_merge( $this->woo_order->get_items(), $this->woo_order->get_items('fee') );

		foreach ( $order_items as $key => $item ) {
			if ( ! $item->get_total() ) {
				continue;
			}
			
			$item_vat_rate = woo_bg_get_order_item_vat_rate( $item, $this->woo_order );

			if ( is_a( $item, 'WC_Order_Item_Fee' ) ) {
				$total = $item->get_total() + $item->get_total_tax();

				$items[] = array(
					'name' => $item->get_name(),
					'qty' => $item->get_quantity(),
					'total' => $total,
					'vat_rate' => apply_filters( 'woo_bg/admin/export/item_vat', $item_vat_rate, $item, $this->woo_order ),
				);
			} else {
				$total = round( $item->get_subtotal() + $item->get_subtotal_tax(), 2 );

				$items[] = array(
					'name' => $item->get_name(),
					'qty' => $item->get_quantity(),
					'total' => $total,
					'vat_rate' => apply_filters( 'woo_bg/admin/export/item_vat', $item_vat_rate, $item, $this->woo_order ),
				);
			}
		}

		foreach ( $this->woo_order->get_items( 'shipping' ) as $item ) {
			if ( ! $item->get_total() ) {
				continue;
				$price = 0;
			}

			$price = $item->get_total() + $item->get_total_tax();
			$item_vat = $this->vat_groups[ $this->vat_group ];

			if ( wc_tax_enabled() ) {
				if ( $item->get_total_tax() ) {
					$item_vat = $shipping_vat;
				}
			}

			$price = apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order );
			$item_vat = apply_filters( 'woo_bg/admin/export/item_vat', $item_vat, $item, $this->woo_order );

			$items[] = array(
				'name' => sprintf( __( 'Shipping: %s', 'bulgarisation-for-woocommerce' ), $item->get_name() ),
				'qty' => $item->get_quantity(),
				'total' => $price,
				'vat_rate' => $item_vat,
			);
		}

		return apply_filters( 'woo_bg/admin/nra_export/items', $items, $this->woo_order );
	}

	public function get_order_data() {
		$remove_shipping = woo_bg_maybe_remove_shipping( $this->woo_order );
		$shipping_items = $this->woo_order->get_items( 'shipping' );

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->remove_item( $item_id );
			}

			$this->woo_order->calculate_totals( true );
		}

		$data = [
			'order_id' => $this->order_id_to_show,
			'date_created' => $this->woo_order->get_date_created()->format('Y-m-d'), 
			'order_document_number' => $this->order_document_number,
			'date_modified' => $this->woo_order->get_date_modified()->format('Y-m-d'),
			'total_discount' => apply_filters( 'woo_bg/admin/nra_export/order_total_discount', $this->woo_order->get_total_discount( false ), $this->woo_order ), 
			'payment_method_type' => $this->payment_method_type,
			'pos_number' => $this->pos_number,
			'transaction_id' => $this->woo_order->get_transaction_id(), 
			'identifier' => $this->identifier,
			'items' => $this->get_items_from_order(),
		];

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				if ( $metas = $item->get_meta_data() ) {
					foreach ( $metas as $meta ) {
						$meta_data = $meta->get_data();

						$item->add_meta_data( $meta_data['key'], $meta_data['value'], true );
					}
				}
				
				$this->woo_order->add_item( $item );
				break;
			}

			$this->woo_order->calculate_totals();
		}

		return $data;
	}
}
