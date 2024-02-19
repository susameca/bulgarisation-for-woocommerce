<?php
namespace Woo_BG\Admin\Tabs;

defined( 'ABSPATH' ) || exit;

class Pro_Tab extends Base_Tab {
	public function __construct() {
		$this->set_name( __( 'Pro Addon', 'woo-bg' ) );
		$this->set_tab_slug( "pro" );
	}

	public function render_tab_html() {
		woo_bg_support_text();
		?>

		<div class="woo-bg-text-wrapper">
			<h3><?php esc_html_e( 'Pro Addon', 'woo-bg' ) ?></h3>

			<?php 

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
			?>
		</div>
		<?php
	}
}
