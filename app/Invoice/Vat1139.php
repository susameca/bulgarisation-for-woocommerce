<?php
namespace Woo_BG\Invoice;

class Vat1139 {
    public function __construct() {
        add_filter( 'woo_bg/invoice/cart_headers', [ __CLASS__, 'filter_cart_headers' ], 10, 2 );
        add_action( 'woo_bg/invoice/pdf/default_template/additional_css', [ __CLASS__, 'add_filter_for_order_items' ], 10, 2 );
        add_action( 'woo_bg/invoice/pdf/default_template/after_table', [ __CLASS__, 'action_after_table' ], 15, 2 );  
    }

    public static function add_filter_for_order_items( $pdf ) {
        if ( is_a( $pdf->document, 'Woo_BG\Invoice\Document\Invoice' ) ) {
            add_filter( 'woo_bg/invoice/order/items', [ __CLASS__, 'filter_order_items' ], 10, 2 );
            add_filter( 'woo_bg/invoice/order/total_items', [ __CLASS__, 'filter_order_totals' ], 10, 2 );
        }
    }

    public static function filter_cart_headers( $items, $doc ) {
        if ( is_a( $doc, 'Woo_BG\Invoice\Document\Invoice' ) ) {
            unset( $items[2] );
        }

        return $items;
    }

    public static function filter_order_items( $items, $order ) {
        foreach ( $items as &$item ) {
            unset( $item['vat_rate'] );
        }

        return $items;
    }

    public static function filter_order_totals( $totals, $order ) {
        $totals['subtotal']['label'] = __( 'Total amount', 'bulgarisation-for-woocommerce' ) . ":";
        $totals['total']['label'] = '<strong>' . __( 'Amount due', 'bulgarisation-for-woocommerce' ) . "</strong>:";

        return $totals;
    }

    public static function action_after_table( $order, $pdf ) {
        if ( is_a( $pdf->document, 'Woo_BG\Invoice\Document\Invoice' ) ) {
            $text = apply_filters('woo_bg/invoice/vat_113_9_additional_text', __('The invoice has been issued on the basis of Article 113, paragraph 9 of the Bulgarian VAT Act.', 'bulgarisation-for-woocommerce' ) );
            echo wp_kses_post( '<p class="fz-12">' . $text . '</p>' );

            remove_filter( 'woo_bg/invoice/order/items', [ __CLASS__, 'filter_order_items' ], 10, 2 );
            remove_filter( 'woo_bg/invoice/order/total_items', [ __CLASS__, 'filter_order_totals' ], 10, 2 );
        }
    }
}