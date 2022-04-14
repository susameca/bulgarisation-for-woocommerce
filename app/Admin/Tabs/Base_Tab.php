<?php
namespace Woo_BG\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

abstract class Base_Tab {
	protected $name;
	protected $description;
	public $tab_slug;

	//Getters
	public function get_name() {
		return $this->name;
	}

	public function get_description() {
		return $this->description;
	}

	//Setters
	public function set_name( $name ) {
		return $this->name = esc_html( $name );
	}

	public function set_description( $description ) {
		return $this->description = esc_html( $description );
	}

	public function set_tab_slug( $slug ) {
		return $this->tab_slug = urlencode( $slug );
	}

	public function set_fields( $fields ) {
		$this->localized_fields = self::populate_fields( $fields );

		$this->fields = $fields;
	}

	public static function populate_fields( $fields ) {
		$populated_fields = array();

		foreach ( $fields as $group => $group_fields ) {
			$populate_fields[ $group ] = [];

			foreach ( $group_fields as $field ) {
				$populated_fields[ $group ][ $field->get_name() ] = $field->populate( $group );
			}
		}

		return $populated_fields;
	}
	
	//abstract methods
	abstract public function render_tab_html();
}
