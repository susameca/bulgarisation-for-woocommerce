<?php
namespace Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Text_Field extends Base_Field {
	public function __construct( $name, $title, $help_text = '', $validation_rules = '', $desc = '' ) {
		$this->set_type( 'text' );
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
		$value = sanitize_text_field( $_REQUEST[ 'options' ][ $group ][ $this->get_name() ][ 'value' ] );

		update_option( 'woo_bg_settings_' . $group . '_' . $this->get_name(), sanitize_text_field( $value ) );
	}
}
