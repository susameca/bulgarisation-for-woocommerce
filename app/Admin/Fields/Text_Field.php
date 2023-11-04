<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Text_Field extends Base_Field {
	protected $subtype;

	public function __construct( $name, $title, $help_text = '', $validation_rules = '', $desc = '', $subtype = 'text' ) {
		$this->set_type( 'text' );
		$this->set_subtype( $subtype );
		$this->set_name( $name );
		$this->set_title( $title );
		$this->set_help_text( $help_text );
		$this->set_desc( $desc );
		$this->set_validation_rules( $validation_rules );
	}

	public function format_value( $value ) {
		return ( $value ) ? $value : '';
	}

	public function save_value( $group ) {
		$value = wp_unslash( sanitize_text_field( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ] ) );

		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), sanitize_text_field( $value ) );
	}

	//Getters
	public function get_subtype() {
		return $this->subtype;
	}

	//Setters
	public function set_subtype( $subtype ) {
		return $this->subtype = sanitize_title_with_dashes( $subtype );
	}

	public function populate( $group ) {
		$option = woo_bg_get_option( $group, $this->get_name() );
		
		return array(
			'name' => $this->get_name(),
			'title' => $this->get_title(),
			'help_text' => $this->get_help_text(),
			'description' => $this->get_desc(),
			'value' => $this->format_value( $option ),
			'type' => $this->get_type(),
			'subtype' => $this->get_subtype(),
			'validation_rules' => $this->get_validation_rules(),
		); 
	}
}