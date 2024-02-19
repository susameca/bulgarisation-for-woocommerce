<?php
namespace Woo_BG\Admin\Tabs;

use Woo_BG\Admin\Order\Documents;
use Woo_BG\Export\Nra\Export as NraExport;
use Woo_BG\Export\Delta\Export as DeltaExport;

defined( 'ABSPATH' ) || exit;

class Export_Tab extends Base_Tab {
	public function __construct() {
		$this->set_name( __( 'Export', 'woo-bg' ) );
		$this->set_description( __( 'Exporting XML', 'woo-bg' ) );
		$this->set_tab_slug( "export" );
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<?php if ( woo_bg_get_option( 'invoice', 'nra_n18' ) === 'yes' ): ?>
			<div id="woo-bg-exports"></div><!-- /#woo-bg-export -->
		<?php endif ?>
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
			'choose_month' => __( 'Select a month for the report', 'woo-bg' ),
			'generated_files' => __( 'Generate documents for orders before the plugin was installed?', 'woo-bg' ),
			'download' => __( 'Generate an XML file', 'woo-bg' ),
			'export_documents' => __( 'Export documents', 'woo-bg' ),
		);
	}

	public function get_microinvest_description() {
		ob_start();
		?>
		<h2><?php esc_html_e('Export documents for Microinvest', 'woo-bg' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public function get_description() {
		ob_start();
		?>
		<h2><?php esc_html_e('Standardized audit file according to Annex № 38', 'woo-bg' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public static function woo_bg_export_nap_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$export = new NraExport( 
			sanitize_text_field( $_REQUEST[ 'year' ] ), 
			sanitize_text_field( $_REQUEST[ 'generate_files' ] ) 
		);
		
		$generated_file = $export->get_xml_file();

		if ( !$generated_file ) {
			wp_send_json_success( array(
				"message" => __( 'No orders found for this month.', 'woo-bg' ),
			) );
		}

		$additional_message = '';

		if ( !empty( $generated_file['not_included_orders'] ) ) {
			$additional_message = '. ' . sprintf( 
				__( 'Some of the orders was not included because the payment method options was not found or the payment method was disabled. List of orders id\'s: %s', 'woo-bg' ), 
				implode(', ', $generated_file['not_included_orders'] ) 
			);
		}

		if ( !empty( $generated_file['totals'] ) ) {
			$additional_message .= "<br><br>" . $generated_file['totals'];
		}

		$additional_message = apply_filters( 'woo_bg/admin/export/nra/additional_message', $additional_message, $export );
		
		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'woo-bg' ), $generated_file['file'] ) . $additional_message,
		) );
	}


	public static function woo_bg_export_microinvest_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$export = new DeltaExport( sanitize_text_field( $_REQUEST[ 'year' ] ) );
		$generated_file = $export->get_file();

		if ( !$generated_file ) {
			wp_send_json_success( array(
				"message" => __( 'No orders found for this month.', 'woo-bg' ),
			) );
		}

		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'woo-bg' ), $generated_file['file'] ),
		) );
	}
}
