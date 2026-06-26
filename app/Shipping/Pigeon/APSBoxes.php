<?php
namespace Woo_BG\Shipping\Pigeon;

use Woo_BG\Admin\Fields;
use Woo_BG\Shipping\Packer\APSPackage;

defined( 'ABSPATH' ) || exit;

class APSBoxes {
	const OPTION_NAME = 'auto_size';
	const MAX_LOCKER_WEIGHT = 30;

	private static $box_sizes = array(
		array(
			'name'       => 'S',
			'length'     => 61,
			'width'      => 44,
			'height'     => 8,
			'max_weight' => 30,
		),
		array(
			'name'       => 'M',
			'length'     => 61,
			'width'      => 44,
			'height'     => 18,
			'max_weight' => 30,
		),
		array(
			'name'       => 'L',
			'length'     => 61,
			'width'      => 44,
			'height'     => 37,
			'max_weight' => 30,
		),
	);

	public function __construct() {
		if ( woo_bg_get_option( 'pigeon', self::OPTION_NAME ) === 'yes' ) {
			add_filter( 'woo_bg/pigeon/calculate_label', array( __CLASS__, 'calculate_label' ), 20, 2 );
		}
	}

	public static function calculate_label( $label, $method ) {
		if ( ! self::should_handle_method( $method ) ) {
			return $label;
		}

		try {
			$packed_boxes = APSPackage::pack_package( $method->package, self::$box_sizes );
		} catch ( \Throwable $e ) {
			return $label;
		}

		if ( empty( $packed_boxes ) ) {
			return $label;
		}

		$label['packages'] = self::build_packages( $packed_boxes );

		return $label;
	}

	public static function package_fits_largest_locker( $package ) {
		return APSPackage::package_products_fit_largest_box( $package, self::$box_sizes );
	}

	public static function package_weight_fits_locker( $package ) {
		return self::get_package_weight( $package ) <= self::MAX_LOCKER_WEIGHT;
	}

	private static function should_handle_method( $method ) {
		return isset( $method->delivery_type ) && $method->delivery_type === 'locker' && ! empty( $method->package );
	}

	private static function build_packages( $packed_boxes ) {
		$packages = array();

		foreach ( $packed_boxes as $packed_box ) {
			$box        = $packed_box['box'];
			$weight     = ! empty( $packed_box['weight'] ) ? $packed_box['weight'] : 1;
			$packages[] = array(
				'weight' => $weight > 0 && $weight < 0.100 ? 0.100 : $weight,
				'width'  => $box['width'],
				'length' => $box['length'],
				'height' => $box['height'],
			);
		}

		return $packages;
	}

	private static function get_package_weight( $package ) {
		$weight = 0;

		if ( empty( $package['contents'] ) ) {
			return $weight;
		}

		foreach ( $package['contents'] as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) || ! $cart_item['data']->get_weight() ) {
				continue;
			}

			$quantity = ! empty( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
			$weight  += wc_get_weight( $cart_item['data']->get_weight(), 'kg' ) * $quantity;
		}

		return (float) $weight;
	}
}
