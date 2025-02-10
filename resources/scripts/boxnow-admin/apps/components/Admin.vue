<template>
	<div class="panel-wrap woocommerce woocommerce--boxnow ajax-container" :data-loading="loading">
		<div id="order_data" class="panel woocommerce-order-data">
			<div class="order_data_column_container">
				<div class="order_data_column order_data_column--half">
					<h3>{{i18n.labelData}}</h3>

					<form>
						<h4>{{i18n.sendFrom}}</h4>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.warehouseApm}}:
							</label>

							<multiselect 
								v-model="origin" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="name" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( origins )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }}</strong></template>
							</multiselect>
						</p>

						<h4>{{i18n.sendTo}}</h4>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.apm}}:
							</label>

							<multiselect 
								v-model="destination" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="name" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( destinations )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }}</strong></template>
							</multiselect>
						</p>

						<p  class="form-field form-field-wide">
							<label>
								{{i18n.total}}:
							</label>

							<input v-model="declaredValue" type="number" step="0.01">
						</p>

						<p class="form-field form-field--checkbox">
							<label>
								{{i18n.allowReturn}}:
							</label>

							<input v-model="allowReturn" type="checkbox">
						</p>

						<p class="form-field form-field-wide" v-if="shipmentStatus">
							<button @click="deleteLabel" name="save" type="submit" :value="i18n.deleteLabel" class="button-secondary">{{i18n.deleteLabel}}</button>

							<button @click="updateShipmentStatus" name="save" type="submit" :value="i18n.updateShipmentStatus" class="button-primary woocommerce-save-button">{{i18n.updateShipmentStatus}}</button>
						</p>

						<p v-else class="form-field form-field-wide">
							<button @click="updateLabel" name="save" type="submit" :value="i18n.generateLabel" class="button-primary woocommerce-save-button">{{i18n.generateLabel}}</button>
						</p>
					</form>

					<div class="clear"></div>

					<div v-if="message" class="notice notice-error notice-alt"><p>{{message}}</p></div>
				</div><!-- /.order_data_column order_data_column-/-half -->

				<div class="order_data_column order_data_column--half">
					<div class="generated-label" v-if="shipmentStatus">
						<h3>{{i18n.label}}: {{shipmentStatus.id}}</h3>

						<iframe v-for="(iframe, key) in iframes" v-if="iframes.length" id="woo-bg--boxnow-label-print" :src="iframe"></iframe>
					</div>
				</div><!-- /.order_data_column order_data_column-/-half -->
			</div><!-- /.order_data_column_container -->

			<div class="woocommerce_order_status" v-if="statuses.length">
				<h3>{{i18n.shipmentStatus}}</h3>

				<table>
					<thead>
						<tr>
							<th> {{i18n.time}} </th>
							<th> {{i18n.details}} </th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(status, key) in statuses">
							<th> {{status.time}} </th>
							<th> {{status.details}} </th>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="clear"></div>
	</div>
</template>

<script>
import cloneDeep from 'lodash/cloneDeep';
import debounce from 'lodash/debounce';
import axios from 'axios';
import Qs from 'qs';
import Multiselect from 'vue-multiselect';

export default {
	components: { Multiselect },
	data() {
		return {
			loading: false,
			shipmentStatus : '',
			labelData : wooBg_boxnow.label,
			document: $( document.body ),
			destination: '',
			destinations: cloneDeep( wooBg_boxnow.destinations ),
			origin: '',
			origins: cloneDeep( wooBg_boxnow.origins ),
			i18n: wooBg_boxnow.i18n,
			cookie_data: cloneDeep( wooBg_boxnow.cookie_data ),
			declaredValue: '',
			allowReturn: false,
			message: '',
			operations : [],
		}
	},
	computed: {
		iframes() {
			let links = [];

			if ( this.shipmentStatus.id !== "undefined" ) {
				this.shipmentStatus.parcels.forEach( function ( parcel ) {
					let link = woocommerce_admin.ajax_url + '?cache-buster=' + Math.random()  + '&action=woo_bg_boxnow_print_label&parcel=' + parcel.id;
					links.push( link );
				});

			}

			return links;
		},
		statuses() {
			let statuses = [];

			if ( this.operations.length ) {
				this.operations.forEach( function ( status ) {
					let details = status.description;

					if ( typeof status.comment !== 'undefined' ) {
						details += ' - ' + status.comment;
					}

					let time = new Date( status.dateTime ).toLocaleString();

					statuses.push({
						time,
						details,
					} );
				});

				statuses.reverse();
			}

			return statuses;
		}
	},
	mounted() {
		let _this = this;

		if ( wooBg_boxnow.shipmentStatus ) {
			this.shipmentStatus = wooBg_boxnow.shipmentStatus;
		}

	  	this.document.on('change', 'input[name="label_size"]', function () {
			_this.size = $(this).val();
		});

		this.destinations.forEach( function ( destination ) {
			if ( destination.id == wooBg_boxnow.cookie_data.selectedApm ) {
				_this.destination = destination;
			}
		});

		this.origins.forEach( function ( origin ) {
			if ( origin.id == wooBg_boxnow.origin ) {
				_this.origin = origin;
			}
		});

		if ( typeof( wooBg_boxnow.label.amountToBeCollected ) !== 'undefined' ) {
			this.declaredValue = wooBg_boxnow.label.amountToBeCollected;
		}

		if ( typeof( wooBg_boxnow.allowReturn ) !== 'undefined' ) {
			this.allowReturn = wooBg_boxnow.allowReturn;
		}

		if ( wooBg_boxnow.operations ) {
			this.operations = wooBg_boxnow.operations;
		}
	},
	methods: {
		updateLabel( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_boxnow.orderId,
				origin: this.origin,
				destination: this.destination,
				declaredValue: this.declaredValue,
				allowReturn: this.allowReturn,
				action: 'woo_bg_boxnow_generate_label',
				nonce: wooBg_boxnow.nonce,
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;
					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					} else {
						_this.shipmentStatus = cloneDeep( response.data.data.shipmentStatus, true );
						_this.labelData = cloneDeep( response.data.data.label, true );
					}
				});
		},
		updateShipmentStatus( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_boxnow.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_speedy_update_shipment_status',
				nonce: wooBg_boxnow.nonce,
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;

					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					} else {
						_this.operations = cloneDeep( response.data.data.operations, true );
					}
				});
		},
		deleteLabel( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_boxnow.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_boxnow_delete_label',
				nonce: wooBg_boxnow.nonce,
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {

					_this.shipmentStatus = '';
					_this.operations = '';
					_this.loading = false;
				});
		},
	}
}
</script>