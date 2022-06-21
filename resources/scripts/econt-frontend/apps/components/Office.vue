<template>
	<div class="woo-bg--econt-delivery">
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
				<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }} ( {{ option.address.fullAddress }} )</strong></template>

				<span slot="noResult">{{i18n.noResult}}</span>
				<span slot="noOptions">{{i18n.noOptions}}</span>
				<span slot="placeholder">{{i18n.searchOffice}}</span>
			</multiselect>

			<a id="woo-bg--econt-office-locator" :href="officeLocatorUrl">{{i18n.officeLocator}}</a>
		</div>
	</div><!-- /.section__contact -->
</template>

<script>
import { getCookie,setCookie } from '../../../utils';
import _ from 'lodash';
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
			i18n: wooBg_econt.i18n,
		}
	},
	computed: {
		officeLocatorUrl() {
			let url = 'https://bgmaps.com/templates/econt?address=' + this.city + '&office_type=to_office_courier&shop_url=' + window.location.href;

			return url;
		},
	},
	mounted() {
		window.econtOfficeIsMounted = true;
		let _this = this;
		this.loadLocalStorage();

		this.checkFields();

		$('#ship-to-different-address-checkbox').on( 'change', function () {
			_this.checkFields();
		});

		this.loadOffices();

		this.initOfficeLocator();

		this.document.on( 'update_checkout.setCookieOffice', this.setCookieData );
		this.phoneField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.firstNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.lastNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		
		if ( window.wooBgEcontDoUpdate ) {
			this.document.trigger('update_checkout');
		}
	},
	methods: {
		setOfficeFromLocator( message ) {
			if ( message.origin !== 'https://bgmaps.com' ) {
				return;
			}

			let officeID = message.data.split('||')[0];

			if ( this.offices.length ) {
				let _this = this;

				this.offices.forEach( function ( office ) {
					if ( office.code == officeID ) {
						_this.selectedOffice = office;
						_this.document.trigger('update_checkout');
					}
				});
			}
			$.magnificPopup.close();
		},
		initOfficeLocator() {
			$('#woo-bg--econt-office-locator').magnificPopup({
				type:'iframe',
				midClick: true,
			});

			window.addEventListener( 'message', this.setOfficeFromLocator, false );
		},
		compileLabel({ name, address }) {
	      return `${name} (${address.fullAddress})`;
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
			let localStorageData = localStorage.getItem( 'woo-bg--econt-office' );
			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.selectedOffice = _.cloneDeep( localStorageData.selectedOffice );
				this.offices = _.cloneDeep( localStorageData.offices );
				this.state = _.cloneDeep( localStorageData.state );
				this.city = _.cloneDeep( localStorageData.city );
				this.type = _.cloneDeep( localStorageData.type );
			}
		},
		loadOffices() {
			this.state = this.stateField.val();
			this.city = this.cityField.val();
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_econt_load_offices',
				state: this.state,
				city: this.city,
				country: this.countryField.val()
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					if ( response.data.data.status === 'invalid-city' ) {
						_this.error = response.data.data.error;
						_this.resetData();
						_this.offices = _.cloneDeep( [] );
					} else {
						_this.offices = _.cloneDeep( response.data.data.offices );
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

			let cookie = {
				type: 'office',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedOffice: this.selectedOffice.code,
				state: this.state,
				city: this.city,
				country: this.countryField.val(),
				payment: $('input[name="payment_method"]:checked').val(),
			};

			cookie = encodeURIComponent( JSON.stringify( cookie ) );

			setCookie( 'woo-bg--econt-address', cookie, 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedOffice: this.selectedOffice,
				offices: this.offices,
				state: this.state,
				city: this.city,
			}

			localStorage.setItem( 'woo-bg--econt-office', JSON.stringify( localStorageData ) );
		},
		resetData() {
			this.offices = [];
			this.selectedOffice = '';
			this.streetNumber = '';
			this.other = '';
			localStorage.removeItem( 'woo-bg--econt-office' );
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
		
		window.removeEventListener( 'message', this.setOfficeFromLocator, false );
	}
}
</script>