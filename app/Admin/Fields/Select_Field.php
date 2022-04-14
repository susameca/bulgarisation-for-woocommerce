<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Select_Field extends Base_Field {
	protected $options;

	public function __construct( array $options, $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'select' );
		$this->set_options( $options );
		$this->set_name( $name );
		$this->set_title( $title );
		$this->set_help_text( $help_text );
		$this->set_desc( $desc );
		$this->set_validation_rules( $validation_rules );
	}

	public function get_options() {
		return $this->options;
	}

	public function set_options( $options ) {
		$this->options = $options;
	}

	public function format_value( $value ) {
		$options = $this->get_options();
		$returned_value = '';

		if ( $value && isset( $options[ $value ] ) ) {
			$returned_value = $options[ $value ];
		} else {
			$first_key = array_key_first( $options );

			if ( isset( $options[ $first_key ] ) ) {
				$returned_value = $options[ $first_key ];
			}
		}

		return $returned_value;
	}

	public function populate( $group ) {
		$option = get_option( 'woo_bg_settings_' . $group . '_' . $this->get_name() );

		return array(
			'title' => $this->get_title(),
			'help_text' => $this->get_help_text(),
			'description' => $this->get_desc(),
			'value' => $this->format_value( $option ),
			'type' => $this->get_type(),
			'validation_rules' => $this->get_validation_rules(),
			'options' => $this->get_options(),
		); 
	}

	public function save_value( $group ) {
		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), sanitize_text_field( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ][ 'id' ] ) );
	}
}
