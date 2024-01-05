<?php
namespace Woo_BG\Invoice\Document;

use Woo_BG\Invoice\Order\Order;
use Woo_BG\Invoice\PDF\PDF;
use Woo_BG\Image_Uploader;
use chillerlan\QRCode\QRCode;

defined( 'ABSPATH' ) || exit;

class BaseDocument {
	public $pdf, $woo_order, $nap_number, $company_name, $mol, $eik, $vat_number, $address, $city, $phone_number, $email, $color, $add_shipping, $prepared_by, $identification_code, $footer_text, $qr_png, $meta, $payment_method, $transaction_id, $title;

	function __construct( $order ) {
		$this->woo_order    = $order;
		$this->order        = new Order( $order );
		$this->company_name = woo_bg_get_option( 'nap', 'company_name' );
		$this->mol          = woo_bg_get_option( 'nap', 'mol' );
		$this->eik          = woo_bg_get_option( 'nap', 'eik' );
		$this->vat_number   = woo_bg_get_option( 'nap', 'dds_number' );
		$this->address      = woo_bg_get_option( 'nap', 'address' );
		$this->city         = woo_bg_get_option( 'nap', 'city' );
		$this->phone_number = woo_bg_get_option( 'nap', 'phone_number' );
		$this->email        = woo_bg_get_option( 'nap', 'email' );
		$this->nap_number   = woo_bg_get_option( 'nap', 'nap_number' );

		$this->color               = woo_bg_get_option( 'invoice', 'color' );
		$this->add_shipping        = woo_bg_get_option( 'invoice', 'add_shipping' );
		$this->prepared_by         = woo_bg_get_option( 'invoice', 'prepared_by' );
		$this->identification_code = woo_bg_get_option( 'invoice', 'identification_code' );
		$this->footer_text         = woo_bg_get_option( 'invoice', 'footer_text' );

		if ( ! is_a( $this->woo_order, '\Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) ) {
			$this->payment_method         = $this->woo_order->get_payment_method_title();
			$this->transaction_id         = $this->woo_order->get_transaction_id();
			$this->generate_QR_code();
		}

		$this->set_pdf_printer();

		if( ! preg_match('/^#[a-f0-9]{6}$/i', $this->color ) ){
			$this->color = '#007fff';
		}
	}

	public function get_head_items() {
		return apply_filters( 'woo_bg/invoice/head_items', array(
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
				'value' => $this->woo_order->get_order_number(),
			),
		), $this );
	}

	public function get_cart_headers() {
		return apply_filters( 'woo_bg/invoice/cart_headers', array(
			array(
				'label' => __( 'Product', 'woo-bg' ),
				'class' => 'w-50',
			),
			array(
				'label' => __( 'Qty', 'woo-bg' ),
				'class' => 'w-10',
			),
			array(
				'label' => __( 'Vat', 'woo-bg' ),
				'class' => 'w-10',
			),
			array(
				'label' => __( 'Price', 'woo-bg' ),
				'class' => 'w-15',
			),
			array(
				'label' => __( 'Total', 'woo-bg' ),
				'class' => 'w-15',
			),
		), $this );
	}

	public function get_from_items() {
		return apply_filters( 'woo_bg/invoice/from_items', array(
			'company_name' => $this->company_name,
			'eik' => sprintf( __('EIK: %s', 'woo-bg' ), $this->eik ),
			'vat_number' => ( $this->vat_number ) ? sprintf( __('VAT Number: %s', 'woo-bg' ), $this->vat_number ) : '&nbsp;',
			'mol' => sprintf( __('MOL: %s', 'woo-bg' ), $this->mol ),
			'address' => $this->address,
			'city' => $this->city,
			'space' => '&nbsp;',
			'phone' => sprintf( __('Phone: %s', 'woo-bg' ), $this->phone_number ),
			'email' => sprintf( __('E-mail: %s', 'woo-bg' ), $this->email ),
		), $this );
	}

	public function get_to_items() {
		if ( $this->woo_order->get_meta('_billing_to_company') === '1' ) {
			$items = apply_filters( 'woo_bg/invoice/set_to_company', array(
				$this->woo_order->get_billing_company(),
				sprintf( __('EIK: %s', 'woo-bg' ), $this->woo_order->get_meta('_billing_company_eik') ),
				sprintf( __('VAT Number: %s', 'woo-bg' ), $this->woo_order->get_meta('_billing_vat_number') ),
				sprintf( __('MOL: %s', 'woo-bg' ), $this->woo_order->get_meta('_billing_company_mol') ),
				$this->woo_order->get_meta('_billing_company_address'),
				$this->woo_order->get_meta('_billing_company_settlement'),
				'&nbsp;',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->woo_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->woo_order->get_billing_email() ),
			), $this->woo_order );
		} else {
			$items = apply_filters( 'woo_bg/invoice/set_to_person', array(
				$this->woo_order->get_billing_first_name() . ' ' . $this->woo_order->get_billing_last_name(),
				$this->woo_order->get_billing_address_1() . " " . $this->woo_order->get_billing_address_2(),
				$this->woo_order->get_billing_city() .", " . $this->woo_order->get_billing_postcode(),
				'&nbsp;',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->woo_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->woo_order->get_billing_email() ),
			), $this->woo_order );
		}

		return apply_filters( 'woo_bg/invoice/to_items', $items, $this );
	}

	public function get_additional_items_labels() {
		$items = array();

		if ( $this->payment_method ) {
			$items[] = __( 'Payment method', 'woo-bg' );
		}

		if ( $this->woo_order->get_payment_method() === 'bacs' ) {
			$items[] = __( 'Bank account', 'woo-bg' );
		}

		if ( $this->transaction_id ) {
			$items[] = __( 'Transaction ID', 'woo-bg' );
		}

		$items[] = __( 'Compiled by', 'woo-bg' );
		$items[] = __( 'Received', 'woo-bg' );

		return apply_filters( 'woo_bg/invoice/additional_items_labels', $items, $this );
	}

	public function get_additional_items() {
		$items = array();

		if ( $this->payment_method ) {
			$items[] = $this->payment_method;
		}

		if ( $this->woo_order->get_payment_method() === 'bacs' ) {
			$bacs_info  = get_option( 'woocommerce_bacs_accounts');

			ob_start();

			foreach ( $bacs_info as $account ) {
				?>
				<strong><?php _e( 'Bank name', 'woo-bg' ) ?>:</strong> <?php echo $account['bank_name'] ?> <br>
				<strong>IBAN:</strong> <?php echo $account['iban'] ?> <br>
				<strong>BIC:</strong> <?php echo $account['bic'] ?> <br>
				<?php
			}

			$items[] = ob_get_clean();
		}

		if ( $this->transaction_id ) {
			$items[] = $this->transaction_id;
		}

		$items[] = $this->prepared_by . " " . $this->identification_code;
		$items[] = $this->woo_order->get_billing_first_name() . ' ' . $this->woo_order->get_billing_last_name();

		return apply_filters( 'woo_bg/invoice/additional_items', $items, $this );
	}

	public function get_document_number() {
		$document_number = $this->woo_order->get_meta( 'woo_bg_order_number' );

		if ( !$document_number ) {
			$document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $document_number + 1, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->update_meta_data( 'woo_bg_order_number', str_pad( $document_number, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->save();
		}

		return $document_number;
	}

	private function set_pdf_printer() {
		$this->pdf = new PDF( $this );
	}

	public function set_title( $title ) {
		$this->title = $title;
	}

	public function generate_QR_code() {
		$qr_code_pieces = array(
			$this->nap_number,
			$this->woo_order->get_order_number(),
			$this->woo_order->get_transaction_id(),
			$this->woo_order->get_date_created()->date_i18n('Y-m-d'),
			$this->woo_order->get_date_created()->date_i18n('g:i:s'),
			$this->woo_order->get_total(),
		);

		$qr_code = new QRCode();

		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );

		$this->qr_png = Image_Uploader::upload_image_from_base64( array(
			'data' => $qr_code->render( implode( '*', $qr_code_pieces ) ),
			'type' => 'image/png',
		), 'qrcode' );

		remove_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
	}

	public function generate_file() {
		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$name = apply_filters( 'woo_bg/admin/invoice/file_name', uniqid( rand(), true ), $this );
		$pdf = wp_upload_bits( $name . '.pdf', null, $this->pdf->generate() );
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