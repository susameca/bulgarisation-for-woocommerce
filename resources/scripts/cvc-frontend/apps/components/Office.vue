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
			stateField: $('#billing_state'),
			cityField: $('#billing_city'),
			firstNameField: $('#billing_first_name'),
			lastNameField: $('#billing_last_name'),
			phoneField: $('#billing_phone'),
			selectedOffice: [],
      		offices: [],
			state: '',
			city: '',
			error: '',
			document: $( document.body ),
			i18n: wooBg_cvc.i18n,
		}
	},
	mounted() {
		window.cvcOfficeIsMounted = true;
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

		if ( window.wooBgCvcDoUpdate ) {
			this.document.trigger('update_checkout');
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
				this.stateField = $( '#shipping_state' );
				this.cityField = $( '#shipping_city' );
				this.firstNameField = $( '#shipping_first_name' );
				this.lastNameField = $( '#shipping_last_name' );
			} else {
				this.countryField = $( '#billing_country' );
				this.stateField = $( '#billing_state' );
				this.cityField = $( '#billing_city' );
				this.firstNameField = $( '#billing_first_name' );
				this.lastNameField = $( '#billing_last_name' );
			}

			this.state = this.stateField.val();

			if ( this.cityField.val() ) {
				this.city = this.cityField.val();
			}

			let _this = this;

			this.cityField.on('change', function () {
				_this.city = $(this).val();
				_this.loadOffices();
			});

			this.stateField.on('change', function () {
				_this.state = $(this).val();
				_this.loadOffices();
			});
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--cvc-office' );
			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedOffice = cloneDeep( localStorageData.selectedOffice );
				this.offices = cloneDeep( localStorageData.offices );
				this.state = cloneDeep( localStorageData.state );
				this.city = cloneDeep( localStorageData.city );
				this.type = cloneDeep( localStorageData.type );
			}
		},
		loadOffices() {
			this.state = this.stateField.val();
			this.city = this.cityField.val();
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_cvc_load_offices',
				state: this.state,
				city: this.city,
				country: this.countryField.val()
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					console.log(response);
					if ( response.data.data.status === 'invalid-city' ) {
						_this.error = response.data.data.error;
						_this.resetData();
						_this.offices = cloneDeep( [] );
					} else {
						_this.offices = cloneDeep( response.data.data.offices );
						_this.error = false;
					}

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

			console.log( this.selectedOffice );
			let cookie = {
				type: 'office',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedOffice: this.selectedOffice.id,
				state: this.state,
				city: this.city,
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
				state: this.state,
				city: this.city,
			}

			localStorage.setItem( 'woo-bg--cvc-office', JSON.stringify( localStorageData ) );
		},
		resetData() {
			this.offices = [];
			this.selectedOffice = '';
			this.streetNumber = '';
			this.other = '';
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