<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Base_Field Class.
 */
abstract class Base_Field {
	protected $type, $name, $title, $value, $help_text, $validation_rules, $desc;

	//Getters
	public function get_type() {
		return $this->type;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_help_text() {
		return $this->help_text;
	}

	public function get_desc() {
		return $this->desc;
	}

	public function get_validation_rules() {
		return $this->validation_rules;
	}

	//Setters
	public function set_type( $type ) {
		return $this->type = sanitize_title_with_dashes( $type );
	}

	public function set_name( $name ) {
		return $this->name = sanitize_title_with_dashes( $name );
	}

	public function set_title( $title ) {
		return $this->title = esc_html( $title );
	}

	public function set_help_text( $help_text ) {
		return $this->help_text = ( $help_text ) ? wp_kses_post( $help_text ) : '';
	}

	public function set_desc( $desc ) {
		return $this->desc = ( $desc ) ? wp_kses_post( $desc ) : '';
	}

	public function set_validation_rules( $validation_rules ) {
		return $this->validation_rules = $validation_rules;
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
			'validation_rules' => $this->get_validation_rules(),
		); 
	}
	
	//abstract methods
	abstract public function format_value( $value );
	abstract public function save_value( $group );
}
