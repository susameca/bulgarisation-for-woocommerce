<?php
namespace Woo_BG\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

class Help_Tab extends Base_Tab {
	public function __construct() {
		$this->set_name( __( 'Help', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'If you need help', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "help" );
	}

	public function render_tab_html() {
		$this->admin_localize();

		woo_bg_support_text();
		?>

		<h3><?php esc_html_e( 'Recommended settings', 'bulgarisation-for-woocommerce' ) ?></h3>

		<?php 
		echo wp_kses_post( wpautop( __( 'If your shop is in group B or G, we suggest you to enable the default WooCommerce settings at "Settings >> General >> Enable taxes". After that go to "Settings >> Tax >> Yes, I will enter prices inclusive of tax". Also, go to "Standard rates" submenu and add your rate ( 20% or 9% ).', 'bulgarisation-for-woocommerce' ) ) . wpautop( __('You can find more help in the official Facebook Group for the plugin: <a target="_blank" href="https://www.facebook.com/groups/bulgarisationforwoocommerce/">Bulgarisation For WooCommerce</a>.', 'bulgarisation-for-woocommerce' ) ) );
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_help', array(
			'i18n' => $this->get_i18n(),
			'nonce' => wp_create_nonce( 'woo_bg_contact' ),
		) );
	}

	public function get_i18n() {
		return array(
			'name' => __( 'Name:', 'bulgarisation-for-woocommerce' ),
			'email' => __( 'E-mail:', 'bulgarisation-for-woocommerce' ),
			'message' => __( 'Message:', 'bulgarisation-for-woocommerce' ),
			'send' => __( 'Send Request', 'bulgarisation-for-woocommerce' ),
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
			"message" => __( "Thank you for your message! We'll contact you shortly.", 'bulgarisation-for-woocommerce' ),
		) );

		wp_die();
	}
}
