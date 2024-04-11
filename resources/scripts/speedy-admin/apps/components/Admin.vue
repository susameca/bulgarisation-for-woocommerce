<template>
	<div class="panel-wrap woocommerce woocommerce--speedy ajax-container" :data-loading="loading">
		<div id="order_data" class="panel woocommerce-order-data">
			<div class="order_data_column_container">
				<div class="order_data_column order_data_column--half">
					<h3>{{i18n.labelData}}</h3>

					<form>
						<p v-if="!shipmentStatus" class="form-field form-field-wide">
							<label>
								{{i18n.deliveryType}}:
							</label>

							<multiselect 
								v-model="type" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( types )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="( type.id == 'office' )" class="form-field form-field-wide">
							<label>
								{{i18n.office}}:
							</label>

							<multiselect 
								v-model="office" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="name" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( offices )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }}</strong></template>
							</multiselect>
						</p>

						<div v-if="( type.id == 'address' )">
							<p class="form-field form-field-wide">
								<label>
									{{i18n.streetQuarter}}:
								</label>

								<multiselect 
									v-model="street" 
									deselect-label="" 
									selectLabel="" 
									track-by="id" 
									label="label" 
									:selectedLabel="i18n.selected" 
									:placeholder="i18n.choose"
									:options="Object.values( streets )" 
									:searchable="true" 
									:allow-empty="false"
									@search-change="asyncFind"
								>
									<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
								</multiselect>
							</p>

							<p class="form-field form-field-wide">
								<input 
									class="woo-bg-multiselect--additional-field"
									:placeholder="i18n.streetNumber" 
									type="text" 
									v-model="streetNumber" 
									v-if="( street.type && street.type === 'streets' )"
								>
								
								<input 
									class="woo-bg-multiselect--additional-field"
									:placeholder="i18n.mysticQuarter" 
									type="text" 
									v-model="cookie_data.mysticQuarter" 
									v-if="!streets.length"
								>

								<input 
									class="woo-bg-multiselect--additional-field"
									:placeholder="i18n.blVhEt" 
									type="text" 
									v-model="other" 
									v-if="( street.type && street.type === 'quarters' || !streets.length )"
								>
							</p>
							<p class="form-field form-field-wide">
								<input 
									class="woo-bg-multiselect--additional-field"
									:placeholder="i18n.blVhEt" 
									type="text" 
									v-model="other" 
									v-if="( street.type && street.type === 'streets' )"
								>
							</p>
						</div>

						<p v-if="labelData.content" class="form-field form-field-wide">
							<label>
								{{i18n.packCount}}:
							</label>

							<input v-model="labelData.content.parcelsCount" type="number">
						</p>

						<p v-if="( typeof( labelData.service.additionalServices ) !== 'undefined' && typeof( labelData.service.additionalServices.cod) !== 'undefined' ) && paymentType === 'cod'" class="form-field form-field-wide">
							<label>
								{{i18n.cd}}:
							</label>

							<input v-model="labelData.service.additionalServices.cod.amount" type="number">
						</p>

						<p  class="form-field form-field-wide">
							<label>
								{{i18n.declaredValue}}:
							</label>

							<input v-model="declaredValue" type="number">
						</p>

						<p v-if="labelData.weight" class="form-field form-field-wide">
							<label>
								{{i18n.weight}}:
							</label>

							<input v-model="labelData.weight" type="number">
						</p>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.description}}:
							</label>

							<input v-model="labelData.content.contents" type="text">
						</p>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.deliveryPayedBy}}:
							</label>

							<multiselect 
								v-model="paymentBy" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose" 
								:options="Object.values( paymentByTypes )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="( paymentBy.id === 'fixed' )" class="form-field form-field-wide">
							<label>
								{{i18n.fixedPrice}}:
							</label>

							<input v-model="cookie_data.fixed_price" type="number">
						</p>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.reviewAndTest}}:
							</label>

							<multiselect 
								v-model="testOption" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose" 
								:options="Object.values( testsOptions )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p class="form-field form-field-wide" v-if="shipmentStatus">
							<button @click="updateLabel" name="save" type="submit" :value="i18n.updateLabel" class="button-primary woocommerce-save-button">{{i18n.updateLabel}}</button>

							<button @click="updateShipmentStatus" name="save" type="submit" :value="i18n.updateShipmentStatus" class="button-primary woocommerce-save-button">{{i18n.updateShipmentStatus}}</button>

						</p>

						<p v-else class="form-field form-field-wide">
							<button @click="updateLabel" name="save" type="submit" :value="i18n.generateLabel" class="button-primary woocommerce-save-button">{{i18n.generateLabel}}</button>
						</p>

						<p class="form-field form-field-wide"> 
							<button v-if="shipmentStatus" @click="deleteLabel" name="save" type="submit" :value="i18n.deleteLabel" class="button-secondary">{{i18n.deleteLabel}}</button>

							<a v-clipboard:copy="labelJSON" v-clipboard:success="onCopy" class="button-secondary">{{i18n.copyLabelData}}</a> 
						</p>
					</form>

					<div class="clear"></div>

					<div v-if="message" class="notice notice-error notice-alt"><p>{{message}}</p></div>
				</div><!-- /.order_data_column order_data_column-/-half -->

				<div class="order_data_column order_data_column--half">
					<div class="generated-label" v-if="shipmentStatus">
						<h3>{{i18n.label}}: {{shipmentStatus.id}}</h3>

						<div>
							<span class="woo-bg--radio">
					  			<input id="label_size_default" type="radio" name="label_size" value="A6" checked>
					  			<label for="label_size_default">A6</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_A4_4xA6" type="radio" name="label_size" value="A4_4xA6" >
					  			<label for="label_size_A4_4xA6">A4_4xA6</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_a4" type="radio" name="label_size" value="A4" >
					  			<label for="label_size_a4">A4</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_a4_same" type="radio" name="label_size" value="A4&awbsc=ON_SAME_PAGE" >
					  			<label for="label_size_a4_same">{{i18n.a4WithCopy}}</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_a4_single" type="radio" name="label_size" value="A4&awbsc=ON_SINGLE_PAGE" >
					  			<label for="label_size_a4_single">{{i18n.a4OnSingle}}</label>
							</span>
						</div>

						<iframe id="woo-bg--speedy-label-print" :src="iframeUrl"></iframe>
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
			type: '',
			types: [
				{
					id: 'office',
					label: wooBg_speedy.i18n.office
				},
				{
					id: 'address',
					label: wooBg_speedy.i18n.address
				}
			],
			paymentType: wooBg_speedy.paymentType,
			paymentBy: '',
			paymentByTypes: [
				{
					id: 'RECIPIENT',
					label: wooBg_speedy.i18n.buyer
				},
				{
					id: 'SENDER',
					label: wooBg_speedy.i18n.sender
				},
				{
					id: 'fixed',
					label: wooBg_speedy.i18n.fixedPrice
				}
			],
			size: 'A6',
			shipmentStatus : '',
			labelData : wooBg_speedy.label,
			document: $( document.body ),
			office: '',
			offices: cloneDeep( wooBg_speedy.offices ),
			street: '',
			streets: cloneDeep( wooBg_speedy.streets ),
			testOption: '',
			testsOptions: cloneDeep( wooBg_speedy.testsOptions ),
			streetNumber: '',
			other: '',
			message: '',
			i18n: wooBg_speedy.i18n,
			cookie_data: cloneDeep( wooBg_speedy.cookie_data ),
			declaredValue: '',
			operations : [],
		}
	},
	computed: {
		iframeUrl() {
			let parcels = [];
			let link = '';

			if ( this.shipmentStatus.id !== "undefined" ) {
				this.shipmentStatus.parcels.forEach( function ( parcel ) {
					parcels.push( parcel.id );
				});

				link = woocommerce_admin.ajax_url + '?cache-buster=' + Math.random()  + '&action=woo_bg_speedy_print_labels&parcels=' + parcels.join('|') + "&size=" + this.size;
			}

			return ( parcels.length ) ? link : '';
		},
		labelJSON() {
			return JSON.stringify( this.labelData );
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

		if ( wooBg_speedy.shipmentStatus ) {
			this.shipmentStatus = wooBg_speedy.shipmentStatus;
		}

	  	this.document.on('change', 'input[name="label_size"]', function () {
			_this.size = $(this).val();
		});

		this.types.forEach( function ( type ) {
			if ( type.id == wooBg_speedy.cookie_data.type ) {
				_this.type = type;
			}
		});

		this.testsOptions.forEach( function ( option ) {
			if ( option.id == wooBg_speedy.testOption ) {
				_this.testOption = option;
			}
		});

		if ( wooBg_speedy.cookie_data.type == 'office' ) {
			this.offices.forEach( function ( office ) {
				if ( office.id == wooBg_speedy.cookie_data.selectedOffice ) {
					_this.office = office;
				}
			});
		} else {
			this.streetNumber = wooBg_speedy.cookie_data.streetNumber;
			this.other = wooBg_speedy.cookie_data.other;
			this.streets.forEach( function ( street ) {
				if ( street.orig_key == wooBg_speedy.cookie_data.selectedAddress.orig_key ) {
					_this.street = street;
				}
			});
		}

		this.paymentBy = this.paymentByTypes[0];

		if ( wooBg_speedy.cookie_data.fixed_price ) {
			this.paymentBy = this.paymentByTypes[2];
		} else if (  wooBg_speedy.label.payment.courierServicePayer === 'SENDER'  ) {
			this.paymentBy = this.paymentByTypes[1];
		}

		if ( typeof( wooBg_speedy.label.service.additionalServices.declaredValue ) !== 'undefined' ) {
			this.declaredValue = wooBg_speedy.label.service.additionalServices.declaredValue.amount;
		}

		if ( wooBg_speedy.operations ) {
			this.operations = wooBg_speedy.operations;
		}
	},
	methods: {
		asyncFind: debounce( function( query ) {
			if ( !query ) {
				return;
			}

			this.isLoading = true;
			let data = {
				query,
				action: 'woo_bg_speedy_search_address',
				country: wooBg_speedy.cookie_data.country,
				state: wooBg_speedy.cookie_data.state,
				city: wooBg_speedy.cookie_data.city,
				nonce: wooBg_speedy.nonce,
			}

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then( response => {
					if ( response.data.data.cities ) {
						this.streets = cloneDeep( response.data.data.cities );
					} else if ( response.data.data.streets ) {
						this.streets = cloneDeep( response.data.data.streets );
					}

					this.isLoading = false
				});
		}, 200 ),
		onCopy: function (e) {
	      alert( this.i18n.copyLabelDataMessage );
	    },
		updateLabel( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				type: this.type,
				label_data: this.labelData,
				office: this.office,
				street: this.street,
				streetNumber: this.streetNumber,
				other: this.other,
				paymentBy: this.paymentBy,
				testOption: this.testOption,
				cookie_data: this.cookie_data,
				orderId: wooBg_speedy.orderId,
				declaredValue: this.declaredValue,
				action: 'woo_bg_speedy_generate_label',
				nonce: wooBg_speedy.nonce,
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
				orderId: wooBg_speedy.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_speedy_update_shipment_status',
				nonce: wooBg_speedy.nonce,
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
				orderId: wooBg_speedy.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_speedy_delete_label',
				nonce: wooBg_speedy.nonce,
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