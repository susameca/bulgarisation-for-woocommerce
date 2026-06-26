<?php
namespace Woo_BG\Shipping\Pigeon;

use Woo_BG\Shipping\Packer\Carton_Packer;
use Woo_BG\Shipping\Packer\Product;
use Woo_BG\Shipping\Packer\Size;

defined( 'ABSPATH' ) || exit;

class PackageBoxes {
	const MAX_LENGTH   = 200;
	const MAX_WIDTH    = 200;
	const MAX_HEIGHT   = 200;
	const MAX_WEIGHT   = 30;
	const MAX_SHIPMENT_WEIGHT = 400;
	const DEFAULT_SIZE = 20;

	public function __construct() {
		add_filter( 'woo_bg/pigeon/calculate_label', array( __CLASS__, 'calculate_label' ), 21, 2 );
	}

	public static function calculate_label( $label, $method ) {
		if ( ! self::should_handle_method( $method ) ) {
			return $label;
		}

		try {
			$packages = self::build_packages( $method->package, $method );
		} catch ( \Throwable $e ) {
			$packages = self::split_weight_into_packages( self::get_package_weight( $method->package, $method ) );
		}

		if ( empty( $packages ) ) {
			return $label;
		}

		$label['packages'] = $packages;

		return $label;
	}

	public static function package_weight_fits_shipment( $package, $method = null ) {
		return self::get_package_weight( $package, $method ) <= self::MAX_SHIPMENT_WEIGHT;
	}

	private static function should_handle_method( $method ) {
		return isset( $method->delivery_type ) && $method->delivery_type !== 'locker' && ! empty( $method->package );
	}

	private static function build_packages( $package, $method ) {
		$items = self::get_product_items( $package );

		if ( empty( $items ) ) {
			return self::split_weight_into_packages( self::get_package_weight( $package, $method ) );
		}

		$packer   = new Carton_Packer();
		$groups   = array();
		$products = array();
		$weight   = 0;

		foreach ( $items as $item ) {
			$candidate_products = array_merge( $products, array( $item['product'] ) );
			$candidate_weight   = $weight + $item['weight'];

			if ( self::group_fits( $packer, $candidate_products, $candidate_weight ) ) {
				$products = $candidate_products;
				$weight   = $candidate_weight;
				continue;
			}

			if ( empty( $products ) ) {
				return self::split_weight_into_packages( self::get_package_weight( $package, $method ) );
			}

			$groups[] = array(
				'products' => $products,
				'weight'   => $weight,
			);

			$products = array( $item['product'] );
			$weight   = $item['weight'];

			if ( ! self::group_fits( $packer, $products, $weight ) ) {
				return self::split_weight_into_packages( self::get_package_weight( $package, $method ) );
			}
		}

		if ( ! empty( $products ) ) {
			$groups[] = array(
				'products' => $products,
				'weight'   => $weight,
			);
		}

		return self::groups_to_packages( $packer, $groups );
	}

	private static function get_product_items( $package ) {
		$items          = array();
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		if ( empty( $package['contents'] ) ) {
			return $items;
		}

		foreach ( $package['contents'] as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
				continue;
			}

			$product  = $cart_item['data'];
			$quantity = ! empty( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
			$name     = woo_bg_normalize_text_for_label( $product->get_name() );
			$length   = $product->get_length() ? wc_get_dimension( $product->get_length(), 'mm', $dimension_unit ) : wc_get_dimension( self::DEFAULT_SIZE, 'mm', 'cm' );
			$width    = $product->get_width() ? wc_get_dimension( $product->get_width(), 'mm', $dimension_unit ) : wc_get_dimension( self::DEFAULT_SIZE, 'mm', 'cm' );
			$height   = $product->get_height() ? wc_get_dimension( $product->get_height(), 'mm', $dimension_unit ) : wc_get_dimension( self::DEFAULT_SIZE, 'mm', 'cm' );
			$weight   = $product->get_weight() ? wc_get_weight( $product->get_weight(), 'kg' ) : 0;

			foreach ( range( 1, $quantity ) as $i ) {
				$items[] = array(
					'product' => new Product(
						$name,
						new Size(
							(int) ceil( $length ),
							(int) ceil( $width ),
							(int) ceil( $height )
						)
					),
					'weight'  => (float) $weight,
				);
			}
		}

		return $items;
	}

	private static function group_fits( $packer, $products, $weight ) {
		if ( $weight > self::MAX_WEIGHT ) {
			return false;
		}

		try {
			$packer->find_best_carton( $products, self::get_max_carton() );
		} catch ( \Throwable $e ) {
			return false;
		}

		return true;
	}

	private static function groups_to_packages( $packer, $groups ) {
		$packages = array();

		foreach ( $groups as $group ) {
			$result = $packer->find_best_carton( $group['products'], self::get_max_carton() );

			$packages[] = array(
				'weight' => self::normalize_weight( $group['weight'] ),
				'width'  => wc_get_dimension( $result->W, 'cm', 'mm' ),
				'length' => wc_get_dimension( $result->L, 'cm', 'mm' ),
				'height' => wc_get_dimension( $result->H, 'cm', 'mm' ),
			);
		}

		return $packages;
	}

	private static function get_max_carton() {
		return array(
			'length' => wc_get_dimension( self::MAX_LENGTH, 'mm', 'cm' ),
			'width'  => wc_get_dimension( self::MAX_WIDTH, 'mm', 'cm' ),
			'height' => wc_get_dimension( self::MAX_HEIGHT, 'mm', 'cm' ),
		);
	}

	private static function split_weight_into_packages( $weight ) {
		$packages = array();
		$weight   = max( self::normalize_weight( $weight ), 0.100 );

		while ( $weight > self::MAX_WEIGHT ) {
			$packages[] = self::max_size_package( self::MAX_WEIGHT );
			$weight    -= self::MAX_WEIGHT;
		}

		$packages[] = self::max_size_package( $weight );

		return $packages;
	}

	private static function max_size_package( $weight ) {
		return array(
			'weight' => self::normalize_weight( $weight ),
			'width'  => self::MAX_WIDTH,
			'length' => self::MAX_LENGTH,
			'height' => self::MAX_HEIGHT,
		);
	}

	private static function get_package_weight( $package, $method ) {
		$weight = 0;

		if ( ! empty( $package['contents'] ) ) {
			foreach ( $package['contents'] as $cart_item ) {
				if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
					continue;
				}

				if ( $cart_item['data']->get_weight() ) {
					$quantity = ! empty( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
					$weight  += wc_get_weight( $cart_item['data']->get_weight(), 'kg' ) * $quantity;
				}
			}
		}

		if ( ! $weight ) {
			$weight = apply_filters( 'woo_bg/pigeon/label/weight', 1, $package, $method );
		}

		return (float) $weight;
	}

	private static function normalize_weight( $weight ) {
		$weight = (float) $weight;

		if ( $weight > 0 && $weight < 0.100 ) {
			return 0.100;
		}

		return round( $weight, 3 );
	}
}
