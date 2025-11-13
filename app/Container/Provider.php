<?php
namespace Woo_BG\Container;

use Pimple\ServiceProviderInterface;

abstract class Provider implements ServiceProviderInterface {
	protected $callbacks = [];

	public function __get( $property ) {
		if ( array_key_exists( $property, $this->callbacks ) ) {
			return $this->callbacks[ $property ];
		}
		return null;
	}

	protected function create_callback( $identifier, callable $callback ) {
		if ( array_key_exists( $identifier, $this->callbacks ) ) {
			throw new \InvalidArgumentException( sprintf( esc_html( __( 'Invalid identifier: %s has already been set.', 'bulgarisation-for-woocommerce' ), esc_html( $identifier ) ) ) );
		}
		
		$this->callbacks[ $identifier ] = $callback;
		return $callback;
	}
}
