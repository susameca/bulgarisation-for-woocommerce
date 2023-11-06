<?php
namespace Woo_BG\Admin\Tabs;
use Woo_BG\Admin\Fields;

defined( 'ABSPATH' ) || exit;
class Nra_Tab extends Base_Tab {
	protected $fields, $localized_fields;
	
	public function __construct() {
		$this->set_name( __( 'Documents', 'woo-bg' ) );
		$this->set_tab_slug( "documents" );

		if ( !empty( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === $this->get_tab_slug() ) {
			$this->load_fields();
		}
	}

	public function render_tab_html() {
		$this->admin_localize();
		
		woo_bg_support_text();
		?>
		<div id="woo-bg-settings"></div><!-- /#woo-bg-export -->
		<?php
	}

	public function admin_localize() {
		wp_localize_script( 'woo-bg-js-admin', 'wooBg_settings', array(
			'fields' => $this->get_localized_fields(),
			'payment_types' => woo_bg_get_payment_types(),
			'groups_titles' => $this->get_groups_titles(),
			'tab' => get_called_class(),
			'nonce' => wp_create_nonce( 'woo_bg_settings' ),
		) );
	}

	public function load_fields() {
		$fields = apply_filters( 'woo_bg/admin/nra/fields', array(
			'nap' => array(
				new Fields\Text_Field( 'company_name', __( 'Company Name', 'woo-bg' ), __( 'Your company name.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'mol', __( 'MOL', 'woo-bg' ), __( 'Your company MOL.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'eik', __( 'EIK', 'woo-bg' ), __( 'Your company EIK.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'dds_number', __( 'VAT Number', 'woo-bg' ), __( 'Your company VAT Number. To be filled in if you have a VAT registration.', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'dds_number_required', __( 'VAT number required on checkout?', 'woo-bg' ) ),
				new Fields\Text_Field( 'phone_number', __( 'Company phone Number', 'woo-bg' ), __( 'Contact phone number of your company.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'city', __( 'Settlement', 'woo-bg' ), __( 'The place of registration of your company.', 'woo-bg' ) ),
				new Fields\Text_Field( 'address', __( 'Company Address', 'woo-bg' ), __( 'The registration address of your company.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'email', __( 'Contact email', 'woo-bg' ), '', 'required|email' ),
				new Fields\Text_Field( 'nap_number', __( 'NRA unique number', 'woo-bg' ), __( 'The number received by the NRA when submitting information under Art. 52p according to Annex № 33.', 'woo-bg' ) ),
				new Fields\Text_Field( 'domain', __( 'Domain Name', 'woo-bg' ), __( 'Web address of the e-shop. It is introduced in the same way as when submitting information under Art. 52p according to Annex № 33 in the portal for electronic services of the NRA.', 'woo-bg' ) ),
			),
			'invoice' => array(
				new Fields\TrueFalse_Field( 'nra_n18', __( 'Enable N-18', 'woo-bg' ), null, null, __( 'Enable required documents for N-18 and enable the export tab for XML file.', 'woo-bg' ) ),
				new Fields\Select_Field( array(
					'disable' => array(
						'id' => 'disable',
						'label' => __( 'Disable invoice generation', 'woo-bg' ),
					),
					'only_for_company' => array(
						'id' => 'only_for_company',
						'label' => __( 'Only for companies', 'woo-bg' ),
					),
					'always' => array(
						'id' => 'always',
						'label' => __( 'Always', 'woo-bg' ),
					),
				), 'invoices', __( 'When to generate invoices?', 'woo-bg' ) ),
				new Fields\Select_Field( array(
					'order_created' => array(
						'id' => 'order_created',
						'label' => __( 'Order created', 'woo-bg' ),
					),
					'order_completed' => array(
						'id' => 'order_completed',
						'label' => __( 'Order completed', 'woo-bg' ),
					)
				), 'trigger', __( 'When to generate documents?', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'remove_shipping', __( 'Remove delivery from documents', 'woo-bg' ), null, null, __( 'This option will remove the delivery from invoices and XML file.', 'woo-bg' ) ),
				new Fields\Text_Field( 'next_invoice_number', __( 'Next Invoice Number', 'woo-bg' ), __( 'The invoice number of the next order.', 'woo-bg' ), 'required' ),
				new Fields\Text_Field( 'prepared_by', __( 'Prepared by', 'woo-bg' ), __( 'Enter the name of the compiler of the automatically generated documents, which will be displayed in them.', 'woo-bg' ), null ),
				new Fields\Text_Field( 'identification_code', __( 'Identification code', 'woo-bg' ), null, null, __( "Enter an ID in the invoices and credit memos that replace the compiler's signature.", 'woo-bg' ) ),
				new Fields\Text_Field( 'footer_text', __( 'Footer text', 'woo-bg' ) ),
				new Fields\TrueFalse_Field( 'total_text', __( 'Total in text', 'woo-bg' ), null, null, __( 'Print order total after the table. Works only in levs.', 'woo-bg' ) ),
			),
			'shop' => array(
				new Fields\Select_Field( array(
					'a' => array(
						'id' => 'a',
						'label' => __( 'Group - A', 'woo-bg' ),
					),
					'b' => array(
						'id' => 'b',
						'label' => __( 'Group - B', 'woo-bg' ),
					),
					'g' => array(
						'id' => 'g',
						'label' => __( 'Group - G', 'woo-bg' ),
					),
				), 'vat_group', __( 'Vat Group', 'woo-bg' ), null, null, __( 'group "A" - for goods and services, the sales of which are exempt from taxation, for goods and services, the sales of which are subject to 0% VAT, as well as for sales, for which VAT is not charged; group "B" - for goods and services, the sales of which are subject to 20% value added tax; group "D" - for goods and services, the sales of which are subject to 9% value added tax; combined - the group is determined for each product in the store separately.', 'woo-bg' ) ),
				new Fields\Select_Field( woo_bg_get_return_methods(), 'return_method', __( 'Return method', 'woo-bg' ), __( 'How to return the amount when the client cancel the order.', 'woo-bg' ) ),
			)
		) );

		$fields = $this->add_payment_methods_fields( $fields );
		$fields = $this->add_shipping_methods_fields( $fields );

		$this->set_fields( $fields );
	}

	public function gateways_titles() {
		$gateways = WC()->payment_gateways->payment_gateways();
		
		if ( empty( $gateways ) ) {
			return;
		}

		foreach ( $gateways as $slug => $gateway ) {
			if ( wc_string_to_bool( $gateway->enabled ) ) {
				add_filter( 'woo_bg/admin/nra/groups_titles', function( $groups_titles ) use ( $gateway, $slug ) {
					$groups_titles[ $slug ] = array(
						'title' => sprintf( __( 'Payment method: %s', 'woo-bg' ), $gateway->title ),
					);

					return $groups_titles;
				} );
			}
		}
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_localized_fields() {
		return $this->localized_fields;
	}

	public function get_groups_titles() {
		$this->gateways_titles();

		$titles = apply_filters( 'woo_bg/admin/nra/groups_titles', array(
			'nap' => array(
				'title' => __( 'Main Company Settings', 'woo-bg' ),
			),
			'invoice' => array(
				'title' => __( 'Documents Settings', 'woo-bg' ),
			),
			'shop' => array(
				'title' => __( 'Global Shop Settings', 'woo-bg' ),
			),
			'shippings' => array(
				'title' => __( 'Shipping Methods Settings', 'woo-bg' ),
			),
		) );

		return $titles;
	}

	public function add_shipping_methods_fields( $fields ) {
		$gateways = WC()->payment_gateways->payment_gateways();

		if ( !empty( $gateways ) ) {
			$fields[ 'gateways' ] = array();
			foreach ( $gateways as $slug => $gateway ) {
				if ( wc_string_to_bool( $gateway->enabled ) ) {
					$fields[ $slug ] = array(
						new Fields\Select_Field( woo_bg_get_payment_types(), 'payment_type', __( 'Payment Type', 'woo-bg' ), __( 'Manner of payment of the order according to the NRA system.', 'woo-bg' ) ),
						new Fields\Text_Field( 'virtual_pos_number', __( 'Virtual POS №', 'woo-bg' ) ),
						new Fields\Text_Field( 'identifier', __( 'Identifier', 'woo-bg' ) ),
					);
				}
			}
		}

		return $fields;
	}

	public function add_payment_methods_fields( $fields ) {
		$shippings = WC()->shipping->get_shipping_methods();

		foreach ( $shippings as $shipping ) {
			$fields['shippings'][] = new Fields\TrueFalse_Field(
				$shipping->id . '_is_courier', 
				sprintf( __( 'Is %s courier?', 'woo-bg' ), $shipping->method_title ),
				__( 'If set to yes, all deliveries with PPP will be removed from documents and XML export', 'woo-bg' ) 
			);
		}

		return $fields;
	}
}
