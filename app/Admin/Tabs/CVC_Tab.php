<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class CVC_Tab extends Base_Tab {
	protected $fields, $localized_fields, $container, $tab_name;
	
	public function __construct() {
		$this->container = woo_bg()->container();
		$this->tab_name = get_called_class();
		$this->set_name( __( 'CVC Settings', 'woo-bg' ) );
		$this->set_description( __( 'cvc.com API Settings', 'woo-bg' ) );
		$this->set_tab_slug( "cvc" );
		$this->maybe_clear_cache();

		add_action( 'woo_bg/admin/settings/afte_save_fields/'. $this->tab_name, array( $this, 'after_save_fields' ) );
		add_filter( 'woo_bg/admin/settings/cvc/fields', array( $this, 'add_send_from_fields' ), 20 );
		add_filter( 'woo_bg/admin/settings/cvc/groups_titles', array( $this, 'add_send_from_group_title' ) );
		
		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->load_fields();
		}
	}

	private function maybe_clear_cache() {
		if ( !isset( $_GET[ 'clear-cache' ] ) ) {
			return;
		}

		$this->container[ Client::CVC ]::clear_cache_folder();
		$this->container[ Client::CVC ]::clear_profile_data();
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<a href="<?php echo add_query_arg( 'clear-cache', true ) ?>" class="button-secondary"><?php _e( 'Clear cache', 'woo-bg' ) ?></a>

		<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->
		<?php		
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'groups_titles' => $this->get_groups_titles(),
			'auth_errors' => $this->auth_test(),
			'tab' => $this->tab_name,
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = array(
			'cvc' => array(
				new Fields\Text_Field( 'token', __( 'API Token', 'woo-bg' ), __( 'Enter your token.', 'woo-bg' ), 'required' ),
			),
		);

		$fields = apply_filters( 'woo_bg/admin/settings/cvc/fields', $fields );

		$this->set_fields( $fields );
	}

	public function add_send_from_fields( $fields ) {
		if ( $this->container[ Client::CVC_PROFILE ]->is_valid_profile( true ) ) {
			$send_from = ( woo_bg_get_option( 'cvc_sender', 'send_from' ) ) ? woo_bg_get_option( 'cvc_sender', 'send_from' ) : 'office';
			
			$addresses = $this->container[ Client::CVC_PROFILE ]->get_formatted_addresses();
			$cities = $this->container[ Client::CVC_CITIES ]->get_formatted_cities();

			$fields[ 'cvc_sender' ] = [
				new Fields\Text_Field( 'name', __( 'Name', 'woo-bg' ), __( 'Enter sender name.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'phone', __( 'Phone', 'woo-bg' ), __( 'Enter sender phone.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'email', __( 'E-mail', 'woo-bg' ), __( 'Enter sender email.', 'woo-bg' ), 'required|email' ),
				new Fields\Select_Field( woo_bg_get_yes_no_options(), 'contract_pay', __( 'Pay by contract', 'woo-bg' ) ),
				new Fields\Select_Field( woo_bg_get_yes_no_options(), 'force_variations_in_desc', __( 'Force variations in label', 'woo-bg' ), null, null, __( 'Add additional variations information. Please use this option only if you want the variation data to be available in the label print and it\'s missing.', 'woo-bg' ) ),
				new Fields\Select_Field( woo_bg_get_yes_no_options(), 'label_after_checkout', __( 'Generate label after checkout', 'woo-bg' ), null, null, __( 'This option will try to generate your label immediately after user checkout. Also, will add the tracking number in the order email.', 'woo-bg' ) ),
				new Fields\Select_Field( $cities, 'city', __( 'City', 'woo-bg' ) ),
				new Fields\Select_Field( 
					array(
						'office' => array(
							'id' => 'office',
							'label' => __( 'Office', 'woo-bg' ),
						),
						'address' => array(
							'id' => 'address',
							'label' => __( 'Address', 'woo-bg' ),
						),
					), 'send_from', __( 'Send From', 'woo-bg' ), null, null, __( 'Select from where you will send the packages and save to show more options.', 'woo-bg' ) 
				),
			];

			switch ( $send_from ) {
				case 'address':
					if ( !empty( $addresses ) ) {
						$fields[ 'cvc_sender' ][] = new Fields\Select_Field( $addresses, 'address', __( 'Select Address', 'woo-bg' ) );
					}

					break;
				case 'office':
					if ( $city = woo_bg_get_option( 'cvc_sender', 'city' ) ) {
						$offices = $this->container[ Client::CVC_OFFICES ]->get_formatted_offices( $city );

						if ( !empty( $offices ) ) {
							$fields[ 'cvc_sender' ][] = new Fields\Select_Field( $offices, 'office', __( 'Office', 'woo-bg' ) );
						}
					}
					
					break;
			}
		}

		return $fields;
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_localized_fields() {
		return $this->localized_fields;
	}

	public function get_groups_titles() {
		$titles = apply_filters( 'woo_bg/admin/settings/cvc/groups_titles', array(
			'cvc' => array(
				'title' => __( 'General', 'woo-bg' ),
			),
		) );

		return $titles;
	}

	public function add_send_from_group_title( $titles ) {
		if ( $this->container[ Client::CVC_PROFILE ]->is_valid_profile( true ) ) {
			$titles['cvc_sender'] = array(
				'title' => __( 'Send From', 'woo-bg' ),
			);
		}

		return $titles;
	}

	public function after_save_fields() {
		$is_valid_profile = $this->container[ Client::CVC_PROFILE ]->check_credentials( true );

		woo_bg_set_option( 'cvc', 'is_valid_profile', $is_valid_profile );

		if ( $is_valid_profile ) {
			$this->container[ Client::CVC_PROFILE ]->get_profile_data();
		}
	}

	public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::CVC_PROFILE ]->is_valid_profile( true ) ) {
			ob_start();
			
			echo wpautop( __( 'API Token is incorrect.', 'woo-bg' ) );
			
			$error = ob_get_clean(); 
		}

		return $error;
	}
}
