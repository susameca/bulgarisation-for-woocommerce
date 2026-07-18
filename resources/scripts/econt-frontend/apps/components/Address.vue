<template>
	<div class="woo-bg--econt-delivery">
	  <multiselect 
	  	id="ajax" 
	  	class="woo-bg-multiselect"
	  	v-model="selectedAddress" 
	  	placeholder=""
	  	deselect-label=""
	  	open-direction="bottom" 
	  	track-by="id"
	  	label="label"
	  	:options-limit="30" 
	  	:limit="6"
	  	:max-height="600" 
	  	:close-on-select="closeOnSelect"
	  	:selectedLabel="i18n.selected"
	  	:selectLabel="i18n.select"
	  	:options="addresses" 
	  	:loading="isLoading" 
	  	:multiple="false" 
	  	:searchable="true" 
	  	:internal-search="false" 
	  	:clear-on-select="false" 
	  	:show-no-results="true"
	  	@search-change="asyncFind"
	  	@input="setAddress"
	  >
	  	<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>

	    <span slot="noResult">{{i18n.noResult}}</span>
	    <span slot="noOptions">{{i18n.noOptions}}</span>
	    <span slot="placeholder">{{i18n.searchAddress}}</span>
	  </multiselect>

	  <div
		class="woo-bg-econt-address-details"
		:class="{ 'woo-bg-econt-address-details--quarter': selectedAddress.type === 'quarters' }"
		v-if="selectedAddress && selectedAddress.type"
	  >
		<p class="form-row" v-if="selectedAddress.type === 'streets'">
			<input class="input-text" :placeholder="i18n.streetNumber" type="text" v-model="streetNumber" @input="addressDetailChanged">
		</p>
		<p class="form-row">
			<input class="input-text" :placeholder="i18n.blockNumber" type="text" v-model="blockNumber" @input="addressDetailChanged">
		</p>
		<p class="form-row">
			<input class="input-text" :placeholder="i18n.entranceNumber" type="text" v-model="entranceNumber" @input="addressDetailChanged">
		</p>
		<p class="form-row">
			<input class="input-text" :placeholder="i18n.floorNumber" type="text" v-model="floorNumber" @input="addressDetailChanged">
		</p>
		<p class="form-row">
			<input class="input-text" :placeholder="i18n.apartmentNumber" type="text" v-model="apartmentNumber" @input="addressDetailChanged">
		</p>
	  </div>
	</div>
</template>

<script>
import { getCookie,setCookie } from '../../../utils';
import cloneDeep from 'lodash/cloneDeep';
import debounce from 'lodash/debounce';
import axios from 'axios';
import Qs from 'qs';
import Multiselect from 'vue-multiselect';

export default {
	components: { Multiselect },
	data() {
		return {
			countryField: $('#billing_country'),
			Address1Field: $('#billing_address_1'),
			Address2Field: $('#billing_address_2'),
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
			selectedAddress: [],
      		addresses: [],
			state: '',
			city: '',
			streetNumber: '',
			blockNumber: '',
			entranceNumber: '',
			floorNumber: '',
			apartmentNumber: '',
			other: '',
			isLoading: false,
			document: $( document.body ),
			i18n: wooBg_econt_address.i18n,
		}
	},
	computed: {
		closeOnSelect() {
			return ( this.city ) ? true : false ;
		},
	},
	mounted() {
		let _this = this;
		this.loadLocalStorage();

		this.checkFields();

		$('#ship-to-different-address-checkbox').on( 'change', function () {
			_this.checkFields();
		});

		this.loadCity();

		$('form.checkout').on('checkout_place_order', function (e) {
			$('#billing_address_1').attr('disabled', false );
			$('#shipping_address_1').attr('disabled', false );
		});

		this.document.on( 'update_checkout.onUpdate', this.onUpdate );
		this.phoneField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.firstNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.lastNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.toCompanyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.vatField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.companyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.molField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.eikField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );

		if ( window.econtAddressInitialUpdate ) {
			this.document.trigger('update_checkout');
			window.econtAddressInitialUpdate = false;
			this.setAddress1FieldData();
		}
	},
	methods: {
		checkFields() {
			$('#billing_address_1').attr('disabled', false);
			$('#shipping_address_1').attr('disabled', false);

			if ( $('#ship-to-different-address-checkbox').is(":checked") ) {
				this.countryField = $( '#shipping_country' );
				this.Address1Field = $( '#shipping_address_1' );
				this.Address2Field = $( '#shipping_address_2' );
				this.stateField = $( '#shipping_state' );
				this.cityField = $( '#shipping_city' );
				this.firstNameField = $( '#shipping_first_name' );
				this.lastNameField = $( '#shipping_last_name' );
			} else {
				this.countryField = $( '#billing_country' );
				this.Address1Field = $( '#billing_address_1' );
				this.Address2Field = $( '#billing_address_2' );
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

			let _this = this;

			this.cityField.on('change', function () {
				_this.city = $(this).val();
			});

			this.stateField.on('change', function () {
				_this.state = $(this).val();
			});

			this.countryField.on('change', function () {
				setCookie( 'woo-bg--econt-address', '', 1 );
				_this.state = $(this).val();
			});

			this.cityField.on('change.loadCity', this.loadCity);
			this.stateField.on('change.loadCity', this.loadCity);
			this.countryField.on('change.loadCity', this.loadCity);
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--econt-address' );

			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedAddress = cloneDeep( localStorageData.selectedAddress );
				this.addresses = cloneDeep( localStorageData.addresses );
				this.state = cloneDeep( localStorageData.state );
				this.city = cloneDeep( localStorageData.city );
				this.streetNumber = cloneDeep( localStorageData.streetNumber || '' );
				this.blockNumber = cloneDeep( localStorageData.blockNumber || '' );
				this.entranceNumber = cloneDeep( localStorageData.entranceNumber || '' );
				this.floorNumber = cloneDeep( localStorageData.floorNumber || '' );
				this.apartmentNumber = cloneDeep( localStorageData.apartmentNumber || '' );
				this.other = cloneDeep( localStorageData.other || '' );
			}
		},
		asyncFind: debounce( function( query ) {
			if ( !query ) {
				return;
			}

			this.isLoading = true;
			let data = {
				query,
				action: 'woo_bg_econt_search_address',
				country: this.countryField.val(),
				state: this.state,
				city: this.city
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then( response => {
					if ( response.data.data.cities ) {
						this.addresses = cloneDeep( response.data.data.cities );
					} else if ( response.data.data.streets ) {
						this.addresses = cloneDeep( response.data.data.streets );
					}

					this.isLoading = false
				});
		}, 200 ),
		clearAll () {
			this.selectedAddress = []
		},
		loadCity() {
			this.state = this.stateField.val();
			this.city = this.cityField.val();
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_econt_load_streets',
				state: this.state,
				city: this.city,
				country: this.countryField.val()
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					let selectedAddress = [];
					let clearAdditionaFields = true;

					if ( response.data.data.status === 'invalid-city' ) {
						_this.addresses = cloneDeep( response.data.data.cities );
						_this.resetData();
					} else {
						_this.addresses = cloneDeep( response.data.data.streets );
					}

					_this.addresses.forEach( function ( address ) {
						if ( 
							_this.selectedAddress.id == address.id && 
							_this.selectedAddress.type == address.type && 
							_this.selectedAddress.orig_key == address.orig_key 
						) {
							selectedAddress = address;
							clearAdditionaFields = false;
						}
					});

					_this.selectedAddress = cloneDeep( selectedAddress );
					if ( clearAdditionaFields ) {
						_this.streetNumber = '';
						_this.blockNumber = '';
						_this.entranceNumber = '';
						_this.floorNumber = '';
						_this.apartmentNumber = '';
						_this.other = '';
					}

					_this.setCookieData();
					_this.loading = false;
				} )
				.catch( error => {
					_this.message = "Имаше проблем. За повече информация вижте конзолата.";
					_this.loading = false;
				} );
		},
		setAddress( option, id ) {
			if ( !this.city ) {
				this.city = option.label;
				this.cityField.val( option.label );
				this.addresses = [];
				this.selectedAddress = cloneDeep([]);
				return;
			}

			this.streetNumber = '';
			this.blockNumber = '';
			this.entranceNumber = '';
			this.floorNumber = '';
			this.apartmentNumber = '';
			this.other = '';
			this.setAddress1FieldData();
			this.setLocalStorageData();
			this.document.trigger('update_checkout');
		},
		addressDetailChanged: debounce( function () {
			this.setAddress1FieldData();
			this.setLocalStorageData();

			this.document.trigger('update_checkout');
		}, 2000 ),
		resetData() {
			this.city = '';
			this.selectedAddress = [];
			this.streetNumber = '';
			this.blockNumber = '';
			this.entranceNumber = '';
			this.floorNumber = '';
			this.apartmentNumber = '';
			this.other = '';
			localStorage.removeItem( 'woo-bg--econt-address' );
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
				type: 'address',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedAddress: this.selectedAddress,
				state: this.state,
				city: this.city,
				streetNumber: this.streetNumber,
				blockNumber: this.blockNumber,
				entranceNumber: this.entranceNumber,
				floorNumber: this.floorNumber,
				apartmentNumber: this.apartmentNumber,
				other: this.formatAddressDetails() || this.other,
				otherField: this.Address2Field.val(),
				country: this.countryField.val(),
				payment: $('input[name="payment_method"]:checked').val(),
			}

			cookie = encodeURIComponent( JSON.stringify( cookie ) );

			setCookie( 'woo-bg--econt-address', cookie, 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedAddress: this.selectedAddress,
				addresses: this.addresses,
				state: this.state,
				city: this.city,
				streetNumber: this.streetNumber,
				blockNumber: this.blockNumber,
				entranceNumber: this.entranceNumber,
				floorNumber: this.floorNumber,
				apartmentNumber: this.apartmentNumber,
				other: this.other,
			}

			localStorage.setItem( 'woo-bg--econt-address', JSON.stringify( localStorageData ) );
		},
		setAddress1FieldData() {
			let parts = [];
			let addressDetails = this.formatAddressDetails();

			if ( !this.selectedAddress || !this.selectedAddress.type ) {
				this.Address1Field.val( '' );
				return;
			}

			if ( this.selectedAddress.type === 'streets' ) {
				parts = [this.selectedAddress.label, this.streetNumber, addressDetails];
			} else if ( this.selectedAddress.type === 'quarters' ) {
				parts = [this.selectedAddress.label, addressDetails || this.other];
			}

			this.Address1Field.val( parts.filter(Boolean).join(' ') );
		},
		formatAddressDetails() {
			return [
				{ label: this.i18n.blockNumber, value: this.blockNumber },
				{ label: this.i18n.entranceNumber, value: this.entranceNumber },
				{ label: this.i18n.floorNumber, value: this.floorNumber },
				{ label: this.i18n.apartmentNumber, value: this.apartmentNumber },
			]
				.filter( detail => String( detail.value ).trim() )
				.map( detail => detail.label + ' ' + detail.value )
				.join(', ');
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
		this.cityField.off('change.loadCity');
		this.stateField.off('change.loadCity');
		this.countryField.off('change.loadCity');

		this.document.off( 'update_checkout.onUpdate');
		this.phoneField.off( 'change.triggerUpdate' );
		this.firstNameField.off( 'change.triggerUpdate' );
		this.lastNameField.off( 'change.triggerUpdate' );

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
