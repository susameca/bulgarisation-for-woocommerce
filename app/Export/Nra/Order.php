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
				$this->payment_method_type = ( !empty( $this->payment_methods[ $this->payment_method[ 'payment_type' ]['value']['id'] ] ) ) ? $this->payment_methods[ $this->payment_method[ 'payment_type' ]['value']['id'] ] : null;
			}
		} else {
			$this->pos_number = $this->payment_method['pos_number'];
			$this->identifier = $this->payment_method['identifier'];
			$this->payment_method_type = $this->payment_methods[ $this->payment_method['type'] ];
		}
	}

	protected function get_items_from_order() {
		$items = array();
		$shipping_vat = woo_bg_get_order_shipping_vat( $this->woo_order );
		$order_taxes = $this->woo_order->get_taxes();
		$order_items = array_merge( $this->woo_order->get_items(), $this->woo_order->get_items('fee') );

		foreach ( $order_items as $key => $item ) {
			$price = $item->get_total() / $item->get_quantity();
			$item_vat_rate = woo_bg_get_order_item_vat_rate( $item, $this->woo_order );
			$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;

			if ( $tax_data ) {
				foreach ( $order_taxes as $tax_item ) {
					$tax_item_id       = $tax_item->get_rate_id();
					$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';

					if ( '' !== $tax_item_total ) {
						$item_vat_amout = wc_round_tax_total( $tax_item_total );
					}
				}
			} else {
				$item_vat_amout = $item->get_subtotal_tax();
			}

			if ( is_a( $item, 'WC_Order_Item_Fee' ) ) {
				$items[] = array(
					'name' => $item->get_name(),
					'qty' => $item->get_quantity(),
					'sub_price' => $price,
					'price' => apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order ),
					'vat_rate' => apply_filters( 'woo_bg/admin/export/item_vat', $item_vat_rate, $item, $this->woo_order ),
					'vat_amount' => apply_filters( 'woo_bg/admin/export/item_vat_amount', $item_vat_amout, $item, $this->woo_order ),
				);
			} else {
				$items[] = array(
					'name' => $item->get_name(),
					'qty' => $item->get_quantity(),
					'sub_price' => $this->woo_order->get_item_subtotal( $item, false, true ),
					'price' => apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order ),
					'vat_rate' => apply_filters( 'woo_bg/admin/export/item_vat', $item_vat_rate, $item, $this->woo_order ),
					'vat_amount' => apply_filters( 'woo_bg/admin/export/item_vat_amount', $item_vat_amout, $item, $this->woo_order ),
				);
			}
		}

		foreach ( $this->woo_order->get_items( 'shipping' ) as $item ) {
			if ( ! $item->get_total() ) {
				continue;
				$price = 0;
			} else {
				$price = $item->get_total() / $item->get_quantity();
			}

			$item_vat = $this->vat_groups[ $this->vat_group ];
			$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;

			if ( $tax_data ) {
				foreach ( $order_taxes as $tax_item ) {
					$tax_item_id       = $tax_item->get_rate_id();
					$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
					
					if ( '' !== $tax_item_total ) {
						$item_vat_amout = $tax_item_total;
					}
				}
			} else {
				$item_vat_amout = woo_bg_calculate_vat_from_price( $price, $item_vat_rate ) * $item->get_quantity();
			}

			if ( $price ) {
				if ( wc_tax_enabled() ) {
					if ( $item->get_total_tax() ) {
						$item_vat = $shipping_vat;
					} else {
						$item_tax = $this->_tax::calc_tax( $price, array( array('compound' => 'yes', 'rate' => $item_vat ) ), true )[0];
						$price = $price - $item_tax;
					}
				}
			}

			$price = apply_filters( 'woo_bg/admin/export/item_price', $price, $item, $this->woo_order );
			$item_vat = apply_filters( 'woo_bg/admin/export/item_vat', $item_vat, $item, $this->woo_order );

			$items[] = array(
				'name' => sprintf( __( 'Shipping: %s', 'bulgarisation-for-woocommerce' ), $item->get_name() ),
				'qty' => $item->get_quantity(),
				'sub_price' => $price,
				'price' => $price,
				'vat_rate' => $item_vat,
				'vat_amount' => $item_vat_amout,
			);
		}

		return apply_filters( 'woo_bg/admin/nra_export/items', $items, $this->woo_order );
	}

	public function get_xml_order() {
		$remove_shipping = woo_bg_maybe_remove_shipping( $this->woo_order );
		$shipping_items = $this->woo_order->get_items( 'shipping' );

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->remove_item( $item_id );
			}

			$this->woo_order->calculate_totals( true );
		}

		$items = $this->get_items_from_order();
		$order_total_tax = 0;

		if ( wc_tax_enabled() ) {
			foreach ( $this->woo_order->get_tax_totals() as $code => $tax_total ) {
				$order_total_tax += wc_round_tax_total( $tax_total->amount );
			}
		}

		$xml_order = new Xml\Order(
			$this->order_id_to_show,
			new \DateTime( $this->woo_order->get_date_created() ), 
			$this->order_document_number,
			new \DateTime( $this->woo_order->get_date_created() ),
			apply_filters( 'woo_bg/admin/nra_export/order_total_discount', $this->woo_order->get_total_discount(), $this->woo_order ), 
			$this->payment_method_type,
			[],
			$this->woo_order->get_total(),
			$order_total_tax,
			$this->woo_order->get_subtotal(),
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
				$item['vat_amount'],
				$item['vat_rate'],
				wc_tax_enabled()
			) );
		}

		if ( sizeof( $shipping_items ) > 0 && $remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				if ( $metas = $item->get_meta_data() ) {
					foreach ( $metas as $meta ) {
						$data = $meta->get_data();

						$item->add_meta_data( $data['key'], $data['value'], true );
					}
				}
				
				$this->woo_order->add_item( $item );
				break;
			}

			$this->woo_order->calculate_totals();
		}

		return $xml_order;
	}
}
