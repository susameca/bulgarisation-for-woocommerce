<?php
namespace Woo_BG\Shipping\Packer;

class Carton_Packer {
    /**
     * Try multiple base candidates and return the best-volume solution.
     *
     * @param Product[] $products Products to pack.
     * @param array<int,array<string,mixed>>|array<string,mixed> $cartons Optional max carton size(s).
     * @return Pack_Result
     */
    public function find_best_carton( array $products, array $cartons = array() ): Pack_Result {
        if ( empty( $products ) ) {
            throw new \InvalidArgumentException( 'Products list cannot be empty.' );
        }

        $area_lb  = 0;
        $side_lb  = 0;
        $total_vol = 0;

        foreach ( $products as $p ) {
            $area_lb  += $p->size->min_face_area();
            $side_lb   = max( $side_lb, $p->size->min_max_base_side() );
            $total_vol += $p->size->volume();
        }

        $cartons = $this->normalize_cartons( $cartons );
        $best    = $this->find_homogeneous_carton( $products, $total_vol, $cartons );
        $bestVol = null !== $best ? $best->L * $best->W * $best->H : PHP_INT_MAX;

        // Generate base candidates from aspect ratios + per-item face candidates.
        $ratios     = array( 1.0, 4 / 3, 3 / 2, 16 / 10, 2.0, 5 / 3, 3.0, 16 / 9 );
        $candidates = array();

        $add_candidate = static function ( array &$set, int $L, int $W ): void {
            // normalize orientation of base (L >= W) for dedup key
            if ( $W > $L ) {
                list( $L, $W ) = array( $W, $L );
            }
            $set["{$L}×{$W}"] = array( $L, $W );
        };

        foreach ( $ratios as $r ) {
            foreach ( array( $r, 1 / $r ) as $ratio ) {
                // initial from area lower bound
                $W = (int) ceil( sqrt( $area_lb / $ratio ) );
                $L = (int) ceil( $ratio * $W );
                // ensure only max(L, W) respects side_lb (previously forced both, too strict)
                $max_side = max( $L, $W );
                if ( $max_side < $side_lb ) {
                    $scale = $side_lb / $max_side;
                    $L     = (int) ceil( $L * $scale );
                    $W     = (int) ceil( $W * $scale );
                }
                $add_candidate( $candidates, $L, $W );
                // +10% slack variant
                $add_candidate( $candidates, (int) ceil( $L * 1.10 ), (int) ceil( $W * 1.10 ) );
            }
        }

        // Also include square based exactly on area_lb, scaled only on max side if needed.
        $Smin = (int) ceil( sqrt( $area_lb ) );
        $L    = $Smin;
        $W    = $Smin;
        if ( max( $L, $W ) < $side_lb ) {
            $scale = $side_lb / max( $L, $W );
            $L     = (int) ceil( $L * $scale );
            $W     = (int) ceil( $W * $scale );
        }
        $add_candidate( $candidates, $L, $W );

        // Per-item exact face candidates to allow tight fit for single items and mixes.
        $orientation_bases = array();

        foreach ( $products as $p ) {
            foreach ( $p->size->orientations() as $o ) {
                $add_candidate( $candidates, $o[0], $o[1] );
                // +5% slack to be robust to fragmentation.
                $add_candidate( $candidates, (int) ceil( $o[0] * 1.05 ), (int) ceil( $o[1] * 1.05 ) );

                $orientation_bases[ "{$o[0]}×{$o[1]}" ] = array( $o[0], $o[1] );
            }
        }

        foreach ( $orientation_bases as $base ) {
            for ( $columns = 1; $columns <= count( $products ); $columns++ ) {
                $L = $base[0] * $columns;
                $W = (int) ceil( $area_lb / $L );

                $add_candidate( $candidates, $L, $W );
            }
        }

        foreach ( $cartons as $carton ) {
            $add_candidate( $candidates, $carton['length'], $carton['width'] );
            $add_candidate( $candidates, $carton['length'], $carton['height'] );
            $add_candidate( $candidates, $carton['width'], $carton['height'] );
        }

        foreach ( $candidates as $base ) {
            $L = $base[0];
            $W = $base[1];

            $attempt = $this->pack_with_base( $products, $L, $W );
            if ( null === $attempt ) {
                continue;
            }

            $H   = $attempt['H'];
            $vol = $L * $W * $H;

            if ( ! empty( $cartons ) && ! $this->fits_any_carton( $L, $W, $H, $cartons ) ) {
                continue;
            }

            if ( $vol < $bestVol || ( $vol === $bestVol && ( null !== $best && ( $H < $best->H || ( $H === $best->H && $L < $best->L ) ) ) ) ) {
                $fill  = $total_vol / $vol;
                $best  = new Pack_Result( $L, $W, $H, $attempt['placements'], $fill );
                $bestVol = $vol;
            }
        }

        if ( null === $best ) {
            throw new \RuntimeException( empty( $cartons ) ? 'Failed to pack with generated candidates. Try increasing slack or check items.' : 'Failed to pack inside the supplied carton size.' );
        }

        return $best;
    }

    private function find_homogeneous_carton( array $products, int $total_vol, array $cartons ): ?Pack_Result {
        if ( ! $this->has_equal_product_sizes( $products ) ) {
            return null;
        }

        $best         = null;
        $best_vol     = PHP_INT_MAX;
        $product      = reset( $products );
        $product_count = count( $products );

        foreach ( $this->unique_orientations( $product->size->orientations() ) as $orientation ) {
            $x = $orientation[0];
            $y = $orientation[1];
            $z = $orientation[2];

            for ( $columns = 1; $columns <= $product_count; $columns++ ) {
                for ( $rows = 1; $rows <= (int) ceil( $product_count / $columns ); $rows++ ) {
                    $layers = (int) ceil( $product_count / ( $columns * $rows ) );
                    $dims   = $this->canonical_dimensions( $columns * $x, $rows * $y, $layers * $z );
                    $L      = $dims[0];
                    $W      = $dims[1];
                    $H      = $dims[2];

                    if ( ! empty( $cartons ) && ! $this->fits_any_carton( $L, $W, $H, $cartons ) ) {
                        continue;
                    }

                    $vol = $L * $W * $H;

                    if ( $this->is_better_carton( $L, $W, $H, $vol, $best, $best_vol ) ) {
                        $best     = new Pack_Result( $L, $W, $H, array(), $total_vol / $vol );
                        $best_vol = $vol;
                    }
                }
            }
        }

        return $best;
    }

    private function has_equal_product_sizes( array $products ): bool {
        $first = reset( $products );

        if ( ! $first instanceof Product ) {
            return false;
        }

        $first_dimensions = $this->sorted_dimensions( $first->size->l, $first->size->w, $first->size->h );

        foreach ( $products as $product ) {
            if ( ! $product instanceof Product ) {
                return false;
            }

            if ( $first_dimensions !== $this->sorted_dimensions( $product->size->l, $product->size->w, $product->size->h ) ) {
                return false;
            }
        }

        return true;
    }

    private function unique_orientations( array $orientations ): array {
        $unique = array();

        foreach ( $orientations as $orientation ) {
            $unique[ implode( 'x', $orientation ) ] = $orientation;
        }

        return array_values( $unique );
    }

    private function canonical_dimensions( int $L, int $W, int $H ): array {
        $dimensions = array( $L, $W, $H );
        rsort( $dimensions );

        return $dimensions;
    }

    private function sorted_dimensions( int $L, int $W, int $H ): array {
        $dimensions = array( $L, $W, $H );
        sort( $dimensions );

        return $dimensions;
    }

    private function is_better_carton( int $L, int $W, int $H, int $vol, ?Pack_Result $best, int $best_vol ): bool {
        if ( null === $best ) {
            return true;
        }

        if ( $vol !== $best_vol ) {
            return $vol < $best_vol;
        }

        $max_side      = max( $L, $W, $H );
        $best_max_side = max( $best->L, $best->W, $best->H );

        if ( $max_side !== $best_max_side ) {
            return $max_side < $best_max_side;
        }

        if ( $H !== $best->H ) {
            return $H < $best->H;
        }

        return $L < $best->L;
    }

    private function normalize_cartons( array $cartons ): array {
        if ( empty( $cartons ) ) {
            return array();
        }

        if ( isset( $cartons['length'] ) || isset( $cartons['depth'] ) || isset( $cartons['width'] ) || isset( $cartons['height'] ) ) {
            $cartons = array( $cartons );
        }

        $normalized = array();

        foreach ( $cartons as $carton ) {
            if ( ! is_array( $carton ) ) {
                continue;
            }

            $length = $this->get_carton_dimension( $carton, array( 'length', 'depth', 'l', 'L' ) );
            $width  = $this->get_carton_dimension( $carton, array( 'width', 'w', 'W' ) );
            $height = $this->get_carton_dimension( $carton, array( 'height', 'h', 'H' ) );

            if ( null === $length || null === $width || null === $height ) {
                continue;
            }

            $normalized[] = array(
                'length' => $length,
                'width'  => $width,
                'height' => $height,
            );
        }

        return $normalized;
    }

    private function get_carton_dimension( array $carton, array $keys ): ?int {
        foreach ( $keys as $key ) {
            if ( isset( $carton[ $key ] ) && is_numeric( $carton[ $key ] ) && (float) $carton[ $key ] > 0 ) {
                return (int) ceil( (float) $carton[ $key ] );
            }
        }

        return null;
    }

    private function fits_any_carton( int $L, int $W, int $H, array $cartons ): bool {
        foreach ( $cartons as $carton ) {
            if ( $this->fits_carton( $L, $W, $H, $carton ) ) {
                return true;
            }
        }

        return false;
    }

    private function fits_carton( int $L, int $W, int $H, array $carton ): bool {
        $result_dimensions = array( $L, $W, $H );
        $carton_dimensions = array( $carton['length'], $carton['width'], $carton['height'] );

        sort( $result_dimensions );
        sort( $carton_dimensions );

        return $result_dimensions[0] <= $carton_dimensions[0] && $result_dimensions[1] <= $carton_dimensions[1] && $result_dimensions[2] <= $carton_dimensions[2];
    }

    /**
     * Attempt to pack items into fixed base L×W using shelves (Z), rows (Y), and items along X.
     *
     * @param Product[] $products Products.
     * @param int           $L        Base length.
     * @param int           $W        Base width.
     * @return array{H:int,placements:array<int,Placement>}|null
     */
    private function pack_with_base( array $products, int $L, int $W ): ?array {
        // Order items: largest max-dimension first.
        $items = $products;
        usort(
            $items,
            static function ( Product $a, Product $b ): int {
                $am = max( $a->size->l, $a->size->w, $a->size->h );
                $bm = max( $b->size->l, $b->size->w, $b->size->h );
                return $bm <=> $am;
            }
        );

        $placements = array();

        $H_total      = 0;
        $shelf_index  = -1;
        $row_index    = -1;
        $shelf_height = 0; // Z
        $shelf_used_y = 0; // sum of row heights
        $row_height_y = 0; // Y
        $row_used_x   = 0; // X used in row
        $row_origin_y = 0; // Y origin of current row
        $shelf_origin_z = 0; // Z origin of shelf

        $start_new_shelf = function () use ( &$shelf_index, &$row_index, &$shelf_height, &$shelf_used_y, &$row_height_y, &$row_used_x, &$row_origin_y, &$shelf_origin_z, &$H_total ) {
            $H_total        += $shelf_height;
            $shelf_origin_z  = $H_total;
            $shelf_index++;
            $row_index       = -1;
            $shelf_height    = 0;
            $shelf_used_y    = 0;
            $row_height_y    = 0;
            $row_used_x      = 0;
            $row_origin_y    = 0;
        };

        $start_new_row = function () use ( &$row_index, &$row_height_y, &$row_used_x, &$row_origin_y, &$shelf_used_y ) {
            $row_origin_y = $shelf_used_y;
            $row_index++;
            $row_height_y = 0;
            $row_used_x   = 0;
        };

        // Begin with first shelf and row.
        $start_new_shelf();
        $start_new_row();

        foreach ( $items as $p ) {
            // Quick base feasibility: if no orientation fits base at all -> fail early.
            $fits_base = false;
            foreach ( $p->size->orientations() as $o ) {
                if ( $o[0] <= $L && $o[1] <= $W ) {
                    $fits_base = true;
                    break;
                }
            }
            if ( ! $fits_base ) {
                return null;
            }

            $best_choice = null;
            $best_cost   = PHP_INT_MAX;

            foreach ( $p->size->orientations() as $o ) {
                $x = $o[0];
                $y = $o[1];
                $z = $o[2];

                // Option A: same row.
                if ( $row_used_x + $x <= $L ) {
                    $new_row_h   = max( $row_height_y, $y );
                    $new_shelf_h = max( $shelf_height, $z );
                    if ( $shelf_used_y + $new_row_h <= $W ) {
                        $cost = 1000 * ( $new_shelf_h - $shelf_height ) + 10 * ( $new_row_h - $row_height_y ) + ( $L - ( $row_used_x + $x ) );
                        if ( $cost < $best_cost ) {
                            $best_cost   = $cost;
                            $best_choice = array(
                                'mode'   => 'same-row',
                                'dims'   => array( $x, $y, $z ),
                                'rowH'   => $new_row_h,
                                'shelfH' => $new_shelf_h,
                            );
                        }
                    }
                }

                // Option B: new row.
                if ( $x <= $L && $shelf_used_y + $row_height_y + $y <= $W ) {
                    $new_row_h   = $y;
                    $new_shelf_h = max( $shelf_height, $z );
                    $cost        = 1000 * ( $new_shelf_h - $shelf_height ) + 10 * $new_row_h + ( $L - $x ) + 500; // bias
                    if ( $cost < $best_cost ) {
                        $best_cost   = $cost;
                        $best_choice = array(
                            'mode'   => 'new-row',
                            'dims'   => array( $x, $y, $z ),
                            'rowH'   => $new_row_h,
                            'shelfH' => $new_shelf_h,
                        );
                    }
                }

                // Option C: new shelf.
                if ( $x <= $L && $y <= $W ) {
                    $new_row_h   = $y;
                    $new_shelf_h = $z;
                    $cost        = 1000 * $new_shelf_h + 10 * $new_row_h + 5000; // strong bias
                    if ( $cost < $best_cost ) {
                        $best_cost   = $cost;
                        $best_choice = array(
                            'mode'   => 'new-shelf',
                            'dims'   => array( $x, $y, $z ),
                            'rowH'   => $new_row_h,
                            'shelfH' => $new_shelf_h,
                        );
                    }
                }
            }

            if ( null === $best_choice ) {
                return null;
            }

            $x = $best_choice['dims'][0];
            $y = $best_choice['dims'][1];
            $z = $best_choice['dims'][2];

            switch ( $best_choice['mode'] ) {
                case 'same-row':
                    $origin       = array( $row_used_x, $row_origin_y, $shelf_origin_z );
                    $row_used_x  += $x;
                    // why: row/shelf heights may increase due to larger Y/Z.
                    $row_height_y = max( $row_height_y, $y );
                    $shelf_height = max( $shelf_height, $z );
                    $placements[] = new Placement( $p->name, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
                    break;

                case 'new-row':
                    $shelf_used_y += $row_height_y;
                    $start_new_row();
                    // safety: ensure shelf Y does not overflow when closing previous row
                    if ( $shelf_used_y > $W ) {
                        return null;
                    }
                    $origin       = array( 0, $row_origin_y, $shelf_origin_z );
                    $row_height_y = $best_choice['rowH'];
                    $row_used_x   = $x;
                    $shelf_height = max( $shelf_height, $z );
                    $placements[] = new Placement( $p->name, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
                    break;

                case 'new-shelf':
                    $shelf_used_y += $row_height_y;
                    $start_new_shelf();
                    $start_new_row();
                    $origin       = array( 0, $row_origin_y, $shelf_origin_z );
                    $row_height_y = $best_choice['rowH'];
                    $row_used_x   = $x;
                    $shelf_height = $best_choice['shelfH'];
                    $placements[] = new Placement( $p->name, array( $x, $y, $z ), $origin, $shelf_index, $row_index );
                    break;
            }
        }

        // finalize last shelf
        $shelf_used_y += $row_height_y;
        $H_total      += $shelf_height;

        return array(
            'H'          => $H_total,
            'placements' => $placements,
        );
    }
}
