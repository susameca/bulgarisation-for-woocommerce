<?php
namespace Woo_BG\Reports\Providers;

defined( 'ABSPATH' ) || exit;

use Woo_BG\Container\Client;

class Connectix extends Provider_Base {
    const REPORTS_URL = 'https://api.nekorekten.com/api/v1/reports';
	const IMAGES_BASE_URL = 'https://api.nekorekten.com';

	public function __construct() {
        $this->set_name( __('Connectix', 'bulgarisation-for-woocommerce' ) );
    }

    public static function get_all_reports( $order, $force = false ) {
        $container = woo_bg()->container();
        $phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();
        $meta_key = 'woo_bg_reports-connectix_phone_' . $phone;
		$meta = $order->get_meta( $meta_key );
        $levels = $container[ Client::CONNECTIX ]::get_levels_of_probability();
        $args = [
            'reports' => [],
            'levels' => $levels,
        ];

		if ( $meta && !$force ) {
			$result = $meta;
		} else {
            $container = woo_bg()->container();
		    $result = $container[ Client::CONNECTIX ]->api_call( $container[ Client::CONNECTIX ]::CHECK_ENDPOINT, [ 'phone' => $phone ] );

            $order->update_meta_data( $meta_key, $result );
            $order->save();
        }
        
        $args['result'] = $result;
        $result_index = array_search( $result, array_column( $levels, 'key' ), true );

        if ( $result_index !== false)  {
            $option_index = array_search( woo_bg_get_option( 'connectix', 'level_of_warning' ), array_column( $levels, 'key' ), true );

            if ( $result_index >= $option_index ) {
                $args['reports'] = [ sprintf( __('Possibility that the shipment will not be picked up: %s', 'bulgarisation-for-woocommerce'), $levels[ $result_index ]['label'] ), ];
            }
        } else {
            $args['error'] = sprintf( __( 'Error: %s', 'bulgarisation-for-woocommerce' ), $result );
        }

		return $args;
	}

    public static function get_all_reports_from_meta( $order ) {
        $container = woo_bg()->container();
        $levels = $container[ Client::CONNECTIX ]::get_levels_of_probability();
        $phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();
        $meta_key = 'woo_bg_reports-connectix_phone_' . $phone;
        $args = [ 'reports' => [] ];
        $result = $order->get_meta( $meta_key );
        $result_index = array_search( $result, array_column( $levels, 'key' ), true );

        if ( $result_index !== false)  {
            $option_index = array_search( woo_bg_get_option( 'connectix', 'level_of_warning' ), array_column( $levels, 'key' ), true );

            if ( $result_index >= $option_index ) {
                $args['reports'] = [ sprintf( __('Possibility that the shipment will not be picked up: %s', 'bulgarisation-for-woocommerce'), $levels[ $result_index ]['label'] ), ];
            }
        }

		return $args;
	}

    function print_reports_html( $order ) {
        $reports = self::get_all_reports( $order );
        $result_index = array_search( $reports['result'], array_column( $reports['levels'], 'key' ), true );
        
        if ( $result_index !== false ) {
            $title = sprintf( esc_html__( 'Possibility that the shipment will not be picked up: %s', 'bulgarisation-for-woocommerce' ), $reports['levels'][ $result_index ]['label'] );
        } else {
            $title = $reports['error'];
        }
        ?>
        <div class="order_data_column_container">
            <h4><?php echo esc_html( $title ); ?></h4>
        </div><!-- /.order_data_column_container -->
        <?php
    }

	public static function check_for_reports_on_checkout( $phone ) {
        $result = $container[ Client::CONNECTIX ]->api_call( $container[ Client::CONNECTIX ]::CHECK_ENDPOINT, [ 'phone' => $phone ] );
        $result_index = array_search( $result, array_column( $levels, 'key' ), true );
        $reports = [];

        if ( $result_index !== false)  {
            $option_index = array_search( woo_bg_get_option( 'connectix', 'level_of_warning' ), array_column( $levels, 'key' ), true );

            if ( $result_index >= $option_index ) {
                $reports[] = sprintf( __('Possibility that the shipment will not be picked up: %s', 'bulgarisation-for-woocommerce'), $levels[ $result_index ]['label'] );
            }
        }

		return $reports;
	}
}