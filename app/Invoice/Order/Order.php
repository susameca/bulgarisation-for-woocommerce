<?php
namespace Woo_BG\Invoice\Order;

use Woo_BG\Invoice\CurrencyToString;

defined( 'ABSPATH' ) || exit;

class Order {
	public $woo_order;
	private $vat_group, $vat_percentages, $_tax, $vat, $order_taxes, $remove_shipping;

	function __construct( $order ) {
		$this->woo_order = $order;

		$this->set_vat_and_taxes();
		$this->set_remove_shipping();

		if ( woo_bg_get_option( 'invoice', 'total_text' ) === 'yes' ) {
			add_action( 'woo_bg/invoice/pdf/default_template/after_table', [ __CLASS__, 'print_total_in_words' ], 10, 2 );
		}
	}

	public static function get_default_taxable_shipping_rates() {
		return array( 'local_pickup', 'flat_rate' );
	}

	private function set_vat_and_taxes() {
		$this->vat_group = woo_bg_get_option( 'shop', 'vat_group' );
		$this->vat_percentages = woo_bg_get_vat_groups();
		$this->_tax = new \WC_Tax();
		$this->vat = $this->vat_percentages[ $this->vat_group ];
		$this->order_taxes = $this->woo_order->get_taxes();
	}

	private function set_remove_shipping() {
		$this->remove_shipping = woo_bg_maybe_remove_shipping( $this->woo_order );
	}

	public function get_items() {
		$shipping_items = $this->woo_order->get_items( 'shipping' );

		if ( sizeof( $shipping_items ) > 0 && $this->remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->remove_item( $item_id );
			}

			$this->woo_order->calculate_totals();
		}

		$items = array();
		$order_items = array_merge( $this->woo_order->get_items(), $this->woo_order->get_items('fee') );

		if ( empty( $order_items ) ) {
			$parent_order = wc_get_order( $this->woo_order->get_parent_id() );

			if ( abs( $parent_order->get_total() ) === abs( $this->woo_order->get_total() ) ) {
				$this->woo_order = $parent_order;
				$order_items = array_merge( $parent_order->get_items(), $parent_order->get_items('fee') );
				$this->order_taxes = $this->woo_order->get_taxes();
			} else {
				$items[] = array(
					'name' => apply_filters( 'woo_bg/invoice/order/item_name', $this->woo_order->get_reason(), null ),
					'quantity' => 1, 
					'vat_rate' => '', 
					'price' => wc_price( abs( $this->woo_order->get_total() ), array( 'currency' => $this->woo_order->get_currency() ) ),
					'total' => wc_price( abs( $this->woo_order->get_total() ), array( 'currency' => $this->woo_order->get_currency() ) )
				);
			}
		}

		if ( !empty( $order_items ) ) {
			foreach ( $order_items as $key => $item ) {
				$qty = ( abs( $item->get_quantity() ) ) ? abs( $item->get_quantity() ) : 1;
				
				$items[] = apply_filters( 'woo_bg/invoice/order/item', array(
					'name' => apply_filters( 'woo_bg/invoice/order/item_name', $item->get_name(), $item ),
					'quantity' => $qty, 
					'vat_rate' => woo_bg_get_order_item_vat_rate( $item, $this->woo_order, 1 ) . "%", 
					'price' => wc_price( abs( $item->get_total() / $qty ), array( 'currency' => $this->woo_order->get_currency() ) ),
					'total' => wc_price( abs( $item->get_total() ), array( 'currency' => $this->woo_order->get_currency() ) )
				), $item, $this );
			}
		}

		if ( sizeof( $this->woo_order->get_items( 'shipping' ) ) > 0 && $this->remove_shipping !== 'yes') {
			$shipping_vat = woo_bg_get_order_shipping_vat( $this->woo_order );

			foreach ( $this->woo_order->get_items( 'shipping' ) as $item ) {
				if ( !$item->get_total() ) {
					$item_price = 0;
				} else {
					$item_price = $item->get_total() / absint( $item->get_quantity() );
				}

				$item_vat = $this->vat;
				$item_tax = $item->get_total_tax();
				$item_tax_status = 'taxable';

				if ( in_array( $item->get_method_id(), self::get_default_taxable_shipping_rates() ) ) {
					if ( $instance = \WC_Shipping_Zones::get_shipping_method ( $item->get_instance_id() ) ) {
						$item_tax_status = $instance->get_instance_option( 'tax_status' );
						
						if ( $item_tax_status === 'none' ) {
							$item_vat = 0;
						}
					}
				}

				if ( wc_tax_enabled() && $item_tax_status === 'taxable' ) {
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
					'vat_rate' => woo_bg_maybe_add_rate_group( $item_vat ) . "%", 
					'price' => wc_price( abs( $item_price ), array( 'currency' => $this->woo_order->get_currency() ) ),
					'total' => wc_price( abs( $item_total ), array( 'currency' => $this->woo_order->get_currency() ) )
				);
			}
		}

		if ( sizeof( $shipping_items ) > 0 && $this->remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				if ( $metas = $item->get_meta_data() ) {
					foreach ( $metas as $meta ) {
						$data = $meta->get_data();

						$item->add_meta_data( $data['key'], $data['value'], true );
					}
				}
				
				$this->woo_order->add_item( $item );
			}

			$this->woo_order->calculate_totals();
		}

		return apply_filters( 'woo_bg/invoice/order/items', $items, $this );
	}

	public function get_total_items() {
		$shipping_items = $this->woo_order->get_items( 'shipping' );
		$shipping_total = $this->woo_order->get_shipping_total();
		$subtotal = abs( $this->woo_order->get_subtotal() + $shipping_total + $this->woo_order->get_total_fees() );

		if ( method_exists( $this->woo_order, 'get_reason' ) && $this->woo_order->get_reason() && $subtotal == 0 ) {
			$subtotal = abs( $this->woo_order->get_total() );
		}

		foreach ( $shipping_items as $item ) {
			if ( in_array( $item->get_method_id(), self::get_default_taxable_shipping_rates() ) ) {
				if ( $instance = \WC_Shipping_Zones::get_shipping_method ( $item->get_instance_id() ) ) {
					$item_tax_status = $instance->get_instance_option( 'tax_status' );
					
					if ( $item_tax_status === 'none' ) {
						$shipping_total = 0;
					}
				}
			}
		}

		if ( sizeof( $shipping_items ) > 0 && $this->remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				$this->woo_order->remove_item( $item_id );
			}

			$this->woo_order->calculate_totals();

			$subtotal = abs( $this->woo_order->get_subtotal() + $this->woo_order->get_total_fees() );
		}

		$items = array(
			'subtotal' => array(
				'label' => __( "Total", 'woo-bg' ),
				'value' => wc_price(  $subtotal, array( 'currency' => $this->woo_order->get_currency() ) ),
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
			'value_number' => abs( $this->woo_order->get_total() ),
		);

		if ( sizeof( $shipping_items ) > 0 && $this->remove_shipping === 'yes' ) {
			foreach ( $shipping_items as $item_id => $item ) {
				if ( $metas = $item->get_meta_data() ) {
					foreach ( $metas as $meta ) {
						$data = $meta->get_data();

						$item->add_meta_data( $data['key'], $data['value'], true );
					}
				}

				$this->woo_order->add_item( $item );
			}

			$this->woo_order->calculate_totals();
		}

		return apply_filters( 'woo_bg/invoice/order/total_items', $items, $this );
	}

	public function get_woo_order() {
		return $this->woo_order;
	}

	public static function print_total_in_words( $woo_order, $pdf ) {
		if ( $woo_order->get_currency() === 'BGN' ) {
			$total_items = $pdf->document->order->get_total_items();
			echo wp_kses_post( '<p class="fz-12"><strong>Словом</strong>: ' . CurrencyToString::number_to_lev( $total_items['total']['value_number'] ) . "</p>" );
		}
	}
}