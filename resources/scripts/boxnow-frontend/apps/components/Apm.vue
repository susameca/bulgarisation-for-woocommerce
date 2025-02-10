 <template>
	<div class="woo-bg--boxnow-delivery">
		<div class="woo-bg--apm-error" v-if="error">{{error}}</div>

		<div v-else>
			<multiselect 
				id="ajax" 
				class="woo-bg-multiselect"
				placeholder=""
				track-by="id"
				label="name"
			  	deselect-label=""
				open-direction="bottom" 
				v-model="selectedApm" 
				:options-limit="1000" 
				:limit="6"
				:max-height="600" 
				:selectedLabel="i18n.selected" 
			  	:selectLabel="i18n.select"
				:options="apms" 
				:custom-label="compileLabel" 
				:multiple="false" 
				:searchable="true" 
				:show-no-results="true"
				@input="setApm"
			>
				<span slot="noResult">{{i18n.noResult}}</span>
				<span slot="noOptions">{{i18n.noOptions}}</span>
				<span slot="placeholder">{{i18n.searchApm}}</span>
			</multiselect>

			<a id="woo-bg--boxnow-apm-locator" :href="apmLocatorUrl">{{i18n.apmLocator}}</a>
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
			Address1Field: '',
			selectedApm: [],
      		apms: [],
			error: '',
			document: $( document.body ),
			i18n: wooBg_boxnow.i18n,
		}
	},
	computed: {
		apmLocatorUrl() {
			let url = 'https://widget-v5.boxnow.bg/iframe.html?gps=yes&autoselect=yes';
			let _this = this;

			setTimeout(function() {
				_this.initApmLocator();
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

		this.loadApms();

		this.initApmLocator();

		this.document.on( 'update_checkout.onUpdate', this.onUpdate );

		if ( window.boxnowApmInitialUpdate ) {
			this.document.trigger('update_checkout');
			window.boxnowApmInitialUpdate = false;
			this.setAddress1FieldData();
		}
	},
	methods: {
		setApmFromLocator( message ) {
			if ( message.origin !== 'https://widget-v5.boxnow.bg' ) {
				return;
			}

			let apmID = message.data.boxnowLockerId;

			if ( this.apms.length ) {
				let _this = this;

				this.apms.forEach( function ( apm ) {
					if ( apm.id == apmID ) {
						_this.selectedApm = apm;
						_this.setApm();
					}
				});
			}
			$.magnificPopup.close();
		},
		initApmLocator() {
			$('#woo-bg--boxnow-apm-locator').magnificPopup({
				type:'iframe',
				midClick: true,
			});

			window.addEventListener( 'message', this.setApmFromLocator, false );
		},
		compileLabel({ name, addressLine1, addressLine2 }) {
	      return `${name} (${addressLine2} - ${addressLine1})`;
	    },
		checkFields() {

			if ( $('#ship-to-different-address-checkbox').is(":checked") ) {
				this.Address1Field = $( '#shipping_address_1' );
			} else {
				this.Address1Field = $( '#billing_address_1' );
			}
		},
		loadLocalStorage(){
			let localStorageData = localStorage.getItem( 'woo-bg--boxnow-apm' );
			if ( localStorageData ) {
				localStorageData = JSON.parse( localStorageData );
				this.apms = cloneDeep( localStorageData.apms );
				this.selectedApm = cloneDeep( localStorageData.selectedApm );
			}
		},
		loadApms() {
			this.loading = true;

			let _this = this;
			let data = {
				action: 'woo_bg_boxnow_load_apms'
			}

			axios.post( woocommerce_params.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.error = '';
					let selectedApm = [];


					if ( response.data.data.apms.length ) {
						_this.apms = cloneDeep( response.data.data.apms );
					} else {
						_this.error = response.data.data.error;
						_this.resetData();
					}

					_this.apms.forEach( function ( apm ) {
						if ( _this.selectedApm && _this.selectedApm.id == apm.id ) {
							selectedApm = apm;
						}
					});

					_this.selectedApm = cloneDeep( selectedApm );
					_this.setCookieData();

					_this.loading = false;
				} )
				.catch( error => {
					_this.message = "Имаше проблем. За повече информация вижте конзолата.";
					console.log(error);
					_this.loading = false;
				} );
		},
		setApm() {
			this.setLocalStorageData();
			this.setAddress1FieldData();

			this.document.trigger('update_checkout');
		},
		setCookieData() {
			let cookie = {
				selectedApm: ( this.selectedApm ) ? this.selectedApm.id : null,
			};

			cookie = encodeURIComponent( JSON.stringify( cookie ) );

			setCookie( 'woo-bg--boxnow-apm', cookie, 1 );
		},
		setLocalStorageData() {
			let localStorageData = {
				selectedApm: this.selectedApm,
				apms: this.apms,
			}

			localStorage.setItem( 'woo-bg--boxnow-apm', JSON.stringify( localStorageData ) );
		},
		resetData() {
			this.apms = cloneDeep( [] );
			this.selectedApm = '';
			localStorage.removeItem( 'woo-bg--boxnow-apm' );
			this.setCookieData();
		},
		setAddress1FieldData() {
			let shippingAddress = "";

			if ( this.selectedApm && this.selectedApm.name ) {
				shippingAddress = this.i18n.toApm + this.selectedApm.name + ' ( ' + this.selectedApm.addressLine2 + ' - ' + this.selectedApm.addressLine1 + ' ) ';
			}

			this.Address1Field.val( shippingAddress );
		},
		triggerUpdateCheckout() {
			this.document.trigger('update_checkout');
		},
		onUpdate() {
			this.setCookieData();
		},
	},
	beforeDestroy() {
		this.document.off( 'update_checkout.setCookieApm');
		this.document.off( 'update_checkout.onUpdate');
		
		window.removeEventListener( 'message', this.setApmFromLocator, false );
	}
}
</script>