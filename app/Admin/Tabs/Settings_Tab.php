<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;
class Settings_Tab extends Base_Tab {
	protected $fields, $localized_fields;
	
	public function __construct() {
		$this->set_name( __( 'Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'Plugin Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "settings" );

		if ( 
			empty( $_GET[ 'tab' ] ) ||
			( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() )
		) {
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
		$fields = apply_filters( 'woo_bg/admin/settings/fields', array(
			'apis' => array(
				new Fields\TrueFalse_Field( 'enable_documents', __( 'Enable Documents Generation', 'bulgarisation-for-woocommerce' ), null, null, __( 'If you want to generate invoices or enable N-18', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_econt', __( 'Enable Econt Delivery? ', 'bulgarisation-for-woocommerce' ), null, null, __( 'Enables Econt Shipping methods.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_speedy', __( 'Enable Speedy Delivery? ', 'bulgarisation-for-woocommerce' ), null, null, __( 'Enables Speedy Shipping methods.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_boxnow', __( 'Enable BOX NOW Delivery? ', 'bulgarisation-for-woocommerce' ), null, null, __( 'Enables BOX NOW Shipping method.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_cvc', __( 'Enable CVC Delivery? ', 'bulgarisation-for-woocommerce' ), null, null, __( 'Enables CVC Shipping methods.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_nekorekten', __( 'Enable nekorekten.com API? ', 'bulgarisation-for-woocommerce' ), null, null, __( 'If yes, you will receive information about the customer from nekorekten.com.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_multi_currency', __( 'Enable BGN/EUR dual price', 'bulgarisation-for-woocommerce' ), null, null, __( 'Enables dual price showing for migrating to EUR.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_stats', __( 'Collection non-sensitive data', 'bulgarisation-for-woocommerce' ), null, null, __( 'Send us non-sensitive data to improve user experience. This data include all of the above options ( what is enabled/disabled )', 'bulgarisation-for-woocommerce' ) ),
			),
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
		$titles = apply_filters( 'woo_bg/admin/settings/groups_titles', array(
			'apis' => array(
				'title' => __( 'Main functionalities', 'bulgarisation-for-woocommerce' ),
			),
			'checkout' => array(
				'title' => __( 'Checkout Settings', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}

	public static function woo_bg_save_settings_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_settings' ) ) {
			wp_send_json_error();
			wp_die();
		}

		$tab_class_name = stripslashes( $_REQUEST['tab'] );
		$tab = new $tab_class_name();
		$tab->load_fields();
		$fields = $tab->get_fields();

		foreach ( $fields as $group => $group_fields ) {
			foreach ( $group_fields as $field ) {
				$field->save_value( $group );
			}
		}

		do_action( 'woo_bg/admin/settings/afte_save_fields/' . $tab_class_name );

		$tab->load_fields();
		
		wp_send_json_success( array(
			'fields' => $tab->get_localized_fields(),
			'groups_titles' => $tab->get_groups_titles(),
			'message' => __( 'Settings saved successfully!', 'bulgarisation-for-woocommerce' ),
			'auth_errors' => $tab->auth_test(),
		) );

		wp_die();
	}
}
