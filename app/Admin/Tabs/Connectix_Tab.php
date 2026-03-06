<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;
class Connectix_Tab extends Base_Tab {
	protected $fields, $localized_fields, $tab_name, $container;
	
	public function __construct() {
        $this->container = woo_bg()->container();
        $this->tab_name = get_called_class();
		$this->set_name( __( 'Connectix Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'Connectix.bg API Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "connectix" );

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
		$fields = apply_filters( 'woo_bg/admin/settings/connectix/fields', array(
			'connectix' => array(
                new Fields\Select_Field( array(
                    'live' => array(
                        'id' => 'live',
                        'label' => __( 'Live', 'bulgarisation-for-woocommerce' ),
                    ),
                    'sandbox' => array(
                        'id' => 'sandbox',
                        'label' => __( 'Sandbox', 'bulgarisation-for-woocommerce' ),
                    ),
                ), 'env', __( 'Environment', 'bulgarisation-for-woocommerce' ), null, null, __( 'Select used environment.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\Text_Field( 'token', __( 'Token', 'bulgarisation-for-woocommerce' ), null, 'required', __( 'Enter the provided token from connectix.bg.', 'bulgarisation-for-woocommerce' ) ),
                new Fields\Select_Field( array(
                    'low' => array(
                        'id' => 'low',
                        'label' => __( 'Low', 'bulgarisation-for-woocommerce' ),
                    ),
                    'medium' => array(
                        'id' => 'medium',
                        'label' => __( 'Medium', 'bulgarisation-for-woocommerce' ),
                    ),
                    'high' => array(
                        'id' => 'high',
                        'label' => __( 'High', 'bulgarisation-for-woocommerce' ),
                    ),
                    'ultra_high' => array(
                        'id' => 'ultra_high',
                        'label' => __( 'Ultra high', 'bulgarisation-for-woocommerce' ),
                    ),
                ), 'level_of_warning', __( 'Level of Warning', 'bulgarisation-for-woocommerce' ), null, null, __( 'Choose level of warning that will trigger notification or disable COD.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'enable_tracking', __( 'Send tracking information', 'bulgarisation-for-woocommerce' ), null, null, __('By enabling this option, the plugin will send Speedy and Econt tracking numbers to Connectix.bg. This way, you will help the service work better.', 'bulgarisation-for-woocommerce' ) ),
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
		$titles = apply_filters( 'woo_bg/admin/settings/connectix/groups_titles', array(
			'connectix' => array(
				'title' => __( 'General', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}

    public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::CONNECTIX ]->validate_access() ) {
			$error = __( 'Token is incorrect.', 'bulgarisation-for-woocommerce' ); 
		}

		return wpautop( $error );
	}
}
