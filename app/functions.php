<?php
use Woo_BG\Export\Nra\Xml\PaymentTypes;

function woo_bg_assets_bundle( $path ) {
	static $manifest = null;

	if ( is_null( $manifest ) ) {
		$manifest_path = woo_bg()->plugin_dir_path() . 'dist/manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( file_get_contents( $manifest_path ), true );
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
	<h3><?php _e( 'Supporting the development', 'woo-bg' ) ?></h3> 
	<?php
	echo wpautop( __( 'For single donation as development support you can send at ', 'woo-bg' ) . '<a target="_blank" href="https://revolut.me/tihomi9gj5">Revolut</a>' );
}

function woo_bg_get_shipping_tests_options() {
	return array(
		'no' => __( 'No', 'woo-bg' ),
		'review' => __( 'Review only', 'woo-bg' ),
		'test' => __( 'Review and test', 'woo-bg' ),
	);
}

function woo_bg_get_order_shipping_vat( $order ) {
	$shipping_vat = 0;

	if ( wc_tax_enabled() && !empty( $order->get_items( 'tax' ) ) ) {
		foreach ( $order->get_items( 'tax' ) as $tax_item ) {
			if ( $tax_item->get_shipping_tax_total() ) {
				$shipping_vat = $tax_item->get_rate_percent();
				break;
			}
		}
	}

	return apply_filters( 'woo_bg/order_item/shipping_vat_rate', $shipping_vat, $order );
}

function woo_bg_get_order_item_vat_rate( $item, $order ) {
	$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
	$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
	$vat_percentages     = woo_bg_get_vat_groups();
	$rate = $vat_percentages[ $vat_group ];

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

	return apply_filters( 'woo_bg/order_item/vat_rate', $rate, $item, $order );
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

	if ( is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) || is_a( $order, 'WC_Order_Refund' ) ) {
		$order = wc_get_order( $order->get_parent_id() );
	}

	$shipping_method = @array_shift( $order->get_shipping_methods() );
	$shipping_method_id = $shipping_method['method_id'];

	if ( $order->get_payment_method() === 'cod' && woo_bg_get_option('shippings', $shipping_method_id . '_is_courier' ) === 'yes' ) {
		$remove_shipping = 'yes';
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

function woo_bg_format_phone( $phone ) {
	if ( substr( $phone, 1 ) !== '+' ) {
		$phone = str_pad( $phone, 10, '0', STR_PAD_LEFT );
	}

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