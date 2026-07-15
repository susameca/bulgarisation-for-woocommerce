<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;
class Shipping_Tab extends Base_Tab {
	protected $fields, $localized_fields;
	
	public function __construct() {
		$this->set_name( __( 'Shipping', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "shipping" );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->load_fields();
		}
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'payment_types' => woo_bg_get_payment_types(),
			'groups_titles' => $this->get_groups_titles(),
			'tab' => get_called_class(),
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = apply_filters( 'woo_bg/admin/shipping/fields', array(
			'checkout' => array(
				new Fields\TrueFalse_Field( 'alternative_shipping_table', __( 'Alternative shipping options layout ( checkout )', 'bulgarisation-for-woocommerce' ), null, null, __( 'Make shipping options on 2 rows and full width in the checkout table.', 'bulgarisation-for-woocommerce' ) ),
			)
		) );

		$this->set_fields( $fields );
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_localized_fields() {
		return $this->localized_fields;
	}

	public function get_groups_titles() {
		$titles = apply_filters( 'woo_bg/admin/shipping/groups_titles', array(
			'checkout' => array(
				'title' => __( 'Checkout Settings', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}
}
