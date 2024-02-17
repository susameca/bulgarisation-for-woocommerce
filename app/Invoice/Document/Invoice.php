<?php
namespace Woo_BG\Invoice\Document;
use \Clegginabox\PDFMerger\PDFMerger;

defined( 'ABSPATH' ) || exit;

class Invoice extends BaseDocument {
	public $title;

	function __construct( $order ) {
		parent::__construct( $order );

		$this->set_title( apply_filters( 'woo_bg/admin/invoice/invoice_title', __( 'Sale Invoice - Original', 'woo-bg' ) ) );
		$this->meta = 'woo_bg_invoice_document';
	}

	public function generate_file() {
		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$upload_dir = wp_upload_dir();

		//Original Invoice Generation
		$original_invoice = $upload_dir['path'] . '/original.pdf';
		file_put_contents( $original_invoice, $this->pdf->generate() );

		//Copy Invoice Generation
		$copy_invoice = $upload_dir['path'] . '/copy.pdf';
		$this->set_title( apply_filters( 'woo_bg/admin/invoice/invoice_title_copy', __( 'Sale Invoice - Copy', 'woo-bg' ) ) );
		file_put_contents( $copy_invoice, $this->pdf->generate() );

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