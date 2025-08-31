<?php
namespace Woo_BG\Shipping\Packer;

class Size {
    /**
     * Length (X), width (Y), height (Z) in millimetres.
     *
     * @var int
     */
    public int $l;

    /**
     * @var int
     */
    public int $w;

    /**
     * @var int
     */
    public int $h;

    /**
     * @param int $l Length.
     * @param int $w Width.
     * @param int $h Height.
     */
    public function __construct( int $l, int $w, int $h ) {
        if ( $l <= 0 || $w <= 0 || $h <= 0 ) {
            throw new InvalidArgumentException( 'Dimensions must be positive.' );
        }

        $this->l = $l;
        $this->w = $w;
        $this->h = $h;
    }

    /**
     * All 6 axis-aligned orientations as [x, y, z].
     *
     * @return array<int, array{0:int,1:int,2:int}>
     */
    public function orientations(): array {
        $l = $this->l;
        $w = $this->w;
        $h = $this->h;

        return array(
            array( $l, $w, $h ),
            array( $l, $h, $w ),
            array( $w, $l, $h ),
            array( $w, $h, $l ),
            array( $h, $l, $w ),
            array( $h, $w, $l ),
        );
    }

    /**
     * Minimum face area among (L×W, L×H, W×H).
     */
    public function min_face_area(): int {
        return min( $this->l * $this->w, $this->l * $this->h, $this->w * $this->h );
    }

    /**
     * Minimal possible max(X, Y) across orientations (lower bound for carton side).
     */
    public function min_max_base_side(): int {
        $min = PHP_INT_MAX;

        foreach ( $this->orientations() as $o ) {
            $min = min( $min, max( $o[0], $o[1] ) );
        }

        return $min;
    }

    public function volume(): int {
        return $this->l * $this->w * $this->h;
    }
}