<?php
namespace Woo_BG\Shipping\Econt;

use Woo_BG\Shipping\Packer\APSPackage;

defined( 'ABSPATH' ) || exit;

class APSBoxes {
	const OPTION_NAME = 'auto_size';

	private static $box_sizes = array(
		array(
			'name'       => 'small',
			'length'     => 44,
			'width'      => 61,
			'height'     => 8,
			'max_weight' => 4.99,
		),
		array(
			'name'       => 'medium',
			'length'     => 44,
			'width'      => 61,
			'height'     => 18,
			'max_weight' => 30.5,
		),
		array(
			'name'       => 'big',
			'length'     => 44,
			'width'      => 61,
			'height'     => 37,
			'max_weight' => 30.5,
		),
	);

	public function __construct() {
		if ( woo_bg_get_option( 'econt', self::OPTION_NAME ) === 'yes' ) {
			add_filter( 'woo_bg/econt/calculate_label', array( __CLASS__, 'calculate_label' ), 20, 2 );
		}
	}

	public static function calculate_label( $request_body, $method ) {
		if ( ! self::should_handle_method( $method ) || empty( $request_body['label'] ) ) {
			return $request_body;
		}

		try {
			$packed_boxes = APSPackage::pack_package( $method->package, self::$box_sizes );
		} catch ( \Throwable $e ) {
			return $request_body;
		}

		if ( empty( $packed_boxes ) ) {
			return $request_body;
		}

		$request_body['label'] = self::apply_packed_boxes_to_label( $request_body['label'], $packed_boxes );

		return $request_body;
	}

	public static function package_fits_largest_automat( $package ) {
		return APSPackage::package_fits_largest_box( $package, self::$box_sizes );
	}

	private static function should_handle_method( $method ) {
		return isset( $method->delivery_type ) && $method->delivery_type === 'automat' && ! empty( $method->package );
	}

	private static function apply_packed_boxes_to_label( $label, $packed_boxes ) {
		$packs = self::build_packs( $packed_boxes );

		if ( empty( $packs ) ) {
			return $label;
		}

		$label['packCount'] = count( $packs );
		$label['packs']     = $packs;

		unset( $label['shipmentDimensionsW'], $label['shipmentDimensionsL'], $label['shipmentDimensionsH'] );

		return $label;
	}

	private static function build_packs( $packed_boxes ) {
		$packs = array();

		foreach ( $packed_boxes as $packed_box ) {
			if ( empty( $packed_box['box'] ) ) {
				continue;
			}

			$size   = self::get_packed_size( $packed_box );
			$weight = ! empty( $packed_box['weight'] ) ? $packed_box['weight'] : 1;

			$packs[] = array(
				'width'  => $size['width'],
				'height' => $size['height'],
				'length' => $size['length'],
				'weight' => $weight > 0 && $weight < 0.100 ? 0.100 : $weight,
			);
		}

		return $packs;
	}

	private static function get_packed_size( $packed_box ) {
		$box = $packed_box['box'];

		if ( empty( $packed_box['placements'] ) ) {
			return array(
				'width'  => $box['width'],
				'height' => $box['height'],
				'length' => $box['length'],
			);
		}

		$size = array(
			'width'  => 0,
			'height' => 0,
			'length' => 0,
		);

		foreach ( $packed_box['placements'] as $placement ) {
			$size['length'] = max( $size['length'], $placement['origin']['x'] + $placement['dimensions']['length'] );
			$size['width']  = max( $size['width'], $placement['origin']['y'] + $placement['dimensions']['width'] );
			$size['height'] = max( $size['height'], $placement['origin']['z'] + $placement['dimensions']['height'] );
		}

		return $size;
	}
}
