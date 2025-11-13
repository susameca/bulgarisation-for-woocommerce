<?php defined( 'ABSPATH' ) || exit; ?>
<p>
	<?php if ( !empty( $data[ 'number' ] ) && is_array( $data ) ): ?>
		<a target="_blank" href="<?php echo $data['link'] ?>">
			<?php echo $data['number'] ?>
		</a>
	<?php elseif( empty( $data[ 'number' ] ) && !empty( $data['items'] ) ): ?>
		<?php foreach ( $data['items'] as $label ): ?>
			<a target="_blank" href="<?php echo $label['link'] ?>">
				<?php echo $label['number'] ?>
			</a>
		<?php endforeach ?>
	<?php else: ?>
		<a 
			class="woo-bg--generate-label" 
			href="#" 
			data-method="<?php echo $data['method'] ?>"
			data-nonce="<?php echo wp_create_nonce( 'woo_bg_admin_label' ) ?>" 
			data-orderid="<?php echo $order->get_id() ?>"
		>
				<?php _e( 'Generate label', 'bulgarisation-for-woocommerce' ) ?>
		</a>
	<?php endif ?>
</p>