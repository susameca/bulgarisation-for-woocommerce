<?php
namespace Woo_BG;

defined( 'ABSPATH' ) || exit;

class Plugin {
	const VERSION = '3.0.29';

	protected static $_instance;

	/** @var \Pimple\Container */
	protected $container = null;

	/**
	 * @var Container\Provider[]
	 */
	private $providers = [];

	/**
	 * @param \Pimple\Container $container
	 */
	public function __construct( \Pimple\Container $container ) {
		$this->container = $container;
	}

	public function __get( $property ) {
		if ( array_key_exists( $property, $this->providers ) ) {
			return $this->providers[ $property ];
		}

		return null;
	}

	public function init() {
		$this->load_functions();
		$this->load_default_options();
		$this->load_classes();
		$this->load_service_providers();

		add_filter( 'plugin_action_links_' . plugin_basename( woo_bg()->plugin_dir_path() . 'woocommerce-bulgarisation.php' ), array( __CLASS__, 'plugin_action_links' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'set_webpack_path' ), 0);
		add_action( 'admin_footer', array( __CLASS__, 'set_webpack_path' ), 0);
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 99, 2 );
		add_filter( 'rest_attachment_query', array( __CLASS__, 'exclude_pdf_from_rest' ), 10, 2);
	}

	private function load_default_options() {
		if ( ! woo_bg_get_option( 'apis', 'enable_documents' ) ) {
			$order_documents_trigger = woo_bg_get_option( 'invoice', 'trigger' );

			if ( $order_documents_trigger !== 'disabled' ) {
				woo_bg_set_option( 'apis', 'enable_documents', 'yes' );
				woo_bg_set_option( 'invoice', 'nra_n18', 'yes' );
				woo_bg_set_option( 'invoice', 'trigger', $order_documents_trigger );
			} else {
				woo_bg_set_option( 'apis', 'enable_documents', 'no' );
				woo_bg_set_option( 'invoice', 'nra_n18', 'no' );
			}

			woo_bg_set_option( 'checkout', 'alternative_shipping_table', woo_bg_get_option( 'nap', 'alternative_shipping_table' ) );

			add_action( 'admin_notices', function() {
				$message = sprintf( __( 'Bulgarisation for WooCommerce - Please review the options and save them again from "%s".', 'woo-bg' ), sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=woo-bg' ), __( 'Settings', 'woo-bg' ) ) );

				echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
			} );
		}

		if ( !woo_bg_get_option( 'shippings', 'woo_bg_econt_is_courier' ) ) {
			woo_bg_set_option( 'shippings', 'woo_bg_econt_is_courier', 'yes' );
			woo_bg_set_option( 'shippings', 'woo_bg_speedy_is_courier', 'yes' );
			woo_bg_set_option( 'shippings', 'woo_bg_cvc_is_courier', 'yes' );
		}
	}

	private function load_classes() {
		new Admin\Admin_Menus();
		new Admin\Order\Columns();

		if ( woo_bg_get_option( 'apis', 'enable_documents' ) === 'yes' ) {
			new Admin\Order\Actions();
			new Admin\Order\Emails();
			new Admin\Order\MetaBox();
			new Admin\Order\Subscriptions();

			if ( woo_bg_get_option( 'invoice', 'invoices' ) !== 'disable' ) {
				new Front_End\Checkout\Company();

				if ( ! class_exists( 'WC_EU_VAT_Number_Init' ) ) {
					new Front_End\Checkout\EU_Vat();
					new Admin\EU_Vat();
				}
			}
		}

		new Shipping\Register( $this->container );
		new Shipping\CheckoutLayout();

		if ( woo_bg_get_option( 'apis', 'enable_nekorekten' ) === 'yes' ) {
			new Admin\Nekorekten_Com();
		}
		
		do_action( 'woo_bg/init-classes' );
	}

	private function load_functions() {
		// Get relative path to this file
		$functions_file = __DIR__ . '/functions.php';

		require_once $functions_file;
	}

	private function load_service_providers() {
		$this->providers['client'] = new \Woo_BG\Container\Client();
		/**
		 * Filter the service providers the power the plugin
		 *
		 * @param Container\Provider[] $providers
		 */
		$this->providers = apply_filters( 'woo_bg/plugin/providers', $this->providers );

		foreach ( $this->providers as $provider ) {
			$this->container->register( $provider );
		}
	}

	public function container() {
		return $this->container;
	}

	/**
	 * @return string The URL for the plugin's root directory, with a trailing slash
	 */
	public function plugin_dir_url() {
		return plugin_dir_url( $this->container()[ 'plugin_file' ] );
	}

	/**
	 * @return string The file system path for the plugin's root directory, with a trailing slash
	 */
	public function plugin_dir_path() {
		return plugin_dir_path( $this->container()[ 'plugin_file' ] );
	}

	/**
	 * @param null|\ArrayAccess $container
	 *
	 * @return self
	 * @throws \Exception
	 */
	public static function instance( $container = null ) {
		if ( ! isset( self::$_instance ) ) {
			if ( empty( $container ) ) {
				throw new \Exception( 'You need to provide a Pimple container' );
			}

			$className       = __CLASS__;
			self::$_instance = new $className( $container );
		}

		return self::$_instance;
	}

	public static function plugin_action_links( $actions ) {
		$custom_actions = array(
			'settings' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=woo-bg&tab=settings' ), __( 'Settings', 'woo-bg' ) ),
		);
		return array_merge( $custom_actions, $actions );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-frontend',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-frontend',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'frontend.css' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-admin',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'admin.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_enqueue_style(
			'woo-bg-css-bundle',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'admin.css' )
		);
	}

	public static function set_webpack_path() {
		$dir = woo_bg()->plugin_dir_url() . 'dist/';
		?>
		<script type="text/javascript">
			window.__webpack_public_path__ = '<?php echo esc_url( $dir ) ?>';
		</script>
		<?php
	}

	public static function robots_txt( $output, $public ) {
		$plugin_dir_url = str_replace( site_url(), '', woo_bg()->plugin_dir_url() );
		$upload_dir = wp_upload_dir();
		$upload_dir = str_replace( site_url(), '', $upload_dir['baseurl'] );

		$output .= "Disallow: " . $plugin_dir_url . "\n";
		$output .= "Disallow: " . $upload_dir . "/woo-bg/\n";
	
		return $output;
	}

	public static function exclude_pdf_from_rest( $args, $request ) {
		$unsupported_mimes = array( 'application/pdf', 'application/xml', 'text/plain' );
		$all_mimes = get_allowed_mime_types();
		$accepted_mimes = array_diff( $all_mimes, $unsupported_mimes );
		$args[ 'post_mime_type' ] = $accepted_mimes;

		return $args;
	}
}
