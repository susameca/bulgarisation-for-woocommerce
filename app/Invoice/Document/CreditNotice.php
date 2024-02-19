<?php
namespace Woo_BG\Invoice\Document;
use Woo_BG\Invoice\Order\Order;
use \Automattic\WooCommerce\Admin\Overrides\OrderRefund;
use \Clegginabox\PDFMerger\PDFMerger;
use Woo_BG\File;

defined( 'ABSPATH' ) || exit;

class CreditNotice extends NRARefunded {
	public $title, $parent_order, $return_methods, $return_method;

	function __construct( $refund_id ) {
		parent::__construct( $refund_id );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/credit_notice_title', __( 'Credit Notice - Original', 'woo-bg' ) ) );
		$this->meta = 'woo_bg_refunded_invoice_document';
	}

	public function get_head_items() {
		return apply_filters( 'woo_bg/invoice/head_items', array(
			'parent_reference' => array(
				'label' => __('To invoice №', 'woo-bg'),
				'value' => str_pad( $this->parent_order->get_meta( 'woo_bg_order_number' ), 10, '0', STR_PAD_LEFT ),
			),
			'reference' => array(
				'label' => '№',
				'value' => str_pad( $this->get_document_number(), 10, '0', STR_PAD_LEFT ),
			),
			'date' => array(
				'label' => __( 'Order date', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'date-of-tax' => array(
				'label' => __( 'Date of tax. event', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'due-date' => array(
				'label' => __( 'Due date', 'woo-bg' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->woo_order->get_date_created() ) ),
			),
			'address' => array(
				'label' => __( 'Place of transaction', 'woo-bg' ),
				'value' => $this->city,
			),
			'order-number' => array(
				'label' => __( 'Order Number', 'woo-bg' ),
				'value' => $this->parent_order->get_order_number(),
			),
		), $this );
	}

	public function generate_file() {
		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$upload_dir = wp_upload_dir();

		//Original Invoice Generation
		$original_invoice = $upload_dir['path'] . '/original.pdf';
		File::put_to_file( $original_invoice, $this->pdf->generate() );

		//Copy Invoice Generation
		$copy_invoice = $upload_dir['path'] . '/copy.pdf';
		$this->set_title( apply_filters( 'woo_bg/admin/invoice/credit_notice_title_copy', __( 'Credit Notice - Copy', 'woo-bg' ) ) );
		File::put_to_file( $copy_invoice, $this->pdf->generate() );

		// Merge both PDF's
		$merger = new PDFMerger();
		$merger->addPDF( $original_invoice );
		$merger->addPDF( $copy_invoice );

		//Upload single document
		$name = apply_filters( 'woo_bg/admin/invoice/file_name', uniqid( wp_rand(), true ), $this );
		$pdf = wp_upload_bits( $name . '.pdf', null, $merger->merge( 'string' ) );
		remove_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );

		//Delete both PDF files
		wp_delete_file( $original_invoice );
		wp_delete_file( $copy_invoice );

		remove_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );

		if ( is_wp_error( $pdf ) ) {
			return;
		}

		$attach_id = wp_insert_attachment( array(
			'guid' => $pdf[ 'file' ], 
			'post_mime_type' => $pdf['type'],
			'post_title' => $name,
			'post_content' => '',
			'post_status' => 'inherit'
		), $pdf[ 'file' ] );

		$this->woo_order->update_meta_data( $this->meta, $attach_id );
		$this->woo_order->save();
		
		wp_delete_attachment( $this->qr_png, 1 );
	}
}