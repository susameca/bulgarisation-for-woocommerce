<p>
	<?php if ( !empty( $data[ 'number' ] ) ): ?>
		<a target="_blank" href="<?php echo $data['link'] ?>">
			<?php echo $data['number'] ?>
		</a>
	<?php else: ?>
		<a 
			class="woo-bg--generate-label" 
			href="#" 
			data-method="<?php echo $data['method'] ?>"
			data-nonce="<?php echo wp_create_nonce( 'woo_bg_admin_label' ) ?>" 
			data-orderid="<?php echo $order->get_id() ?>"
		>
				<?php _e( 'Generate label', 'woo-bg' ) ?>
		</a>
	<?php endif ?>
</p>