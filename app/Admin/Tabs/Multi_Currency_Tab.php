<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;
class Multi_Currency_Tab extends Base_Tab {
	protected $fields, $localized_fields;
	
	public function __construct() {
		$this->set_name( __( 'BGN/EUR dual price', 'woo-bg' ) );
		$this->set_description( __( 'nekorekten.com API Settings', 'woo-bg' ) );
		$this->set_tab_slug( "bgn-eur" );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->load_fields();
		}
	}

	public function render_tab_html() {
		$this->admin_localize();

		woo_bg_support_text();
		?>

		<div class="notice"><p><?php _e( 'Please use "Classic Checkout" and "Classic Cart" blocks or [woocommerce_checkout] and [woocommerce_cart] shortcodes.', 'woo-bg' ) ?></p></div>

		<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->

		<div id="woo-bg--change-bgn-to-eur" class="wrap woo-bg-bgntoeur-wrapper">
			<form 
				action="" 
				method="post" 
				enctype="multipart/form-data"
				data-loading-text="0%"
			>
				<fieldset>
					<input type="hidden" name="action" value="woo_bg_change_bgn_to_eur">

					<?php submit_button( __( 'Change product prices from BGN to EUR', 'woo-bg' ) ); ?>
				</fieldset>

				<?php wp_nonce_field( 'woo_bg_change_bgn_to_eur' ); ?>
			</form>

			<div id="woo-bg-bgntoeur-status" class="status" style="display: none;">
				<div class="progress">
					<div class="progress-wrapper">
						<div class="progress-bar" style="width:0%">
							<p class="progress-value progress-value-2">
								<span class="value"></span>
							</p>
						</div>

						<p class="progress-value progress-value-1">
							<span class="value"></span>
						</p>
					</div>
				</div><!-- /.progress -->

				<div class="messages">
					<p><?php _e( 'Loading products...', 'woo-bg' ) ?></p>
				</div>
			</div>
		</div><!-- /.wrap -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'groups_titles' => $this->get_groups_titles(),
			'tab' => get_called_class(),
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = apply_filters( 'woo_bg/admin/settings/nekorektencom/fields', array(
			'multi_currency' => array(
				new Fields\TrueFalse_Field( 'product_rate_message', __( 'Rate message on Product page', 'woo-bg' ), null, null, __( 'Show message about conversion rate on the product page.', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'cart_rate_message', __( 'Rate message on Cart page', 'woo-bg' ), null, null, __( 'Show message about conversion rate on the cart page.', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'shop_rate_message', __( 'Rate message on Shop page', 'woo-bg' ), null, null, __( 'Show message about conversion rate on the shop page in each product.', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'email_rate_message', __( 'Rate message in Emails', 'woo-bg' ), null, null, __( 'Show message about conversion rate in emails.', 'woo-bg' ) ),
				new Fields\Textarea_Field( 'checkout_message', __( 'Checkout Message', 'woo-bg' ), null, null, 'Примерен текст: <br><code>&lt;p&gt;&lt;strong&gt;Всички плащания ще се извършват в лева&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&lt;small&gt;Сумата в евро се получава чрез конвертиране на цената по фиксирания обменен курс на БНБ &lt;br&gt; 1 EUR = 1.95583 BGN &lt;/small&gt;&lt;/p&gt;</code>' ),
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
		$titles = apply_filters( 'woo_bg/admin/settings/nekorektencom/groups_titles', array(
			'multi_currency' => array(
				'title' => __( 'Conversion rate message', 'woo-bg' ),
			),
		) );

		return $titles;
	}
}
