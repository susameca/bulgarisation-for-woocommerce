<?php
namespace Woo_BG\Admin;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

class Nekorekten_Com {
	const REPORTS_URL = 'https://api.nekorekten.com/api/v1/reports';
	const IMAGES_BASE_URL = 'https://api.nekorekten.com';

	public function __construct() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'customer_status_info' ) );

		if ( woo_bg_get_option( 'nekorekten', 'column' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'set_customer_status_info' ) );

			add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
			add_filter( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );

			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
			add_filter( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );
		}
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_nekorekten_reports', esc_html__( 'Reports', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
	}

	public static function customer_status_info( $order ) {
		$reports = self::get_all_reports( $order, isset( $_GET[ 'woo-bg--nekorekten-refresh' ] ) );

		if ( $reports[ 'count' ] ) {
			?>
			<p class="form-field form-field-wide woo-bg--centered-paragraph">
				<i class="woo-bg-icon woo-bg-icon--alert"></i>
				
				<a href="#woo_bg_nekorekten_reports">
					 <?php _e( 'We have found negative reports about this customer. <br> Click for more information.', 'woo-bg' ) ?>
				</a>
			</p>
			<?php
		} else {
			?>
			<p class="form-field form-field-wide woo-bg--centered-paragraph">
				<i class="woo-bg-icon woo-bg-icon--check"></i>
				
				<a href="#woo_bg_nekorekten_reports">
					 <?php esc_html_e( 'No reports was found for this customer!', 'woo-bg' ) ?>
				</a>
			</p>
			<?php
		}
	}

	public static function meta_box() {
		global $theorder;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$post = get_post( $_GET['id'] );
		} else {
			global $post;
		}

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		} else {
			$order = $theorder;
		}
		$phone = ( $theorder->get_shipping_phone() ) ? $theorder->get_shipping_phone() : $theorder->get_billing_phone();
		$email = $theorder->get_billing_email();
		$title = esc_html__( 'There was some error in one of the reports.', 'woo-bg' );
		$reports = self::get_all_reports( $theorder );
		
		if ( $reports[ 'count' ] ) {
			$title = esc_html__( 'We have found negative reports about this customer.', 'woo-bg' );
		} else if ( 
			( !empty( $reports['reports_by_phone'] ) && $reports['reports_by_phone'][ 'server' ]['httpCode'] === 200 ) && 
			( !empty( $reports['reports_by_email'] ) && $reports['reports_by_email'][ 'server' ]['httpCode'] === 200)
		) {
			$title = esc_html__( 'No reports was found.', 'woo-bg' );
		} 
		?>
		<div class="panel-wrap woocommerce">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_html__( 'Order', 'woocommerce' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
			<div id="order_data" class="panel woocommerce-order-data">
				<a href="<?php echo esc_url( add_query_arg( 'woo-bg--nekorekten-refresh', true ) ) ?>"><?php esc_html_e( 'Refresh data', 'woo-bg' ) ?></a>

				<h2 class="woocommerce-order-data__heading">
					<?php echo esc_html( $title ) ?>
				</h2>

				<div class="order_data_column_container">
					<div class="order_data_column order_data_column--half">
						<h3><?php printf( esc_html__( 'By Phone ( %s )', 'woo-bg' ), esc_html( $phone ) ) ?></h3>

						<?php
						if ( !empty( $reports['reports_by_phone'] ) && $reports['reports_by_phone'][ 'server' ][ 'httpCode' ] == 200 ) {
							self::meta_box_success( $reports['reports_by_phone'] );
						} else {
							self::meta_box_error( $reports['reports_by_phone'] );
						}
						?>
					</div><!-- /.order_data_column order_data_column-/-half -->

					<div class="order_data_column order_data_column--half">
						<h3><?php printf( esc_html__( 'By Email ( %s )', 'woo-bg' ), esc_html( $email ) ) ?></h3>

						<?php  
						if ( !empty( $reports['reports_by_email'] ) && $reports['reports_by_email'][ 'server' ][ 'httpCode' ] == 200 ) {
							self::meta_box_success( $reports['reports_by_email'] );
						} else {
							self::meta_box_error( $reports['reports_by_email'] );
						}
						?>
					</div><!-- /.order_data_column order_data_column-/-half -->
				</div><!-- /.order_data_column_container -->
			</div>
		</div>

		<div class="clear"></div>
		<?php

		self::render_submit_form( $theorder );
	}

	public static function meta_box_error( $report ) {
		?>
		<?php if ( isset( $report[ 'server' ][ 'date' ] ) ): ?>
			<span><?php printf( esc_html__( 'Date: %s', 'woo-bg' ), esc_html( $report[ 'server' ][ 'date' ] ) ) ?></span>
		<?php endif ?>

		<?php if ( !empty( $report['message'] ) ): ?>
			<h3><?php printf( esc_html__( 'Error: "%s"', 'woo-bg' ), esc_html( $report['message'] ) ) ?></h3>
		<?php else: ?>
			<h3><?php printf( esc_html__( 'Invalid access.', 'woo-bg' ) ) ?></h3>
		<?php endif ?>
		<?php
	}

	public static function meta_box_success( $report ) {
		?>
		<?php if ( isset( $report[ 'server' ][ 'date' ] ) ): ?>
			<span><?php printf( esc_html__( 'Date: %s', 'woo-bg' ), esc_html( $report[ 'server' ][ 'date' ] ) ) ?></span>
		<?php endif ?>

		<?php if ( isset( $report[ 'count' ] ) ): ?>
			<p>
				<strong><?php printf( esc_html__( 'Found: %s', 'woo-bg' ), esc_html( $report[ 'count' ] ) ) ?></strong>
			</p>
		<?php endif ?>
			
		<?php if ( isset( $report[ 'message' ] ) ): ?>
			<h4><?php echo esc_html( $report[ 'message' ] ) ?></h4>
		<?php endif ?>

		<br> <br>

		<?php foreach ( $report[ 'items' ] as $item ): ?>
			<div class="customer">
				<p>
					<strong><?php esc_html_e('First Name:', 'woo-bg') ?></strong> <span> <?php echo esc_html( $item['firstName'] ); ?> </span>
					<strong><?php esc_html_e('Last Name:', 'woo-bg') ?></strong> <span> <?php echo esc_html( $item['lastName'] ); ?> </span>
					<strong><?php esc_html_e('Phone:', 'woo-bg') ?></strong> <span> <?php echo esc_html( $item['phone'] ); ?> </span>
					<strong><?php esc_html_e('Email:', 'woo-bg') ?></strong> <span> <?php echo esc_html( $item['email'] ); ?> </span>
				</p>

				<p> <strong><?php esc_html_e('Date:', 'woo-bg') ?> </strong> <span> <?php echo esc_html( $item[ 'createDate' ] ) ?></span> </p>

				<p> <strong><?php esc_html_e('Text:', 'woo-bg') ?></strong> <?php echo esc_html( $item[ 'text' ] ) ?> </p>

				<?php if ( !empty( $item['files'] ) ): ?>
					<?php foreach ( $item['files'] as $file_item ): 
						$link = self::IMAGES_BASE_URL . $file_item['previewUrl'];
					?>
						<a href="<?php echo esc_url( $link ) ?>" target="_blank" class="customer-reports--image">
							<img src="<?php echo esc_url( $link ) ?>" alt="">
						</a>
					<?php endforeach ?>
				<?php endif ?>
			</div><!-- /.address -->
		<?php endforeach ?>
		<?php
	}

	public static function get_user_report_from_order( $order, $search_by, $key, $force = false ) {
		$meta_key = 'woo_bg_reports-' . $key . "_" . $search_by;
		$meta = $order->get_meta( $meta_key );

		if ( $meta && !$force ) {
			return $meta;
		}

		$body = self::api_call( self::REPORTS_URL, [ $key => $search_by ] ); 

		if ( !empty( $body ) && $body[ 'server' ]['httpCode'] === 200) {
			$order->update_meta_data( $meta_key, $body );
			$order->save();
		}

		return $body;
	}

	public static function api_call( $endpoint, $args, $method = 'get' ) {
		$request_args = array(
			'timeout'     => 15,
			'headers' => array(
				'Api-Key' => woo_bg_get_option( 'nekorekten', 'api_key' ),
			),
			'body' => $args
		);

		if ( $method === 'get' ) {
			$request = wp_remote_get( $endpoint, $request_args );
		} else {
			$request = wp_remote_post( $endpoint, $request_args );
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), 1 );

		return $body;
	}

	public static function get_all_reports( $order, $force = false ) {
		$phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();
		$email = $order->get_billing_email();

		$reports_by_phone = self::get_user_report_from_order( $order, $phone, 'phone', $force ); 
		$reports_by_email = self::get_user_report_from_order( $order, $email, 'email', $force );
		$phone_counts = ( isset( $reports_by_phone['count'] ) ) ? $reports_by_phone['count'] : 0;
		$email_counts = ( isset( $reports_by_email['count'] ) ) ? $reports_by_email['count'] : 0;

		return [
			'reports_by_phone' => $reports_by_phone,
			'reports_by_email' => $reports_by_email,
			'count' => $phone_counts + $email_counts,
		];
	}

	public static function get_all_reports_from_meta( $order ) {
		$phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();
		$email = $order->get_billing_email();


		$reports_by_phone = $order->get_meta( 'woo_bg_reports-phone_' . $phone ); 
		$reports_by_email = $order->get_meta( 'woo_bg_reports-email_' . $email ); 

		if ( !$reports_by_phone && !$reports_by_email ) {
			return;
		}

		$phone_counts = ( isset( $reports_by_phone['count'] ) ) ? $reports_by_phone['count'] : 0;
		$email_counts = ( isset( $reports_by_email['count'] ) ) ? $reports_by_email['count'] : 0;

		return [
			'reports_by_phone' => $reports_by_phone,
			'reports_by_email' => $reports_by_email,
			'count' => $phone_counts + $email_counts,
		];
	}

	public static function add_order_list_column( $columns ) {
		$reordered_columns = array();

	    foreach( $columns as $key => $column){
	        $reordered_columns[ $key ] = $column;

	        if( $key ==  'shipping_address' ){
	            $reordered_columns[ 'order_nekorekten' ] = esc_html__( 'Nekorekten', 'woo-bg' );
	        }
	    }

	    return $reordered_columns;
	}

	public static function add_order_list_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'order_nekorekten' :
				$order = wc_get_order( $post_id );
				$reports = self::get_all_reports_from_meta( $order );

				if ( empty( $reports ) ) {
					echo '<i class="woo-bg-icon woo-bg-icon--question"></i>';
				} else if ( $reports[ 'count' ] ) {
					echo '<i class="woo-bg-icon woo-bg-icon--alert"></i>';
				} else {
					echo '<i class="woo-bg-icon woo-bg-icon--check"></i>';
				}
				
				break;
		}
	}

	public static function set_customer_status_info( $order_id ) {
		$order = wc_get_order( $order_id );

		self::get_all_reports( $order, 1 );
	}

	public static function render_submit_form( $order ) {
		?>
		<div id="order_data" class="panel woocommerce-order-data">
			<div class="order_data_column_container">
				<div class="order_data_column order_data_column--half">
					<h3><?php _e( 'Submit Review', 'woo-bg' ); ?></h3> 
					<form></form>
					<form class="ajax-container" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ) ?>">
						<p class="form-field" style="clear: none;">
							<label>
								<?php _e('First Name:', 'woo-bg') ?>

								<input type="text" name="first_name" required value="<?php echo $order->get_billing_first_name() ?>">
							</label> 
						</p> 
						<p class="form-field" style="float: right; clear: none;">
							<label>
								<?php _e('Last Name:', 'woo-bg') ?>

								<input type="text" name="last_name" required value="<?php echo $order->get_billing_last_name() ?>">
							</label> 
						</p>
						<p class="form-field" style="clear: none;">
							<label>
								<?php _e('Phone:', 'woo-bg') ?>

								<input type="text" name="phone" required value="<?php echo $order->get_billing_phone() ?>">
							</label> 
						</p>
						<p class="form-field" style="float: right; clear: none;">
							<label> 
								<?php _e('E-mail:', 'woo-bg') ?>

								<input type="text" name="email" value="<?php echo $order->get_billing_email() ?>">
							</label> 
						</p>

						<p class="form-field form-field-wide">
							<label> 
								<?php _e('Description', 'woo-bg') ?>

								<textarea name="description" required rows="4"></textarea>
							</label> 
						</p>

						<input type="hidden" name="action" value="woo_bg_nekorekten_submit">
						<?php wp_nonce_field( 'woo_bg_nekorekten_submit' ); ?>

						<button type="submit" class="button-primary woocommerce-save-button"><?php _e('Submit', 'woo-bg') ?></button>
					</form>
				</div>
				
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public static function submit_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woo_bg_nekorekten_submit' ) ) {
			wp_send_json_error( [
				'message' => __( 'Nonce was not provided!', 'woo-bg' ),
			] );

			wp_die();
		}

		$args = [
			'text' => sanitize_text_field( $_REQUEST['description'] ),
			'firstName' => sanitize_text_field( $_REQUEST['first_name'] ),
			'lastName' => sanitize_text_field( $_REQUEST['last_name'] ),
			'email' => sanitize_text_field( $_REQUEST['email'] ),
			'phone' => sanitize_text_field( $_REQUEST['phone'] ),
		];

		$resonse = self::api_call( self::REPORTS_URL, $args, 'post' ); 

		$json = [
			'message' => $resonse['message'],
		];

		if ( !empty( $resonse ) && $resonse[ 'server' ]['httpCode'] === 200 ) {
			wp_send_json_success( $json );

			wp_die();
		}

		wp_send_json_error( $json );

		wp_die();
	}
}
