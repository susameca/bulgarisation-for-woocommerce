<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Fields;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class BoxNow_Tab extends Base_Tab {
	protected $fields, $localized_fields, $container, $tab_name;
	
	public function __construct() {
		$this->container = woo_bg()->container();
		$this->tab_name = get_called_class();
		$this->set_name( __( 'BOX NOW Settings', 'woo-bg' ) );
		$this->set_description( __( 'boxnow.bg API Settings', 'woo-bg' ) );
		$this->set_tab_slug( "boxnow" );

		add_action( 'woo_bg/admin/settings/afte_save_fields/'. $this->tab_name, array( $this, 'after_save_fields' ) );
		add_filter( 'woo_bg/admin/settings/boxnow/fields', array( $this, 'add_send_from_fields' ), 20 );
		add_filter( 'woo_bg/admin/settings/boxnow/groups_titles', array( $this, 'add_send_from_group_title' ) );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->maybe_clear_cache();
			$this->load_fields();
		}
	}

	private function maybe_clear_cache() {
		if ( !isset( $_GET[ 'clear-cache' ] ) ) {
			return;
		}

		$this->container[ Client::BOXNOW ]::clear_cache_folder();
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<a href="<?php echo esc_html( add_query_arg( 'clear-cache', true ) ) ?>" class="button-secondary"><?php esc_html_e( 'Clear cache', 'woo-bg' ) ?></a>

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
			'boxnow' => array(
				new Fields\Select_Field( array(
						'demo' => array(
							'id' => 'demo',
							'label' => __( 'Demo', 'woo-bg' ),
						),
						'live' => array(
							'id' => 'live',
							'label' => __( 'Live', 'woo-bg' ),
						),
					), 'env', __( 'Environment', 'woo-bg' ), null, null, __( 'Select used environment.', 'woo-bg' ) ),
				new Fields\Text_Field( 'client_id', __( 'Client ID', 'woo-bg' ), __( 'Enter your Client ID.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'client_secret', __( 'Client Secret', 'woo-bg' ), __( 'Enter your Client Secret.', 'woo-bg' ), 'required', null, 'password' ),
			)
		);

		$fields = apply_filters( 'woo_bg/admin/settings/boxnow/fields', $fields );

		$this->set_fields( $fields );
	}

	public function add_send_from_fields( $fields ) {
		if ( $this->container[ Client::BOXNOW ]->get_access_token() ) {
			$fields[ 'boxnow_send_from' ][] = new Fields\Text_Field( 'name', __( 'Name', 'woo-bg' ) );
			$fields[ 'boxnow_send_from' ][] = new Fields\Text_Field( 'phone', __( 'Phone', 'woo-bg' ) );
			$fields[ 'boxnow_send_from' ][] = new Fields\Text_Field( 'email', __( 'E-mail', 'woo-bg' ) );
			$fields[ 'boxnow_send_from' ][] = new Fields\Select_Field( 
				self::get_from_locations(), 
				'location', 
				__( 'Location', 'woo-bg' ), 
			);
			$fields[ 'boxnow_send_from' ][] = new Fields\TrueFalse_Field( 'allow_return', __( 'Allow returns', 'woo-bg' ) );
			$fields[ 'boxnow_send_from' ][] = new Fields\TrueFalse_Field( 'label_after_checkout', __( 'Generate label after checkout', 'woo-bg' ), null, null, __( 'This option will try to generate your label immediately after user checkout. Also, will add the tracking number in the order email.', 'woo-bg' ) );

			$fields[ 'boxnow_price' ][] = new Fields\Select_Field( 
				array(
					'from_to_kg' => array(
						'id' => 'from_to_kg',
						'label' => __( 'From/To in kg', 'woo-bg' ),
					),
					'from_to_order_total' => array(
						'id' => 'from_to_order_total',
						'label' => __( 'From/To Order Total', 'woo-bg' ),
					),
					'from_to_kg_and_order_total' => array(
						'id' => 'from_to_kg_and_order_total',
						'label' => __( 'From/To kg and Order Total', 'woo-bg' ),
					),
				), 
				'price_type', 
				__( 'Price type', 'woo-bg' ), 
				null,
				null,
				__( 'After you select the option, you must save the settings in order the correct price settings to be shown.', 'woo-bg' )
			);

			if ( $price_type = woo_bg_get_option( 'boxnow_price', 'price_type' ) ) {
				switch ( $price_type ) {
					case 'from_to_kg':
						$fields[ 'boxnow_price' ][] = new Fields\Multi_Field(
							[
								'type' => __('kg', 'woo-bg'),
								'separator_text' => __(' to ', 'woo-bg'),
								'price_text' => __('Price', 'woo-bg'),
								'add_row_text' => __('+ Add new row', 'woo-bg'),
								'currency' => get_woocommerce_currency_symbol(),
								'max' => 20,
								'step' => 0.001,
							],
							'from_to_kg',
							__( 'From/To in kg', 'woo-bg' ),
							null,
							'required'
						); 
						break;
					case 'from_to_order_total':
						$fields[ 'boxnow_price' ][] = new Fields\Multi_Field(
							[
								'type' => get_woocommerce_currency_symbol(),
								'separator_text' => __(' to ', 'woo-bg'),
								'add_row_text' => __('+ Add new row', 'woo-bg'),
								'price_text' => __('Price', 'woo-bg'),
								'currency' => get_woocommerce_currency_symbol(),
							],
							'from_to_order_total',
							__( 'From/To Order Total', 'woo-bg' ),
							null,
							'required'
						);
						break;
					case 'from_to_kg_and_order_total':
						$fields[ 'boxnow_price' ][] = new Fields\Multi_Two_Field(
							[
								'type' => __('kg', 'woo-bg'),
								'type2' => get_woocommerce_currency_symbol(),
								'and_text' => __('and', 'woo-bg'),
								'separator_text' => __(' to ', 'woo-bg'),
								'price_text' => __('Price', 'woo-bg'),
								'add_row_text' => __('+ Add new row', 'woo-bg'),
								'currency' => get_woocommerce_currency_symbol(),
								'max_type1' => 20,
								'step_type1' => 0.001,
								'step_type2' => 0.001,
							],
							'from_to_kg_and_order_total',
							__( 'From/To kg and Order Total', 'woo-bg' ),
							null,
							'required'
						); 
						break;
				}
			}

			$fields[ 'boxnow_price' ][] = new Fields\Text_Field( 'free_shipping_over', __( 'Free shipping over', 'woo-bg' ), __( 'Free shipping over total cart price.', 'woo-bg' ), null, null, 'number' );
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
		$titles = apply_filters( 'woo_bg/admin/settings/boxnow/groups_titles', array(
			'boxnow' => array(
				'title' => __( 'General', 'woo-bg' ),
			),
		) );

		return $titles;
	}

	public function add_send_from_group_title( $titles ) {
		if ( $this->container[ Client::BOXNOW ]->get_access_token() ) {
			$titles['boxnow_send_from'] = array(
				'title' => __( 'Send From', 'woo-bg' ),
			);
			$titles['boxnow_price'] = array(
				'title' => __( 'Price options', 'woo-bg' ),
			);
		}
		return $titles;
	}

	public function after_save_fields() {
		$this->container[ Client::BOXNOW ]->load_access_token( true );
	}

	public function auth_test() {
		$error = '';

		$this->container[ Client::BOXNOW ]->load_access_token( true );

		if ( !$this->container[ Client::BOXNOW ]->get_access_token() ) {
			$error = __( 'Incorrect Client ID or Client Secret.', 'woo-bg' ); 
		}

		$this->container[ Client::BOXNOW ]::clear_cache_folder();

		return wpautop( $error );
	}

	public static function get_from_locations() {
		$container = woo_bg()->container();

		return array_filter( $container[ Client::BOXNOW_ORIGINS ]->get_formatted_origins() ); 
	}
}
