<?php
namespace Woo_BG\Shipping;
defined( 'ABSPATH' ) || exit;

class ProductAdditionalFields {
	public function __construct( $container ) {
		add_action( 'woocommerce_product_options_shipping_product_data', array( __CLASS__, 'add_product_fields_in_dashboard' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
	}

	public static function add_product_fields_in_dashboard() {
		global $thepostid, $product_object;

		$checkboxes = array(
			'woo_bg_fragile'      => array(
				'id'            => '_woo_bg_fragile',
				'wrapper_class' => '',
				'label'         => __( 'Fragile', 'woo-bg' ),
				'description'   => __( 'Check if you want to make the product with fragile status.', 'woo-bg' ),
				'default'       => 'no',
			),
		);

		foreach ( $checkboxes as $key => $option ) {
			?>
			<p class="form-field ">
				<?php $selected_value = ( $product_object->get_meta( $option['id'] ) == 'on' ); ?>

				<label for="<?php echo esc_attr( $option['id'] ); ?>" class="<?php echo esc_attr( $option['wrapper_class'] ); ?> tips" data-tip="<?php echo esc_attr( $option['description'] ); ?>">
					<?php echo esc_html( $option['label'] ); ?>
				</label>
				<input type="checkbox" name="<?php echo esc_attr( $option['id'] ); ?>" id="<?php echo esc_attr( $option['id'] ); ?>" <?php echo checked( $selected_value, true, false ); ?> />
			</p>
			<?php
		}
	}

	public static function save( $product ) {
		if ( isset( $_POST[ '_woo_bg_fragile' ] ) ) {
			$product->update_meta_data( '_woo_bg_fragile', sanitize_text_field( $_POST[ '_woo_bg_fragile' ] ) );
		}
	}
}
