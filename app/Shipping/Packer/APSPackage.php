<?php
namespace Woo_BG\Shipping\Packer;

defined( 'ABSPATH' ) || exit;

class APSPackage {
	public static function pack_package( $package, array $box_sizes ): array {
		$products = self::get_products_from_package( $package );

		if ( empty( $products ) ) {
			return array();
		}

		return ( new APSPacker() )->pack( $products, $box_sizes );
	}

	public static function package_fits_largest_box( $package, array $box_sizes ): bool {
		$products = self::get_products_from_package( $package );

		if ( empty( $products ) ) {
			return true;
		}

		try {
			$packed_boxes = ( new APSPacker() )->pack( $products, array( self::get_largest_box_size( $box_sizes ) ) );
		} catch ( \Throwable $e ) {
			return false;
		}

		return count( $packed_boxes ) === 1;
	}

	public static function package_products_fit_largest_box( $package, array $box_sizes ): bool {
		$products = self::get_products_from_package( $package );

		if ( empty( $products ) ) {
			return true;
		}

		$largest_box = self::get_largest_box_size( $box_sizes );
		$packer      = new APSPacker();

		foreach ( $products as $product ) {
			$product['quantity'] = 1;

			try {
				$packed_boxes = $packer->pack( array( $product ), array( $largest_box ) );
			} catch ( \Throwable $e ) {
				return false;
			}

			if ( count( $packed_boxes ) !== 1 ) {
				return false;
			}
		}

		return true;
	}

	private static function get_largest_box_size( array $box_sizes ): array {
		if ( empty( $box_sizes ) ) {
			return array();
		}

		usort(
			$box_sizes,
			static function ( array $a, array $b ): int {
				return ( $a['length'] * $a['width'] * $a['height'] ) <=> ( $b['length'] * $b['width'] * $b['height'] );
			}
		);

		return end( $box_sizes );
	}

	private static function get_products_from_package( $package ): array {
		$products       = array();
		$default_weight = 0;
		$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );

		foreach ( self::get_package_contents( $package ) as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
				return array();
			}

			$product = $cart_item['data'];
			$weight  = $product->get_weight() ? wc_get_weight( $product->get_weight(), 'kg' ) : 0;
			$quantity = ! empty( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;

			if ( ! $product->get_length() || ! $product->get_width() || ! $product->get_height() ) {
				$default_weight += $weight * $quantity;
				continue;
			}

			$products[] = array(
				'name'     => $product->get_name(),
				'length'   => wc_get_dimension( $product->get_length(), 'cm', $dimension_unit ),
				'width'    => wc_get_dimension( $product->get_width(), 'cm', $dimension_unit ),
				'height'   => wc_get_dimension( $product->get_height(), 'cm', $dimension_unit ),
				'weight'   => $weight,
				'quantity' => $quantity,
			);
		}

		if ( empty( $products ) ) {
			$products[] = array(
				'name'     => 'Default package',
				'length'   => 20,
				'width'    => 20,
				'height'   => 20,
				'weight'   => $default_weight > 0 ? $default_weight : 1,
				'quantity' => 1,
			);
		}

		return $products;
	}

	private static function get_package_contents( $package ): array {
		if ( empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
			return array();
		}

		return $package['contents'];
	}
}
