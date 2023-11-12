<?php
namespace Woo_BG\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

class Help_Tab extends Base_Tab {
	public function __construct() {
		$this->set_name( __( 'Help', 'woo-bg' ) );
		$this->set_description( __( 'If you need help', 'woo-bg' ) );
		$this->set_tab_slug( "help" );
	}

	public function render_tab_html() {
		$this->admin_localize();

		woo_bg_support_text();
		?>

		<h3><?php _e( 'Recommended settings', 'woo-bg' ) ?></h3>

		<?php 
		echo wpautop( __( 'If your shop is in group B or G, we suggest you to enable the default WooCommerce settings at "Settings >> General >> Enable taxes". After that go to "Settings >> Tax >> Yes, I will enter prices inclusive of tax". Also, go to "Standard rates" submenu and add your rate ( 20% or 9% ).', 'woo-bg' ) ) . wpautop( _e('You can find more help in the official Facebook Group for the plugin: <a target="_blank" href="https://www.facebook.com/groups/bulgarisationforwoocommerce/">Bulgarisation For WooCommerce</a>.', 'woo-bg' ) );
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_help', array(
			'i18n' => $this->get_i18n(),
			'nonce' => wp_create_nonce( 'woo_bg_contact' ),
		) );
	}

	public function get_i18n() {
		return array(
			'name' => __( 'Name:', 'woo-bg' ),
			'email' => __( 'E-mail:', 'woo-bg' ),
			'message' => __( 'Message:', 'woo-bg' ),
			'send' => __( 'Send Request', 'woo-bg' ),
		);
	}

	public static function woo_bg_send_request_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_contact' ) ) {
			wp_send_json_error();
			wp_die();
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$message = "Име : " . sanitize_text_field( $_REQUEST[ 'name' ] ) . "<br>";
		$message .= "E-mail : " . sanitize_text_field( $_REQUEST[ 'email' ] ) . "<br>";
		$message .= "Съобщение : " . sanitize_text_field( $_REQUEST[ 'message' ] );
		wp_mail( 'wordpress@autopolis.bg', 'Bulgarisation for WooCommerce - Help', $message, $headers );

		wp_send_json_success( array(
			"message" => __( "Thank you for your message! We'll contact you shortly.", 'woo-bg' ),
		) );

		wp_die();
	}
}
