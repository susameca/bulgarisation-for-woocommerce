<template>
	<div class="woo-bg--econt-delivery">
	  <multiselect 
	  	v-model="selectedAddress" 
	  	id="ajax" 
	  	class="woo-bg-multiselect"
	  	:selectedLabel="i18n.selected" 
		:placeholder="i18n.searchAddress"
	  	:selectLabel="i18n.select"
	  	open-direction="bottom" 
	  	track-by="id"
	  	label="label"
	  	:options="addresses" 
	  	:multiple="false" 
	  	:searchable="true" 
	  	:loading="isLoading" 
	  	:internal-search="false" 
	  	:clear-on-select="false" 
	  	:close-on-select="closeOnSelect"
	  	:options-limit="30" 
	  	:limit="6"
	  	:max-height="600" 
	  	:show-no-results="true"
	  	@search-change="asyncFind"
	  	@input="setAddress"
	  >
	  	<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>

	    <span slot="noResult">{{i18n.noResult}}</span>
	    <span slot="noOptions">{{i18n.noOptions}}</span>
	  </multiselect>

	  <input 
	  	class="woo-bg-multiselect--additional-field"
	  	:placeholder="i18n.streetNumber" 
	  	type="text" 
	  	v-model="streetNumber" 
	  	v-if="( selectedAddress.type && selectedAddress.type === 'streets' )"
	  	@keyup="streetNumberChanged"
	  >
	  <input 
	  	class="woo-bg-multiselect--additional-field"
	  	:placeholder="i18n.blVhEt" 
	  	type="text" v-model="other" 
	  	v-if="( selectedAddress.type && selectedAddress.type === 'quarters' )"
	  	@keyup="streetNumberChanged"
	  >
	</div>
</template>

<script>
import { getCookie,setCookie } from '../../../utils';
import _ from 'lodash';
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
			selectedAddress: [],
      		addresses: [],
			state: '',
			city: '',
			streetNumber: '',
			other: '',
			isLoading: false,
			document: $( document.body ),
			i18n: wooBg_econt.i18n,
		}
	},
	computed: {
		closeOnSelect() {
			return ( this.city ) ? true : false ;
		},
	},
	mounted() {
		window.econtAddressIsMounted = true;
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

		if ( window.wooBgEcontDoUpdate ) {
			this.document.trigger('update_checkout');
		}
	},
	methods: {
		onUpdate() {
			this.Address1Field.attr('disabled', true);
			this.setCookieData();
		},
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
				_this.loadCity();
			});

			this.stateField.on('change', function () {
				_this.state = $(this).val();
				_this.loadCity();
			});

			this.countryField.on('change', function () {
				setCookie( 'woo-bg--econt-address', '', 1 );
				_this.state = $(this).val();
				_this.loadCity();
			});
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--econt-address' );

			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedAddress = _.cloneDeep( localStorageData.selectedAddress );
				this.addresses = _.cloneDeep( localStorageData.addresses );
				this.state = _.cloneDeep( localStorageData.state );
				this.city = _.cloneDeep( localStorageData.city );
				this.streetNumber = _.cloneDeep( localStorageData.streetNumber );
				this.other = _.cloneDeep( localStorageData.other );
			}
		},
		asyncFind: _.debounce( function( query ) {
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
						this.addresses = _.cloneDeep( response.data.data.cities );
					} else if ( response.data.data.streets ) {
						this.addresses = _.cloneDeep( response.data.data.streets );
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
					if ( response.data.data.status === 'invalid-city' ) {
						_this.addresses = _.cloneDeep( response.data.data.cities );
						_this.resetData();
					} else {
						_this.addresses = _.cloneDeep( response.data.data.streets );
					}

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
				this.selectedAddress = _.cloneDeep([]);
			}
		},
		streetNumberChanged: _.debounce( function () {
			this.setAddress1FieldData();
			this.setLocalStorageData();

			this.document.trigger('update_checkout');
		}, 500 ),
		resetData() {
			this.city = '';
			this.selectedAddress = '';
			this.streetNumber = '';
			this.other = '';
			localStorage.removeItem( 'woo-bg--econt-address' );
		},
		setCookieData() {
			let first_name = this.firstNameField.val();
			let last_name = this.lastNameField.val();
			let phone = this.phoneField.val();

			let cookie = _.cloneDeep( {
				type: 'address',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedAddress: this.selectedAddress,
				state: this.state,
				city: this.city,
				streetNumber: this.streetNumber,
				other: this.other,
				otherField: this.Address2Field.val(),
				country: this.countryField.val(),
				payment: $('input[name="payment_method"]:checked').val(),
			} );

			setCookie( 'woo-bg--econt-address', JSON.stringify( cookie ), 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedAddress: this.selectedAddress,
				addresses: this.addresses,
				state: this.state,
				city: this.city,
				streetNumber: this.streetNumber,
				other: this.other,
			}

			localStorage.setItem( 'woo-bg--econt-address', JSON.stringify( localStorageData ) );
		},
		setAddress1FieldData() {
			let shippingAddress = '';

			if ( this.selectedAddress.type === 'streets' ) {
				shippingAddress = this.selectedAddress.label + ' ' + this.streetNumber;
			} else if ( this.selectedAddress.type === 'quarters' ) {
				shippingAddress = this.selectedAddress.label + ' ' + this.other;
			}

			this.Address1Field.val( shippingAddress );
		},
	},
	beforeDestroy() {
		this.document.off( 'update_checkout.onUpdate');

		//setCookie( 'woo-bg--econt-address', '', 1 );
		$('#billing_address_1').attr('disabled', false);
		$('#shipping_address_1').attr('disabled', false);
	}
}
</script>