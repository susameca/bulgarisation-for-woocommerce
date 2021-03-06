<?php
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
		'1' => \Audit\PaymentTypes\WithoutPostPayment::class, //1
		'2' => \Audit\PaymentTypes\VirtualPOSTerminal::class, //2
		'3' => \Audit\PaymentTypes\WithPostPayment::class, //3
		'4' => \Audit\PaymentTypes\PaymentService::class, //4
		'5' => \Audit\PaymentTypes\Other::class, //5
		'6' => \Audit\PaymentTypes\ReflectedWithReceipt::class, //5
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

	foreach ( $array as $key => $value ) {
		$id = $key;

		if ( $user_value_for_id ) {
			$id = $value;
		}

		$item = array(
			'id' => $id,
			'label' => $value,
		);

		if ( !empty( $additional_attributes ) ) {
			$item = array_merge( $item, $additional_attributes );
		}

		$new_array[] = $item;
	}

	return $new_array;
}

function woo_bg_return_bg_states() {
	return array(
		'' => '',
		'BG-01' => '??????????????????????',
		'BG-02' => '????????????',
		'BG-03' => '??????????',
		'BG-04' => '???????????? ??????????????',
		'BG-05' => '??????????',
		'BG-06' => '??????????',
		'BG-07' => '??????????????',
		'BG-08' => '????????????',
		'BG-09' => '????????????????',
		'BG-10' => '??????????????????',
		'BG-11' => '??????????',
		'BG-12' => '??????????????',
		'BG-13' => '??????????????????',
		'BG-14' => '????????????',
		'BG-15' => '????????????',
		'BG-16' => '??????????????',
		'BG-17' => '??????????????',
		'BG-18' => '????????',
		'BG-19' => '????????????????',
		'BG-20' => '????????????',
		'BG-21' => '????????????',
		'BG-22' => '?????????? ????????????',
		'BG-23' => '??????????',
		'BG-24' => '?????????? ????????????',
		'BG-25' => '??????????????????',
		'BG-26' => '??????????????',
		'BG-27' => '??????????',
		'BG-28' => '??????????',
	);
}

function woo_bg_support_text() {
	?> 
		<h3><?php _e( 'Supporting the development', 'woo-bg' ) ?></h3> 
	<?php
	echo wpautop( __( 'Consider that for better maintenance and work of the plugin or adding new features we need your help in form of donations. Also, this will help keep the plugin free.', 'woo-bg' ) );
	echo wpautop( __( 'For monthly development support or need a premium plugin support you can donate at ', 'woo-bg' ) . '<a target="_blank" href="https://www.patreon.com/tparushev">Patreon</a>.' );
	echo wpautop( __( 'For single donation as development support you can send at ', 'woo-bg' ) . '<a target="_blank" href="https://revolut.me/tihomi9gj5">Revolute</a>.' );
}

function woo_bg_get_shipping_tests_options() {
	return array(
		'no' => __( 'No', 'woo-bg' ),
		'review' => __( 'Review only', 'woo-bg' ),
		'test' => __( 'Review and test', 'woo-bg' ),
	);
}