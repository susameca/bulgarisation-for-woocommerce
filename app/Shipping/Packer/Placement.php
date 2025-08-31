<?php
namespace Woo_BG\Shipping\Packer;

class Placement {
	public string $name;
	/** @var array{0:int,1:int,2:int} */
	public array $dims;   // [x, y, z]
	/** @var array{0:int,1:int,2:int} */
	public array $origin; // [x, y, z]
	public int $shelf_index;
	public int $row_index;

	public function __construct( string $name, array $dims, array $origin, int $shelf_index, int $row_index ) {
		$this->name        = $name;
		$this->dims        = $dims;
		$this->origin      = $origin;
		$this->shelf_index = $shelf_index;
		$this->row_index   = $row_index;
	}
}