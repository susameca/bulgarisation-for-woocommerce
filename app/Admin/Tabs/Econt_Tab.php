<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Econt_Tab extends Base_Tab {
	protected $fields, $localized_fields, $container, $tab_name;
	
	public function __construct() {
		$this->container = woo_bg()->container();
		$this->tab_name = get_called_class();
		$this->set_name( __( 'Econt Settings', 'woo-bg' ) );
		$this->set_description( __( 'econt.com API Settings', 'woo-bg' ) );
		$this->set_tab_slug( "econt" );
		$this->maybe_clear_cache();

		add_action( 'woo_bg/admin/settings/afte_save_fields/'. $this->tab_name, array( $this, 'after_save_fields' ) );
		add_filter( 'woo_bg/admin/settings/econt/fields', array( $this, 'add_profile_data_fields' ), 10 );
		add_filter( 'woo_bg/admin/settings/econt/fields', array( $this, 'add_send_from_fields' ), 20 );
		add_filter( 'woo_bg/admin/settings/econt/groups_titles', array( $this, 'add_send_from_group_title' ) );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->load_fields();
		}
	}

	private function maybe_clear_cache() {
		if ( !isset( $_GET[ 'clear-cache' ] ) ) {
			return;
		}

		$this->container[ Client::ECONT ]::clear_cache_folder();
		$this->container[ Client::ECONT ]::clear_profile_data();
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
			'econt' => array(
				new Fields\Select_Field( array(
						'demo' => array(
							'id' => 'demo',
							'label' => __( 'Demo', 'woo-bg' ),
						),
						'live' => array(
							'id' => 'live',
							'label' => __( 'Live', 'woo-bg' ),
						),
					), 'env', __( 'Environment', 'woo-bg' ), null, null, __( 'Select used environment. For demo you can use Username: "iasp-dev" and Password: "1Asp-dev". For live environment use your credentials or create a registration at <a target="_blank" href="https://login.econt.com/register/">Econt Delivery</a>', 'woo-bg' ) ),
				new Fields\Text_Field( 'user', __( 'Username', 'woo-bg' ), __( 'Enter your username.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'password', __( 'Password', 'woo-bg' ), __( 'Enter your password.', 'woo-bg' ), 'required', null, 'password' ),
			)
		);

		$fields = apply_filters( 'woo_bg/admin/settings/econt/fields', $fields );

		$this->set_fields( $fields );
	}

	public function add_profile_data_fields( $fields ) {
		if ( $this->container[ Client::ECONT_PROFILE ]->is_valid_profile( true ) ) {
			$all_profiles = $this->container[ Client::ECONT_PROFILE ]->get_profiles_for_settings();

			$fields[ 'econt' ][] = new Fields\Select_Field( $all_profiles, 'profile_key', __( 'Select profile', 'woo-bg' ), null, null, __( 'Select the profile you want to use and save to show or update the other options.', 'woo-bg' )
			);

			$fields[ 'econt' ][] = new Fields\Text_Field( 'name', __( 'Name', 'woo-bg' ), null, 'required' );
			$fields[ 'econt' ][] = new Fields\Text_Field( 'phone', __( 'Phone', 'woo-bg' ), null, 'required' );
			$fields[ 'econt' ][] = new Fields\Select_Field( woo_bg_get_yes_no_options(), 'force_variations_in_desc', __( 'Force variations in label', 'woo-bg' ), null, null, __( 'Add additional variations information. Please use this option only if you want the variation data to be available in the label print and it\'s missing.', 'woo-bg' ) );
			$fields[ 'econt' ][] = new Fields\Select_Field( woo_bg_get_yes_no_options(), 'label_after_checkout', __( 'Generate label after checkout', 'woo-bg' ), null, null, __( 'This option will try to generate your label immediately after user checkout. Also, will add the tracking number in the order email.', 'woo-bg' ) );
			$fields[ 'econt' ][] = new Fields\Select_Field( $this->generate_pay_options(), 'pay_options', __( 'Cash on delivery agreement', 'woo-bg' ), null, null, __( 'Choose cash on delivery agreement', 'woo-bg' ) );
			$fields[ 'econt' ][] = new Fields\Select_Field( 
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
			);

		}

		return $fields;
	}

	public function add_send_from_fields( $fields ) {
		if ( $this->container[ Client::ECONT_PROFILE ]->is_valid_profile( true ) ) {
			$all_profiles = $this->container[ Client::ECONT_PROFILE ]->get_profiles_for_settings();

			$send_from = ( woo_bg_get_option( 'econt', 'send_from' ) ) ? woo_bg_get_option( 'econt', 'send_from' ) : 'office';
			$addresses = $this->container[ Client::ECONT_PROFILE ]->get_formatted_addresses();
			$cities = $this->container[ Client::ECONT_CITIES ]->get_formatted_cities();
			$offices = $this->container[ Client::ECONT_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'econt_send_from', 'office_city' ) );
			$fields[ 'econt_send_from' ] = [];

			switch ( $send_from ) {
				case 'address':
					$fields[ 'econt_send_from' ][] = new Fields\Select_Field( $addresses, 'address', __( 'Select Address', 'woo-bg' ) );
					break;
				case 'office':
					$fields[ 'econt_send_from' ][] = new Fields\Select_Field( $cities, 'office_city', __( 'City', 'woo-bg' ) );
					$fields[ 'econt_send_from' ][] = new Fields\Select_Field( 
						$offices['shops'], 
						'office', 
						__( 'Office', 'woo-bg' ), 
						null, 
						null, 
						__('Choose a city and save in order to show offices.', 'woo-bg' ) 
					);
					break;
			}
		}

		return $fields;
	}

	public function generate_pay_options() {
		$pay_options = [
			'no' => array(
				'id' => 'no',
				'label' => __( 'No agreement', 'woo-bg' )
			),
		];

		if ( !empty( $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['cdPayOptions'] ) ) {
			foreach ( $this->container[ Client::ECONT_PROFILE ]->get_profile_data()['cdPayOptions'] as $option ) {
				$pay_options[ $option[ 'num' ] ] = array(
					'id' => $option[ 'num' ],
					'label' => $option[ 'num' ],
				);
			}
		}
		
		return $pay_options;
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_localized_fields() {
		return $this->localized_fields;
	}

	public function get_groups_titles() {
		$titles = apply_filters( 'woo_bg/admin/settings/econt/groups_titles', array(
			'econt' => array(
				'title' => __( 'General', 'woo-bg' ),
			),
		) );

		return $titles;
	}

	public function add_send_from_group_title( $titles ) {
		if ( $this->container[ Client::ECONT_PROFILE ]->is_valid_profile( true ) ) {
			$titles['econt_send_from'] = array(
				'title' => __( 'Send From', 'woo-bg' ),
			);
		}
		return $titles;
	}

	public function after_save_fields() {
		$is_valid_profile = $this->container[ Client::ECONT_PROFILE ]->check_credentials( true );

		woo_bg_set_option( 'econt', 'is_valid_profile', $is_valid_profile );

		if ( $is_valid_profile ) {
			$this->update_profile_fields();
		}
	}

	public function update_profile_fields() {
		$profile_data = $this->container[ Client::ECONT_PROFILE ]->get_profile_data( 1 );

		$name = woo_bg_get_option( 'econt', 'name' );
		$phone = woo_bg_get_option( 'econt', 'phone' );

		if ( !$name ) {
			woo_bg_set_option( 'econt', 'name', $profile_data['client']['molName'] );
		}
		
		if ( !$phone ) {
			woo_bg_set_option( 'econt', 'phone', $profile_data['client']['phones'][0] );
		}
	}

	public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::ECONT_PROFILE ]->is_valid_profile( true ) ) {
			ob_start();
			$tooltip = '<span class="woocommerce-help-tip woocommerce-help-tip--with-image" data-tip="<img src=\'' . woo_bg()->plugin_dir_url() . '/app/Admin/Tabs/Econt_Tab/images/econt-help.png\'>"></span>';
			echo wpautop( sprintf( __( 'Username and password are incorrect. Please generate API keys from "Integration for online shops" %s', 'woo-bg' ), $tooltip ) );
			$error = ob_get_clean(); 
		}

		return $error;
	}
}
