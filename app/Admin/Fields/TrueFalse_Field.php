<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class TrueFalse_Field extends Base_Field {
	protected $subtype;

	public function __construct( $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'true_false' );
		$this->set_name( $name );
		$this->set_title( $title );
		$this->set_help_text( $help_text );
		$this->set_desc( $desc );
		$this->set_validation_rules( $validation_rules );
	}

	public function format_value( $value ) {
		return wc_string_to_bool( $value );
	}

	public function save_value( $group ) {
		$value = ( wc_string_to_bool( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ] ) ) ? 'yes' : 'no';
		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), sanitize_text_field( $value ) );
	}

	//Setters
	public function populate( $group ) {
		$option = woo_bg_get_option( $group, $this->get_name() );
		
		return array(
			'name' => $this->get_name(),
			'title' => $this->get_title(),
			'help_text' => $this->get_help_text(),
			'description' => $this->get_desc(),
			'value' => $this->format_value( $option ),
			'type' => $this->get_type(),
			'validation_rules' => $this->get_validation_rules(),
		); 
	}
}