<?php
namespace Woo_BG\Admin;
use Woo_BG\Admin\Fields;
use Woo_BG\Front_End\Checkout\EU_Vat as Front_End_EU_Vat;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class EU_Vat {
	protected $tax_classes, $eu_countries;

	public function __construct() {
		add_filter( 'woo_bg/admin/settings/fields', array( __CLASS__, 'add_fields_in_settings' ) );
		add_action( 'woocommerce_admin_billing_fields', array( __CLASS__, 'admin_billing_fields' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );


		if ( ! woo_bg_get_option( 'checkout', 'enable_company_billing' ) ) {
			woo_bg_set_option( 'checkout', 'enable_company_billing', 'no' );
		}

		if ( ! woo_bg_get_option( 'checkout', 'digital_tax_classes' ) ) {
			woo_bg_set_option( 'checkout', 'digital_tax_classes', 'standard' );
		}
	}

	public static function add_fields_in_settings( $fields ) {
		if ( extension_loaded( 'soap' ) ) {
			$fields[ 'checkout' ][] = new Fields\TrueFalse_Field( 'enable_vies', __( 'Enable VIES validation', 'woo-bg' ), null, null, __( 'Validate the VAT number with the European VIES system or only with regex.', 'woo-bg' ) );
		}

		$fields[ 'checkout' ][] = new Fields\Select_Field( woo_bg_get_tax_classes(), 'digital_tax_classes', __( 'Tax Classes for Digital Goods', 'woo-bg' ), null, null, __( 'This option tells the plugin which of your tax classes are for digital goods. This affects the taxable location of the user as of 1st Jan 2015.', 'woo-bg' ) );

		return $fields;
	}

	public static function admin_billing_fields( $fields ) {
		global $theorder;

		$vat_number = is_object( $theorder ) ? woo_bg_get_vat_from_order( $theorder ) : '';

		$fields[ 'vat_number' ] = array(
			'label' => __( 'VAT number', 'woo-bg' ),
			'show'  => false,
			'id'    => '_billing_vat_number',
			'value' => $vat_number,
		);

		return $fields;
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		add_meta_box( 'woo_bg_eu_vat', __( 'EU VAT', 'woo-bg' ), array( __CLASS__, 'output_meta_box' ), array( 'shop_order', 'shop_subscription' ), 'side' );
	}

	protected static function is_eu_order( $order ) {
		return in_array( $order->get_billing_country(), Front_End_EU_Vat::get_eu_countries() );
	}

	public static function output_meta_box() {
		global $post, $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		// We only need this box for EU orders.
		if ( ! self::is_eu_order( $theorder ) ) {
			echo wpautop( __( 'This order is out of scope for EU VAT.', 'woo-bg' ) );
			return;
		}

		$data      = self::get_order_vat_data( $theorder );
		$countries = WC()->countries->get_countries();
		?>
		<table class="wc-eu-vat-table" cellspacing="0">
			<tbody>
				<?php if ( $data->vat_number ) : ?>
					<tr>
						<th><?php echo __('VAT number', 'woo-bg') ?></th>
						<td><?php echo esc_html( $data->vat_number ); ?></td>
						<td><?php
							if ( ! $data->validated ) {
								echo '<span class="tips" data-tip="' . wc_sanitize_tooltip( __( 'Validation was not possible', 'woo-bg' ) ) . '">?<span>';
							} else {
								echo $data->valid ? '&#10004;' : '&#10008;';
							}
						?></td>
					</tr>
				<?php else : ?>
					<tr>
						<th><?php _e( 'IP Address', 'woo-bg' ); ?></th>
						<td><?php echo $data->ip_address ? esc_html( $data->ip_address ) : __( 'Unknown', 'woo-bg' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php _e( 'IP Country', 'woo-bg' ); ?></th>
						<td><?php
							if ( $data->ip_country ) {
								echo esc_html__( $countries[ $data->billing_country ] ) . ' ';

								if ( $data->billing_country === $data->ip_country ) {
									echo '<span style="color:green">&#10004;</span>';
								} elseif ( $data->self_declared ) {
									esc_html_e( '(self-declared)', 'woo-bg' );
								} else {
									echo '<span style="color:red">&#10008;</span>';
								}
							} else {
								esc_html_e( 'Unknown', 'woo-bg' );
							}
						?><td></td>
					</tr>
					<tr>
						<th><?php _e( 'Billing Country', 'woo-bg' ); ?></th>
						<td><?php echo $data->billing_country ? esc_html( $countries[ $data->billing_country ] ) : __( 'Unknown', 'woo-bg' ); ?></td>
						<td></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	protected static function get_order_vat_data( $order ) {
		return (object) array(
			'vat_number'      => woo_bg_get_vat_from_order( $order ),
			'valid'           => wc_string_to_bool( $order->get_meta( '_vat_number_is_valid', true ) ),
			'validated'       => wc_string_to_bool( $order->get_meta( '_vat_number_is_validated', true ) ),
			'billing_country' => $order->get_billing_country(),
			'ip_address'      => $order->get_customer_ip_address(),
			'ip_country'      => $order->get_meta( '_customer_ip_country', true ),
			'self_declared'   => wc_string_to_bool( $order->get_meta( '_customer_self_declared_country', true ) ),
		);
	}
}
