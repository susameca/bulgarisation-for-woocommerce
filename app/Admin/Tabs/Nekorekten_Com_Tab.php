<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;
class Nekorekten_Com_Tab extends Base_Tab {
	protected $fields, $localized_fields;
	
	public function __construct() {
		$this->set_name( __( 'nekorekten.com Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'nekorekten.com API Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "nekorekten" );

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
		$fields = apply_filters( 'woo_bg/admin/settings/nekorektencom/fields', array(
			'nekorekten' => array(
				new Fields\Text_Field( 'api_key', __( 'API Key', 'bulgarisation-for-woocommerce' ), __( 'Enter the generated API key.', 'bulgarisation-for-woocommerce' ), 'required', sprintf( __( 'For generating an API key, use this IP address: %s', 'bulgarisation-for-woocommerce' ), $_SERVER['SERVER_ADDR'] ) . __( 'Note that nekorekten.com may recognize you by another IP address. If so, you\'ll find a message where the reports appear. Copy the IP address from the error and generate a new API key.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'column', __( 'Add column on orders list.', 'bulgarisation-for-woocommerce' ), null, null, __( 'This option will add a column with the customer status next to the shipping info column. Please notice that the maximum requests to nekorekten.com are 5 ( 2 per order ), so if you see "?" icon please open the order details page, to generate the information again.', 'bulgarisation-for-woocommerce' ) ),
				new Fields\TrueFalse_Field( 'check_on_checkout', __( 'Check on checkout and disable COD.', 'bulgarisation-for-woocommerce' ), null, null, __( 'Check by phone number on checkout and disable COD payment method.', 'bulgarisation-for-woocommerce' ) . '<br>' . __('<strong>WARNING:</strong> Please notice that the server has limit of 10 calls per minute.', 'bulgarisation-for-woocommerce') ),
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
		$titles = apply_filters( 'woo_bg/admin/settings/nekorektencom/groups_titles', array(
			'nekorekten' => array(
				'title' => __( 'General', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}
}
