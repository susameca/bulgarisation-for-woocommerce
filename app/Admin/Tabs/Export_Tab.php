<?php
namespace Woo_BG\Admin\Tabs;

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
		<div id="woo-bg-exports"></div><!-- /#woo-bg-export -->
		<div id="woo-bg-exports--microinvest"></div><!-- /#woo-bg-export -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_export', array(
			'i18n' => $this->get_i18n(),
			'year' => date( 'Y-m', strtotime( 'today - 1 month' ) ),
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
		<h2><?php _e('Export documents for Microinvest', 'woo-bg' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public function get_description() {
		ob_start();
		?>
		<h2><?php _e('Standardized audit file according to Annex № 38', 'woo-bg' ) ?></h2>
		<?php
		return ob_get_clean();
	}

	public static function woo_bg_export_nap_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$generated_file = self::generate_xml_file( sanitize_text_field( $_REQUEST[ 'year' ] ), sanitize_text_field( $_REQUEST[ 'generate_files' ] ) );

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
		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'woo-bg' ), $generated_file['file'] ) . $additional_message,
		) );
	}

	public static function generate_xml_file( $date, $generate_files ) {
		$invoices = new \Woo_BG\Admin\Invoice\Menu();
		$orders = wc_get_orders( array(
			'date_created' => strtotime( 'first day of ' . $date ) . '...' . strtotime( 'last day of ' . $date . ' 23:59' ),
			'status' => array( 'wc-completed' ),
			'limit' => -1,
		) );

		$not_included_orders = array();
		$add_shipping = woo_bg_get_option( 'invoice', 'add_shipping' );
		$vat_group = woo_bg_get_option( 'shop', 'vat_group' );
		$vat_groups = woo_bg_get_vat_groups();
		$_tax = new \WC_Tax();

		$return_methods = array(
			'1' => \Audit\ReturnMethods\IBAN::class, //1
			'2' => \Audit\ReturnMethods\Card::class, //2
			'3' => \Audit\ReturnMethods\Cash::class, //3
			'4' => \Audit\ReturnMethods\Other::class, //4
		);
		
		$settings = new Settings_Tab();
		$settings->load_fields();
		$options = $settings->get_localized_fields();
		$payment_methods = woo_bg_get_payment_types_for_meta();

		$shop = new \Audit\Shop(
			$options['nap']['eik']['value'], 
			$options['nap']['nap_number']['value'],
			$options['nap']['domain']['value'],
			new \DateTime(), 
			false, 
			date('Y', strtotime( $date ) ), 
			date('m', strtotime( $date ) )
		);

		$orders_ids = wp_list_pluck( $orders, 'id' );

		foreach ( $orders as $key => $order ) {
			if ( is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) ) {
				$parent_order = new \WC_Order( $order->get_parent_id() );

				$order_id_to_show = apply_filters( 'woo_bg/admin/export/refunded_order_id', $parent_order->get_order_number(), $order );

				$returnedOrder = new \Audit\ReturnedOrder(
					$order_id_to_show, 
					abs( $order->get_total() ), 
					new \DateTime( $order->get_date_created() ), 
					$return_methods[ $options[ 'shop' ][ 'return_method' ][ 'value' ]['id'] ]
				);

				$shop->addReturnedOrder( $returnedOrder );
				$returnedOrder = $order;

				if ( !$order->get_items() || !in_array( $order->get_parent_id(), $orders_ids ) ) {
					if ( !in_array( $order->get_parent_id(), $orders_ids ) ) {
						$orders_ids[] = $order->get_parent_id(); 
					}

					$order = new \WC_Order( $order->get_parent_id() );
				} else {
					continue;
				}
			}

			$order_document_number = get_post_meta( $order->get_id(), 'woo_bg_order_number', 1 );

			if (  $generate_files == 'true' && !$order_document_number ) {
				$invoices->generate_documents( $order->get_id() );
				$order_document_number = get_post_meta( $order->get_id(), 'woo_bg_order_number', 1 );
			}

			$payment_method = get_post_meta( $order->get_id(), 'woo_bg_payment_method', 1 );
			$payment_method_type = null;
			$order_id_to_show = apply_filters( 'woo_bg/admin/export/order_id', $order->get_order_number(), $order );

			if ( empty( $payment_method ) ) {
				$payment_method = ( !empty( $options[ $order->get_payment_method() ] ) ) ? $options[ $order->get_payment_method() ] : null;
				
				$pos_number = ( !empty( $payment_method['virtual_pos_number'] ) ) ? $payment_method['virtual_pos_number']['value'] : null;
				$identifier = ( !empty( $payment_method['identifier'] ) ) ? $payment_method['identifier']['value'] : null;

				if ( $payment_method ) {
					$payment_method_type = ( !empty( $payment_methods[ $payment_method[ 'payment_type' ]['value']['id'] ] ) ) ? $payment_methods[ $payment_method[ 'payment_type' ]['value']['id'] ] : null;
				}
			} else {
				$pos_number = $payment_method['pos_number'];
				$identifier = $payment_method['identifier'];
				$payment_method_type = $payment_methods[ $payment_method['type'] ];
			}

			if ( ! $payment_method_type ) {
				$not_included_orders[] = $order_id_to_show;
				continue;
			}

			$xml_order = new \Audit\Order(
				$order_id_to_show,
				new \DateTime( $order->get_date_created() ), 
				$order_document_number,
				new \DateTime( $order->get_date_created() ),
				$order->get_total_discount(), 
				$payment_method_type,
				[], 
				$pos_number,
				$order->get_transaction_id(), 
				$identifier,
			);

			foreach ( $order->get_items() as $key => $item ) {
				$price = $item->get_total() / $item->get_quantity();
				$item_vat = $vat_groups[ $vat_group ];
				$item_tax_class = $_tax->get_rates( $item->get_tax_class() );

				if ( !empty( $item_tax_class ) ) {
					$item_vat = array_shift( $item_tax_class )['rate'];
				}

				$price = apply_filters( 'woo_bg/admin/export/item_price', $price, $item );
				$item_vat = apply_filters( 'woo_bg/admin/export/item_vat', $item_vat, $item );

				$xml_item = new \Audit\Item( 
					$item->get_name(), 
					$item->get_quantity(), 
					$price, 
					$item_vat,
				);

				$xml_order->addItem( $xml_item );
			}

			if ( $add_shipping === 'yes' ) {
				foreach ($order->get_items( 'shipping' ) as $item ) {
					$item_vat = 0;
					$item_tax_class = $_tax->get_rates( $item->get_tax_class() );

					if ( !empty( $item_tax_class ) ) {
						$vat = array_shift( $item_tax_class )['rate'];
					}

					$xml_item = new \Audit\Item( 
						sprintf( __( 'Shipping: %s', 'woo-bg' ), $item->get_name() ), 
						$item->get_quantity(), 
						$item->get_total() / $item->get_quantity(),
						$item_vat
					);

					$xml_order->addItem( $xml_item );
				}
			}

			$shop->addOrder( $xml_order );
		}

		$name = uniqid( rand(), true );

		$xml = wp_upload_bits( $name . '.xml', null, \Audit\XmlConverter::convert( $shop ) );

		if ( is_wp_error( $xml ) ) {
			return;
		}

		$attachment = array(
			 'guid' => $xml[ 'file' ], 
			 'post_mime_type' => $xml['type'],
			 'post_title' => $name,
			 'post_content' => '',
			 'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $xml[ 'file' ] );

		return array(
			'file' => wp_get_attachment_url( $attach_id ),
			'not_included_orders' => $not_included_orders,
		);
	}

	public static function woo_bg_export_microinvest_callback() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'woo_bg_export_nap' ) ) {
			wp_send_json_error();
		}

		$generated_file = self::generate_microinvest_file( sanitize_text_field( $_REQUEST[ 'year' ] ) );

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
		wp_send_json_success( array(
			"message" => sprintf( __( 'File generated successfully! <a href="%s" download target="_blank">Download</a>', 'woo-bg' ), $generated_file['file'] ) . $additional_message,
		) );
	}

	public static function generate_microinvest_file( $date ) {
		$orders = wc_get_orders( array(
			'date_created' => strtotime( 'first day of ' . $date ) . '...' . strtotime( 'last day of ' . $date . ' 23:59' ),
			'limit' => -1,
		) );
		
		$add_shipping = woo_bg_get_option( 'invoice', 'add_shipping' );
		$documents = [];

		foreach ( $orders as $key => $order ) {
			$names = [];
			foreach ( $order->get_items() as $item ) {
				$names[] = $item->get_name();
			}

			$temp_order = $order;
			$company = '';
			$eik = '';
			$vat_number = '';
			$mol = '';
			$address = '';
			$city = '';

			if ( is_a( $order, 'Automattic\WooCommerce\Admin\Overrides\OrderRefund' ) ) {
				$temp_order = new \WC_Order( $order->get_parent_id() );
			}

			if ( $temp_order->get_meta('_billing_to_company') === '1' ) {
				$company = $temp_order->get_billing_company();
				$eik = $temp_order->get_meta('_billing_company_eik');
				$vat_number = $temp_order->get_meta('_billing_vat_number');
				$mol = $temp_order->get_meta('_billing_company_mol');
				$city = $temp_order->get_meta('_billing_company_settlement');
				$address = $temp_order->get_meta('_billing_company_address');
			} else {
				$company = $temp_order->get_billing_first_name() . ' ' . $temp_order->get_billing_last_name();
				$address = $temp_order->get_billing_address_1() . " " . $temp_order->get_billing_address_2();
				$city = $temp_order->get_billing_city();
			}

			if ( get_post_meta( $order->get_id(), 'woo_bg_invoice_document', 1 ) ) {
				$total_due = $order->get_total();

				if ( $add_shipping === 'no' ) {	
					$total_due = number_format( $order->get_total() - $order->get_shipping_total() - $order->get_shipping_tax(), 2 );
				}

				$documents[] = apply_filters( 'woo_bg/admin/export/microinvest-order', array(
					'2', 
					date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) ),
					get_post_meta( $order->get_id(), 'woo_bg_order_number', 1 ),
					'Ф-ра',
					$total_due,
					'16',
					$company,
					$mol,
					$city,
					$address,
					$vat_number,
					$eik,
					'',
					'Продажба',
					implode( ', ', $names ),
					'-1'
				), $order, $temp_order );
			}

			if ( get_post_meta( $order->get_id(), 'woo_bg_refunded_invoice_document', 1 ) ) {
				$documents[] = apply_filters( 'woo_bg/admin/export/microinvest-order', array(
					'2', 
					date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) ),
					get_post_meta( $order->get_id(), 'woo_bg_refunded_order_number', 1 ),
					'КИ',
					$total_due,
					'16',
					$company,
					$mol,
					$city,
					$address,
					$vat_number,
					$eik,
					'',
					'Продажба',
					implode( ', ', $names ),
					'-1'
				), $order, $temp_order );
			}
		}

		$rows = array_map( function ( $document ) {
			return implode('|', $document );
		}, $documents );

		$txt = wp_upload_bits( 'import.txt', null, implode( "\n", $rows ) );

		if ( is_wp_error( $txt ) ) {
			return;
		}

		$attachment = array(
			'guid' => $txt[ 'file' ], 
			'post_mime_type' => $txt['type'],
			'post_title' => $name,
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $txt[ 'file' ] );

		return array(
			'file' => wp_get_attachment_url( $attach_id ),
		);
	}
}
