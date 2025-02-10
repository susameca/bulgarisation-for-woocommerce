<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Multi_Field extends Base_Field {
	protected $fields_types;

	public function __construct( $fields_types, $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'multi' );
		$this->set_name( $name );
		$this->set_title( $title );
		$this->set_help_text( $help_text );
		$this->set_desc( $desc );
		$this->set_validation_rules( $validation_rules );
		$this->set_fields_types( $fields_types );
	}

	public function get_fields_types() {
		return $this->fields_types;
	}

	public function set_fields_types( $fields_types ) {
		$this->fields_types = $fields_types;
	}

	public function format_value( $value ) {
		return ( $value ) ? json_decode( $value, 1 ) : [ ['from' => '', 'to' => '', 'price' => ''] ];
	}

	public function populate( $group ) {
		$option = get_option( 'woo_bg_settings_' . $group . '_' . $this->get_name() );

		return array(
			'title' => $this->get_title(),
			'help_text' => $this->get_help_text(),
			'description' => $this->get_desc(),
			'value' => $this->format_value( $option, 1 ),
			'type' => $this->get_type(),
			'validation_rules' => $this->get_validation_rules(),
			'fields_types' => $this->get_fields_types(),
		); 
	}

	public function save_value( $group ) {
		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), json_encode( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ] ) );
	}
}
