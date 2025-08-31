<?php
namespace Woo_BG\Shipping\Packer;

class Pack_Result {
	public int $L;
	public int $W;
	public int $H;
	/** @var Placement[] */
	public array $placements;
	public float $fill_rate;

	public function __construct( int $L, int $W, int $H, array $placements, float $fill_rate ) {
		$this->L          = $L;
		$this->W          = $W;
		$this->H          = $H;
		$this->placements = $placements;
		$this->fill_rate  = $fill_rate;
	}
}