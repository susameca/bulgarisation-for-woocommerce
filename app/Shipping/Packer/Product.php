<?php
namespace Woo_BG\Shipping\Packer;

class Product {
    public string $name;
    public Size $size;

    public function __construct( string $name, Size $size ) {
        $this->name = $name;
        $this->size = $size;
    }
}