<template>
	<div class="woo-bg--cvc-delivery">
		<div v-if="error">{{error}}</div>

		<div v-else>
			<multiselect 
				id="ajax" 
				class="woo-bg-multiselect"
				placeholder=""
				track-by="id"
				label="name"
			  	deselect-label=""
				open-direction="bottom" 
				v-model="selectedOffice" 
				:options-limit="30" 
				:limit="6"
				:max-height="600" 
				:selectedLabel="i18n.selected" 
			  	:selectLabel="i18n.select"
				:options="offices" 
				:custom-label="compileLabel" 
				:multiple="false" 
				:searchable="true" 
				:show-no-results="true"
				@input="setOffice"
			>
				<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name_bg }}</strong></template>

				<span slot="noResult">{{i18n.noResult}}</span>
				<span slot="noOptions">{{i18n.noOptions}}</span>
				<span slot="placeholder">{{i18n.searchOffice}}</span>
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
			firstNameField: $('#billing_first_name'),
			lastNameField: $('#billing_last_name'),
			phoneField: $('#billing_phone'),
			selectedOffice: [],
      		offices: [],
			error: '',
			document: $( document.body ),
			i18n: wooBg_cvc.i18n,
		}
	},
	mounted() {
		let _this = this;
		this.loadLocalStorage();

		this.checkFields();

		$('#ship-to-different-address-checkbox').on( 'change', function () {
			_this.checkFields();
		});

		this.loadOffices();

		this.document.on( 'update_checkout.setCookieOffice', this.setCookieData );
		this.phoneField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.firstNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.lastNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );

		if ( window.cvcOfficeInitialUpdate ) {
			this.document.trigger('update_checkout');
			window.cvcOfficeInitialUpdate = false;
		}
	},
	methods: {
		compileLabel({ name_bg }) {
	      return `${name_bg}`;
	    },
		checkFields() {
			$('#billing_address_1').attr('disabled', false);
			$('#shipping_address_1').attr('disabled', false);

			if ( $('#ship-to-different-address-checkbox').is(":checked") ) {
				this.countryField = $( '#shipping_country' );
				this.firstNameField = $( '#shipping_first_name' );
				this.lastNameField = $( '#shipping_last_name' );
			} else {
				this.countryField = $( '#billing_country' );
				this.firstNameField = $( '#billing_first_name' );
				this.lastNameField = $( '#billing_last_name' );
			}

			let _this = this;
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--cvc-office' );
			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedOffice = cloneDeep( localStorageData.selectedOffice );
				this.offices = cloneDeep( localStorageData.offices );
				this.type = cloneDeep( localStorageData.type );
			}
		},
		loadOffices() {
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_cvc_load_offices',
				country: this.countryField.val()
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.offices = cloneDeep( response.data.data.offices );
					_this.error = false;

					_this.loading = false;
				} )
				.catch( error => {
					_this.message = "Имаше проблем. За повече информация вижте конзолата.";
					console.log(error);
					_this.loading = false;
				} );
		},
		setOffice() {
			this.setLocalStorageData();

			this.document.trigger('update_checkout');
		},
		setCookieData() {
			let first_name = this.firstNameField.val();
			let last_name = this.lastNameField.val();
			let phone = this.phoneField.val();

			let cookie = {
				type: 'office',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedOffice: this.selectedOffice.id,
				state: '',
				city: '',
				country: this.countryField.val(),
				payment: $('input[name="payment_method"]:checked').val(),
			};

			cookie = encodeURIComponent( JSON.stringify( cookie ) );

			setCookie( 'woo-bg--cvc-address', cookie, 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedOffice: this.selectedOffice,
				offices: this.offices,
			}

			localStorage.setItem( 'woo-bg--cvc-office', JSON.stringify( localStorageData ) );
		},
		resetData() {
			this.offices = [];
			this.selectedOffice = '';
			localStorage.removeItem( 'woo-bg--cvc-office' );
		},
		triggerUpdateCheckout() {
			this.document.trigger('update_checkout');
		},
	},
	beforeDestroy() {
		this.document.off( 'update_checkout.setCookieOffice');
		this.phoneField.off( 'change.triggerUpdate' );
		this.firstNameField.off( 'change.triggerUpdate' );
		this.lastNameField.off( 'change.triggerUpdate' );
	}
}
</script>