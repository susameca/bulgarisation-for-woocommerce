<?php defined( 'ABSPATH' ) || exit; ?>
<p>
	<?php if ( !empty( $data[ 'number' ] ) && is_array( $data ) ): ?>
		<a target="_blank" href="<?php echo esc_url( $data['link'] ); ?>">
			<?php echo wp_kses_post( $data['number'] ) ?>
		</a>
	<?php elseif( empty( $data[ 'number' ] ) && !empty( $data['items'] ) ): ?>
		<?php foreach ( $data['items'] as $label ): ?>
			<a target="_blank" href="<?php echo esc_url( $label['link'] ) ?>">
				<?php echo esc_html( $label['number'] ) ?>
			</a>
		<?php endforeach ?>
	<?php else: ?>
		<a 
			class="woo-bg--generate-label" 
			href="#" 
			data-method="<?php echo esc_attr( $data['method'] ) ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'woo_bg_admin_label' ) ) ?>" 
			data-orderid="<?php echo esc_attr( $order->get_id() ) ?>"
		>
				<?php esc_html_e( 'Generate label', 'bulgarisation-for-woocommerce' ) ?>
		</a>
	<?php endif ?>
</p>