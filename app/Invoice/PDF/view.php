<!DOCTYPE html>
<html>
<head>
	<title><?php echo esc_html( $this->document->title ) ?></title>
</head>
<style type="text/css">
	h1 { font-size:20px; }
	.va-top { vertical-align:top; }
	.m-0 { margin: 0px; }
	.p-0 { padding: 0px; }
	.fz-12 { font-size: 12px; }
	.pt-5 { padding-top:5px; }
	.mt-10 { margin-top:10px; }
	.text-center { text-align:center !important; }
	.text-right { text-align:right !important; }
	.w-100 { width: 100%; }
	.w-85 { width:85%; }
	.w-80 { width:80%; }
	.w-60 { width:60%; }
	.w-55 { width:55%; }
	.w-50 { width:50%; }
	.w-45 { width:45%; }
	.w-40 { width:40%; }
	.w-35 { width:35%; }
	.w-20 { width:20%; }
	.w-15 { width:15%; }
	.w-10 { width:10%; }
	.logo img { width:150px; height:150px; }
	.logo span { margin-left:8px; top:19px; position: absolute; font-weight: bold; font-size:25px; }
	.gray-color { color:#5D5D5D; }
	.text-bold { font-weight: bold; }
	.border { border:1px solid black; }
	table tr,th,td { border: 1px solid #d2d2d2; border-collapse:collapse; padding:7px 8px; }
	table tr th { background: #F4F4F4; font-size:13px; }
	table tr td { font-size:10px; }
	table { border-collapse:collapse; }
	.box-text p { line-height:8px; }
	.float-left { float:left; }
	.total-part { font-size:12px; line-height: 5px; }
	footer { font-size: 12px; position: fixed; bottom: -40px; left: 0px; right: 0px; height: 50px; }
	footer p { display: inline-block; }
	.page-number:before { content: counter(page); }
	.page-count { float:right }

	<?php do_action( 'woo_bg/invoice/pdf/default_template/additional_css', $this ) ?>
</style>
<body>
	<main>
		<div class="add-detail">
			<div class="w-45 float-left logo">
				<?php echo wp_kses_post( apply_filters( 'woo_bg/invoice/pdf/default_template/qr', wp_get_attachment_image( $this->document->qr_png, 'medium_large' ), $this ) ) ?>
			</div>
			<div class="w-55 float-left text-right">
				<h1 class="m-0 p-0"><?php echo esc_html( $this->document->title ) ?></h1>

				<?php if ( !empty( $this->document->get_head_items() ) ): ?>
					<?php foreach ( $this->document->get_head_items() as $head_item ): ?>
						<p class="m-0 pt-0 fz-12 text-bold w-100">
							<?php printf('%s: <span class="gray-color">%s</span>', esc_html( $head_item['label'] ), esc_html( $head_item['value'] ) ) ?>
						</p>
					<?php endforeach ?>
				<?php endif ?>
			</div>

			<div style="clear: both;"></div>
		</div>

		<div class="table-section bill-tbl w-100 mt-10">
			<table class="table w-100 mt-10">
				<tr>
					<th class="w-50"><?php esc_html_e( 'Billing from', 'woo-bg' ) ?></th>
					<th class="w-50"><?php esc_html_e( 'Billing to', 'woo-bg' ) ?></th>
				</tr>

				<tr class="va-top">
					<td>
						<div class="box-text">
							<?php 
							foreach ( $this->document->get_from_items() as $item ) {
								echo wp_kses_post( wpautop( $item ) );
							}
							?>
						</div>
					</td>

					<td>
						<div class="box-text">
							<?php 
							foreach ( $this->document->get_to_items() as $item ) {
								echo wp_kses_post( wpautop( $item ) );
							}
							?>
						</div>
					</td>
				</tr>
			</table>
		</div>

		<div class="table-section bill-tbl w-100 mt-10">
			<table class="table w-100">
				<?php $headers = $this->document->get_cart_headers(); ?>

				<tr>
					<?php foreach ( $headers as $item ): ?>
						<th class="<?php echo esc_attr( $item['class'] ) ?>"><?php echo wp_kses_post( $item['label'] ) ?></th>
					<?php endforeach ?>
				</tr>

				<?php foreach ( $this->document->order->get_items() as $item ): ?>
					<tr>
						<?php foreach ( $item as $key => $col ): ?>
							<td <?php echo ( $key !== 'name' ) ? 'align="center"' : '' ?>><?php echo wp_kses_post( $col ) ?></td>
						<?php endforeach ?>
					</tr>
				<?php endforeach ?>

				<tr>
					<td colspan="<?php echo count( $headers ) ?>">
						<div class="total-part">
							<div class="total-left w-85 float-left" align="right">
								<?php 
								foreach ( $this->document->order->get_total_items() as $item ) {
									echo wp_kses_post( wpautop( $item['label'] . ":" ) );
								}
								?>
							</div>

							<div class="total-right w-15 float-left text-bold" align="right">
								<?php 
								foreach ( $this->document->order->get_total_items() as $item ) {
									echo wp_kses_post( wpautop( $item['value'] ) );
								}
								?>
							</div>

							<div style="clear: both;"></div>
						</div> 
					</td>
				</tr>
			</table>

			<?php do_action( 'woo_bg/invoice/pdf/default_template/after_table', $this->document->order->woo_order, $this ); ?>
		</div>

		<div class="table-section bill-tbl w-100 mt-10">
			<table class="table w-100 mt-10">
				<tr>
					<?php foreach ( $this->document->get_additional_items_labels() as $label ): ?>
						<th class="w-50"><?php echo wp_kses_post( $label ) ?></th>
					<?php endforeach ?>
				</tr>

				<tr class="text-center">
					<?php foreach ( $this->document->get_additional_items() as $value ): ?>
						<td><?php echo wp_kses_post( $value ) ?></td>
					<?php endforeach ?>
				</tr>
			</table>
		</div>

		<?php 
		if ( $this->document->footer_text ) {
			echo wp_kses_post( wpautop( '<span class="gray-color fz-12">' . $this->document->footer_text . '</span>' ) );
		}
		?>
	</main>

	<footer>
		<?php echo esc_html( get_bloginfo( 'name' ) ) ?>

		<span class="page-count text-right">
			<?php printf( esc_html__('Page %s of %s', 'woo-bg'), '<span class="page-number"></span>', '%PC%' ) ?>
		</span>
	</footer>
</html>