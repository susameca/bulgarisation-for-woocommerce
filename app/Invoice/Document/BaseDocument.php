<?php
namespace Woo_BG\Invoice\Document;

use Woo_BG\Invoice\Order\Order;
use Woo_BG\Invoice\PDF\PDF;

defined( 'ABSPATH' ) || exit;

class BaseDocument {
	public $order, $pdf, $woo_order, $nap_number, $company_name, $mol, $eik, $vat_number, $address, $city, $phone_number, $email, $color, $add_shipping, $prepared_by, $identification_code, $footer_text, $meta, $payment_method, $transaction_id, $title, $document_number_meta, $document_number_option, $logo;

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

		$this->document_number_meta = 'woo_bg_order_number';
		$this->document_number_option = 'next_invoice_number';

		$this->color               = woo_bg_get_option( 'invoice', 'color' );
		$this->add_shipping        = woo_bg_get_option( 'invoice', 'add_shipping' );
		$this->prepared_by         = woo_bg_get_option( 'invoice', 'prepared_by' );
		$this->identification_code = woo_bg_get_option( 'invoice', 'identification_code' );
		$this->footer_text         = woo_bg_get_option( 'invoice', 'footer_text' );

		if ( ! is_a( $this->woo_order, '\Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) ) {
			$this->payment_method         = $this->woo_order->get_payment_method_title();
			$this->transaction_id         = $this->woo_order->get_transaction_id();
		}

		$this->set_pdf_printer();

		if( ! preg_match('/^#[a-f0-9]{6}$/i', $this->color ) ){
			$this->color = '#007fff';
		}
	}

	public function get_head_items() {
		$items = array();

		if ( $document_number = $this->get_document_number() ) {
			$items[ 'reference' ] = array(
				'label' => '№',
				'value' => str_pad( $document_number, 10, '0', STR_PAD_LEFT ),
			);
		}

		$items = array_merge( $items, array(
			'date' => array(
				'label' => __( 'Order date', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->get_document_date() ) ),
			),
			'date-of-tax' => array(
				'label' => __( 'Date of tax. event', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->get_document_date() ) ),
			),
			'due-date' => array(
				'label' => __( 'Due date', 'bulgarisation-for-woocommerce' ),
				'value' => date_i18n( 'M d, Y', strtotime( $this->get_document_due_date() ) ),
			),
			'address' => array(
				'label' => __( 'Place of transaction', 'bulgarisation-for-woocommerce' ),
				'value' => $this->city,
			),
			'order-number' => array(
				'label' => __( 'Order Number', 'bulgarisation-for-woocommerce' ),
				'value' => $this->woo_order->get_order_number(),
			),
		) );

		return apply_filters( 'woo_bg/invoice/head_items', $items , $this );
	}

	public function get_cart_headers() {
		return apply_filters( 'woo_bg/invoice/cart_headers', array(
			array(
				'label' => __( 'Product', 'bulgarisation-for-woocommerce' ),
				'class' => '',
			),
			array(
				'label' => __( 'Qty', 'bulgarisation-for-woocommerce' ),
				'class' => '',
			),
			array(
				'label' => __( 'Vat', 'bulgarisation-for-woocommerce' ),
				'class' => '',
			),
			array(
				'label' => __( 'Price', 'bulgarisation-for-woocommerce' ),
				'class' => '',
			),
			array(
				'label' => __( 'Total amount', 'bulgarisation-for-woocommerce' ),
				'class' => '',
			),
		), $this );
	}

	public function get_from_items() {
		return apply_filters( 'woo_bg/invoice/from_items', array(
			'company_name' => $this->company_name,
			'eik' => sprintf( __('EIK: %s', 'bulgarisation-for-woocommerce' ), $this->eik ),
			'vat_number' => ( $this->vat_number ) ? sprintf( __('VAT Number: %s', 'bulgarisation-for-woocommerce' ), $this->vat_number ) : '&nbsp;',
			'mol' => sprintf( __('MOL: %s', 'bulgarisation-for-woocommerce' ), $this->mol ),
			'address' => $this->address,
			'city' => $this->city,
			'space' => '&nbsp;',
			'phone' => sprintf( __('Phone: %s', 'bulgarisation-for-woocommerce' ), $this->phone_number ),
			'email' => sprintf( __('E-mail: %s', 'bulgarisation-for-woocommerce' ), $this->email ),
		), $this );
	}

	public function get_to_items() {
		if ( $this->woo_order->get_meta('_billing_to_company') === '1' ) {
			$items = [
				$this->woo_order->get_billing_company(),
				sprintf( __('EIK: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_meta('_billing_company_eik') ),
			];

			if ( $this->woo_order->get_meta('_billing_vat_number') ) {
				$items[] = sprintf( __('VAT Number: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_meta('_billing_vat_number') );
			}

			if ( $this->woo_order->get_meta('_billing_company_mol') ) {
				$items[] = sprintf( __('MOL: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_meta('_billing_company_mol') );
			}

			$items[] = $this->woo_order->get_meta('_billing_company_address');
			$items[] = $this->woo_order->get_meta('_billing_company_settlement');
			$items[] = '&nbsp;';
			$items[] = sprintf( __('Phone: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_billing_phone() );
			$items[] = sprintf( __('E-mail: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_billing_email() );

			$items = apply_filters( 'woo_bg/invoice/set_to_company', $items, $this->woo_order );
		} else {
			$items = apply_filters( 'woo_bg/invoice/set_to_person', array(
				$this->woo_order->get_billing_first_name() . ' ' . $this->woo_order->get_billing_last_name(),
				$this->woo_order->get_billing_address_1() . " " . $this->woo_order->get_billing_address_2(),
				$this->woo_order->get_billing_city() .", " . $this->woo_order->get_billing_postcode(),
				'&nbsp;',
				sprintf( __('Phone: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'bulgarisation-for-woocommerce' ), $this->woo_order->get_billing_email() ),
			), $this->woo_order );
		}

		return apply_filters( 'woo_bg/invoice/to_items', $items, $this );
	}

	public function get_additional_items_labels() {
		$items = array();

		if ( $this->payment_method ) {
			$items[] = __( 'Payment method', 'bulgarisation-for-woocommerce' );
		}

		if ( 
			method_exists( $this->woo_order , 'get_payment_method' ) &&
			$this->woo_order->get_payment_method() === 'bacs' 
		) {
			$items[] = __( 'Bank account', 'bulgarisation-for-woocommerce' );
		}

		if ( $this->transaction_id ) {
			$items[] = __( 'Transaction ID', 'bulgarisation-for-woocommerce' );
		}

		if ( woo_bg_get_option( 'apis', 'enable_multi_currency' ) === 'yes' ) {
			$items[] = str_replace(':', '', __( 'Fixed conversion rate:', 'bulgarisation-for-woocommerce' ) );
		}

		if ( $this->prepared_by || $this->identification_code ) {
			$items[] = __( 'Compiled by', 'bulgarisation-for-woocommerce' );
		}

		$items[] = __( 'Received', 'bulgarisation-for-woocommerce' );

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
				<strong><?php esc_html_e( 'Bank name', 'bulgarisation-for-woocommerce' ) ?>:</strong> <?php echo esc_html( $account['bank_name'] ) ?> <br>
				<strong>IBAN:</strong> <?php echo esc_html( $account['iban'] ) ?> <br>
				<strong>BIC:</strong> <?php echo esc_html( $account['bic'] ) ?> <br>
				<?php
			}

			$items[] = ob_get_clean();
		}

		if ( $this->transaction_id ) {
			$items[] = $this->transaction_id;
		}

		if ( woo_bg_get_option( 'apis', 'enable_multi_currency' ) === 'yes' ) {
			$items[] = __( '1 EUR = 1.95583 BGN', 'bulgarisation-for-woocommerce' );
		}

		if ( $this->prepared_by || $this->identification_code ) {
			$items[] = $this->prepared_by . " " . $this->identification_code;
		}
		$items[] = $this->woo_order->get_billing_first_name() . ' ' . $this->woo_order->get_billing_last_name();

		return apply_filters( 'woo_bg/invoice/additional_items', $items, $this );
	}

	public function get_document_number() {
		$document_number = $this->woo_order->get_meta( $this->document_number_meta );

		if ( !$document_number ) {
			$document_number = woo_bg_get_option( 'invoice', $this->document_number_option );

			if ( woo_bg_check_if_order_with_that_doc_number_exists( $document_number, $this->document_number_meta ) ) {
				$document_number = woo_bg_get_next_document_number( $this->document_number_meta );
			}

			woo_bg_set_option( 'invoice', $this->document_number_option, str_pad( $document_number + 1, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->update_meta_data( $this->document_number_meta, str_pad( $document_number, 10, '0', STR_PAD_LEFT ) );
			$this->woo_order->save();
		}

		return $document_number;
	}

	public function get_document_date() {
		return $this->woo_order->get_date_created();
	}

	public function get_document_due_date() {
		return $this->get_document_date();
	}

	private function set_pdf_printer() {
		$this->pdf = new PDF( $this );
	}

	public function set_title( $title ) {
		$this->title = $title;
	}

	public function after_file_generated() {}

	public function generate_file() {
		add_filter( 'upload_dir', array( 'Woo_BG\Image_Uploader', 'change_upload_dir' ) );
		$name = apply_filters( 'woo_bg/admin/invoice/file_name', uniqid( wp_rand(), true ), $this );
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

		if ( $old_file = $this->woo_order->get_meta( $this->meta ) ) {
			wp_delete_attachment( $old_file );
		}

		$this->woo_order->update_meta_data( $this->meta, $attach_id );
		$this->woo_order->save();

		$this->after_file_generated();
	}

	public function render_pdf_logo() {
		$logo = apply_filters( 'woo_bg/invoice/pdf/default_template/qr', $this->logo, $this );

		if ( !$logo ) {
			return;
		}

		echo wp_kses( $logo, 'post', array_merge( wp_allowed_protocols(), array( 'data' ) ) );
	}
}