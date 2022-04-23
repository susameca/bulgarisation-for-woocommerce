<?php
namespace Woo_BG\Admin\Invoice;

use Woo_BG\Admin\Tabs\Settings_Tab;
use Woo_BG\Admin\Invoice\Printer;
use Woo_BG\Image_Uploader;
use chillerlan\QRCode\QRCode;

defined( 'ABSPATH' ) || exit;

class Menu {
	private $invoice, $order, $parent_order, $refunded_order, $qr_png, $document_number, $currency_symbol;

	function __construct() {
		$order_documents_trigger = woo_bg_get_option( 'invoice', 'trigger' );

		if ( !$order_documents_trigger || $order_documents_trigger === "order_created" ) {
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'generate_documents' ) );
		} else if ( $order_documents_trigger === "order_completed" ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'generate_documents' ) );
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'set_payment_method' ) );
		add_action( 'woocommerce_order_refunded', array( $this, 'refunded_items' ), 15, 2 );
		add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_woo_bg_regenerate_pdfs', array( $this, 'process_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'add_invoice_to_customer_order' ) );
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_invoice_to_mail' ), 10, 4 );

		//subscriptions
		add_action( 'woocommerce_checkout_subscription_created', array( $this, 'subscriptions_created' ), 20, 2 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'subscriptions_created' ), 20, 2 );
	}

	public function add_invoice_to_customer_order( $order ) {
		$ids = [ $order->get_id() ];

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$ids[] = $refund->get_id();
			}
		}
		?>
		<div class="woo-bg--files">
			<?php foreach ($ids as $id ): ?>
				<?php if ( $invoice_pdf = get_post_meta( $id, 'woo_bg_invoice_document', 1 ) ): ?>
					<p>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $invoice_pdf ); ?>" class="woo-bg button button--pdf">
							<?php _e('Invoice PDF', 'woo-bg') ?>
						</a>
					</p>
				<?php endif ?>

				<?php if ( $refunded_invoice_pdf = get_post_meta( $id, 'woo_bg_refunded_invoice_document', 1 ) ): ?>
					<p>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_invoice_pdf ); ?>" class="woo-bg button button--pdf">
							<?php _e('Refunded Invoice PDF', 'woo-bg') ?>
						</a>
					</p>
				<?php endif ?>
			<?php endforeach ?>
		</div>
		<?php
	}

	public function add_meta_boxes() {
		// create PDF buttons
		add_meta_box( 'woo_bg_pdf-box', __( 'PDF Files', 'woo-bg' ), array( $this, 'pdf_actions_meta_box' ), array( 'shop_order' ), 'side', 'high' );
	}

	public function pdf_actions_meta_box( $post ) {
		$ids = [ $post->ID ];
		$order = new \WC_Order( $post->ID );

		if ( $refunds = $order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$ids[] = $refund->get_id();
			}
		}
		?>
		<ul class="wpo_wcpdf-actions">
			<?php foreach ( $ids as $id ): ?>
				<li>
					<?php if ( $order_pdf = get_post_meta( $id, 'woo_bg_order_document', 1 ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $order_pdf ); ?>" class="button">
							<?php _e('Order PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $invoice_pdf = get_post_meta( $id, 'woo_bg_invoice_document', 1 ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $invoice_pdf ); ?>" class="button">
							<?php _e('Invoice PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $refunded_order_pdf = get_post_meta( $id, 'woo_bg_refunded_order_document', 1 ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_order_pdf ); ?>" class="button">
							<?php _e('Refunded Order PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>

					<?php if ( $refunded_invoice_pdf = get_post_meta( $id, 'woo_bg_refunded_invoice_document', 1 ) ): ?>
						<a target="_blank" href="<?php echo wp_get_attachment_url( $refunded_invoice_pdf ); ?>" class="button">
							<?php _e('Refunded Invoice PDF', 'woo-bg') ?>
						</a>
					<?php endif ?>
				</li>
			<?php endforeach ?>
		</ul>
		<?php
	}

	public function refunded_items( $order_get_id, $refund_get_id ) {
		$this->generate_refunded_documents( $refund_get_id );
	}

	public function set_payment_method( $post ) {
		$this->order = new \WC_Order( $post );
		$settings = new Settings_Tab();
		$options = $settings->get_localized_fields();
		$payment_method = $options[ $this->order->get_payment_method() ];
		$payment_methods = woo_bg_get_payment_types_for_meta();

		$args = array(
			'type' => $payment_method[ 'payment_type' ]['value']['id'],
			'pos_number' => ( $payment_method['virtual_pos_number'] ) ? $payment_method['virtual_pos_number']['value'] : null,
			'identifier' => ( $payment_method['identifier'] ) ? $payment_method['identifier']['value'] : null,
		);

		if ( $payment_methods[ $payment_method[ 'payment_type' ]['value']['id'] ] ) {
			update_post_meta( $post, 'woo_bg_payment_method', $args );
		}
	}

	public function generate_documents( $post ) {
		$this->order = new \WC_Order( $post );
		$this->currency_symbol = html_entity_decode( get_woocommerce_currency_symbol( $this->order->get_currency() ) );

		$order_document_number = get_post_meta( $post, 'woo_bg_order_number', 1 );
		if ( !$order_document_number ) {
			$order_document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $order_document_number + 1, 10, '0', STR_PAD_LEFT ) );
			update_post_meta( $post, 'woo_bg_order_number', str_pad( $order_document_number, 10, '0', STR_PAD_LEFT ) );
		}

		$this->document_number = $order_document_number;

		$this->generate_QR_code();
		$this->save_order_document();

		if ( woo_bg_get_option( 'invoice', 'invoices' ) === 'yes' ) {
			$this->save_invoice_document();
		}

		if ( $this->order->get_meta('_billing_to_company') ) {
			update_post_meta( $this->order->get_id(), 'woo_bg_attach_invoice', 'yes' );
		}

		if ( $refunds = $this->order->get_refunds() ) {
			foreach ( $refunds as $refund ) {
				$this->generate_refunded_documents( $refund->get_id() );
			}
		}

		wp_delete_attachment( $this->qr_png, 1 );
	}

	public function generate_QR_code() {
		$nap_number = woo_bg_get_option( 'nap', 'nap_number' );
		$qr_code_pieces = array(
			$nap_number,
			$this->order->get_id(),
			$this->order->get_transaction_id(),
			$this->order->get_date_created()->date_i18n('Y-m-d, g:i:s'),
			$this->order->get_total()
		);

		$qr_code = new QRCode();

		$this->qr_png = Image_Uploader::upload_image_from_base64( array(
			'data' => $qr_code->render( implode( '*', $qr_code_pieces ) ),
			'type' => 'image/png',
		), 'qrcode' );
	}

	public function generate_basic_invoice( $items = '' ) {
		$company_name = woo_bg_get_option( 'nap', 'company_name' );
		$mol          = woo_bg_get_option( 'nap', 'mol' );
		$eik          = woo_bg_get_option( 'nap', 'eik' );
		$vat_number   = woo_bg_get_option( 'nap', 'dds_number' );
		$address      = woo_bg_get_option( 'nap', 'address' );
		$city         = woo_bg_get_option( 'nap', 'city' );
		$phone_number = woo_bg_get_option( 'nap', 'phone_number' );
		$email        = woo_bg_get_option( 'nap', 'email' );
		
		$color               = woo_bg_get_option( 'invoice', 'color' );
		$add_shipping        = woo_bg_get_option( 'invoice', 'add_shipping' );
		$prepared_by         = woo_bg_get_option( 'invoice', 'prepared_by' );
		$identification_code = woo_bg_get_option( 'invoice', 'identification_code' );
		$footer_text         = woo_bg_get_option( 'invoice', 'footer_text' );
		$enabled_taxes       = get_option( 'woocommerce_calc_taxes' );
		$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
		$vat_percentages     = woo_bg_get_vat_groups();
		$_tax = new \WC_Tax();
		$vat = ( $enabled_taxes === 'yes' ) ? $vat_percentages[ $vat_group ] : null;
		$this->invoice->setNumberFormat( '.', ',', str_replace( '_space', '', get_option( 'woocommerce_currency_pos' ) ), true, false );

		if( ! preg_match('/^#[a-f0-9]{6}$/i', $color) ){
			$color = '#007fff';
		}

		if ( empty( $items ) ) {
			$items = array_merge( $this->order->get_items(), $this->order->get_items( 'fee' ) );
		}

		/* Header settings */
		$this->invoice->setColor( $color );
		$this->invoice->setReference( str_pad( $this->document_number, 10, '0', STR_PAD_LEFT ) );
		$this->invoice->setDate( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setTime( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setDue( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setAddress( $city );
		$this->invoice->setOrderNumber( $this->order->get_order_number() );
		$this->invoice->setPaymentType( $this->order->get_payment_method_title() );
		$this->invoice->setCompiledBy( $prepared_by . " " . $identification_code );
		$this->invoice->setReceivedBy( $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name() );

		$this->invoice->setFrom( array(
			$company_name,
			sprintf( __('EIK: %s', 'woo-bg' ), $eik ),
			( $vat_number ) ? sprintf( __('VAT Number: %s', 'woo-bg' ), $vat_number ) : null,
			sprintf( __('MOL: %s', 'woo-bg' ), $mol ),
			$address,
			$city,
			'',
			sprintf( __('Phone: %s', 'woo-bg' ), $phone_number ),
			sprintf( __('E-mail: %s', 'woo-bg' ), $email ),
		) );

		if ( $this->order->get_meta('_billing_to_company') === '1' ) {
			$this->invoice->setTo( array(
				$this->order->get_billing_company(),
				sprintf( __('EIK: %s', 'woo-bg' ), $this->order->get_meta('_billing_company_eik') ),
				sprintf( __('VAT Number: %s', 'woo-bg' ), $this->order->get_meta('_billing_vat_number') ),
				sprintf( __('MOL: %s', 'woo-bg' ), $this->order->get_meta('_billing_company_mol') ),
				$this->order->get_meta('_billing_company_address'),
				$this->order->get_meta('_billing_company_settlement'),
				'',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->order->get_billing_email() ),
			) );
		} else {
			$this->invoice->setTo( array(
				$this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
				$this->order->get_billing_address_1() . " " . $this->order->get_billing_address_2(),
				$this->order->get_billing_city() .", " . $this->order->get_billing_postcode(),
				'',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->order->get_billing_email() ),
			) );
		}
		
		foreach ( $items as $key => $item ) {
			$item_tax_class = $_tax->get_rates( $item->get_tax_class() );

			if ( !empty( $item_tax_class ) ) {
				$vat = array_shift( $item_tax_class )['rate'];
			}

			$this->invoice->addItem( 
				$item->get_name(), 
				'',
				abs( $item->get_quantity() ), 
				$vat . "%", 
				abs( number_format( $item->get_total() / $item->get_quantity(), 2 ) ),
				false,
				abs( number_format( $item->get_total(), 2) )
			);
		}

		$total = number_format( $this->order->get_total() - $this->order->get_total_tax(), 2 );
		$total_vat = number_format( $this->order->get_total_tax(), 2 );
		$total_due = number_format( $this->order->get_total(), 2 );

		if ( $add_shipping === 'yes' ) {
			foreach ($this->order->get_items( 'shipping' ) as $item ) {
				$item_price = $item->get_total() / $item->get_quantity();
				$shipping_vat = $vat;

				if ( ! $item->get_total_tax() ) {
					$shipping_vat = 0;
				}

				$this->invoice->addItem( 
					sprintf( __('Shipping: %s', 'woo-bg'), $item->get_name() ), 
					'',
					$item->get_quantity(), 
					$shipping_vat . "%", 
					abs( number_format( $item_price, 2 ) ),
					false,
					number_format( $item->get_total(), 2)
				);
			}
		} else {
			$total = number_format( $this->order->get_total() - $this->order->get_total_tax() - $this->order->get_shipping_total(), 2 );
			$total_vat = number_format( $this->order->get_total_tax() - $this->order->get_shipping_tax(), 2 );
			$total_due = number_format( $this->order->get_total() - $this->order->get_shipping_total() - $this->order->get_shipping_tax(), 2 );
		}

		$this->invoice->addTotal( __( "Total", 'woo-bg'), abs( $total ) );

		if ( $enabled_taxes === 'yes' ) {
			$this->invoice->addTotal( sprintf( __( 'VAT %s', 'woo-bg' ), $vat_percentages[ $vat_group ] . "%" ), $total_vat );
		}


		$this->invoice->addTotal( __( "Total due", 'woo-bg' ), abs( $total_due ), true);
		$this->invoice->addParagraph( $footer_text );
		$this->invoice->setFooternote( get_bloginfo( 'name' ) );
	}
	public function generate_refunded_invoice( $items ) {
		$company_name = woo_bg_get_option( 'nap', 'company_name' );
		$mol          = woo_bg_get_option( 'nap', 'mol' );
		$eik          = woo_bg_get_option( 'nap', 'eik' );
		$vat_number   = woo_bg_get_option( 'nap', 'dds_number' );
		$address      = woo_bg_get_option( 'nap', 'address' );
		$city         = woo_bg_get_option( 'nap', 'city' );
		$phone_number = woo_bg_get_option( 'nap', 'phone_number' );
		$email        = woo_bg_get_option( 'nap', 'email' );
		
		$color               = woo_bg_get_option( 'invoice', 'color' );
		$add_shipping        = woo_bg_get_option( 'invoice', 'add_shipping' );
		$prepared_by         = woo_bg_get_option( 'invoice', 'prepared_by' );
		$identification_code = woo_bg_get_option( 'invoice', 'identification_code' );
		$footer_text         = woo_bg_get_option( 'invoice', 'footer_text' );
		$enabled_taxes       = get_option( 'woocommerce_calc_taxes' );
		$vat_group           = woo_bg_get_option( 'shop', 'vat_group' );
		$vat_percentages     = woo_bg_get_vat_groups();
		$_tax = new \WC_Tax();
		$vat = ( $enabled_taxes === 'yes' ) ? $vat_percentages[ $vat_group ] : null;
		$this->invoice->setNumberFormat( '.', ',', str_replace( '_space', '', get_option( 'woocommerce_currency_pos' ) ), true, false );

		if( ! preg_match('/^#[a-f0-9]{6}$/i', $color) ){
			$color = '#007fff';
		}

		/* Header settings */
		$this->invoice->setColor( $color );
		$this->invoice->setReference( str_pad( $this->document_number, 10, '0', STR_PAD_LEFT ) );
		$this->invoice->setDate( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setTime( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setDue( date_i18n( 'M d, Y', strtotime( $this->order->get_date_created() ) ) );
		$this->invoice->setAddress( $city );
		$this->invoice->setOrderNumber( $this->parent_order->get_order_number() );
		$this->invoice->setPaymentType( $this->parent_order->get_payment_method_title() );
		$this->invoice->setCompiledBy( $prepared_by . " " . $identification_code );
		$this->invoice->setReceivedBy( $this->parent_order->get_billing_first_name() . ' ' . $this->parent_order->get_billing_last_name() );

		$this->invoice->setFrom( array(
			$company_name,
			sprintf( __('EIK: %s', 'woo-bg' ), $eik ),
			( $vat_number ) ? sprintf( __('VAT Number: %s', 'woo-bg' ), $vat_number ) : null,
			sprintf( __('MOL: %s', 'woo-bg' ), $mol ),
			$address,
			$city,
			'',
			sprintf( __('Phone: %s', 'woo-bg' ), $phone_number ),
			sprintf( __('E-mail: %s', 'woo-bg' ), $email ),
		) );

		if ( $this->parent_order->get_meta('_billing_to_company') === '1' ) {
			$this->invoice->setTo( array(
				$this->parent_order->get_billing_company(),
				sprintf( __('EIK: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_company_eik') ),
				sprintf( __('VAT Number: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_vat_number') ),
				sprintf( __('MOL: %s', 'woo-bg' ), $this->parent_order->get_meta('_billing_company_mol') ),
				$this->parent_order->get_meta('_billing_company_address'),
				$this->parent_order->get_meta('_billing_company_settlement'),
				'',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->parent_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->parent_order->get_billing_email() ),
			) );
		} else {
			$this->invoice->setTo( array(
				$this->parent_order->get_billing_first_name() . ' ' . $this->parent_order->get_billing_last_name(),
				$this->parent_order->get_billing_address_1() . " " . $this->parent_order->get_billing_address_2(),
				$this->parent_order->get_billing_city() .", " . $this->parent_order->get_billing_postcode(),
				'',
				sprintf( __('Phone: %s', 'woo-bg' ), $this->parent_order->get_billing_phone() ),
				sprintf( __('E-mail: %s', 'woo-bg' ), $this->parent_order->get_billing_email() ),
			) );
		}

		$total = 0;
		$total_vat = 0;

		foreach ( $items as $key => $item ) {
			$item_vat = $vat;
			$item_total = abs( number_format( $item->get_total(), 2) );

			$item_tax_class = $_tax->get_rates( $item->get_tax_class() );

			if ( !empty( $item_tax_class ) ) {
				$item_vat = array_shift( $item_tax_class )['rate'];
			}

			$this->invoice->addItem( 
				$item->get_name(), 
				'',
				abs( $item->get_quantity() ), 
				$item_vat . "%", 
				abs( number_format( $item->get_total() / $item->get_quantity(), 2 ) ),
				false,
				$item_total
			);

			$total += abs( $item_total );
			$total_vat += abs( $item->get_total_tax() );
		}


		if ( $add_shipping === 'yes' ) {
			foreach ($this->parent_order->get_items( 'shipping' ) as $item ) {
				$item_price = abs( number_format( $item->get_total() / $item->get_quantity(), 2 ) );

				$shipping_vat = $vat;

				if ( ! $item->get_total_tax() ) {
					$shipping_vat = 0;
				}

				$this->invoice->addItem( 
					sprintf( __('Shipping: %s', 'woo-bg'), $item->get_name() ), 
					'',
					$item->get_quantity(), 
					$shipping_vat . "%", 
					$item_price,
					false,
					number_format( $item->get_total(), 2)
				);

				$total += abs( number_format( $item->get_total(), 2) );
				$total_vat += abs( number_format( $item->get_total_tax(), 2) );
			}
		}

		$total = number_format( $total, 2 );
		$total_vat = number_format( $total_vat, 2 );
		$total_due = number_format( $total + $total_vat, 2 );


		$this->invoice->addTotal( __( "Total", 'woo-bg'), abs( $total ) );

		if ( $enabled_taxes === 'yes' ) {
			$this->invoice->addTotal( sprintf( __( 'VAT %s', 'woo-bg' ), '' ), $total_vat );
		}

		$this->invoice->addTotal( __( "Total due", 'woo-bg' ), abs( $total_due ), true);
		$this->invoice->addParagraph( $footer_text );
		$this->invoice->setFooternote( get_bloginfo( 'name' ) );
	}

	public function save_order_document() {
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_basic_invoice();
		$this->invoice->setLogo( wp_get_original_image_path( $this->qr_png ) );
		$this->invoice->setType( __( 'Order - Original', 'woo-bg' ) );    // Invoice Type

		$name = uniqid( rand(), true );
		$pdf = wp_upload_bits( $name . '.pdf', null, $this->invoice->render( $name . '.pdf', 'S' ) );

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

		update_post_meta( $this->order->get_id(), 'woo_bg_order_document', $attach_id );
	}

	public function save_invoice_document() {
		$upload_dir = wp_upload_dir();

		//Original Invoice
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_basic_invoice();
		$this->invoice->setLogo( wp_get_original_image_path( $this->qr_png ) );
		$this->invoice->setType( __( 'Sale Invoice - Original', 'woo-bg' ) );    // Invoice Type

		$original_invoice = $upload_dir['path'] . '/original.pdf';
		$this->invoice->render( $original_invoice, 'F' );

		//Invoice Copy
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_basic_invoice();
		$this->invoice->setLogo( wp_get_original_image_path( $this->qr_png ) );
		$this->invoice->setType( __( 'Sale Invoice - Copy', 'woo-bg' ) );    // Invoice Type

		$copy_invoice = $upload_dir['path'] . '/copy.pdf';
		$this->invoice->render( $copy_invoice, 'F' );

		// Merge both PDF's
		$merger = new Merger();
		$merger->add( $original_invoice );
		$merger->add( $copy_invoice );

		//Upload single document
		$name = uniqid( rand(), true );
		$pdf = wp_upload_bits( $name . '.pdf', null, $merger->output( true ) );

		//Delete both PDF files
		unlink( $original_invoice );
		unlink( $copy_invoice );

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

		update_post_meta( $this->order->get_id(), 'woo_bg_invoice_document', $attach_id );
	}

	public function generate_refunded_documents( $post ) {
		$this->order = new \Automattic\WooCommerce\Admin\Overrides\OrderRefund( $post );
		$this->parent_order = new \WC_Order( $this->order->get_parent_id() );

		$this->currency_symbol = html_entity_decode( get_woocommerce_currency_symbol( $this->order->get_currency() ) );
		$this->return_methods = woo_bg_get_return_methods();
		$this->return_method = woo_bg_get_option( 'shop', 'return_method' );
		$items = array_merge( $this->order->get_items(), $this->order->get_items( 'fee' ) );

		if ( empty( $items ) ) {
			$items = array_merge( $this->parent_order->get_items(), $this->parent_order->get_items( 'fee' ) );
		}

		$order_document_number = get_post_meta( $post, 'woo_bg_refunded_order_number', 1 );
		if ( !$order_document_number ) {
			$order_document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $order_document_number + 1, 10, '0', STR_PAD_LEFT ) );
			update_post_meta( $post, 'woo_bg_refunded_order_number', str_pad( $order_document_number, 10, '0', STR_PAD_LEFT ) );
		}

		$this->document_number = $order_document_number;


		$this->save_refunded_order_document( $items );

		if ( woo_bg_get_option( 'invoice', 'invoices' ) === 'yes' ) {
			$this->save_credit_notice_document();
		}
	}

	public function save_refunded_order_document( $items ) {
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_refunded_invoice( $items );
		$this->invoice->setType( __( 'Refunded Order - Original', 'woo-bg' ) );
		$this->invoice->setPaymentType( $this->return_methods[ $this->return_method ]['label'] );
		$this->invoice->setOrderNumber( $this->order->get_id() );
		
		$name = uniqid( rand(), true );
		$pdf = wp_upload_bits( $name . '.pdf', null, $this->invoice->render( $name . '.pdf', 'S' ) );

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

		update_post_meta( $this->order->get_id(), 'woo_bg_refunded_order_document', $attach_id );
	}

	public function save_credit_notice_document() {
		$upload_dir = wp_upload_dir();

		if ( $this->order->get_parent_id() ) {
			$order_document_number = get_post_meta( $this->order->get_parent_id(), 'woo_bg_order_number', 1 );
		} else {
			$order_document_number = get_post_meta( $this->order->get_id(), 'woo_bg_order_number', 1 );
		}
		
		$items = array_merge( $this->order->get_items(), $this->order->get_items( 'fee' ) );

		if ( empty( $items ) ) {
			$items = array_merge( $this->parent_order->get_items(), $this->parent_order->get_items( 'fee' ) );
		}
		
		//Original credit notice
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_refunded_invoice( $items );
		$this->invoice->setType( __( 'Credit Notice - Original', 'woo-bg' ) );
		$this->invoice->setOriginalInvoiceNumber( $order_document_number );
		$this->invoice->setPaymentType( $this->return_methods[ $this->return_method ]['label'] );
		$this->invoice->setOrderNumber( $this->parent_order->get_order_number() );

		$original_invoice = $upload_dir['path'] . '/original.pdf';
		$this->invoice->render( $original_invoice, 'F' );

		//Invoice credit notice
		$this->invoice = new Printer( 'A4', $this->currency_symbol );
		$this->generate_refunded_invoice( $items  );
		$this->invoice->setType( __( 'Credit Notice - Copy', 'woo-bg' ) );
		$this->invoice->setOriginalInvoiceNumber( $order_document_number );
		$this->invoice->setPaymentType( $this->return_methods[ $this->return_method ]['label'] );

		$copy_invoice = $upload_dir['path'] . '/copy.pdf';
		$this->invoice->render( $copy_invoice, 'F' );

		// Merge both PDF's
		$merger = new Merger();
		$merger->add( $original_invoice );
		$merger->add( $copy_invoice );

		//Upload single document
		$name = uniqid( rand(), true );
		$pdf = wp_upload_bits( $name . '.pdf', null, $merger->output( true ) );

		//Delete both PDF files
		unlink( $original_invoice );
		unlink( $copy_invoice );

		if ( is_wp_error( $pdf ) ) {
			return;
		}

		if ( $this->refunded_order ) {
			$this->order = $this->refunded_order;
		}

		$attach_id = wp_insert_attachment( array(
			 'guid' => $pdf[ 'file' ], 
			 'post_mime_type' => $pdf['type'],
			 'post_title' => $name,
			 'post_content' => '',
			 'post_status' => 'inherit'
		), $pdf[ 'file' ] );

		update_post_meta( $this->order->get_id(), 'woo_bg_refunded_invoice_document', $attach_id );
	}

	public function attach_invoice_to_mail( $attachments, $email_id, $order, $email ) {
		if ( !is_object( $order ) ) {
			return;
		}

		$order_documents_trigger = woo_bg_get_option( 'invoice', 'trigger' );

		if ( !$order_documents_trigger || $order_documents_trigger === 'order_created' ) {
			$email_ids = array( 'customer_processing_order' );
		} else if ( $order_documents_trigger === 'order_completed' ) {
			$email_ids = array( 'customer_completed_order' );
		}

		$attach_invoice = get_post_meta( $order->get_id(), 'woo_bg_attach_invoice', 1 );

		if ( $attach_invoice == 'yes' && in_array ( $email_id, $email_ids ) ) {
			if ( $invoice_id = get_post_meta( $order->get_id(), 'woo_bg_invoice_document', 1 ) ) {
				$attachments[] = get_attached_file( $invoice_id );
			}
		}

		return $attachments;
	}

	public function add_order_meta_box_actions( $actions ) {
		global $post;

		if ( get_post_type( $post ) !== 'shop_subscription' ) {
			$actions[ 'woo_bg_regenerate_pdfs' ] = __( 'Regenerate PDF\'s', 'woo-bg' );
		}

		return $actions;
	}

	public function process_order_meta_box_actions( $order ) {
		$this->generate_documents( $order->get_id() );
	}

	public function subscriptions_created( $subscription, $order ) {
		$this->increase_order_doc_number( $order->get_id() );

		$this->generate_documents( $order->get_id() );
	}

	public function increase_order_doc_number( $order_id ) {
		$order_document_number = get_post_meta( $order_id, 'woo_bg_order_number', 1 );

		if ( $order_document_number ) {
			$order_document_number = woo_bg_get_option( 'invoice', 'next_invoice_number' );
			woo_bg_set_option( 'invoice', 'next_invoice_number', str_pad( $order_document_number + 1, 10, '0', STR_PAD_LEFT ) );
			update_post_meta( $order_id, 'woo_bg_order_number', str_pad( $order_document_number, 10, '0', STR_PAD_LEFT ) );
		}
	}
}
