<?php
namespace Woo_BG\Reports\Providers;

defined( 'ABSPATH' ) || exit;

abstract class Provider_Base {
    private $name;

    abstract static function get_all_reports( $order, $force = false );

    abstract static function get_all_reports_from_meta( $order );

    abstract function print_reports_html( $order );

    public function get_name() {
        return $this->name;
    }

    public function set_name( $name ) {
		return $this->name = esc_html( $name );
	}
}