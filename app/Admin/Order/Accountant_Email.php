<?php
namespace Woo_BG\Admin\Order;

defined( 'ABSPATH' ) || exit;

class Accountant_Email extends \WC_Email {
	public function __construct() {
		$this->id             = 'woo_bg_accountant_documents';
		$this->title          = __( 'Accountant Document Copies', 'bulgarisation-for-woocommerce' );
		$this->description    = __( 'This email sends invoice and credit notice copies to the accountant.', 'bulgarisation-for-woocommerce' );
		$this->heading        = __( 'Document Copy', 'bulgarisation-for-woocommerce' );
		$this->subject        = __( '[{site_title}]: Document copy for order #{order_number}', 'bulgarisation-for-woocommerce' );

		$this->template_html  = '';
		$this->template_plain = '';

		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
			'{site_title}'   => $this->get_blogname(),
		);

		parent::__construct();

		$accountant_email = woo_bg_get_option( 'invoice', 'accountant_email' );
		$this->recipient  = $accountant_email ? $accountant_email : '';
	}

	public function trigger( $order_id, $attachments = array() ) {
		$this->setup_locale();

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->restore_locale();
			return;
		}

		$this->object                         = $order;
		$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}'] = $this->object->get_order_number();

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			$this->restore_locale();
			return;
		}

		$this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$attachments
		);

		$this->restore_locale();
	}

	public function get_content_html() {
		$order_number = '';

		if ( $this->object ) {
			$order_number = $this->object->get_order_number();
		}

		return sprintf(
			'<p>%s</p>',
			sprintf(
				__( 'Please find attached the document copies for order #%s.', 'bulgarisation-for-woocommerce' ),
				esc_html( $order_number )
			)
		);
	}

	public function get_content_plain() {
		$order_number = '';

		if ( $this->object ) {
			$order_number = $this->object->get_order_number();
		}

		return sprintf(
			__( 'Please find attached the document copies for order #%s.', 'bulgarisation-for-woocommerce' ),
			$order_number
		);
	}

	public function get_default_subject() {
		return __( '[{site_title}]: Document copy for order #{order_number}', 'bulgarisation-for-woocommerce' );
	}

	public function get_default_heading() {
		return __( 'Document Copy', 'bulgarisation-for-woocommerce' );
	}
}
