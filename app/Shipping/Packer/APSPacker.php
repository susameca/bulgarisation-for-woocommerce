<?php
namespace Woo_BG\Shipping\Packer;

defined( 'ABSPATH' ) || exit;

class APSPacker {
	private $volumetric_weight_divisor;

	public function __construct( $volumetric_weight_divisor = null ) {
		$this->volumetric_weight_divisor = ( is_numeric( $volumetric_weight_divisor ) && (float) $volumetric_weight_divisor > 0 )
			? (float) $volumetric_weight_divisor
			: null;
	}

	public function pack( array $products, array $box_sizes ): array {
		$products = $this->normalize_products( $products );

		if ( empty( $products ) ) {
			return array();
		}

		$boxes = $this->normalize_boxes( $box_sizes );

		if ( empty( $boxes ) ) {
			throw new \InvalidArgumentException( 'Box sizes list cannot be empty.' );
		}

		$result             = array();
		$current_products   = array();
		$current_box_index  = 0;
		$current_placements = array();
		$product_index      = 0;

		while ( $product_index < count( $products ) ) {
			$product        = $products[ $product_index ];
			$candidate_list = array_merge( $current_products, array( $product ) );
			$start_index    = empty( $current_products ) ? 0 : $current_box_index;
			$fit            = null;

			for ( $box_index = $start_index; $box_index < count( $boxes ); $box_index++ ) {
				$placements = $this->pack_in_box( $candidate_list, $boxes[ $box_index ] );

				if ( null !== $placements ) {
					$fit = array(
						'box_index'  => $box_index,
						'placements' => $placements,
					);
					break;
				}
			}

			if ( null !== $fit ) {
				$current_products   = $candidate_list;
				$current_box_index  = $fit['box_index'];
				$current_placements = $fit['placements'];
				$product_index++;
				continue;
			}

			if ( empty( $current_products ) ) {
				throw new \RuntimeException(
					sprintf( 'Product "%s" does not fit in any of the available boxes.', $product['name'] )
				);
			}

			$result[] = $this->build_packed_box(
				$boxes[ $current_box_index ],
				$current_products,
				$current_placements
			);

			$current_products   = array();
			$current_box_index  = 0;
			$current_placements = array();
		}

		if ( ! empty( $current_products ) ) {
			$result[] = $this->build_packed_box(
				$boxes[ $current_box_index ],
				$current_products,
				$current_placements
			);
		}

		return $result;
	}

	private function normalize_products( array $products ): array {
		$normalized = array();

		foreach ( $products as $index => $product ) {
			$quantity = $this->get_quantity( $product );
			$item     = $this->normalize_item( $product, $index, 'Product' );
			$weight   = $this->get_weight( $product, $index );

			for ( $i = 1; $i <= $quantity; $i++ ) {
				$copy                   = $item;
				$copy['weight']             = $weight;
				$copy['chargeable_weight'] = $this->get_chargeable_weight( $item, $weight );
				$copy['source_index']       = $index;
				$copy['quantity_index']     = $i;
				$normalized[]               = $copy;
			}
		}

		usort(
			$normalized,
			static function ( array $a, array $b ): int {
				if ( $a['volume'] === $b['volume'] ) {
					return $a['max_side'] <=> $b['max_side'];
				}

				return $a['volume'] <=> $b['volume'];
			}
		);

		return $normalized;
	}

	private function normalize_boxes( array $box_sizes ): array {
		$boxes = array();

		foreach ( $box_sizes as $index => $box ) {
			$item                = $this->normalize_item( $box, $index, 'Box' );
			$item['max_weight']  = $this->get_max_weight( $box, $index );
			$item['index']       = $index;
			$boxes[]             = $item;
		}

		usort(
			$boxes,
			static function ( array $a, array $b ): int {
				if ( $a['volume'] === $b['volume'] ) {
					if ( $a['max_side'] === $b['max_side'] ) {
						return $a['max_weight'] <=> $b['max_weight'];
					}

					return $a['max_side'] <=> $b['max_side'];
				}

				return $a['volume'] <=> $b['volume'];
			}
		);

		return $boxes;
	}

	private function normalize_item( $item, int $index, string $type ): array {
		$size = $this->get_nested_size( $item );

		$length = $this->get_dimension( $size, array( 'length', 'l', 'L', 'depth' ), $type, $index, 'length' );
		$width  = $this->get_dimension( $size, array( 'width', 'w', 'W' ), $type, $index, 'width' );
		$height = $this->get_dimension( $size, array( 'height', 'h', 'H' ), $type, $index, 'height' );
		$name   = $this->get_value( $item, array( 'name', 'id', 'label' ) );

		if ( null === $name || '' === $name ) {
			$name = sprintf( '%s #%s', $type, (string) $index );
		}

		return array(
			'name'     => (string) $name,
			'length'   => $length,
			'width'    => $width,
			'height'   => $height,
			'volume'   => $length * $width * $height,
			'max_side' => max( $length, $width, $height ),
		);
	}

	private function get_nested_size( $item ) {
		$size = $this->get_value( $item, array( 'size', 'dimensions' ) );

		if ( null !== $size ) {
			return $size;
		}

		return $item;
	}

	private function get_quantity( $item ): int {
		$quantity = $this->get_value( $item, array( 'quantity', 'qty' ) );

		if ( null === $quantity || '' === $quantity ) {
			return 1;
		}

		if ( ! is_numeric( $quantity ) || (int) $quantity <= 0 ) {
			throw new \InvalidArgumentException( 'Product quantity must be a positive number.' );
		}

		return (int) $quantity;
	}

	private function get_weight( $item, int $index ): float {
		$weight = $this->get_value( $item, array( 'weight', 'kg' ) );

		if ( null === $weight || '' === $weight ) {
			$weight = $this->get_value( $this->get_nested_size( $item ), array( 'weight', 'kg' ) );
		}

		if ( null === $weight || '' === $weight ) {
			return 0.0;
		}

		if ( ! is_numeric( $weight ) || (float) $weight < 0 ) {
			throw new \InvalidArgumentException(
				sprintf( 'Product #%s has invalid weight.', (string) $index )
			);
		}

		return (float) $weight;
	}

	private function get_chargeable_weight( array $item, float $weight ): float {
		if ( null === $this->volumetric_weight_divisor ) {
			return $weight;
		}

		return max( $weight, $item['volume'] / $this->volumetric_weight_divisor );
	}

	private function get_max_weight( $item, int $index ): float {
		$weight = $this->get_value( $item, array( 'max_weight', 'maxWeight', 'max_weight_kg', 'maxWeightKg', 'weight', 'kg' ) );

		if ( null === $weight || '' === $weight ) {
			$weight = $this->get_value( $this->get_nested_size( $item ), array( 'max_weight', 'maxWeight', 'max_weight_kg', 'maxWeightKg', 'weight', 'kg' ) );
		}

		if ( null === $weight || '' === $weight || ! is_numeric( $weight ) || (float) $weight <= 0 ) {
			throw new \InvalidArgumentException(
				sprintf( 'Box #%s has invalid max weight.', (string) $index )
			);
		}

		return (float) $weight;
	}

	private function get_dimension( $item, array $keys, string $type, int $index, string $dimension ): float {
		$value = $this->get_value( $item, $keys );

		if ( null === $value || '' === $value || ! is_numeric( $value ) || (float) $value <= 0 ) {
			throw new \InvalidArgumentException(
				sprintf( '%s #%s has invalid %s.', $type, (string) $index, $dimension )
			);
		}

		return (float) $value;
	}

	private function get_value( $item, array $keys ) {
		foreach ( $keys as $key ) {
			if ( is_array( $item ) && array_key_exists( $key, $item ) ) {
				return $item[ $key ];
			}

			if ( is_object( $item ) && isset( $item->{$key} ) ) {
				return $item->{$key};
			}
		}

		return null;
	}

	private function pack_in_box( array $products, array $box ): ?array {
		if ( $this->products_volume( $products ) > $box['volume'] ) {
			return null;
		}

		if ( $this->products_weight( $products ) > $box['max_weight'] ) {
			return null;
		}

		$items = $products;
		usort(
			$items,
			static function ( array $a, array $b ): int {
				if ( $a['volume'] === $b['volume'] ) {
					return $a['max_side'] <=> $b['max_side'];
				}

				return $a['volume'] <=> $b['volume'];
			}
		);

		$box_length = $box['length'];
		$box_width  = $box['width'];
		$box_height = $box['height'];

		$placements     = array();
		$height_total   = 0.0;
		$shelf_index    = -1;
		$row_index      = -1;
		$shelf_height   = 0.0;
		$shelf_used_y   = 0.0;
		$row_height_y   = 0.0;
		$row_used_x     = 0.0;
		$row_origin_y   = 0.0;
		$shelf_origin_z = 0.0;

		$start_new_shelf = function () use ( &$shelf_index, &$row_index, &$shelf_height, &$shelf_used_y, &$row_height_y, &$row_used_x, &$row_origin_y, &$shelf_origin_z, &$height_total ): void {
			$height_total   += $shelf_height;
			$shelf_origin_z  = $height_total;
			$shelf_index++;
			$row_index       = -1;
			$shelf_height    = 0.0;
			$shelf_used_y    = 0.0;
			$row_height_y    = 0.0;
			$row_used_x      = 0.0;
			$row_origin_y    = 0.0;
		};

		$start_new_row = function () use ( &$row_index, &$row_height_y, &$row_used_x, &$row_origin_y, &$shelf_used_y ): void {
			$row_origin_y = $shelf_used_y;
			$row_index++;
			$row_height_y = 0.0;
			$row_used_x   = 0.0;
		};

		$start_new_shelf();
		$start_new_row();

		foreach ( $items as $product ) {
			$best_choice = null;
			$best_cost   = PHP_FLOAT_MAX;

			foreach ( $this->orientations( $product ) as $orientation ) {
				$x = $orientation[0];
				$y = $orientation[1];
				$z = $orientation[2];

				if ( $x > $box_length || $y > $box_width || $z > $box_height ) {
					continue;
				}

				$new_row_height   = max( $row_height_y, $y );
				$new_shelf_height = max( $shelf_height, $z );

				if (
					$row_used_x + $x <= $box_length &&
					$shelf_used_y + $new_row_height <= $box_width &&
					$height_total + $new_shelf_height <= $box_height
				) {
					$cost = 1000 * ( $new_shelf_height - $shelf_height ) + 10 * ( $new_row_height - $row_height_y ) + ( $box_length - ( $row_used_x + $x ) );

					if ( $cost < $best_cost ) {
						$best_cost   = $cost;
						$best_choice = array(
							'mode'         => 'same-row',
							'dimensions'   => $orientation,
							'row_height'   => $new_row_height,
							'shelf_height' => $new_shelf_height,
						);
					}
				}

				if (
					$x <= $box_length &&
					$shelf_used_y + $row_height_y + $y <= $box_width &&
					$height_total + $new_shelf_height <= $box_height
				) {
					$cost = 1000 * ( $new_shelf_height - $shelf_height ) + 10 * $y + ( $box_length - $x ) + 500;

					if ( $cost < $best_cost ) {
						$best_cost   = $cost;
						$best_choice = array(
							'mode'         => 'new-row',
							'dimensions'   => $orientation,
							'row_height'   => $y,
							'shelf_height' => $new_shelf_height,
						);
					}
				}

				if (
					$x <= $box_length &&
					$y <= $box_width &&
					$height_total + $shelf_height + $z <= $box_height
				) {
					$cost = 1000 * $z + 10 * $y + ( $box_length - $x ) + 5000;

					if ( $cost < $best_cost ) {
						$best_cost   = $cost;
						$best_choice = array(
							'mode'         => 'new-shelf',
							'dimensions'   => $orientation,
							'row_height'   => $y,
							'shelf_height' => $z,
						);
					}
				}
			}

			if ( null === $best_choice ) {
				return null;
			}

			$x = $best_choice['dimensions'][0];
			$y = $best_choice['dimensions'][1];
			$z = $best_choice['dimensions'][2];

			switch ( $best_choice['mode'] ) {
				case 'same-row':
					$origin         = array( $row_used_x, $row_origin_y, $shelf_origin_z );
					$row_used_x    += $x;
					$row_height_y   = max( $row_height_y, $y );
					$shelf_height   = max( $shelf_height, $z );
					$placements[]   = $this->build_placement( $product, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
					break;

				case 'new-row':
					$shelf_used_y += $row_height_y;
					$start_new_row();

					$origin         = array( 0.0, $row_origin_y, $shelf_origin_z );
					$row_height_y   = $best_choice['row_height'];
					$row_used_x     = $x;
					$shelf_height   = max( $shelf_height, $z );
					$placements[]   = $this->build_placement( $product, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
					break;

				case 'new-shelf':
					$shelf_used_y += $row_height_y;
					$start_new_shelf();
					$start_new_row();

					$origin         = array( 0.0, $row_origin_y, $shelf_origin_z );
					$row_height_y   = $best_choice['row_height'];
					$row_used_x     = $x;
					$shelf_height   = $best_choice['shelf_height'];
					$placements[]   = $this->build_placement( $product, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
					break;
			}
		}

		$height_total += $shelf_height;

		if ( $height_total > $box_height ) {
			return null;
		}

		return $placements;
	}

	private function products_volume( array $products ): float {
		$volume = 0.0;

		foreach ( $products as $product ) {
			$volume += $product['volume'];
		}

		return $volume;
	}

	private function products_weight( array $products, bool $chargeable = true ): float {
		$weight = 0.0;

		foreach ( $products as $product ) {
			$weight += $chargeable && isset( $product['chargeable_weight'] ) ? $product['chargeable_weight'] : $product['weight'];
		}

		return $weight;
	}

	private function orientations( array $item ): array {
		$orientations = array(
			array( $item['length'], $item['width'], $item['height'] ),
			array( $item['length'], $item['height'], $item['width'] ),
			array( $item['width'], $item['length'], $item['height'] ),
			array( $item['width'], $item['height'], $item['length'] ),
			array( $item['height'], $item['length'], $item['width'] ),
			array( $item['height'], $item['width'], $item['length'] ),
		);

		$unique = array();

		foreach ( $orientations as $orientation ) {
			$key            = implode( 'x', $orientation );
			$unique[ $key ] = $orientation;
		}

		return array_values( $unique );
	}

	private function build_placement( array $product, array $dimensions, array $origin, int $shelf_index, int $row_index ): array {
		return array(
			'name'           => $product['name'],
			'source_index'   => $product['source_index'],
			'quantity_index' => $product['quantity_index'],
			'weight'         => $product['weight'],
			'dimensions'     => array(
				'length' => $dimensions[0],
				'width'  => $dimensions[1],
				'height' => $dimensions[2],
			),
			'origin'         => array(
				'x' => $origin[0],
				'y' => $origin[1],
				'z' => $origin[2],
			),
			'shelf_index'    => $shelf_index,
			'row_index'      => $row_index,
		);
	}

	private function build_packed_box( array $box, array $products, array $placements ): array {
		$total_volume = $this->products_volume( $products );
		$total_weight = $this->products_weight( $products, false );

		return array(
			'box'        => array(
				'name'       => $box['name'],
				'index'      => $box['index'],
				'length'     => $box['length'],
				'width'      => $box['width'],
				'height'     => $box['height'],
				'max_weight' => $box['max_weight'],
			),
			'weight'      => $total_weight,
			'products'   => array_map(
				static function ( array $product ): array {
					return array(
						'name'           => $product['name'],
						'source_index'   => $product['source_index'],
						'quantity_index' => $product['quantity_index'],
						'weight'         => $product['weight'],
						'length'         => $product['length'],
						'width'          => $product['width'],
						'height'         => $product['height'],
					);
				},
				$products
			),
			'placements' => $placements,
			'fill_rate'  => $box['volume'] > 0 ? $total_volume / $box['volume'] : 0,
		);
	}
}
