<template>
	<div class="panel-wrap woocommerce woocommerce--cvc ajax-container" :data-loading="loading">
		<div id="order_data" class="panel woocommerce-order-data">
			<div class="order_data_column_container">
				<div class="order_data_column order_data_column--half">
					<h3>{{i18n.labelData}}</h3>

					<form>
						<p v-if="( type.id == 'office' )" class="form-field form-field-wide">
							<label>
								{{i18n.office}}:
							</label>

							<multiselect 
								v-model="office" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="name_bg" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( offices )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name_bg }}</strong></template>
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
									:placeholder="i18n.blVhEt" 
									type="text" 
									v-model="other" 
									v-if="( street.type && street.type === 'quarters' )"
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

						<p v-if="labelData.total_parcels" class="form-field form-field-wide">
							<label>
								{{i18n.packCount}}:
							</label>

							<input v-model="labelData.total_parcels" type="number">
						</p>

						<p v-if="paymentType === 'cod'" class="form-field form-field-wide">
							<label>
								{{i18n.cd}}:
							</label>

							<input v-model="labelData.cod_amount" type="number">
						</p>

						<p  class="form-field form-field-wide">
							<label>
								{{i18n.declaredValue}}:
							</label>

							<input v-model="declaredValue" type="number">
						</p>

						<p v-if="labelData.total_kgs" class="form-field form-field-wide">
							<label>
								{{i18n.weight}}:
							</label>

							<input v-model="labelData.total_kgs" type="number">
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

						<p class="form-field form-field-wide" v-if="!shipmentStatus">
							<button @click="updateLabel" name="save" type="submit" :value="i18n.generateLabel" class="button-primary woocommerce-save-button">{{i18n.generateLabel}}</button>
						</p>

						<p v-else class="form-field form-field-wide">
							<button @click="updateActions" name="save" type="submit" :value="i18n.updateShipmentStatus" class="button-primary woocommerce-save-button">{{i18n.updateShipmentStatus}}</button>
						</p>

						<p class="form-field form-field-wide" > 
							<button v-if="shipmentStatus" @click="deleteLabel" name="save" type="submit" :value="i18n.deleteLabel" class="button-secondary">{{i18n.deleteLabel}}</button>

							<a v-clipboard:copy="labelJSON" v-clipboard:success="onCopy" class="button-secondary">{{i18n.copyLabelData}}</a> 
						</p>
					</form>

					<div class="clear"></div>

					<div v-if="message" class="notice notice-error notice-alt"><p>{{message}}</p></div>
				</div><!-- /.order_data_column order_data_column-/-half -->

				<div class="order_data_column order_data_column--half">
					<div class="generated-label" v-if="shipmentStatus">
						<h3>{{i18n.label}}: {{shipmentStatus.wb}}</h3> <br>

						<iframe id="woo-bg--cvc-label-print" :src="iframeUrl"></iframe>
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
					label: wooBg_cvc.i18n.office
				},
				{
					id: 'address',
					label: wooBg_cvc.i18n.address
				}
			],
			paymentType: wooBg_cvc.paymentType,
			paymentBy: '',
			paymentByTypes: [
				{
					id: 'rec',
					label: wooBg_cvc.i18n.buyer
				},
				{
					id: 'sender',
					label: wooBg_cvc.i18n.sender
				},
				{
					id: 'fixed',
					label: wooBg_cvc.i18n.fixedPrice
				}
			],
			size: '',
			shipmentStatus : '',
			labelData : wooBg_cvc.label,
			document: $( document.body ),
			office: '',
			offices: cloneDeep( wooBg_cvc.offices ),
			street: '',
			streets: cloneDeep( wooBg_cvc.streets ),
			testOption: '',
			testsOptions: cloneDeep( wooBg_cvc.testsOptions ),
			streetNumber: '',
			other: '',
			message: '',
			i18n: wooBg_cvc.i18n,
			cookie_data: cloneDeep( wooBg_cvc.cookie_data ),
			declaredValue: '',
			shipmentActions : [],
		}
	},
	watch: {
	},
	computed: {
		iframeUrl() {
			return this.shipmentStatus.pdf.replace(/^https?:/, '');
		},
		labelJSON() {
			return JSON.stringify( this.labelData );
		},
		statuses() {
			let statuses = [];
			if ( this.shipmentActions.length ) {
				this.shipmentActions.forEach( function ( status ) {
					let details = status.status
					if ( status.by_person ) {
						details += ' : ' + status.by_person;
					}

					statuses.push({
						time: status.status_date,
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

		if ( wooBg_cvc.shipmentStatus ) {
			this.shipmentStatus = wooBg_cvc.shipmentStatus;
		}

		this.types.forEach( function ( type ) {
			if ( type.id == wooBg_cvc.cookie_data.type ) {
				_this.type = type;
			}
		});

		this.testsOptions.forEach( function ( option ) {
			if ( option.id == wooBg_cvc.testOption ) {
				_this.testOption = option;
			}
		});

		if ( wooBg_cvc.cookie_data.type == 'office' ) {
			this.offices.forEach( function ( office ) {
				if ( office.id == wooBg_cvc.cookie_data.selectedOffice ) {
					_this.office = office;
				}
			});
		} else {
			this.streetNumber = wooBg_cvc.cookie_data.streetNumber;
			this.other = wooBg_cvc.cookie_data.other;
			this.streets.forEach( function ( street ) {
				console.log( street.orig_key );
				console.log( wooBg_cvc.cookie_data.selectedAddress.orig_key );
				if ( street.orig_key == wooBg_cvc.cookie_data.selectedAddress.orig_key ) {
					_this.street = street;
				}
			});
		}

		this.paymentBy = this.paymentByTypes[0];

		if ( wooBg_cvc.cookie_data.fixed_price ) {
			this.paymentBy = this.paymentByTypes[2];
		} else if ( wooBg_cvc.label.payer == 'sender' || wooBg_cvc.label.payer == 'contract' ) {
			this.paymentBy = this.paymentByTypes[1];
		}

		if ( wooBg_cvc.label.os_value ) {
			this.declaredValue = wooBg_cvc.label.os_value;
		}

		if ( wooBg_cvc.actions ) {
			this.shipmentActions = wooBg_cvc.actions;
		}
	},
	methods: {
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
				orderId: wooBg_cvc.orderId,
				declaredValue: this.declaredValue,
				action: 'woo_bg_cvc_generate_label',
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;
					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					} else {
						_this.shipmentStatus = cloneDeep( response.data.data.shipmentStatus, true );
						_this.labelData = cloneDeep( response.data.data.label, true );
						_this.size = 'refresh';
					}
				});
		},
		updateActions( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_cvc.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_cvc_update_actions',
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;

					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					} else {
						_this.shipmentActions = cloneDeep( response.data.data.actions, true );
					}
				});
		},
		deleteLabel( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_cvc.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_cvc_delete_label',
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {

					_this.shipmentStatus = null;
					_this.shipmentActions = [];
					_this.loading = false;
					_this.size = 'refresh';

					setTimeout(function() {
						_this.document.find('input[name="label_size"]:checked').trigger('change');
					}, 10);
				});
		},
	}
}
</script>