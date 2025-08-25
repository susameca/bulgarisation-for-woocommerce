<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Speedy_Tab extends Base_Tab {
	protected $fields, $localized_fields, $container, $tab_name;
	
	public function __construct() {
		$this->container = woo_bg()->container();
		$this->tab_name = get_called_class();
		$this->set_name( __( 'Speedy Settings', 'woo-bg' ) );
		$this->set_description( __( 'speedy.bg API Settings', 'woo-bg' ) );
		$this->set_tab_slug( "speedy" );

		add_action( 'woo_bg/admin/settings/afte_save_fields/'. $this->tab_name, array( $this, 'after_save_fields' ) );
		add_filter( 'woo_bg/admin/settings/speedy/fields', array( $this, 'add_profile_data_fields' ), 10 );
		add_filter( 'woo_bg/admin/settings/speedy/fields', array( $this, 'add_send_from_fields' ), 20 );
		add_filter( 'woo_bg/admin/settings/speedy/groups_titles', array( $this, 'add_send_from_group_title' ) );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->maybe_clear_cache();
			$this->load_fields();
		}
	}

	private function maybe_clear_cache() {
		if ( !isset( $_GET[ 'clear-cache' ] ) ) {
			return;
		}

		$this->container[ Client::SPEEDY ]::clear_cache_folder();
		$this->container[ Client::SPEEDY ]::clear_profile_data();
	}

	public function render_tab_html() {
		$this->admin_localize();
		woo_bg_support_text();
		?>
		<a href="<?php echo esc_url( add_query_arg( 'clear-cache', true ) ) ?>" class="button-secondary"><?php esc_html_e( 'Clear cache', 'woo-bg' ) ?></a>

		<div class="notice"><p><?php _e( 'Please use "Classic Checkout" block or [woocommerce_checkout] shortcode.', 'woo-bg' ) ?></p></div>

		<div class="notice"><p><?php _e( 'Use credentials for API.', 'woo-bg' ) ?></p></div>
		
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
			'speedy' => array(
				new Fields\Text_Field( 'user', __( 'Username', 'woo-bg' ), __( 'Enter your username.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'password', __( 'Password', 'woo-bg' ), __( 'Enter your password.', 'woo-bg' ), 'required', null, 'password' ),
			)
		);

		$fields = apply_filters( 'woo_bg/admin/settings/speedy/fields', $fields );

		$this->set_fields( $fields );
	}

	public function add_profile_data_fields( $fields ) {
		if ( $this->container[ Client::SPEEDY_PROFILE ]->is_valid_profile( true ) ) {
			$all_profiles = $this->container[ Client::SPEEDY_PROFILE ]->get_profiles_for_settings();

			if ( !empty( $all_profiles ) ) {
				$fields[ 'speedy' ][] = new Fields\Select_Field( $all_profiles, 'profile_key', __( 'Select profile', 'woo-bg' ), null, null, __( 'Select the profile you want to use and save to show or update the other options.', 'woo-bg' )
				);
				$fields[ 'speedy' ][] = new Fields\Text_Field( 'name', __( 'Name', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\Text_Field( 'phone', __( 'Phone', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'ppp', __( 'Cash on delivery as PPP.', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'kb', __( 'Cash on delivery with cash receipt.', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'disable_apt', __( 'Remove APT from offices', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'force_variations_in_desc', __( 'Force variations in label', 'woo-bg' ), null, null, __( 'Add additional variations information. Please use this option only if you want the variation data to be available in the label print and it\'s missing.', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'label_after_checkout', __( 'Generate label after checkout', 'woo-bg' ), null, null, __( 'This option will try to generate your label immediately after user checkout. Also, will add the tracking number in the order email.', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\TrueFalse_Field( 'declared_value', __( 'Declared value', 'woo-bg' ), null, null, __( 'Adds declared value to the label if payment is COD.', 'woo-bg' ) );
				$fields[ 'speedy' ][] = new Fields\Select_Field( 
					array(
						'office' => array(
							'id' => 'office',
							'label' => __( 'Office', 'woo-bg' ),
						),
						'address' => array(
							'id' => 'address',
							'label' => __( 'Address', 'woo-bg' ),
						),
					), 'send_from', __( 'Send From', 'woo-bg' ), null, null, __( 'Select from where you will send the packages and save to show more options.', 'woo-bg' ) . " " . __('If you choose "Address" the profile address will be used.', 'woo-bg'),
				);
			}
		}

		return $fields;
	}

	public function add_send_from_fields( $fields ) {
		if ( $this->container[ Client::SPEEDY_PROFILE ]->is_valid_profile( true ) ) {
			$all_profiles = $this->container[ Client::SPEEDY_PROFILE ]->get_profiles_for_settings();

			if ( !empty( $all_profiles ) ) {
				$send_from = ( woo_bg_get_option( 'speedy', 'send_from' ) ) ? woo_bg_get_option( 'speedy', 'send_from' ) : 'office';
				$cities = $this->container[ Client::SPEEDY_CITIES ]->get_formatted_cities();
				$fields[ 'speedy_send_from' ] = [];
				$fields[ 'speedy_send_from' ][] = new Fields\Select_Field( $cities, 'city', __( 'City', 'woo-bg' ) );

				if ( woo_bg_get_option( 'speedy_send_from', 'city' ) ) {
					$offices = $this->container[ Client::SPEEDY_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'speedy_send_from', 'city' ) );
					if ( !empty( $offices['shops'] ) ) {
						$fields[ 'speedy_send_from' ][] = new Fields\Select_Field( 
							$offices['shops'], 
							'office', 
							__( 'Office', 'woo-bg' ), 
							null, 
							null, 
							__('Choose a city and save in order to show offices.', 'woo-bg' ) 
						);
					}
				}
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
		$titles = apply_filters( 'woo_bg/admin/settings/speedy/groups_titles', array(
			'speedy' => array(
				'title' => __( 'General', 'woo-bg' ),
			),
		) );

		return $titles;
	}

	public function add_send_from_group_title( $titles ) {
		if ( $this->container[ Client::SPEEDY_PROFILE ]->is_valid_profile( true ) ) {
			$titles['speedy_send_from'] = array(
				'title' => __( 'Send From office', 'woo-bg' ),
			);
		}
		return $titles;
	}

	public function after_save_fields() {
		$is_valid_profile = $this->container[ Client::SPEEDY_PROFILE ]->check_credentials( true );

		woo_bg_set_option( 'speedy', 'is_valid_profile', $is_valid_profile );

		if ( $is_valid_profile ) {
			$this->update_profile_fields();
		}
	}

	public function update_profile_fields() {
		$profile = $this->container[ Client::SPEEDY_PROFILE ]->get_profile_data( 1 );
		$clients = $this->container[ Client::SPEEDY_PROFILE ]->get_clients();
		$client = $clients[ $profile['clientId'] ];

		$name = woo_bg_get_option( 'speedy', 'name' );
		$phone = woo_bg_get_option( 'speedy', 'phone' );

		if ( !$name ) {
			woo_bg_set_option( 'speedy', 'name', $client['contactName'] );
		}
		
		if ( !$phone ) {
			woo_bg_set_option( 'speedy', 'phone', $client['phones'][0]['number'] );
		}
	}

	public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::SPEEDY_PROFILE ]->is_valid_profile( true ) ) {
			$error = __( 'Username and password are incorrect.', 'woo-bg' ); 
		} else {
			$all_profiles = $this->container[ Client::SPEEDY_PROFILE ]->get_profiles_for_settings();

			if ( empty( $all_profiles ) ) {
				$error = __( 'No profiles was found. Please contact with Speedy.', 'woo-bg' );
			}
		}

		return wpautop( $error );
	}
}
