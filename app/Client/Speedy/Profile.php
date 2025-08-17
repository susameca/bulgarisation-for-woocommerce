<?php
namespace Woo_BG\Client\Speedy;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Profile {
    const PROFILE_ENDPOINT = 'client/contract';
    const CLIENT_ENDPOINT = 'client/';

    private $is_valid_profile, $profile_data, $container, $clients;

	public function __construct( $container ) {
		$this->container = $container;
		$this->set_is_valid_profile();
		$this->get_profile_data( true );
	}

	public function get_profile_data( $forced = false ) {
		if ( $forced ) {
			$this->set_profile_data();
			$this->load_clients();
		}

		return $this->return_single_profile();
	}

	public function get_client( $id ) {
		return $this->container[ Client::SPEEDY ]->api_call( self::CLIENT_ENDPOINT . $id, array() );
	}

	public function get_clients() {
		return $this->clients;
	}

	public function fetch_profile_data() {
		$this->container[ Client::SPEEDY ]->load_user();
		$this->container[ Client::SPEEDY ]->load_password();

		$profile_data = $this->container[ Client::SPEEDY ]->api_call( self::PROFILE_ENDPOINT, array() );

		if ( $this->container[ Client::SPEEDY ]->validate_access( $profile_data ) ) {
			$clients = [];

			if ( !empty( $profile_data['clients'] ) ) {
				foreach ( $profile_data['clients'] as $profile ) {
					$client = $this->get_client( $profile['clientId'] );

					if ( isset( $client[ 'client' ] ) ) {
						$clients[ $profile['clientId'] ] = $client[ 'client' ];
					}
				}
			} else {
				$profile_client = $this->container[ Client::SPEEDY ]->api_call( self::CLIENT_ENDPOINT, array() );

				if ( !empty( $profile_client ) ) {
					$client = $this->get_client( $profile_client['clientId'] );

					$clients[ $profile_client['clientId'] ] = $client[ 'client' ];

					$profile_data = [
						'clients' => [ $client[ 'client' ] ],
					];
				}
			} 

			woo_bg_set_option( 'speedy', 'profile_data', $profile_data );
				
			if ( !empty( $clients ) ) {
				woo_bg_set_option( 'speedy', 'clients', $clients );
			}
		}

		return $profile_data;
	}

	public function check_credentials() {
		$profile_data = $this->fetch_profile_data();

		return $this->container[ Client::SPEEDY ]::validate_access( $profile_data );
	}

	public function is_valid_profile( $forced = false ) {
		if ( $forced ) {
			$this->set_is_valid_profile();
		}

		return $this->is_valid_profile;
	}

	protected function load_clients() {
		$this->clients = woo_bg_get_option( 'speedy', 'clients' );
	}

	private function set_is_valid_profile() {
		$this->is_valid_profile = filter_var( woo_bg_get_option( 'speedy', 'is_valid_profile' ), FILTER_VALIDATE_BOOLEAN );
	}

	private function set_profile_data() {
		$this->profile_data = woo_bg_get_option( 'speedy', 'profile_data' );

		if ( ! $this->profile_data ) {
			$this->profile_data = $this->fetch_profile_data();
		}
	}

	protected function return_single_profile() {
		$profile_id = 0;

		if ( !empty( $this->profile_data[ 'clients' ] ) ) {
			if ( $selected_id = woo_bg_get_option( 'speedy', 'profile_key' ) ) {
				$profile_id = $selected_id;
			}

			return $this->profile_data[ 'clients' ][ $profile_id ];
		}

		return;
	}

	public function get_profiles_for_settings() {
		$all_profiles = woo_bg_get_option( 'speedy', 'profile_data' );
		$options = array();

		if ( !empty( $all_profiles['clients'] ) ) {
			foreach ( $all_profiles['clients'] as $key => $profile ) {
				$client = $this->clients[ $profile['clientId'] ];

				$options[ $key ] = array(
					'id' => $key,
					'label' => $client['clientName'] . " ( ID:" . $profile['clientId'] . " )",
				);

			}
		}

		return $options;
	}
}
