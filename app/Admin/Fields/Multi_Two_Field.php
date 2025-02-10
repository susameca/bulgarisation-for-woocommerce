<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Multi_Two_Field extends Multi_Field {
	public function __construct( $fields_types, $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'multi_two' );
		$this->set_name( $name );
		$this->set_title( $title );
		$this->set_help_text( $help_text );
		$this->set_desc( $desc );
		$this->set_validation_rules( $validation_rules );
		$this->set_fields_types( $fields_types );
	}
	public function format_value( $value ) {
		return ( $value ) ? json_decode( $value, 1 ) : [ ['from' => '', 'to' => '', 'from_pice' => '', 'to_price' => '', 'price' => ''] ];
	}
}
