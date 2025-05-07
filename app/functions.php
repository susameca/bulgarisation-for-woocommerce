<?php
use Woo_BG\Export\Nra\Xml\PaymentTypes;
use Woo_BG\File;

function woo_bg_assets_bundle( $path ) {
	static $manifest = null;

	if ( is_null( $manifest ) ) {
		$manifest_path = woo_bg()->plugin_dir_path() . 'dist/manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( File::get_file( $manifest_path ), true );
		} else {
			$manifest = array();
		}
	}

	$path = isset( $manifest[ $path ] ) ? $manifest[ $path ] : $path;

	return 'dist/' . $path;
}

function woo_bg_get_option( $group, $name ) {
	return get_option( 'woo_bg_settings_' . $group . '_' . $name );
}

function woo_bg_set_option( $group, $name, $value ) {
	return update_option( 'woo_bg_settings_' . $group . '_' . $name, $value );
}

function woo_bg_get_vat_groups() {
	return array(
		'a' => '0',
		'b' => '20',
		'g' => '9',
	);
}

function woo_bg_get_return_methods() {
	return array(
		1 => array(
			'id' => 1,
			'label' => __( 'On a payment account', 'woo-bg' ),
		),
		2 => array(
			'id' => 2,
			'label' => __( 'Card', 'woo-bg' ),
		),
		3 => array(
			'id' => 3,
			'label' => __( 'Cash', 'woo-bg' ),
		),
		4 => array(
			'id' => 4,
			'label' => __( 'Other', 'woo-bg' ),
		),
	);
}

function woo_bg_get_yes_no_options() {
	return array(
		'no' => array(
			'id' => 'no',
			'label' => __( 'No', 'woo-bg' ),
		),
		'yes' => array(
			'id' => 'yes',
			'label' => __( 'Yes', 'woo-bg' ),
		),
	);
}

function woo_bg_get_payment_types() {
	return array(
		1 => array(
			'id' => 1,
			'label' => __( 'Released under Art. 3 payment without PPP', 'woo-bg' ),
		),
		2 => array(
			'id' => 2,
			'label' => __( 'Virtual POS terminal', 'woo-bg' ),
		),
		3 => array(
			'id' => 3,
			'label' => __( 'Cash on delivery with PPP', 'woo-bg' ),
		),
		4 => array(
			'id' => 4,
			'label' => __( 'Payment service provider', 'woo-bg' ),
		),
		5 => array(
			'id' => 5,
			'label' => __( 'Other', 'woo-bg' ),
		),
		6 => array(
			'id' => 6,
			'label' => __( 'Reflected with receipt', 'woo-bg' ),
		),
	);
}

function woo_bg_get_payment_types_for_meta() {
	return array(
		'1' => PaymentTypes\WithoutPostPayment::class, //1
		'2' => PaymentTypes\VirtualPOSTerminal::class, //2
		'3' => PaymentTypes\WithPostPayment::class, //3
		'4' => PaymentTypes\PaymentService::class, //4
		'5' => PaymentTypes\Other::class, //5
		'6' => PaymentTypes\ReflectedWithReceipt::class, //5
	);
}

function woo_bg_get_tax_classes() {
	$tax_classes = WC_Tax::get_tax_classes();

	$classes_options = array(
		'standard' => array(
			'id' => 'standard',
			'label' => __( 'Standard', 'woo-bg' ),
		),
	);

	foreach ( $tax_classes as $class ) {
		$slug = sanitize_title( $class );
		$classes_options[ $slug ] = array(
			'id' => $slug,
			'label' => esc_html( $class ),
		);
	}

	return $classes_options;
}


function woo_bg_get_vat_from_order( $order ) {
	if ( !$order ) {
		return '';
	}

	$vat = $order->get_meta( '_billing_vat_number', true ) ? $order->get_meta( '_billing_vat_number', true ) : '';

	if ( !$vat ) {
		$vat = $order->get_meta( '_vat_number', true ) ? $order->get_meta( '_vat_number', true ) : '';
	}

	return $vat;
}

function woo_bg_return_array_for_select( $array, $user_value_for_id = false, $additional_attributes = array() ) {
	$new_array = [];

	if ( !empty( $array ) ) {
		foreach ( $array as $key => $value ) {
			$id = $key;

			if ( $user_value_for_id ) {
				$id = $value;
			}

			$item = array(
				'id' => $id,
				'orig_key' => $key,
				'label' => $value,
			);

			if ( !empty( $additional_attributes ) ) {
				$item = array_merge( $item, $additional_attributes );
			}

			$new_array[] = $item;
		}
	}

	return $new_array;
}

function woo_bg_return_bg_states() {
	return array(
		'' => '',
		'BG-01' => 'Благоевград',
		'BG-02' => 'Бургас',
		'BG-03' => 'Варна',
		'BG-04' => 'Велико Търново',
		'BG-05' => 'Видин',
		'BG-06' => 'Враца',
		'BG-07' => 'Габрово',
		'BG-08' => 'Добрич',
		'BG-09' => 'Кърджали',
		'BG-10' => 'Кюстендил',
		'BG-11' => 'Ловеч',
		'BG-12' => 'Монтана',
		'BG-13' => 'Пазарджик',
		'BG-14' => 'Перник',
		'BG-15' => 'Плевен',
		'BG-16' => 'Пловдив',
		'BG-17' => 'Разград',
		'BG-18' => 'Русе',
		'BG-19' => 'Силистра',
		'BG-20' => 'Сливен',
		'BG-21' => 'Смолян',
		'BG-22' => 'София',
		'BG-23' => 'София Област',
		'BG-24' => 'Стара Загора',
		'BG-25' => 'Търговище',
		'BG-26' => 'Хасково',
		'BG-27' => 'Шумен',
		'BG-28' => 'Ямбол',
	);
}

function woo_bg_support_text() {
	?> 
	<div class="notice notice-info">
		<h3><?php esc_html_e( 'Supporting the development', 'woo-bg' ) ?></h3> 
		
		<?php echo wp_kses_post( wpautop( esc_html__( 'For single donation as development support you can send at ', 'woo-bg' ) . '<a target="_blank" href="https://revolut.me/tihomi9gj5">Revolut</a>' ) ); ?>
	</div>
	<?php
}

function woo_bg_get_shipping_tests_options() {
	return array(
		'no' => __( 'No', 'woo-bg' ),
		'review' => __( 'Review only', 'woo-bg' ),
		'test' => __( 'Review and test', 'woo-bg' ),
	);
}

function woo_bg_get_order_shipping_vat( $order, $show_group = false ) {
	$shipping_vat = 0;

	if ( wc_tax_enabled() && !empty( $order->get_items( 'tax' ) ) ) {
		foreach ( $order->get_items( 'tax' ) as $tax_item ) {
			if ( $tax_item->get_shipping_tax_total() ) {
				$shipping_vat = $tax_item->get_rate_percent();
				break;
			}
		}
	}

	if ( $show_group ) {
		$shipping_vat = woo_bg_maybe_add_rate_group( $shipping_vat );
	}

	return apply_filters( 'woo_bg/order_item/shipping_vat_rate', $shipping_vat, $order );
}

function woo_bg_get_order_item_vat_rate( $item, $order, $show_group = false ) {
	$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
	$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
	$vat_percentages     = woo_bg_get_vat_groups();
	$rate = ( isset( $vat_percentages[ $vat_group ] ) ) ? $vat_percentages[ $vat_group ] : 0;

	if ( $tax_data ) {
		$founded = false;
		$order_taxes = $order->get_taxes();

		foreach ( $order_taxes as $tax_item ) {
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

	if ( $show_group ) {
		$rate = woo_bg_maybe_add_rate_group( $rate );
	}

	return apply_filters( 'woo_bg/order_item/vat_rate', $rate, $item, $order );
}

function woo_bg_maybe_add_rate_group( $rate ) {
	$group = woo_bg_get_vat_group_from_rate( $rate );

	if ( $group ) {
		$rate = $group . " - " . $rate;
	}

	return $rate;
}

function woo_bg_get_vat_group_from_rate( $rate ) {
	switch ( $rate ) {
		case '0':
			$group = "А";
			break;
		case '9':
			$group = "Г";
			break;
		case '20':
			$group = "Б";
			break;
		default:
			$group = "";
			break;
	}

	return $group;
}

function woo_bg_tax_based_price( $price, $rate = 20 ) {
	if ( wc_tax_enabled() ) {
		$price = round( $price - woo_bg_calculate_vat_from_price( $price, $rate ), 2, PHP_ROUND_HALF_DOWN );
	}

	return number_format( $price, 2 );
}

function woo_bg_calculate_vat_from_price( $price, $rate = 20 ) {
	$vat = (new \WC_Tax)::calc_tax( $price, array( array('compound' => 'yes', 'rate' => $rate ) ), true )[0];
    $vat = round( $vat, 3, PHP_ROUND_HALF_DOWN );
    $vat = number_format( $vat, 2, '.', '');
    
    return $vat;
}

function woo_bg_maybe_remove_shipping( $order ) {
	$remove_shipping = woo_bg_get_option( 'invoice', 'remove_shipping' );

	if ( 
		is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) || 
		is_a( $order, 'WC_Order_Refund' ) 
	) {
		$order = wc_get_order( $order->get_parent_id() );
	}

	if ( !empty( $order->get_shipping_methods() ) ) {
		$shipping_method = @array_shift( $order->get_shipping_methods() );
		$shipping_method_id = $shipping_method['method_id'];

		if ( $order->get_payment_method() === 'cod' && woo_bg_get_option('shippings', $shipping_method_id . '_is_courier' ) === 'yes' ) {
			$remove_shipping = 'yes';
		}
	}

	return $remove_shipping;
}

function woo_bg_has_free_shipping_coupon_in_cart() {
	$has_coupon = false;
	$coupons = WC()->cart->get_coupons();

	if ( $coupons ) {
		foreach ( $coupons as $code => $coupon ) {
			if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
				$has_coupon = true;
				break;
			}
		}
	}

	return $has_coupon;
}

function woo_bg_format_phone( $phone, $country = 'BG' ) {
	if ( $country !== 'BG' ) {
		return $phone;
	}
	
	$phone = str_replace( [ ' ', '+' ], '', $phone );

	if ( substr( $phone, 0, 3 ) === '359' ) {
		$phone = str_pad( substr( $phone, 3 ), 10, '0', STR_PAD_LEFT );
	}

	$phone = str_pad( $phone, 10, '0', STR_PAD_LEFT );

	return $phone;
}

function woo_bg_check_admin_label_actions() {
	$errors = [];
		
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_admin_label' ) ) {
		$errors[] = __( 'Nonce was not provided!', 'woo-bg' );
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		$errors[] = __( 'You cannot create labels.', 'woo-bg' );
	}

	if ( !empty( $errors ) ) {
		wp_send_json_error( [
			'message' => implode( ' ', $errors ),
		] );
		wp_die();
	}
}

function woo_bg_get_package_total() {
	$total = floatval( WC()->cart->get_cart_contents_total() ) + floatval( WC()->cart->get_cart_contents_tax() );

	if ( apply_filters('woo_bg/shipping/package_total_includes_fees', true ) ) {
		$total += floatval( WC()->cart->get_fee_total() ) + floatval( WC()->cart->get_fee_tax() );
	}

	$total = number_format( $total, 2, '.', '' );

	return apply_filters( 'woo_bg/shipping/package_total', $total );
}

function woo_bg_is_shipping_enabled() {
	$enabled = false;

	if ( 
		woo_bg_get_option( 'apis', 'enable_econt' ) === 'yes' || 
		woo_bg_get_option( 'apis', 'enable_cvc' ) === 'yes' || 
		woo_bg_get_option( 'apis', 'enable_boxnow' ) === 'yes' || 
		woo_bg_get_option( 'apis', 'enable_speedy' ) === 'yes' 
	) {
		$enabled = true;
	}

	return $enabled;
}

function woo_bg_get_order_label( $order_id ) {
	$order = wc_get_order( $order_id );
	$data = [];
	$label_data = [];
	$method = '';

	if ( !empty( $order->get_items( 'shipping' ) ) ) {
		$items = $order->get_items( 'shipping' );
		$method_object = array_shift( $items );
		$method = $method_object['method_id'];

		$label_data = woo_bg_get_label_data_for_shipping_method( $method_object, $order );
		
		foreach ( $order->get_items( 'shipping' ) as $shipping ) {
			$method = $shipping['method_id'];
		}
	}

	if ( !empty( $label_data ) ) {
		switch ( $method ) {
			case 'woo_bg_speedy':
				$shipment_status = $order->get_meta( 'woo_bg_speedy_shipment_status' );
				
				if ( $shipment_status && $label_data['id'] ) {
					$data = [
						'number' => $label_data['id'],
						'link' => admin_url( 'admin-ajax.php' ) . '?cache-buster=' . rand() . '&action=woo_bg_speedy_print_labels&parcels=' . implode('|', wp_list_pluck( $shipment_status['parcels'], 'id' ) ) . "&size=A6",
					];
				}
				break;
			case 'woo_bg_econt':
				$shipment_status = $order->get_meta( 'woo_bg_econt_shipment_status' );

				if ( $shipment_status && !empty( $label_data['label']['shipmentNumber'] ) ) {
					$data = [
						'number' => $label_data['label']['shipmentNumber'],
						'link' => $shipment_status['label']['pdfURL'] . '&label=10x9',
					];
				}
				break;
			case 'woo_bg_cvc':
				$shipment_status = $order->get_meta( 'woo_bg_cvc_shipment_status' );

				if ( $shipment_status && !empty( $shipment_status['pdf'] ) ) {
					$data = [
						'number' => $label_data['wb'],
						'link' => $shipment_status['pdf'],
					];
				}
				
				break;
			case 'woo_bg_boxnow':
				$data = ['items'];

				foreach ( $label_data['parcels'] as $parcel ) {
					$data['items'][] = [
						'number' => $parcel['id'],
						'link' => admin_url( 'admin-ajax.php' ) . '?cache-buster=' . rand() . '&action=woo_bg_boxnow_print_label&parcel=' . $parcel['id'],
					];
				}
				
				break;
		}
	}

	if ( $method ) {
		$data['method'] = $method;
	}

	return apply_filters( 'woo_bg/column/order/shipment_status_data', $data, $order );
}

function woo_bg_get_label_data_for_shipping_method( $shipping ) {
	$order = $shipping->get_order();
	$label_data = null;
	
	if ( $shipping['method_id'] === 'woo_bg_speedy' ) {
		if ( $label = $order->get_meta( 'woo_bg_speedy_label' ) ) {
			$label_data = $label;
		}
	} elseif ( $shipping['method_id'] === 'woo_bg_econt' ) {
		if ( $label = $order->get_meta( 'woo_bg_econt_label' ) ) {
			$label_data = $label;
		}
	} elseif ( $shipping['method_id'] === 'woo_bg_cvc' ) {
		if ( $label = $order->get_meta( 'woo_bg_cvc_label' ) ) {
			$label_data = $label;
		}
	} elseif ( $shipping['method_id'] === 'woo_bg_boxnow' ) {
		if ( $label = $order->get_meta( 'woo_bg_boxnow_shipment_status' ) ) {
			$label_data = $label;
		}
	}

	return $label_data;
}

function woo_bg_is_pro_activated() {
	return class_exists( 'Woo_BG_Pro\Checkout' );
}

function woo_bg_get_shipping_rate_taxes( $price, $country = 'BG' ) {
	if ( !wc_tax_enabled() ) {
		return [];
	}

	$tax_rates = \WC_Tax::find_shipping_rates( ['country' => $country ] );

	if ( empty( $tax_rates ) ) {
		$tax_rates = [ ['rate' => 20, 'compound' => 'yes' ] ];
	}

	return \WC_Tax::calc_tax( $price, $tax_rates, false );
}