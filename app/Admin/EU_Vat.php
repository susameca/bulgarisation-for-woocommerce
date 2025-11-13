<?php
namespace Woo_BG\Admin;
use Woo_BG\Admin\Fields;
use Woo_BG\Front_End\Checkout\EU_Vat as Front_End_EU_Vat;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class EU_Vat {
	protected $tax_classes, $eu_countries;

	public function __construct() {
		add_filter( 'woo_bg/admin/nra/fields', array( __CLASS__, 'add_fields_in_settings' ) );
		add_action( 'woocommerce_admin_billing_fields', array( __CLASS__, 'admin_billing_fields' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );

		if ( ! woo_bg_get_option( 'invoice', 'digital_tax_classes' ) ) {
			woo_bg_set_option( 'invoice', 'digital_tax_classes', 'standard' );
		}
	}

	public static function add_fields_in_settings( $fields ) {
		if ( extension_loaded( 'soap' ) ) {
			$fields[ 'invoice' ][] = new Fields\TrueFalse_Field( 'enable_vies', __( 'Enable VIES validation', 'bulgarisation-for-woocommerce' ), null, null, __( 'Validate the VAT number with the European VIES system or only with regex.', 'bulgarisation-for-woocommerce' ) );
		}

		$fields[ 'invoice' ][] = new Fields\Select_Field( woo_bg_get_tax_classes(), 'digital_tax_classes', __( 'Tax Classes for Digital Goods', 'bulgarisation-for-woocommerce' ), null, null, __( 'This option tells the plugin which of your tax classes are for digital goods. This affects the taxable location of the user as of 1st Jan 2015.', 'bulgarisation-for-woocommerce' ) );

		return $fields;
	}

	public static function admin_billing_fields( $fields ) {
		global $theorder;

		$vat_number = is_object( $theorder ) ? woo_bg_get_vat_from_order( $theorder ) : '';

		$fields[ 'vat_number' ] = array(
			'label' => __( 'VAT number', 'bulgarisation-for-woocommerce' ),
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

		add_meta_box( 'woo_bg_eu_vat', __( 'EU VAT', 'bulgarisation-for-woocommerce' ), array( __CLASS__, 'output_meta_box' ), array( 'shop_order', 'shop_subscription' ), 'side' );
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
			echo wp_kses_post( wpautop( __( 'This order is out of scope for EU VAT.', 'bulgarisation-for-woocommerce' ) ) );
			return;
		}

		$data      = self::get_order_vat_data( $theorder );
		$countries = WC()->countries->get_countries();
		?>
		<table class="wc-eu-vat-table" cellspacing="0">
			<tbody>
				<?php if ( $data->vat_number ) : ?>
					<tr>
						<th><?php echo wp_kses_post( __('VAT number', 'bulgarisation-for-woocommerce') ) ?></th>
						<td><?php echo esc_html( $data->vat_number ); ?></td>
						<td><?php
							if ( ! $data->validated ) {
								echo wp_kses_post( '<span class="tips" data-tip="' . wc_sanitize_tooltip( __( 'Validation was not possible', 'bulgarisation-for-woocommerce' ) ) . '">?<span>' );
							} else {
								echo $data->valid ? '&#10004;' : '&#10008;';
							}
						?></td>
					</tr>
				<?php else : ?>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'bulgarisation-for-woocommerce' ); ?></th>
						<td><?php echo $data->ip_address ? esc_html( $data->ip_address ) : esc_html__( 'Unknown', 'bulgarisation-for-woocommerce' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'IP Country', 'bulgarisation-for-woocommerce' ); ?></th>
						<td><?php
							if ( $data->ip_country ) {
								echo $countries[ $data->billing_country ] . ' ';

								if ( $data->billing_country === $data->ip_country ) {
									echo '<span style="color:green">&#10004;</span>';
								} elseif ( $data->self_declared ) {
									esc_html_e( '(self-declared)', 'bulgarisation-for-woocommerce' );
								} else {
									echo '<span style="color:red">&#10008;</span>';
								}
							} else {
								esc_html_e( 'Unknown', 'bulgarisation-for-woocommerce' );
							}
						?><td></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Billing Country', 'bulgarisation-for-woocommerce' ); ?></th>
						<td><?php echo $data->billing_country ? esc_html( $countries[ $data->billing_country ] ) : esc_html__( 'Unknown', 'bulgarisation-for-woocommerce' ); ?></td>
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
