<?php
namespace Woo_BG\Shipping\Packer;

class Carton_Packer {
    /**
     * Try multiple base candidates and return the best-volume solution.
     *
     * @param Product[] $products Products to pack.
     * @return Pack_Result
     */
    public function find_best_carton( array $products ): Pack_Result {
        if ( empty( $products ) ) {
            throw new InvalidArgumentException( 'Products list cannot be empty.' );
        }

        $area_lb  = 0;
        $side_lb  = 0;
        $total_vol = 0;

        foreach ( $products as $p ) {
            $area_lb  += $p->size->min_face_area();
            $side_lb   = max( $side_lb, $p->size->min_max_base_side() );
            $total_vol += $p->size->volume();
        }

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
        foreach ( $products as $p ) {
            foreach ( $p->size->orientations() as $o ) {
                $add_candidate( $candidates, $o[0], $o[1] );
                // +5% slack to be robust to fragmentation.
                $add_candidate( $candidates, (int) ceil( $o[0] * 1.05 ), (int) ceil( $o[1] * 1.05 ) );
            }
        }

        $best    = null;
        $bestVol = PHP_INT_MAX;

        foreach ( $candidates as $base ) {
            $L = $base[0];
            $W = $base[1];

            $attempt = $this->pack_with_base( $products, $L, $W );
            if ( null === $attempt ) {
                continue;
            }

            $H   = $attempt['H'];
            $vol = $L * $W * $H;

            if ( $vol < $bestVol || ( $vol === $bestVol && ( null !== $best && ( $H < $best->H || ( $H === $best->H && $L < $best->L ) ) ) ) ) {
                $fill  = $total_vol / $vol;
                $best  = new Pack_Result( $L, $W, $H, $attempt['placements'], $fill );
                $bestVol = $vol;
            }
        }

        if ( null === $best ) {
            throw new RuntimeException( 'Failed to pack with generated candidates. Try increasing slack or check items.' );
        }

        return $best;
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