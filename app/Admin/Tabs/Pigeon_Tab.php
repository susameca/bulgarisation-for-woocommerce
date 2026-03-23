<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Pigeon_Tab extends Base_Tab {
	protected $fields, $localized_fields, $container, $tab_name;
	
	public function __construct() {
		$this->container = woo_bg()->container();
		$this->tab_name = get_called_class();
		$this->set_name( __( 'PigeonExpress Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'Pigeon Express API Settings', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "pigeon" );

		add_action( 'woo_bg/admin/settings/afte_save_fields/'. $this->tab_name, array( $this, 'after_save_fields' ) );
		add_filter( 'woo_bg/admin/settings/pigeon/fields', array( $this, 'add_send_from_fields' ), 10 );
		add_filter( 'woo_bg/admin/settings/pigeon/fields', array( $this, 'add_additional_fields' ), 20 );
		add_filter( 'woo_bg/admin/settings/pigeon/groups_titles', array( $this, 'add_send_from_group_title' ) );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->maybe_clear_cache();
			$this->load_fields();
		}
	}

	private function maybe_clear_cache() {
		if ( !isset( $_GET[ 'clear-cache' ] ) ) {
			return;
		}

		$this->container[ Client::PIGEON ]::clear_cache_folder();
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<a href="<?php echo esc_html( add_query_arg( 'clear-cache', true ) ) ?>" class="button-secondary"><?php esc_html_e( 'Clear cache', 'bulgarisation-for-woocommerce' ) ?></a>

		<div class="notice"><p><?php esc_html_e( 'Please use "Classic Checkout" block or [woocommerce_checkout] shortcode.', 'bulgarisation-for-woocommerce' ) ?></p></div>
		
		<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'groups_titles' => $this->get_groups_titles(),
			'auth_errors' => '',//$this->auth_test(),
			'tab' => $this->tab_name,
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = array(
			'pigeon' => array(
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
				new Fields\Text_Field( 'api_key', __( 'API Key', 'bulgarisation-for-woocommerce' ), __( 'Enter your API key.', 'bulgarisation-for-woocommerce' ), 'required' ),
				new Fields\Text_Field( 'api_secret', __( 'API Secret', 'bulgarisation-for-woocommerce' ), __( 'Enter your API secret.', 'bulgarisation-for-woocommerce' ), 'required', null, 'password' ),
			)
		);

		$fields = apply_filters( 'woo_bg/admin/settings/pigeon/fields', $fields );

		$this->set_fields( $fields );
	}

	public function add_additional_fields( $fields ) {
		if ( $this->container[ Client::PIGEON ]->is_valid_access( true ) ) {
			//$fields[ 'pigeon' ][] = new Fields\TrueFalse_Field( 'label_after_checkout', __( 'Generate label after checkout', 'bulgarisation-for-woocommerce' ), null, null, __( 'This option will try to generate your label immediately after user checkout. Also, will add the tracking number in the order email.', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon' ][] = new Fields\TrueFalse_Field( 'auto_size', __( 'Automatically calculate box size', 'bulgarisation-for-woocommerce' ), null, null, __( 'Automatically calculate LxWxH of the box for the parcel.', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon' ][] = new Fields\Select_Field( 
				array(
					'office' => array(
						'id' => 'office',
						'label' => __( 'Office', 'bulgarisation-for-woocommerce' ),
					),
					'address' => array(
						'id' => 'address',
						'label' => __( 'Address', 'bulgarisation-for-woocommerce' ),
					),
				), 'send_from', __( 'Send From', 'bulgarisation-for-woocommerce' ), null, null, __( 'Select from where you will send the packages and save to show more options.', 'bulgarisation-for-woocommerce' ) 
			);
			
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'declared_value', __( 'Declared value', 'bulgarisation-for-woocommerce' ), null, null, __( 'Adds declared value to the label if payment is COD.', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'paper_return_receipt', __( 'Paper Return Receipt', 'bulgarisation-for-woocommerce' ), null, null, __( 'Request for a paper receipt', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'return_receipt', __( 'Return Receipt', 'bulgarisation-for-woocommerce' ), null, null, __( 'Request for a return receipt in electronic format', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'service_return_documents', __( 'Service Return Documents', 'bulgarisation-for-woocommerce' ), null, null, __( 'Return of original documents from the recipient to the sender after signing.', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'id_verification_and_document_signature', __( 'ID Verification and Document Signature', 'bulgarisation-for-woocommerce' ), null, null, __( 'Verify ID and sign documents electronically.', 'bulgarisation-for-woocommerce' ) );
			$fields[ 'pigeon_services' ][] = new Fields\TrueFalse_Field( 'cod_card_fee', __( 'COD Card Fee', 'bulgarisation-for-woocommerce' ), null, null, __( 'Charge a fee for COD card payments.', 'bulgarisation-for-woocommerce' ) );
			
			
		}

		return $fields;
	}

	public function add_send_from_fields( $fields ) {
		if ( $this->container[ Client::PIGEON ]->is_valid_access( true ) ) {
			$cities = $this->container[ Client::PIGEON_CITIES ]->get_formatted_cities();
			$offices = $this->container[ Client::PIGEON_OFFICES ]->get_formatted_offices( woo_bg_get_option( 'pigeon_send_from', 'city' ) );
			$fields[ 'pigeon_send_from' ] = [];

			if ( !empty( $cities ) ) {
				$fields[ 'pigeon_send_from' ][] = new Fields\Select_Field( $cities, 'city', __( 'City', 'bulgarisation-for-woocommerce' ) );
				$fields[ 'pigeon_send_from' ][] = new Fields\Text_Field( 'address', __( 'Address', 'bulgarisation-for-woocommerce' ), __( 'Enter your address.', 'bulgarisation-for-woocommerce' ) );
			}

			if ( !empty( $offices ) ) {
				$fields[ 'pigeon_send_from' ][] = new Fields\Select_Field( 
					$offices, 
					'office', 
					__( 'Office', 'bulgarisation-for-woocommerce' ), 
					null, 
					null, 
					__('Choose a city and save in order to show offices.', 'bulgarisation-for-woocommerce' ) 
				);
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
		$titles = apply_filters( 'woo_bg/admin/settings/pigeon/groups_titles', array(
			'pigeon' => array(
				'title' => __( 'General', 'bulgarisation-for-woocommerce' ),
			),
		) );

		return $titles;
	}

	public function add_send_from_group_title( $titles ) {
		if ( $this->container[ Client::PIGEON ]->is_valid_access( true ) ) {
			$titles['pigeon_send_from'] = array(
				'title' => __( 'Send From', 'bulgarisation-for-woocommerce' ),
			);

			$titles['pigeon_services'] = array(
				'title' => __( 'Additional Services', 'bulgarisation-for-woocommerce' ),
				'description' => sprintf( __( 'You can see the current prices on the <a href="%s" target="_blank">Pigeon Express website</a> or according to your agreed upon terms.', 'bulgarisation-for-woocommerce' ), 'https://pigeonexpress.com/prices-and-taxes/' ),	
			);
		}

		return $titles;
	}

	public function after_save_fields() {
		$is_valid_access = $this->container[ Client::PIGEON ]->check_credentials();

		woo_bg_set_option( 'pigeon', 'is_valid_access', $is_valid_access );
	}

	public function auth_test() {
		$error = '';

		if ( !$this->container[ Client::PIGEON ]->is_valid_access( true ) ) {
			$error = __( 'API Key and API Secret are incorrect.', 'bulgarisation-for-woocommerce' ); 
		}

		return wpautop( $error );
	}
}
