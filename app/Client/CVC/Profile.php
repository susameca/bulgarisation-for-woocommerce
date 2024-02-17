<?php
namespace Woo_BG\Client\CVC;
use Woo_BG\Container\Client;
use Woo_BG\Cache;

defined( 'ABSPATH' ) || exit;

class Profile {
    const PROFILE_ENDPOINT = 'get_custom_locations';

    private $is_valid_profile, $profile_data;

	public function __construct( $container ) {
		$this->container = $container;
		$this->set_is_valid_profile();
		$this->get_profile_data( true );
	}

	public function get_profile_data( $forced = false ) {
		if ( $forced ) {
			$this->set_profile_data();
		}

		return $this->profile_data;
	}

	public function fetch_profile_data() {
		$this->container[ Client::CVC ]->load_token();
		$profile_data = $this->container[ Client::CVC ]->api_call( self::PROFILE_ENDPOINT, [ 'profile' => true ], 'GET' );

		if ( $this->container[ Client::CVC ]->validate_access( $profile_data ) ) {
			woo_bg_set_option( 'cvc', 'profile_data', $profile_data );
		}

		return $profile_data;
	}

	public function check_credentials() {
		$profile_data = $this->fetch_profile_data();

		return $this->container[ Client::CVC ]::validate_access( $profile_data );
	}

	public function is_valid_profile( $forced = false ) {
		if ( $forced ) {
			$this->set_is_valid_profile();
		}

		return $this->is_valid_profile;
	}

	private function set_is_valid_profile() {
		$this->is_valid_profile = filter_var( woo_bg_get_option( 'cvc', 'is_valid_profile' ), FILTER_VALIDATE_BOOLEAN );
	}

	private function set_profile_data() {
		$this->profile_data = woo_bg_get_option( 'cvc', 'profile_data' );

		if ( !$this->profile_data ) {
			$this->profile_data = $this->fetch_profile_data();
		}
	}

	public function get_formatted_addresses() {
		$formatted = [];
		$profile_data = $this->get_profile_data();

		if ( $profile_data['success'] ) {
			foreach ( $this->get_profile_data()['locations'] as $key => $address ) {
				$id = $address['id'];
				$formatted[ $id ] = array(
					'id' => $id,
					'label' => $address[ 'name' ],
				);
			}
		}

		return $formatted;
	}

	public static function get_sender_payment_method() {
		$method = 'cash';
		$cd_pay_option = woo_bg_get_option( 'cvc', 'pay_options' );

		if ( $cd_pay_option && $cd_pay_option !== 'no' ) {
			$method = 'credit';
		}

		return $method;
	}
}
