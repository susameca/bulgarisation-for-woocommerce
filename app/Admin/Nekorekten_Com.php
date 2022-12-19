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
		}
	}

	public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		add_meta_box( 'woo_bg_nekorekten_reports', __( 'Reports', 'woo-bg' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
	}

	public static function customer_status_info( $order ) {
		$reports = self::get_all_reports( $order, isset( $_GET[ 'woo-bg--nekorekten-refresh' ] ), 1 );

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
					 <?php _e( 'No reports was found for this customer!', 'woo-bg' ) ?>
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
		$title = __( 'There was some error in one of the reports.', 'woo-bg' );
		$reports = self::get_all_reports( $theorder );

		if ( $reports[ 'count' ] ) {
			$title = __( 'We have found negative reports about this customer.', 'woo-bg' );
		} else if ( $reports['reports_by_phone'][ 'server' ]['httpCode'] === 200 && $reports['reports_by_email'][ 'server' ]['httpCode'] === 200 ) {
			$title = __( 'No reports was found.', 'woo-bg' );
		} 
		?>
		<div class="panel-wrap woocommerce">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Order', 'woocommerce' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
			<div id="order_data" class="panel woocommerce-order-data">
				<a href="<?php echo add_query_arg( 'woo-bg--nekorekten-refresh', true ) ?>"><?php _e( 'Refresh data', 'woo-bg' ) ?></a>

				<h2 class="woocommerce-order-data__heading">
					<?php echo $title ?>
				</h2>

				<div class="order_data_column_container">
					<div class="order_data_column order_data_column--half">
						<h3><?php printf( __( 'By Phone ( %s )', 'woo-bg' ), $phone ) ?></h3>

						<?php
						if ( $reports['reports_by_phone'][ 'server' ][ 'httpCode' ] == 200 ) {
							self::meta_box_success( $reports['reports_by_phone'] );
						} else {
							self::meta_box_error( $reports['reports_by_phone'] );
						}
						?>
					</div><!-- /.order_data_column order_data_column-/-half -->

					<div class="order_data_column order_data_column--half">
						<h3><?php printf( __( 'By Email ( %s )', 'woo-bg' ), $email ) ?></h3>

						<?php  
						if ( $reports['reports_by_email'][ 'server' ][ 'httpCode' ] == 200 ) {
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

		<div>
			<?php _e( 'You can add a report from <a href="https://nekorekten.com/" target="_blank">https://nekorekten.com/</a>', 'woo-bg' ) ?>
		</div>
		<?php
	}

	public static function meta_box_error( $report ) {
		?>
		<?php if ( isset( $report[ 'server' ][ 'date' ] ) ): ?>
			<span><?php printf( __( 'Date: %s', 'woo-bg' ), $report[ 'server' ][ 'date' ] ) ?></span>
		<?php endif ?>

		<h3><?php printf( __( 'Error: "%s"', 'woo-bg' ), $report['message'] ) ?></h3>
		<?php
	}

	public static function meta_box_success( $report ) {
		?>
		<?php if ( isset( $report[ 'server' ][ 'date' ] ) ): ?>
			<span><?php printf( __( 'Date: %s', 'woo-bg' ), $report[ 'server' ][ 'date' ] ) ?></span>
		<?php endif ?>

		<?php if ( isset( $report[ 'count' ] ) ): ?>
			<p>
				<strong><?php printf( __( 'Found: %s', 'woo-bg' ), $report[ 'count' ] ) ?></strong>
			</p>
		<?php endif ?>
			
		<?php if ( isset( $report[ 'message' ] ) ): ?>
			<h4><?php echo $report[ 'message' ] ?></h4>
		<?php endif ?>

		<br> <br>

		<?php foreach ( $report[ 'items' ] as $item ): ?>
			<div class="customer">
				<p>
					<strong><?php _e('First Name:', 'woo-bg') ?></strong> <span> <?php echo $item['firstName']; ?> </span>
					<strong><?php _e('Last Name:', 'woo-bg') ?></strong> <span> <?php echo $item['lastName']; ?> </span>
					<strong><?php _e('Phone:', 'woo-bg') ?></strong> <span> <?php echo $item['phone']; ?> </span>
					<strong><?php _e('Email:', 'woo-bg') ?></strong> <span> <?php echo $item['email']; ?> </span>
				</p>

				<p> <strong><?php _e('Date:', 'woo-bg') ?> </strong> <span> <?php echo $item[ 'createDate' ] ?></span> </p>

				<p> <strong><?php _e('Text:', 'woo-bg') ?></strong> <?php echo $item[ 'text' ] ?> </p>

				<?php if ( !empty( $item['files'] ) ): ?>
					<?php foreach ( $item['files'] as $file_item ): 
						$link = self::IMAGES_BASE_URL . $file_item['previewUrl'];
					?>
						<a href="<?php echo $link ?>" target="_blank" class="customer-reports--image">
							<img src="<?php echo $link ?>" alt="">
						</a>
					<?php endforeach ?>
				<?php endif ?>
			</div><!-- /.address -->
		<?php endforeach ?>
		<?php
	}

	public static function get_user_report( $order, $search_by, $key, $force = false ) {
		$meta_key = 'woo_bg_reports-' . $key . "_" . $search_by;
		$meta = $order->get_meta( $meta_key );

		if ( $meta && !$force ) {
			return $meta;
		}

		$request = wp_remote_get( self::REPORTS_URL, array(
			'timeout'     => 15,
			'headers' => array(
				'Api-Key' => woo_bg_get_option( 'nekorekten', 'api_key' ),
			),
			'body' => array(
				$key => $search_by,
			)
		) );

		$body = json_decode( wp_remote_retrieve_body( $request ), 1 );

		if ( $body[ 'server' ]['httpCode'] === 200) {
			$order->update_meta_data( $meta_key, $body );
			$order->save();
		}

		return $body;
	}

	public static function get_all_reports( $order, $force = false ) {
		$phone = ( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone();
		$email = $order->get_billing_email();

		$reports_by_phone = self::get_user_report( $order, $phone, 'phone', $force ); 
		$reports_by_email = self::get_user_report( $order, $email, 'email', $force );
		$phone_counts = ( isset( $reports_by_phone['count'] ) ) ? $reports_by_phone['count'] : 0;
		$email_counts = ( isset( $reports_by_email['count'] ) ) ? $reports_by_email['count'] : 0;

		return [
			'reports_by_phone' => $reports_by_phone,
			'reports_by_email' => $reports_by_email,
			'count' => $phone_counts + $email_counts,
		];
	}

	public static function get_all_reports_from_meta( $order) {
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
	            $reordered_columns[ 'nekorekten' ] = '';
	        }
	    }

	    return $reordered_columns;
	}

	public static function add_order_list_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'nekorekten' :
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

		self::get_all_reports( $order, isset( $_GET[ 'woo-bg--nekorekten-refresh' ] ) );
	}
}
