<template>
	<div class="woo-bg--pigeon-delivery">
		<div class="woo-bg--locker-error" v-if="error">{{error}}</div>

		<div v-else>
			<multiselect 
				id="ajax" 
				class="woo-bg-multiselect"
				placeholder=""
				track-by="id"
				label="name"
				deselect-label=""
				open-direction="bottom" 
				v-model="selectedLocker" 
				:options-limit="1000" 
				:limit="6"
				:max-height="600" 
				:selectedLabel="i18n.selected" 
				:selectLabel="i18n.select"
				:options="lockers" 
				:custom-label="compileLabel" 
				:multiple="false" 
				:searchable="true" 
				:show-no-results="true"
				@input="setLocker"
			>
				<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }} ( {{ option.address }} )</strong></template>

				<span slot="noResult">{{i18n.noResult}}</span>
				<span slot="noOptions">{{i18n.noOptions}}</span>
				<span slot="placeholder">{{i18n.searchAPS}}</span>
			</multiselect>
		</div>
	</div><!-- /.section__contact -->
</template>

<script>
import { getCookie,setCookie } from '../../../utils';
import cloneDeep from 'lodash/cloneDeep';
import axios from 'axios';
import Qs from 'qs';
import Multiselect from 'vue-multiselect';
import 'magnific-popup';

export default {
	components: { Multiselect },
	data() {
		return {
			countryField: $('#billing_country'),
			stateField: $('#billing_state'),
			cityField: $('#billing_city'),
			firstNameField: $('#billing_first_name'),
			lastNameField: $('#billing_last_name'),
			phoneField: $('#billing_phone'),
			toCompanyField: $('#woo-billing-to-company'),
			vatField: $('#woo_bg_eu_vat_number'),
			companyField: $('#billing_company'),
			molField: $('#woo-bg-billing-company-mol'),
			eikField: $('#woo-bg-billing-company-eik'),
			selectedLocker: [],
			lockers: [],
			state: '',
			city: '',
			error: '',
			document: $( document.body ),
			i18n: wooBg_pigeon_locker.i18n,
		}
	},
	mounted() {
		let _this = this;
		this.loadLocalStorage();

		this.checkFields();

		$('#ship-to-different-address-checkbox').on( 'change', function () {
			_this.checkFields();
		});

		this.loadLockers();

		$('form.checkout').on('checkout_place_order', function (e) {
			$('#billing_address_1').attr('disabled', false );
			$('#shipping_address_1').attr('disabled', false );
		});

		this.document.on( 'update_checkout.onUpdate', this.onUpdate );
		this.document.on( 'update_checkout.setCookieLocker', this.setCookieData );
		this.phoneField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.firstNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.lastNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.toCompanyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.vatField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.companyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.molField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.eikField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.cityField.on('change.loadLockers', function () {
			_this.city = $(this).val();
			_this.loadLockers();
		});

		this.stateField.on('change.loadLockers', function () {
			_this.state = $(this).val();
			_this.loadLockers();
		});
		
		if ( window.pigeonLockerInitialUpdate ) {
			this.document.trigger('update_checkout');
			window.pigeonLockerInitialUpdate = false;
			this.setAddress1FieldData();
		}
	},
	methods: {
		compileLabel({ name, address }) {
		  return `${name} (${address})`;
		},
		checkFields() {
			$('#billing_address_1').attr('disabled', false);
			$('#shipping_address_1').attr('disabled', false);

			if ( $('#ship-to-different-address-checkbox').is(":checked") ) {
				this.countryField = $( '#shipping_country' );
				this.Address1Field = $( '#shipping_address_1' );
				this.stateField = $( '#shipping_state' );
				this.cityField = $( '#shipping_city' );
				this.firstNameField = $( '#shipping_first_name' );
				this.lastNameField = $( '#shipping_last_name' );
			} else {
				this.countryField = $( '#billing_country' );
				this.Address1Field = $( '#billing_address_1' );
				this.stateField = $( '#billing_state' );
				this.cityField = $( '#billing_city' );
				this.firstNameField = $( '#billing_first_name' );
				this.lastNameField = $( '#billing_last_name' );
			}

			this.Address1Field.attr('disabled', true);

			this.state = this.stateField.val();

			if ( this.cityField.val() ) {
				this.city = this.cityField.val();
			}
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--pigeon-locker' );
			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedLocker = cloneDeep( localStorageData.selectedLocker );
				this.lockers = cloneDeep( localStorageData.lockers );
				this.state = cloneDeep( localStorageData.state );
				this.city = cloneDeep( localStorageData.city );
				this.type = cloneDeep( localStorageData.type );
			}
		},
		loadLockers() {
			this.state = this.stateField.val();
			this.city = this.cityField.val();
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_pigeon_load_lockers',
				state: this.state,
				city: this.city,
				country: this.countryField.val()
			};

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.error = '';
					let selectedLocker = [];

					if ( response.data.data.status === 'invalid-city' ) {
						_this.error = response.data.data.error;
						_this.resetData();
					} else {
						if ( response.data.data.lockers.length ) {
							_this.lockers = cloneDeep( response.data.data.lockers );
						} else {
							_this.error = response.data.data.error;
							_this.resetData();
						}
					}

					_this.lockers.forEach( function ( locker ) {
						if ( _this.selectedLocker && _this.selectedLocker.id == locker.id ) {
							selectedLocker = locker;
						}
					});

					_this.selectedLocker = cloneDeep( selectedLocker );

					_this.setCookieData();
					_this.loading = false;
				} )
				.catch( error => {
					_this.message = "Имаше проблем. За повече информация вижте конзолата.";
					console.log(error);
					_this.loading = false;
				} );
		},
		setLocker() {
			this.setLocalStorageData();
			this.setAddress1FieldData();

			this.document.trigger('update_checkout');
		},
		setCookieData() {
			let first_name = this.firstNameField.val();
			let last_name = this.lastNameField.val();
			let phone = this.phoneField.val();
			let formData = $('form[name="checkout"]').serializeArray().reduce((accumulator, value) => {
			  return {...accumulator, [value.name]: value.value};
			}, {});

			let cookie = {
				billing_to_company: this.toCompanyField.val(),
				billing_company_mol: formData.billing_company_mol,
				billing_company: this.companyField.val(),
				billing_vat_number: formData.billing_vat_number,
				type: 'locker',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedLocker: ( this.selectedLocker ) ? this.selectedLocker.id : null,
				state: this.state,
				city: this.city,
				country: this.countryField.val(),
				payment: $('input[name="payment_method"]:checked').val(),
			};

			cookie = encodeURIComponent( JSON.stringify( cookie ) );

			setCookie( 'woo-bg--pigeon-address', cookie, 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedLocker: this.selectedLocker,
				lockers: this.lockers,
				state: this.state,
				city: this.city,
			}

			localStorage.setItem( 'woo-bg--pigeon-locker', JSON.stringify( localStorageData ) );
		},
		resetData() {
			this.lockers = [];
			this.selectedLocker = '';
			this.streetNumber = '';
			this.other = '';
			localStorage.removeItem( 'woo-bg--pigeon-locker' );
			this.setCookieData();
		},
		setAddress1FieldData() {
			let shippingAddress = "";

			if ( this.selectedLocker && this.selectedLocker.name ) {
				shippingAddress = this.i18n.toAPS + this.selectedLocker.name + ' ( ' + this.selectedLocker.address + ' ) ';
			}

			this.Address1Field.val( shippingAddress );
		},
		triggerUpdateCheckout() {
			this.document.trigger('update_checkout');
		},
		maybeTriggerUpdate() {
			if ( 
				this.toCompanyField.val() &&
				this.vatField.val() &&
				this.companyField.val() &&
				this.molField.val() &&
				this.eikField.val()
			) {
				this.document.trigger('update_checkout');
			}
		},
		onUpdate() {
			this.Address1Field.attr('disabled', true);
			this.setCookieData();
		},
	},
	beforeDestroy() {
		this.document.off( 'update_checkout.onUpdate');
		this.document.off( 'update_checkout.setCookieLocker');
		this.phoneField.off( 'change.triggerUpdate' );
		this.firstNameField.off( 'change.triggerUpdate' );
		this.lastNameField.off( 'change.triggerUpdate' );
		this.cityField.off('change.loadLockers' );
		this.stateField.off('change.loadLockers' );

		this.toCompanyField.off( 'change.maybeTriggerUpdate' );
		this.vatField.off( 'change.maybeTriggerUpdate' );
		this.companyField.off( 'change.maybeTriggerUpdate' );
		this.molField.off( 'change.maybeTriggerUpdate' );
		this.eikField.off( 'change.maybeTriggerUpdate' );

		$('#billing_address_1').attr('disabled', false);
		$('#shipping_address_1').attr('disabled', false);
	}
}
</script>