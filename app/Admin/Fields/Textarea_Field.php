<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Textarea_Field extends Base_Field {
	protected $subtype;

	public function __construct( $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'textarea' );
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
		$allowed_html = array(
		  'a' => array(
		    'href' => array(),
		  ),
		  'br' => array(),
		  'p' => array(),
		  'strong' => array(),
		  'span' => array(),
		  'small' => array(),
		);

		$value = wp_kses( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ], $allowed_html );

		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), $value );
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
}