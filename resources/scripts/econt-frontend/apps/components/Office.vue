<template>
	<div class="woo-bg--econt-delivery">
		<div class="woo-bg--office-error" v-if="error">{{error}}</div>

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
			let url = 'https://officelocator.econt.com/?city=' + this.city + '&officeType=office&shopUrl=' + window.location.href;
			let _this = this;

			setTimeout(function() {
				_this.initOfficeLocator();
			}, 50);

			return url;
		},
	},
	mounted() {
		let _this = this;
		this.loadLocalStorage();

		this.checkFields();

		$('#ship-to-different-address-checkbox').on( 'change', function () {
			_this.checkFields();
		});

		this.loadOffices();

		this.initOfficeLocator();

		$('form.checkout').on('checkout_place_order', function (e) {
			$('#billing_address_1').attr('disabled', false );
			$('#shipping_address_1').attr('disabled', false );
		});

		this.document.on( 'update_checkout.onUpdate', this.onUpdate );
		this.document.on( 'update_checkout.setCookieOffice', this.setCookieData );
		this.phoneField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.firstNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.lastNameField.on( 'change.triggerUpdate', this.triggerUpdateCheckout );
		this.toCompanyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.vatField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.companyField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.molField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.eikField.on( 'change.maybeTriggerUpdate', this.maybeTriggerUpdate );
		this.cityField.on('change.loadOffices', function () {
			_this.city = $(this).val();
			_this.loadOffices();
		});

		this.stateField.on('change.loadOffices', function () {
			_this.state = $(this).val();
			_this.loadOffices();
		});
		
		if ( window.econtOfficeInitialUpdate ) {
			this.document.trigger('update_checkout');
			window.econtOfficeInitialUpdate = false;
			this.setAddress1FieldData();
		}
	},
	methods: {
		setOfficeFromLocator( message ) {
			if ( message.origin !== 'https://officelocator.econt.com' ) {
				return;
			}

			if ( typeof message.data.office === 'undefined' ) {
				return;
			}

			let officeID = message.data.office.code;

			if ( this.offices.length ) {
				let _this = this;

				this.offices.forEach( function ( office ) {
					if ( office.code == officeID ) {
						_this.selectedOffice = office;
						_this.setOffice();
					}
				});
			}
			$.magnificPopup.close();
		},
		initOfficeLocator() {
			console.log($('#woo-bg--econt-office-locator').length);

			$('#woo-bg--econt-office-locator').magnificPopup({
				type:'iframe',
				midClick: true,
				iframe: {
					markup: '<div class="mfp-iframe-scaler">'+
		            '<div class="mfp-close"></div>'+
		            '<iframe allow="geolocation;" style="border-width: 0px;" class="mfp-iframe"></iframe>'+
		          '</div>', 
				}
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
			let localStorageData = localStorage.getItem( 'woo-bg--econt-office' );
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
				action: 'woo_bg_econt_load_offices',
				state: this.state,
				city: this.city,
				country: this.countryField.val()
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.error = '';
					let selectedOffice = [];

					if ( response.data.data.status === 'invalid-city' ) {
						_this.error = response.data.data.error;
						_this.resetData();
					} else {
						if ( response.data.data.offices.length ) {
							_this.offices = cloneDeep( response.data.data.offices );
						} else {
							_this.error = response.data.data.error;
							_this.resetData();
						}
					}

					_this.offices.forEach( function ( office ) {
						if ( _this.selectedOffice.code == office.code ) {
							selectedOffice = office;
						}
					});

					_this.selectedOffice = cloneDeep( selectedOffice );

					_this.setCookieData();
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
				billing_company: formData.billing_company,
				billing_vat_number: formData.billing_vat_number,
				type: 'office',
				receiver: first_name + ' ' + last_name,
				phone: phone,
				selectedOffice: this.selectedOffice.code,
				selectedOfficeIsAPS: this.selectedOffice.isAPS,
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
			this.setCookieData();
		},
		setAddress1FieldData() {
			let shippingAddress = "";

			if ( this.selectedOffice.name ) {
				shippingAddress = this.i18n.toOffice + this.selectedOffice.name + ' ( ' + this.selectedOffice.address.fullAddress + ' ) ';
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
		this.document.off( 'update_checkout.setCookieOffice');
		this.phoneField.off( 'change.triggerUpdate' );
		this.firstNameField.off( 'change.triggerUpdate' );
		this.lastNameField.off( 'change.triggerUpdate' );
		this.cityField.off('change.loadOffices' );
		this.stateField.off('change.loadOffices' );

		this.toCompanyField.off( 'change.maybeTriggerUpdate' );
		this.vatField.off( 'change.maybeTriggerUpdate' );
		this.companyField.off( 'change.maybeTriggerUpdate' );
		this.molField.off( 'change.maybeTriggerUpdate' );
		this.eikField.off( 'change.maybeTriggerUpdate' );

		$('#billing_address_1').attr('disabled', false);
		$('#shipping_address_1').attr('disabled', false);
		
		window.removeEventListener( 'message', this.setOfficeFromLocator, false );
	}
}
</script>