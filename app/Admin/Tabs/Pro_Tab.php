<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;

class Pro_Tab extends Base_Tab {
	protected $fields, $localized_fields;

	public function __construct() {
		$this->set_name( __( 'Pro Addon', 'woo-bg' ) );
		$this->set_tab_slug( "pro" );

		if ( 
			empty( $_GET[ 'tab' ] ) ||
			( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() )
		) {
			$this->load_fields();
		}
	}

	public function render_tab_html() {
		$this->admin_localize();

		woo_bg_support_text();
		?>

		<div class="woo-bg-text-wrapper">
			<?php 
			if ( ! class_exists( '\Woo_BG_Pro\Checkout' ) ) {
				echo "<h3>" . esc_html__( 'Pro Addon', 'woo-bg' ) . "</h3>";
				
				echo wp_kses_post( wpautop( __( 'There is a Pro version of the plugin as an addon ( additional plugin to the free one ). This version of the plugin has 2 major checkout functionalities.', 'woo-bg' ) ) );
				echo wp_kses_post( wpautop( __( 'First one hides most of the unwanted fields as Country, City, State, Postcode, Address 1/2 and in their replacement add one field for city that is a dropdown field with all cities from the courier API.', 'woo-bg' ) ) );
				echo wp_kses_post( wpautop( __( 'Second addtion is changing the default radio buttons for shipping methods with more stylish buttons.', 'woo-bg' ) ) );
				echo wp_kses_post( wpautop( __( 'Screenshot of the options:', 'woo-bg' ) ) );
				?>

				<img src="<?php echo esc_url( woo_bg()->plugin_dir_url() ) ?>/app/Admin/Tabs/Pro_Tab/images/pro-settings.png" width="1000px">

				<?php echo wp_kses_post( wpautop( __( 'Screenshots of the checkout:', 'woo-bg' ) ) ); ?>

				<img src="<?php echo esc_url( woo_bg()->plugin_dir_url() ) ?>/app/Admin/Tabs/Pro_Tab/images/pro-demo1.png" width="600px">
				<img src="<?php echo esc_url( woo_bg()->plugin_dir_url() ) ?>/app/Admin/Tabs/Pro_Tab/images/pro-demo2.png" width="600px">

				<?php 
				echo wp_kses_post( wpautop( sprintf( __( 'You can review a demo at: %s', 'woo-bg' ), '<a target="_blank" href="http://autopolis.bg/woo/porachka-2/">http://autopolis.bg/woo/porachka-2/</a>' ) ) ); 
				echo wp_kses_post( wpautop( sprintf( __( 'For more information on how to get it, you can find at the plugin %s page.', 'woo-bg' ), '<a target="_blank" href="https://www.facebook.com/groups/bulgarisationforwoocommerce/permalink/1640276226421072">Facebook</a>' ) ) ); 
			} else {
				?>
				<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->
				<?php
			}
			?>
		</div>
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'payment_types' => woo_bg_get_payment_types(),
			'groups_titles' => $this->get_groups_titles(),
			'auth_errors' => $this->auth_test(),
			'tab' => get_called_class(),
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = apply_filters( 'woo_bg/admin/pro/fields', array(
			'pro' => array(
				new Fields\Text_Field( 'license_key', __( 'License Key', 'woo-bg' ), '', 'required' ),
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
		$titles = apply_filters( 'woo_bg/admin/settings/groups_titles', array(
			'pro' => array(
				'title' => __( 'License', 'woo-bg' ),
			)
		) );

		return $titles;
	}

	public static function woo_bg_save_settings_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_settings' ) ) {
			wp_send_json_error();
			wp_die();
		}

		$tab_class_name = stripslashes( $_REQUEST['tab'] );
		$tab = new $tab_class_name();
		$tab->load_fields();
		$fields = $tab->get_fields();

		foreach ( $fields as $group => $group_fields ) {
			foreach ( $group_fields as $field ) {
				$field->save_value( $group );
			}
		}

		do_action( 'woo_bg/admin/settings/afte_save_fields/' . $tab_class_name );

		$tab->load_fields();
		
		wp_send_json_success( array(
			'fields' => $tab->get_localized_fields(),
			'groups_titles' => $tab->get_groups_titles(),
			'message' => __( 'Settings saved successfully!', 'woo-bg' ),
			'auth_errors' => $tab->auth_test(),
		) );

		wp_die();
	}

	public function auth_test() {
		\Woo_BG\Cron\Stats::submit_stats();

		if ( class_exists( '\Woo_BG_Pro\Checkout' ) && !\Woo_BG_Pro\License::is_valid() ) {
			return wpautop( __( 'License is invalid!', 'woo-bg' ) );
		}
	}
}
