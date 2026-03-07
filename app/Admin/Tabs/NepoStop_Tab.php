<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;
class NepoStop_Tab extends Base_Tab {
	protected $fields, $localized_fields, $tab_name, $container;
	
	public function __construct() {
        $this->container = woo_bg()->container();
        $this->tab_name = get_called_class();
		$this->set_name( __( 'NepoStop Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'NepoStop API Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "nepostop" );

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
			'groups_titles' => $this->get_groups_titles(),
			'tab' => get_called_class(),
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = apply_filters( 'woo_bg/admin/settings/nepostop/fields', array(
			'nepostop' => array(
				new Fields\Text_Field( 'api_key', __( 'API Key', 'bulgarisation-for-woocommerce' ), null, 'required', __( 'Enter the provided API key from nepostop.com.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\Text_Field( 'level_of_warning', __( 'Level of Warning', 'bulgarisation-for-woocommerce' ), __('In percentages', 'bulgarisation-for-woocommerce'), 'required', __( 'Enter how many percentages will trigger a warning or disable COD.', 'bulgarisation-for-woocommerce' ), 'number' ),
				new Fields\TrueFalse_Field( 'upload_labels', __( 'Automatically upload labels to nepostop.com', 'bulgarisation-for-woocommerce' ), null, null, __('By enabling this option, the plugin will send Speedy and Econt tracking numbers to nepostop.com and you can track them from their platform. This way, you will help the service work better.', 'bulgarisation-for-woocommerce' ) ),
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
		$titles = apply_filters( 'woo_bg/admin/settings/nepostop/groups_titles', array(
			'nepostop' => array(
				'title' => __( 'General', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}

    public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::NEPOSTOP ]->validate_access() ) {
			$error = __( 'API Key is incorrect.', 'bulgarisation-for-woocommerce' ); 
		}

		return wpautop( $error );
	}
}
