<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Order\Documents;
use Woo_BG\Export\Nra\Export as NraExport;
use Woo_BG\Export\Delta\Export as DeltaExport;
use Woo_BG\Export\InvoiceArchive\Export as InvoiceArchiveExport;

defined( 'ABSPATH' ) || exit;

class Export_Tab extends Base_Tab {
	public function __construct() {
		$this->set_name( __( 'Export', 'bulgarisation-for-woocommerce' ) );
		$this->set_description( __( 'Exporting XML', 'bulgarisation-for-woocommerce' ) );
		$this->set_tab_slug( "export" );
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<?php if ( woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ): ?>
			<div id="woo-bg-exports"></div><!-- /#woo-bg-export -->
		<?php endif ?>

		<div id="woo-bg-exports--invoice-archive"></div><!-- /#woo-bg-export -->

		<div id="woo-bg-exports--microinvest"></div><!-- /#woo-bg-export -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_export', array(
			'i18n' => $this->get_i18n(),
			'year' => gmdate( 'Y-m', strtotime( 'today - 1 month' ) ),
			'nonce' => wp_create_nonce( 'woo_bg_export_nap' ),
		) );
	}

	public function get_i18n() {
		return array(
			'description' => $this->get_description(),
			'microinvestDescription' => $this->get_microinvest_description(),
			'invoiceArchiveDescription' => $this->get_invoice_archive_description(),
			'choose_month' => __( 'Select a month for the report', 'bulgarisation-for-woocommerce' ),
			'generated_files' => __( 'Generate documents for orders before the plugin was installed?', 'bulgarisation-for-woocommerce' ),
			'download' => __( 'Generate an XML file', 'bulgarisation-for-woocommerce' ),
			'export_documents' => __( 'Export documents', 'bulgarisation-for-woocommerce' ),
		);
	}

	public function get_microinvest_description() {
		ob_start();
		?>
		<h2><?php esc_html_e('Export documents for Microinvest', 'bulgarisation-for-woocommerce' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public function get_invoice_archive_description() {
		ob_start();
		?>
		<h2><?php esc_html_e('Export archive with invoices for selected month', 'bulgarisation-for-woocommerce' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public function get_description() {
		ob_start();
		?>
		<h2><?php esc_html_e('Standardized audit file according to Annex № 38', 'bulgarisation-for-woocommerce' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public static function woo_bg_export_nap_callback() {
		if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['nonce'] ) ), 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$export = new NraExport( 
			sanitize_text_field( $_REQUEST[ 'year' ] ), 
			sanitize_text_field( $_REQUEST[ 'generate_files' ] ) 
		);
		
		$generated_file = $export->get_xml_file();

		if ( !$generated_file ) {
			wp_send_json_success( array(
				"message" => __( 'No orders found for this month.', 'bulgarisation-for-woocommerce' ),
			) );
		}

		$additional_message = '';

		if ( !empty( $generated_file['not_included_orders'] ) ) {
			$additional_message = '. ' . sprintf( 
				__( 'Some of the orders was not included because the payment method options was not found or the payment method was disabled. List of orders id\'s: %s', 'bulgarisation-for-woocommerce' ), 
				implode(', ', $generated_file['not_included_orders'] ) 
			);
		}

		if ( !empty( $generated_file['totals'] ) ) {
			$additional_message .= "<br><br>" . $generated_file['totals'];
		}

		if ( !empty( $generated_file['errors'] ) ) {
			ob_start();
			?>
			<h2><?php esc_html_e( 'Found errors in file:', 'bulgarisation-for-woocommerce' ) ?></h2>
			<ul>
				<?php foreach ( $generated_file['errors'] as $error ): ?>
					<li><?php echo wp_kses_post( $error ) ?></li>
				<?php endforeach ?>
			</ul>
			<?php
			$additional_message .= "<br><br>" . ob_get_clean();
		}

		$additional_message = apply_filters( 'woo_bg/admin/export/nra/additional_message', $additional_message, $export );
		
		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'bulgarisation-for-woocommerce' ), $generated_file['file'] ) . $additional_message,
		) );
	}


	public static function woo_bg_export_microinvest_callback() {
		if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['nonce'] ) ), 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$export = new DeltaExport( sanitize_text_field( $_REQUEST[ 'year' ] ) );
		$generated_file = $export->get_file();

		if ( !$generated_file ) {
			wp_send_json_success( array(
				"message" => __( 'No orders found for this month.', 'bulgarisation-for-woocommerce' ),
			) );
		}

		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'bulgarisation-for-woocommerce' ), $generated_file['file'] ),
		) );
	}

	public static function woo_bg_export_invoice_archive_callback() {
		if ( !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['nonce'] ) ), 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$export = new InvoiceArchiveExport( sanitize_text_field( $_REQUEST[ 'year' ] ) );
		$generated_file = $export->get_file();

		if ( is_wp_error( $generated_file ) ) {
			wp_send_json_error( $generated_file, 400 );
		} else if ( empty( $generated_file['documents'] ) ) {
			wp_send_json_success( array(
				"message" => __( 'No orders found for this month.', 'bulgarisation-for-woocommerce' ),
			) );
		}

		header('Content-type: application/zip');
		header('Content-Disposition: attachment; filename="' . $generated_file['file_name'] . '"');
		header("Content-length: " . filesize( $generated_file['temp_file'] ) );
		echo file_get_contents( $generated_file['temp_file'] );
	}
}
