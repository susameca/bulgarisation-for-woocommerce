<?php
namespace Woo_BG\Admin;

defined( 'ABSPATH' ) || exit;

class ImpossibleVatPrice {
	const PAGE_SLUG   = 'woo-bg-impossible-prices';
	const SCAN_NONCE  = 'woo_bg_impossible_prices_scan';
	const FIX_NONCE   = 'woo_bg_impossible_prices_fix';

	public function __construct() {
		add_filter( 'admin_body_class', array( __CLASS__, 'add_body_class' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ), 20 );
	}

	public static function add_body_class( $classes ) {
		$classes .= ' woocommerce_woo-bg-impossible-vat-price';

		return $classes;
	}

	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			esc_html__( 'Mathematically impossible prices', 'bulgarisation-for-woocommerce' ),
			esc_html__( 'Mathematically impossible prices', 'bulgarisation-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function enqueue_admin_assets() {
		$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
		$vat_percentages     = woo_bg_get_vat_groups();
		$rate = ( isset( $vat_percentages[ $vat_group ] ) ) ? $vat_percentages[ $vat_group ] : 0;
	
		wp_localize_script(
			'woo-bg-js-admin',
			'wooBgImpossiblePrices',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'pricesIncludeTax'  => ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ),
				'fallbackTaxRate'   => $rate / 100,
				'parentTaxRate'     => (float) woo_bg_impossible_prices_get_product_tax_rate(),
				'taxesEnabled'      => wc_tax_enabled(),
				'priceDecimals'     => (int) wc_get_price_decimals(),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'preferHigherOnTie' => true,
				'taxRates'          => woo_bg_impossible_prices_get_tax_classes_data(),
				'scanNonce' => wp_create_nonce( self::SCAN_NONCE ),
				'fixNonce'  => wp_create_nonce( self::FIX_NONCE ),
				'i18n'      => array(
					'warningPrefix'             => 'Тази цена е математически невъзможна при текущия ДДС.',
					'suggested'                 => 'Най-близка възможна цена:',
					'apply'                     => 'Коригирай',
					'success'                   => 'Цената е коригирана. Запази продукта.',
					'preparing'                 => esc_html__( 'Preparing...', 'bulgarisation-for-woocommerce' ),
					'notStarted'                => esc_html__( 'Not started.', 'bulgarisation-for-woocommerce' ),
					'scanProducts'              => esc_html__( 'Scan products', 'bulgarisation-for-woocommerce' ),
					'scanning'                  => esc_html__( 'Scanning...', 'bulgarisation-for-woocommerce' ),
					'scanError'                 => esc_html__( 'An error occurred during scanning.', 'bulgarisation-for-woocommerce' ),
					'scanAjaxError'             => esc_html__( 'AJAX error during scanning.', 'bulgarisation-for-woocommerce' ),
					'scanFinished'              => esc_html__( 'Scanning finished.', 'bulgarisation-for-woocommerce' ),
					'pageOf'                    => esc_html__( 'Scanning... page %1$s of %2$s', 'bulgarisation-for-woocommerce' ),
					'checkedFound'              => esc_html__( 'Checked: %1$s | Problematic: %2$s', 'bulgarisation-for-woocommerce' ),
					'fixAll'                    => esc_html__( 'Fix all', 'bulgarisation-for-woocommerce' ),
					'fixing'                    => esc_html__( 'Fixing...', 'bulgarisation-for-woocommerce' ),
					'fix'                       => esc_html__( 'Fix', 'bulgarisation-for-woocommerce' ),
					'edit'                      => esc_html__( 'Edit', 'bulgarisation-for-woocommerce' ),
					'problematic'               => esc_html__( 'Problematic', 'bulgarisation-for-woocommerce' ),
					'fixed'                     => esc_html__( 'Fixed', 'bulgarisation-for-woocommerce' ),
					'missingTargetPrice'        => esc_html__( 'Missing target price', 'bulgarisation-for-woocommerce' ),
					'ajaxError'                 => esc_html__( 'AJAX error', 'bulgarisation-for-woocommerce' ),
					'genericError'              => esc_html__( 'Error', 'bulgarisation-for-woocommerce' ),
					'noProblematicFound'        => esc_html__( 'No problematic prices found.', 'bulgarisation-for-woocommerce' ),
					'noProblematicRemaining'    => esc_html__( 'No problematic prices remain.', 'bulgarisation-for-woocommerce' ),
					'nothingToFix'              => esc_html__( 'There are no rows to fix.', 'bulgarisation-for-woocommerce' ),
					'fixAllProgress'            => esc_html__( 'Fixing all... %1$s / %2$s', 'bulgarisation-for-woocommerce' ),
					'fixAllSummary'             => esc_html__( 'Fixed: %1$s | Not fixed: %2$s', 'bulgarisation-for-woocommerce' ),
					'statusFixing'              => esc_html__( 'Fixing...', 'bulgarisation-for-woocommerce' ),
					'percentSuffix'             => "%",
				),
			)
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bulgarisation-for-woocommerce' ) );
		}

		$prices_include_tax = ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Mathematically impossible prices', 'bulgarisation-for-woocommerce' ); ?></h1>

			<p>
				<?php echo esc_html__( 'This tool scans products and shows only problematic prices.', 'bulgarisation-for-woocommerce' ); ?>
				<?php echo wp_kses_post( sprintf( __( 'You can learn more about this problem <a target="_blank" href="%s">here</a>.', 'bulgarisation-for-woocommerce' ), 'https://ddskalkulator.com/art/%D0%B7%D0%B0%D0%BA%D1%80%D1%8A%D0%B3%D0%BB%D1%8F%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F-%D0%BF%D1%80%D0%B8-%D0%B8%D0%B7%D1%87%D0%B8%D1%81%D0%BB%D1%8F%D0%B2%D0%B0%D0%BD%D0%B5-%D0%BD%D0%B0-%D0%B4%D0%B4%D1%81-%D0%BC%D0%B0%D1%82%D0%B5%D0%BC%D0%B0%D1%82%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%B8-%D0%BD%D0%B5%D0%B2%D1%8A%D0%B7%D0%BC%D0%BE%D0%B6%D0%BD%D0%B8-%D1%86%D0%B5%D0%BD%D0%B8' ) ); ?>
			</p>

			<p>
				<strong><?php echo esc_html__( 'Store setting:', 'bulgarisation-for-woocommerce' ); ?></strong>
				<?php echo esc_html( $prices_include_tax ? __( 'Prices are entered including tax', 'bulgarisation-for-woocommerce' ) : __( 'Prices are entered excluding tax', 'bulgarisation-for-woocommerce' ) ); ?>
			</p>

			<?php if ( ! $prices_include_tax ) : ?>
				<div class="notice notice-warning inline">
					<p><?php echo esc_html__( 'This check is mainly applicable when prices are entered including tax.', 'bulgarisation-for-woocommerce' ); ?></p>
				</div>
			<?php endif; ?>

			<p>
				<button type="button" class="button button-primary" id="woo-bg-start-scan"><?php echo esc_html__( 'Scan products', 'bulgarisation-for-woocommerce' ); ?></button>
				<button type="button" class="button button-secondary" id="woo-bg-fix-all" disabled><?php echo esc_html__( 'Fix all', 'bulgarisation-for-woocommerce' ); ?></button>
			</p>

			<div id="woo-bg-progress-wrap">
				<div id="woo-bg-progress-bar"></div>
			</div>

			<p id="woo-bg-progress-text">0%</p>
			<p id="woo-bg-scan-status"><?php echo esc_html__( 'Not started.', 'bulgarisation-for-woocommerce' ); ?></p>
			<p id="woo-bg-results-summary"></p>
			<p id="woo-bg-fix-summary"></p>

			<div id="woo-bg-results-wrap">
				<table class="widefat striped" id="woo-bg-results">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'ID', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Product', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Type', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'SKU', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Field', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Entered price', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Tax', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Nearest', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Down', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Up', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'bulgarisation-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'bulgarisation-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public static function ajax_scan_batch() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission.', 'bulgarisation-for-woocommerce' ),
				),
				403
			);
		}

		check_ajax_referer( self::SCAN_NONCE, 'nonce' );

		$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 1, min( 250, absint( $_POST['per_page'] ) ) ) : 100;

		$query = new \WP_Query(
			array(
				'post_type'              => array( 'product', 'product_variation' ),
				'post_status'            => array( 'publish', 'private', 'draft', 'pending' ),
				'posts_per_page'         => $per_page,
				'paged'                  => $page,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$rows = array();

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					continue;
				}

				$product_rows = self::get_product_impossible_price_rows( $product );

				if ( ! empty( $product_rows ) ) {
					$rows = array_merge( $rows, $product_rows );
				}
			}
		}

		$total_pages      = (int) $query->max_num_pages;
		$scanned_in_batch = count( $query->posts );
		$done             = ( $page >= $total_pages || 0 === $total_pages );

		wp_send_json_success(
			array(
				'page'             => $page,
				'total_pages'      => $total_pages,
				'scanned_in_batch' => $scanned_in_batch,
				'rows'             => $rows,
				'done'             => $done,
			)
		);
	}

	public static function ajax_fix_price() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission.', 'bulgarisation-for-woocommerce' ),
				),
				403
			);
		}

		check_ajax_referer( self::FIX_NONCE, 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$price_key    = isset( $_POST['price_key'] ) ? sanitize_key( wp_unslash( $_POST['price_key'] ) ) : '';
		$target_price = isset( $_POST['target_price'] ) ? wc_format_decimal( wp_unslash( $_POST['target_price'] ), 2 ) : '';

		if ( ! $product_id || ! in_array( $price_key, array( 'regular_price', 'sale_price' ), true ) || '' === $target_price ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid data.', 'bulgarisation-for-woocommerce' ),
				),
				400
			);
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'The product was not found.', 'bulgarisation-for-woocommerce' ),
				),
				404
			);
		}

		$recheck = null;

		if ( 'regular_price' === $price_key ) {
			$recheck = self::check_product_price_for_impossibility( $product, $product->get_regular_price(), 'regular_price', esc_html__( 'Regular price', 'bulgarisation-for-woocommerce' ) );
		} elseif ( 'sale_price' === $price_key ) {
			$recheck = self::check_product_price_for_impossibility( $product, $product->get_sale_price(), 'sale_price', esc_html__( 'Sale price', 'bulgarisation-for-woocommerce' ) );
		}

		if ( ! $recheck ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'The price is no longer problematic.', 'bulgarisation-for-woocommerce' ),
				),
				400
			);
		}

		if ( $recheck['nearest_raw'] !== number_format( (float) $target_price, 2, '.', '' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'The target price no longer matches.', 'bulgarisation-for-woocommerce' ),
				),
				400
			);
		}

		$target_price_float = (float) $target_price;
		$regular_price      = $product->get_regular_price();
		$sale_price         = $product->get_sale_price();

		if ( 'regular_price' === $price_key ) {
			$product->set_regular_price( $target_price );
			$regular_price = $target_price;
		} else {
			$product->set_sale_price( $target_price );
			$sale_price = $target_price;
		}

		$adjustment_message = self::adjust_sale_price_after_fix( $product, $regular_price, $sale_price );

		$product->save();

		$product      = wc_get_product( $product_id );
		$current_rows = $product ? self::get_product_impossible_price_rows( $product ) : array();

		wp_send_json_success(
			array(
				'message'            => esc_html__( 'The price was fixed.', 'bulgarisation-for-woocommerce' ),
				'product_id'         => $product_id,
				'price_key'          => $price_key,
				'target_price_raw'   => number_format( $target_price_float, 2, '.', '' ),
				'target_price_display' => self::format_display_decimal( $target_price_float ),
				'adjustment_message' => $adjustment_message,
				'new_regular_price'  => ( $product && '' !== $product->get_regular_price() ) ? self::format_display_decimal( $product->get_regular_price() ) : '',
				'new_sale_price'     => ( $product && '' !== $product->get_sale_price() ) ? self::format_display_decimal( $product->get_sale_price() ) : '',
				'current_rows'       => $current_rows,
			)
		);
	}

	protected static function get_product_impossible_price_rows( \WC_Product $product ) {
		$rows   = array();
		$checks = array();

		$regular = self::check_product_price_for_impossibility(
			$product,
			$product->get_regular_price(),
			'regular_price',
			esc_html__( 'Regular price', 'bulgarisation-for-woocommerce' )
		);

		if ( $regular ) {
			$checks[] = $regular;
		}

		$sale = self::check_product_price_for_impossibility(
			$product,
			$product->get_sale_price(),
			'sale_price',
			esc_html__( 'Sale price', 'bulgarisation-for-woocommerce' )
		);

		if ( $sale ) {
			$checks[] = $sale;
		}

		if ( empty( $checks ) ) {
			return $rows;
		}

		foreach ( $checks as $check ) {
			$rows[] = array(
				'product_id'           => $product->get_id(),
				'name'                 => $product->get_name(),
				'type'                 => $product->get_type(),
				'sku'                  => $product->get_sku(),
				'price_key'            => $check['price_key'],
				'price_label'          => $check['label'],
				'entered_price_raw'    => $check['entered_price_raw'],
				'entered_price_display'=> $check['entered_price_display'],
				'tax_percent_raw'      => $check['tax_percent_raw'],
				'tax_percent_display'  => $check['tax_percent_display'],
				'nearest_raw'          => $check['nearest_raw'],
				'nearest_display'      => $check['nearest_display'],
				'nearest_down_raw'     => $check['nearest_down_raw'],
				'nearest_down_display' => $check['nearest_down_display'],
				'nearest_up_raw'       => $check['nearest_up_raw'],
				'nearest_up_display'   => $check['nearest_up_display'],
				'edit_link'            => get_edit_post_link( $product->get_id(), '' ),
			);
		}

		return $rows;
	}

	protected static function adjust_sale_price_after_fix( \WC_Product $product, $regular_price, $sale_price ) {
		$regular_price = '' === (string) $regular_price ? '' : wc_format_decimal( $regular_price, 2 );
		$sale_price    = '' === (string) $sale_price ? '' : wc_format_decimal( $sale_price, 2 );

		if ( '' === $sale_price || '' === $regular_price ) {
			return '';
		}

		$regular_float = (float) $regular_price;
		$sale_float    = (float) $sale_price;

		if ( $sale_float < $regular_float ) {
			return '';
		}

		$tax_percent = self::get_single_tax_rate_percent_for_product( $product );

		if ( null === $tax_percent ) {
			$product->set_sale_price( '' );
			return esc_html__( 'The sale price was cleared because it was greater than or equal to the regular price.', 'bulgarisation-for-woocommerce' );
		}

		$new_sale = self::get_nearest_possible_gross_price_below_limit( $regular_float, $tax_percent );

		if ( '' === $new_sale ) {
			$product->set_sale_price( '' );
			return esc_html__( 'The sale price was cleared because it was greater than or equal to the regular price.', 'bulgarisation-for-woocommerce' );
		}

		$new_sale_float = (float) $new_sale;

		if ( $new_sale_float >= $regular_float ) {
			$product->set_sale_price( '' );
			return esc_html__( 'The sale price was cleared because it was greater than or equal to the regular price.', 'bulgarisation-for-woocommerce' );
		}

		$product->set_sale_price( $new_sale );

		return sprintf(
			/* translators: %s: sale price */
			esc_html__( 'The sale price was adjusted to %s so it stays below the regular price.', 'bulgarisation-for-woocommerce' ),
			self::format_display_decimal( $new_sale_float )
		);
	}

	protected static function check_product_price_for_impossibility( \WC_Product $product, $price, $price_key, $label ) {
		$price = (string) $price;

		if ( '' === $price ) {
			return null;
		}

		$prices_include_tax = ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) && wc_tax_enabled();

		if ( $prices_include_tax ) {
			$tax_percent = self::get_single_tax_rate_percent_for_product( $product );

			if ( null === $tax_percent ) {
				return null;
			}
		} else {
			$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
			$vat_percentages     = woo_bg_get_vat_groups();
			$tax_percent = ( isset( $vat_percentages[ $vat_group ] ) ) ? $vat_percentages[ $vat_group ] : 0;
		}

		if ( self::is_gross_price_mathematically_possible( $price, $tax_percent ) ) {
			return null;
		}

		$entered_price = (float) wc_format_decimal( $price, 2 );
		$nearest       = self::get_nearest_possible_gross_price( $price, $tax_percent, 'nearest' );
		$nearest_down  = self::get_nearest_possible_gross_price( $price, $tax_percent, 'down' );
		$nearest_up    = self::get_nearest_possible_gross_price( $price, $tax_percent, 'up' );

		return array(
			'price_key'             => $price_key,
			'label'                 => $label,
			'entered_price_raw'     => number_format( $entered_price, 2, '.', '' ),
			'entered_price_display' => self::format_display_decimal( $entered_price ),
			'tax_percent_raw'       => number_format( (float) $tax_percent, 2, '.', '' ),
			'tax_percent_display'   => self::format_display_decimal( $tax_percent ),
			'nearest_raw'           => $nearest,
			'nearest_display'       => '' !== $nearest ? self::format_display_decimal( $nearest ) : '',
			'nearest_down_raw'      => $nearest_down,
			'nearest_down_display'  => '' !== $nearest_down ? self::format_display_decimal( $nearest_down ) : '',
			'nearest_up_raw'        => $nearest_up,
			'nearest_up_display'    => '' !== $nearest_up ? self::format_display_decimal( $nearest_up ) : '',
		);
	}

	protected static function get_single_tax_rate_percent_for_product( \WC_Product $product ) {
		if ( ! wc_tax_enabled() ) {
			return 0.0;
		}

		if ( 'taxable' !== $product->get_tax_status() ) {
			return 0.0;
		}

		$tax_class = $product->get_tax_class();
		$rates     = \WC_Tax::get_rates( $tax_class );

		if ( empty( $rates ) ) {
			return 0.0;
		}

		$percents = array();

		foreach ( $rates as $rate ) {
			if ( isset( $rate['rate'] ) ) {
				$percents[] = (float) $rate['rate'];
			}
		}

		$percents = array_unique( $percents );

		if ( 1 !== count( $percents ) ) {
			return null;
		}

		return (float) reset( $percents );
	}

	protected static function is_gross_price_mathematically_possible( $gross_price, $tax_percent ) {
		$gross_price = (float) wc_format_decimal( $gross_price, 2 );
		$tax_percent = (float) $tax_percent;

		if ( $gross_price < 0 ) {
			return false;
		}

		if ( $tax_percent <= 0 ) {
			return true;
		}

		$multiplier    = 1 + ( $tax_percent / 100 );
		$estimated_net = $gross_price / $multiplier;
		$start         = max( 0, (int) floor( ( $estimated_net - 1 ) * 100 ) );
		$end           = (int) ceil( ( $estimated_net + 1 ) * 100 );

		for ( $i = $start; $i <= $end; $i++ ) {
			$net        = $i / 100;
			$calc_gross = round( $net * $multiplier, 2 );

			if ( abs( $calc_gross - $gross_price ) < 0.00001 ) {
				return true;
			}
		}

		return false;
	}

	protected static function get_nearest_possible_gross_price( $gross_price, $tax_percent, $direction = 'nearest' ) {
		$gross_price = (float) wc_format_decimal( $gross_price, 2 );
		$tax_percent = (float) $tax_percent;

		if ( $tax_percent <= 0 ) {
			return number_format( $gross_price, 2, '.', '' );
		}

		$multiplier          = 1 + ( $tax_percent / 100 );
		$estimated_net_cents = (int) round( ( $gross_price / $multiplier ) * 100 );
		$candidates          = array();

		for ( $offset = -300; $offset <= 300; $offset++ ) {
			$net_cents = $estimated_net_cents + $offset;

			if ( $net_cents < 0 ) {
				continue;
			}

			$net             = $net_cents / 100;
			$candidate_gross = round( $net * $multiplier, 2 );
			$key             = number_format( $candidate_gross, 2, '.', '' );

			$candidates[ $key ] = (float) $key;
		}

		if ( empty( $candidates ) ) {
			return '';
		}

		$candidates = array_values( $candidates );
		sort( $candidates, SORT_NUMERIC );

		if ( 'down' === $direction ) {
			$best = null;

			foreach ( $candidates as $candidate ) {
				if ( $candidate <= $gross_price ) {
					$best = $candidate;
				}
			}

			return null === $best ? '' : number_format( $best, 2, '.', '' );
		}

		if ( 'up' === $direction ) {
			foreach ( $candidates as $candidate ) {
				if ( $candidate >= $gross_price ) {
					return number_format( $candidate, 2, '.', '' );
				}
			}

			return '';
		}

		$best      = null;
		$best_diff = null;

		foreach ( $candidates as $candidate ) {
			$diff = abs( $candidate - $gross_price );

			if ( null === $best_diff || $diff < $best_diff ) {
				$best      = $candidate;
				$best_diff = $diff;
				continue;
			}

			if ( abs( $diff - $best_diff ) < 0.00001 && $candidate > $best ) {
				$best = $candidate;
			}
		}

		return null === $best ? '' : number_format( $best, 2, '.', '' );
	}

	protected static function get_nearest_possible_gross_price_below_limit( $limit_gross_price, $tax_percent ) {
		$limit_gross_price = (float) wc_format_decimal( $limit_gross_price, 2 );
		$tax_percent       = (float) $tax_percent;

		if ( $limit_gross_price <= 0 ) {
			return '';
		}

		if ( $tax_percent <= 0 ) {
			$candidate = round( $limit_gross_price - 0.01, 2 );
			return $candidate >= 0 ? number_format( $candidate, 2, '.', '' ) : '';
		}

		$multiplier          = 1 + ( $tax_percent / 100 );
		$estimated_net_cents = (int) floor( ( $limit_gross_price / $multiplier ) * 100 );
		$best                = null;

		for ( $offset = -300; $offset <= 300; $offset++ ) {
			$net_cents = $estimated_net_cents + $offset;

			if ( $net_cents < 0 ) {
				continue;
			}

			$net             = $net_cents / 100;
			$candidate_gross = round( $net * $multiplier, 2 );

			if ( $candidate_gross < $limit_gross_price ) {
				if ( null === $best || $candidate_gross > $best ) {
					$best = $candidate_gross;
				}
			}
		}

		if ( null === $best ) {
			return '';
		}

		return number_format( $best, 2, '.', '' );
	}

	protected static function format_display_decimal( $number ) {
		return wc_format_localized_decimal( wc_format_decimal( $number, 2 ) );
	}
}