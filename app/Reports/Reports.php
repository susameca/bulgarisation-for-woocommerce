<?php
namespace Woo_BG\Reports;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

class Reports {
	const CHECKOUT_SESSION_NAME = 'woo_bg_reports_found';
    protected $providers = [];

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
    }

	public function init() {
		$this->providers = self::get_providers();

        if ( empty( $this->providers ) ) {
            return;
        }

        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'customer_status_info' ) );

        if ( woo_bg_get_option( 'reports', 'column' ) === 'yes' ) {
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'set_customer_status_info' ) );

			add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
			add_filter( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );

			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_order_list_column' ), 11 );
			add_filter( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'add_order_list_column_content' ), 11, 2 );
		}

		if ( woo_bg_get_option( 'reports', 'check_on_checkout' ) === 'yes' ) {
			add_action( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_payment_methods' ) );
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'unset_session' ), 30 );
		}
	}
    
	public static function get_providers() {
        $providers = array();

		if ( woo_bg_get_option( 'reports', 'enable_connectix' ) === 'yes' ) {
			$providers[] = new Providers\Connectix();
		}
        
        $providers = array_filter( apply_filters( 'woo-bg/reports/providers', $providers ), function ( $provider ) {
            return ( is_a( $provider, 'Woo_BG\Reports\Providers\Provider_Base' ) );
        } );

        return $providers;        
    }

    public static function add_meta_boxes() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? array( wc_get_page_screen_id( 'shop-order' ), wc_get_page_screen_id( 'shop_subscription' ) )
		: array( 'shop_order', 'shop_subscription' );

		$screen = array_filter( $screen );

		add_meta_box( 'woo_bg_customer_reports', esc_html__( 'Reports', 'bulgarisation-for-woocommerce' ), array( __CLASS__, 'meta_box' ), $screen, 'normal', 'default' );
	}

    public static function meta_box() {
		global $theorder;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$post = get_post( sanitize_text_field( $_GET['id'] ) );
		} else {
			global $post;
		}

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}
		?>
		<div class="panel-wrap woocommerce">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_html__( 'Order', 'bulgarisation-for-woocommerce' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />

			<div id="order_data" class="panel woocommerce-order-data">
				<a href="<?php echo esc_url( add_query_arg( 'woo-bg--reports-refresh', true ) ) ?>"><?php esc_html_e( 'Refresh data', 'bulgarisation-for-woocommerce' ) ?></a>

                <?php foreach ( self::get_providers() as $provider ): ?>
                    <div class="woo-bg--report-provider">
                        <h3><?php echo $provider->get_name(); ?></h3>

                        <?php $provider->print_reports_html( $theorder ); ?>
                    </div><!-- /.order_data_column_container -->
                <?php endforeach; ?>
			</div>
		</div>

		<div class="clear"></div>
		<?php
	}

    public static function customer_status_info( $order ) {
		$reports = self::get_all_reports( $order, isset( $_GET[ 'woo-bg--reports-refresh' ] ) );
		
		if ( count( $reports ) > 0 ) {
			?>
			<p class="form-field form-field-wide woo-bg--centered-paragraph">
				<i class="woo-bg-icon woo-bg-icon--alert"></i>
				
				<a href="#woo_bg_customer_reports">
					<?php esc_html_e( 'We have found negative reports about this customer.', 'bulgarisation-for-woocommerce');?>
					  <br> 
					<?php esc_html_e( 'Click for more information.', 'bulgarisation-for-woocommerce' ) ?>
				</a>
			</p>
			<?php
		} else {
			?>
			<p class="form-field form-field-wide woo-bg--centered-paragraph">
				<i class="woo-bg-icon woo-bg-icon--check"></i>
				
				<a href="#woo_bg_customer_reports">
					 <?php esc_html_e( 'No reports was found for this customer!', 'bulgarisation-for-woocommerce' ) ?>
				</a>
			</p>
			<?php
		}
	}

    public static function set_customer_status_info( $order_id ) {
		$order = wc_get_order( $order_id );

		self::get_all_reports( $order, 1 );
	}

    public static function get_all_reports( $order, $force = false ) {
        $providers = self::get_providers();
        $all_reports = [];

        foreach ( $providers as $provider ) {
            $provider_reports = $provider::get_all_reports( $order, $force );
            $all_reports = array_merge( $all_reports, $provider_reports['reports'] );
        }

        return $all_reports;
    }

    public static function get_all_reports_from_meta( $order ) {
        $providers = self::get_providers();
        $all_reports = [];

        foreach ( $providers as $provider ) {
            $provider_reports = $provider::get_all_reports_from_meta( $order );
			if ( isset( $provider_reports['reports'] ) ) {
				$all_reports = array_merge( $all_reports, $provider_reports['reports'] );
			}
        }

        return $all_reports;
    }

    public static function add_order_list_column( $columns ) {
		$reordered_columns = array();

	    foreach( $columns as $key => $column){
	        $reordered_columns[ $key ] = $column;

	        if( $key ==  'shipping_address' ){
	            $reordered_columns[ 'order_reports' ] = esc_html__( 'Reports', 'bulgarisation-for-woocommerce' );
	        }
	    }

	    return $reordered_columns;
	}

	public static function add_order_list_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'order_reports' :
				$order = wc_get_order( $post_id );
				$reports = self::get_all_reports_from_meta( $order );

				if ( empty( $reports ) ) {
					echo '<i class="woo-bg-icon woo-bg-icon--check"></i>';
				} else if ( count( $reports ) ) {
					echo '<i class="woo-bg-icon woo-bg-icon--alert"></i>';
				}
				
				break;
		}
	}

	public static function filter_payment_methods( $available_gateways ) {    
	    if( 
	    	is_admin() || 
	    	!isset( $_POST['post_data'] ) || 
	    	empty( $available_gateways['cod'] ) 
	    ) {
	        return $available_gateways;
	    }

	    $post_data = map_deep( $_POST['post_data'], 'sanitize_text_field' );
	    parse_str( $post_data, $data );
	    $phone = $data['billing_phone'];
		$reports_by_phone = self::check_in_session( $phone );

		if ( is_array( $reports_by_phone ) && count( $reports_by_phone ) > 0 ) {
	    	unset(  $available_gateways['cod'] );
		}
	     
	    return $available_gateways;
	}

	public static function check_in_session( $phone ) {
		$phones = [];

		if ( $cached = WC()->session->get( self::CHECKOUT_SESSION_NAME ) ) {
			$phones = $cached;
		}

		if ( ! isset( $phones[ md5( $phone ) ] ) ) {
			$found_reports = [];

			foreach ( self::get_providers() as $provider ) {
				$provider_reports = $provider::check_for_reports_on_checkout( $phone );
				$found_reports = array_merge( $found_reports, $provider_reports );
			}

			$phones[ md5( $phone ) ] = $found_reports;

			WC()->session->set( self::CHECKOUT_SESSION_NAME, $phones );
		}
        
        if ( !empty( $phones[ md5( $phone ) ] ) ) {
        	return $phones[ md5( $phone ) ];
        }
	}

	public static function unset_session() {
		WC()->session->__unset( self::CHECKOUT_SESSION_NAME );
	}
}