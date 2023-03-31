<?php
namespace Woo_BG\Export\Nra;

use Woo_BG\Admin\Tabs\Nra_Tab;
use Woo_BG\Admin\Order\Documents;

defined( 'ABSPATH' ) || exit;

class Order {
	public $payment_methods, $payment_method_type, $order_id_to_show, $options, $woo_order, $order_document_number, $payment_method, $pos_number, $identifier, $vat_group, $vat_groups, $_tax;

	function __construct( $woo_order, $generate_files ) {
		$this->woo_order = $woo_order;
		$this->payment_methods = woo_bg_get_payment_types_for_meta();
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
				$this->payment_method_type = ( !empty( $this->payment_methods[ $this->payment_method[ 'payment_type' ]['value']['id'] ] ) ) ? $this->payment_methods[ $this->payment_method[ 'payment_type' ]['value']['id'] ] : null;
			}
		} else {
			$this->pos_number = $this->payment_method['pos_number'];
			$this->identifier = $this->payment_method['identifier'];
			$this->payment_method_type = $this->payment_methods[ $this->payment_method['type'] ];
		}
	}

	protected function get_items_from_order() {
		$remove_shipping = woo_bg_get_option( 'invoice', 'remove_shipping' );
		$shipping_items = $this->woo_order->get_items( 'shipping' );

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->remove_item( $item_id );
			}

			$this->woo_order->calculate_totals();
		}

		$items = array();
		$shipping_vat = woo_bg_get_order_shipping_vat( $this->woo_order );

		foreach ( $this->woo_order->get_items() as $key => $item ) {
			$price = $item->get_total() / $item->get_quantity();
			$item_vat = woo_bg_get_order_item_vat_rate( $item, $this->woo_order );

			$items[] = array(
				'name' => $item->get_name(),
				'qty' => $item->get_quantity(),
				'sub_price' => $item->get_subtotal() / $item->get_quantity(),
				'price' => apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order ),
				'vat' => apply_filters( 'woo_bg/admin/export/item_vat', $item_vat, $item, $this->woo_order ),
			);
		}

		foreach ( $this->woo_order->get_items( 'shipping' ) as $item ) {
			$price = $item->get_total() / $item->get_quantity();
			$item_vat = $this->vat_groups[ $this->vat_group ];

			if ( wc_tax_enabled() ) {
				if ( $item->get_total_tax() ) {
					$item_vat = $shipping_vat;
				} else {
					$item_tax = $this->_tax::calc_tax( $price, array( array('compound' => 'yes', 'rate' => $item_vat ) ), true )[0];
					$price = $price - $item_tax;
				}
			}

			$price = apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order );
			$item_vat = apply_filters( 'woo_bg/admin/export/item_vat', $item_vat, $item, $this->woo_order );

			$items[] = array(
				'name' => sprintf( __( 'Shipping: %s', 'woo-bg' ), $item->get_name() ),
				'qty' => $item->get_quantity(),
				'sub_price' => $price,
				'price' => $price,
				'vat' => $item_vat,
			);
		}

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->add_item( $item );
			}

			$this->woo_order->calculate_totals();
		}

		return apply_filters( 'woo_bg/admin/nra_export/items', $items, $this->woo_order );
	}

	public function get_xml_order() {
		$items = $this->get_items_from_order();

		$xml_order = new Xml\Order(
			$this->order_id_to_show,
			new \DateTime( $this->woo_order->get_date_created() ), 
			$this->order_document_number,
			new \DateTime( $this->woo_order->get_date_created() ),
			$this->woo_order->get_total_discount(), 
			$this->payment_method_type,
			[], 
			$this->pos_number,
			$this->woo_order->get_transaction_id(), 
			$this->identifier,
		);

		foreach ( $items as $item ) {
			$xml_order->addItem( new Xml\Item( 
				$item['name'], 
				$item['qty'], 
				$item['sub_price'],
				$item['price'],
				$item['vat']
			) );

		}

		return $xml_order;
	}
}
