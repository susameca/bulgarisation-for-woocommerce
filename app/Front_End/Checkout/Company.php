<?php
namespace Woo_BG\Front_End\Checkout;

defined( 'ABSPATH' ) || exit;

class Company {
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'vat_number_field' ), 20 );
		add_action( 'woocommerce_admin_billing_fields', array( __CLASS__, 'admin_billing_fields' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_fields' ), 11, 2 );
	}

	public static function vat_number_field( $fields ) {
		if ( class_exists( 'WC_EU_VAT_Number_Init' ) && isset( $fields['billing']['billing_vat_number'] ) ) {
			$fields['billing']['billing_vat_number']['required'] = ( woo_bg_get_option('nap', 'dds_number_required' ) !== 'no' );
			$fields['billing']['billing_vat_number']['class'][] = 'woo-bg-company-info form-row-first';
		}

		if ( isset( $fields['billing']['billing_company'] ) ) {
			$fields['billing']['billing_company']['priority'] = 121;
			$fields['billing']['billing_company']['required'] = true;
			$fields['billing']['billing_company']['class'] = array( 'woo-bg-company-info form-row-last' );
		}
		
		$fields['billing']['billing_to_company'] = array(
			'type'      => 'checkbox',
			'label'    => __( 'Do you want a company invoice?', 'woo-bg' ),
			'class'    => array(
				'form-row-wide',
			),
			'id' => 'woo-billing-to-company',
			'priority' => 119,
		);

		$fields['billing']['billing_company_mol'] = array(
			'label'    => __( 'MOL', 'woo-bg' ),
			'required' => true,
			'class'    => array(
				'form-row-first',
				'woo-bg-company-info',
			),
			'id' => 'woo-bg-billing-company-mol',
			'priority' => 121,
		);

		$fields['billing']['billing_company_eik'] = array(
			'label'    => __( 'EIK', 'woo-bg' ),
			'required' => true,
			'class'    => array(
				'form-row-last',
				'woo-bg-company-info',
			),
			'id' => 'woo-bg-billing-company-eik',
			'priority' => 121,
		);

		$fields['billing']['billing_company_settlement'] = array(
			'label'    => __( 'Settlement', 'woo-bg' ),
			'required' => true,
			'class'    => array(
				'form-row-first',
				'woo-bg-company-info',
			),
			'id' => 'woo-bg-billing-company-settlement',
			'priority' => 121,
		);

		$fields['billing']['billing_company_address'] = array(
			'label'    => __( 'Address', 'woo-bg' ),
			'required' => true,
			'class'    => array(
				'form-row-last',
				'woo-bg-company-info',
			),
			'id' => 'woo-bg-billing-company-address',
			'priority' => 121,
			'show'  => false,
		);

		return $fields;
	}

	public static function admin_billing_fields( $fields ) {
		unset( $fields[ 'company' ] );

		$fields['company'] = array(
			'label' => __( 'Company', 'woocommerce' ),
			'show'  => true,
		);

		$fields['company_mol'] = array(
			'label' => __( 'MOL', 'woo-bg' ),
			'show'  => true,
			'wrapper_class' => '_billing_address_1_field'
		);

		$fields['company_eik'] = array(
			'label' => __( 'EIK', 'woo-bg' ),
			'show'  => true,
			'wrapper_class' => '_billing_address_2_field'
		);

		$fields['company_settlement'] = array(
			'label' => __( 'Settlement', 'woo-bg' ),
			'show'  => true,
			'wrapper_class' => '_billing_address_1_field'
		);

		$fields['company_address'] = array(
			'label' => __( 'Company Address', 'woo-bg' ),
			'show'  => true,
			'wrapper_class' => '_billing_address_2_field'
		);

		return $fields;
	}

	public static function validate_fields( $data, $errors ) {
		$fields = array(
			'billing_company',
			'billing_company_eik',
			'billing_company_mol',
			'billing_company_settlement',
			'billing_company_address',
			'billing_vat_number',
		);
		
		if ( !isset( $_REQUEST[ 'billing_to_company' ] ) || ! sanitize_text_field( $_REQUEST[ 'billing_to_company' ] ) ) {
			foreach ( $fields as $field ) {
				unset( $errors->errors[ $field . '_required'] );
				unset( $errors->error_data[ $field . '_required'] );
			}
		}
	}
}

add_action( 'woocommerce_after_checkout_validation', function( $data, $errors ) {
	unset( $errors->errors[ 'billing_vat_number'] );
	unset( $errors->error_data[ 'billing_vat_number'] );
}, 11, 2 );
