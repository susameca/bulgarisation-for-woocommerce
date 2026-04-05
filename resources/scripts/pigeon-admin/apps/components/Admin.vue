<template>
	<div class="panel-wrap woocommerce woocommerce--pigeon ajax-container woocommerce--woo-bg-label" :data-loading="loading">
		<div id="order_data" class="panel woocommerce-order-data">
			<div class="order_data_column_container">
				<div class="order_data_column order_data_column--half">
					<h3>{{i18n.labelData}}</h3>

					<form>
						<p v-if="!shipmentStatus" class="form-field form-field-wide">
							<label>
								{{i18n.sendFrom}}:
							</label>

							<multiselect 
								v-model="sendFromType" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( sendFromTypes )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="!shipmentStatus && sendFromType.id === 'office'" class="form-field form-field-wide">
							<label>
								{{i18n.office}}:
							</label>

							<multiselect 
								v-model="sendFrom" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( sendFromOffice )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="!shipmentStatus && sendFromType.id === 'address'" class="form-field form-field-wide">
							<label>
								{{i18n.address}}:
							</label>

							<input v-model="sendFromAddress">
						</p>

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
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( offices )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="( type.id == 'locker' )" class="form-field form-field-wide">
							<label>
								{{i18n.locker}}:
							</label>

							<multiselect 
								v-model="locker" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( lockers )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
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
									:placeholder="i18n.other" 
									type="text" 
									v-model="other" 
								>
							</p>
						</div>

						<fieldset
							v-for="(parsel, key) in labelData.packages"
							:key="key"
						>
							<legend>{{ i18n.pack }} {{ key + 1 }}</legend>

							<p class="form-field form-field--1-of-3" style="clear:none">
								<label>
									{{i18n.length}} (cm):
								</label>

								<input v-model="parsel.length" type="number" step="0.1">
							</p>

							<p class="form-field form-field--1-of-3" style="clear:none">
								<label>
									{{i18n.width}} (cm):
								</label>

								<input v-model="parsel.width" type="number" step="0.1">
							</p>

							<p class="form-field form-field--1-of-3" style="float:right; clear:none">
								<label>
									{{i18n.height}} (cm):
								</label>

								<input v-model="parsel.height" type="number" step="0.1">
							</p>

							<p v-if="typeof parsel.weight !== 'undefined'" class="form-field form-field-wide">
								<label>
									{{i18n.weight}}:
								</label>

								<input v-model="parsel.weight" type="number" step="0.001">
							</p>

							<p class="form-field form-field-wide">
								<button
									type="button"
									@click="removeParcel(key)"
									:disabled="labelData.packages.length === 1"
									class="button-secondary"
								>
									{{ i18n.removePack }}
								</button>
							</p>
						</fieldset>

						<p class="form-field form-field-wide">
							<button
								type="button"
								@click="addParcel"
								class="button-secondary"
							>
								{{ i18n.addPack }}
							</button>
						</p>

						<p v-if="( typeof( labelData.service_codes ) !== 'undefined' && typeof( labelData.service_codes.cod_amount) !== 'undefined' ) && paymentType === 'cod'" class="form-field form-field-wide">
							<label>
								{{i18n.cd}}:
							</label>

							<input v-model="labelData.service_codes.cod_amount" type="number">
						</p>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.declaredValue}}:
							</label>

							<input v-model="declaredValue" type="number">
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
								{{i18n.note}}:
							</label>

							<input v-model="labelData.note" type="text">
						</p>

						<p class="form-field form-field--checkbox" style="clear:none !important; float:left !important;">
							<label>
								{{i18n.reviewAndTest}}:
							</label>

							<input v-model="testOption" type="checkbox" class="checkbox">
						</p>

						<p class="form-field form-field--checkbox" style="float:right !important; clear:none !important;">
							<label>
								{{i18n.returnAtMyExpense}}:
							</label>

							<input v-model="returnAtMyExpense" type="checkbox" class="checkbox">
						</p>

						<p class="form-field form-field-wide" v-if="shipmentStatus">
							<button @click="deleteLabel" name="save" type="submit" :value="i18n.deleteLabel" class="button-secondary">{{i18n.deleteLabel}}</button>

							<button @click="updateShipmentStatus" name="save" type="submit" :value="i18n.updateShipmentStatus" class="button-primary woocommerce-save-button">{{i18n.updateShipmentStatus}}</button>
						</p>

						<p v-else class="form-field form-field-wide"> 
							<button @click="updateLabel" name="save" type="submit" :value="i18n.generateLabel" class="button-primary woocommerce-save-button">{{i18n.generateLabel}}</button>
						</p>

						<p class="form-field form-field-wide"> 
							<a v-clipboard:copy="labelJSON" v-clipboard:success="onCopy" class="button-secondary">{{i18n.copyLabelData}}</a> 
						</p>
					</form>

					<div class="clear"></div>

					<div v-if="message" class="notice notice-error notice-alt"><p>{{message}}</p></div>
				</div><!-- /.order_data_column order_data_column-/-half -->

				<div class="order_data_column order_data_column--half">
					<div class="generated-label" v-if="shipmentStatus">
						<h3>{{i18n.label}}: {{shipmentStatus.id}}</h3>

						<iframe id="woo-bg--pigeon-label-print" :src="iframeUrl"></iframe>
					</div>
				</div><!-- /.order_data_column order_data_column-/-half -->
			</div><!-- /.order_data_column_container -->

			<!-- <div class="woocommerce_order_status" v-if="statuses.length">
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
			</div> -->
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
			sendFromOffice: [],
			sendFromAddress: '',
			sendFrom: '',
			sendFromType: '',
			type: {},
			types: [
				{
					id: 'office',
					label: wooBg_pigeon.i18n.office
				},
				{
					id: 'address',
					label: wooBg_pigeon.i18n.address
				},
				{
					id: 'locker',
					label: wooBg_pigeon.i18n.locker
				}
			],
			sendFromTypes: [
				{
					id: 'office',
					label: wooBg_pigeon.i18n.office
				},
				{
					id: 'address',
					label: wooBg_pigeon.i18n.address
				}
			],
			paymentType: wooBg_pigeon.paymentType,
			paymentBy: '',
			paymentByTypes: [
				{
					id: 'receiver',
					label: wooBg_pigeon.i18n.buyer
				},
				{
					id: 'sender',
					label: wooBg_pigeon.i18n.sender
				},
				{
					id: 'fixed',
					label: wooBg_pigeon.i18n.fixedPrice
				}
			],
			shipmentStatus : '',
			labelData : wooBg_pigeon.label,
			document: $( document.body ),
			locker: '',
			lockers: cloneDeep( wooBg_pigeon.lockers ),
			office: '',
			offices: cloneDeep( wooBg_pigeon.offices ),
			street: '',
			streets: cloneDeep( wooBg_pigeon.streets ),
			testOption: wooBg_pigeon.testOption,
			other: '',
			message: '',
			i18n: wooBg_pigeon.i18n,
			cookie_data: cloneDeep( wooBg_pigeon.cookie_data ),
			declaredValue: '',
			returnAtMyExpense: '',
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

				link = woocommerce_admin.ajax_url + '?cache-buster=' + Math.random()  + '&action=woo_bg_pigeon_print_labels&parcels=' + parcels.join('|') + "&size=" + this.size;
			}

			return ( parcels.length ) ? link : '';
		},
		labelJSON() {
			return JSON.stringify( this.labelData );
		},
		// statuses() {
		// 	let statuses = [];

		// 	if ( this.operations.length ) {
		// 		this.operations.forEach( function ( status ) {
		// 			let details = status.description;

		// 			if ( typeof status.comment !== 'undefined' ) {
		// 				details += ' - ' + status.comment;
		// 			}

		// 			let time = new Date( status.dateTime ).toLocaleString();

		// 			statuses.push({
		// 				time,
		// 				details,
		// 			} );
		// 		});

		// 		statuses.reverse();
		// 	}

		// 	return statuses;
		// }
	},
	mounted() {
		let _this = this;

		if ( wooBg_pigeon.shipmentStatus ) {
			this.shipmentStatus = wooBg_pigeon.shipmentStatus;
		}

		this.types.forEach( function ( type ) {
			if ( type.id == wooBg_pigeon.cookie_data.type ) {
				_this.type = cloneDeep(type);
			}
		});

		this.sendFromTypes.forEach( function ( type ) {
			if ( type.id == wooBg_pigeon.sendFrom.type ) {
				_this.sendFromType = cloneDeep(type);
			}
		});

		this.sendFromOffice = wooBg_pigeon.sendFrom.offices;
		this.sendFromAddress = wooBg_pigeon.sendFrom.currentAddress;

		Object.values(this.sendFromOffice).forEach( function ( office ) {
			if ( office.id.toLowerCase() == wooBg_pigeon.sendFrom.currentOffice.toLowerCase() ) {
				_this.sendFrom = office;
			}
		});

		if ( wooBg_pigeon.cookie_data.type == 'office' ) {
			Object.values(this.offices).forEach( function ( office ) {
				if ( office.id == 'officeID-' + wooBg_pigeon.cookie_data.selectedOffice ) {
					_this.office = office;
				}
			});
		} else {
			this.other = wooBg_pigeon.label.receiver_address.additional_info;
			this.streets.forEach( function ( street ) {
				if ( street.orig_key == wooBg_pigeon.cookie_data.selectedAddress.orig_key ) {
					_this.street = street;
				}
			});
		}

		this.paymentBy = this.paymentByTypes[0];

		if ( wooBg_pigeon.cookie_data.fixed_price ) {
			this.paymentBy = this.paymentByTypes[2];
		} else if (  wooBg_pigeon.label.who_pays === 'sender'  ) {
			this.paymentBy = this.paymentByTypes[1];
		}

		if ( typeof( wooBg_pigeon.label.service_codes.declared_value ) !== 'undefined' ) {
			this.declaredValue = wooBg_pigeon.label.service_codes.declared_value;
		}

		if ( typeof( wooBg_pigeon.label.return_at_my_expense ) !== 'undefined' ) {
			this.returnAtMyExpense = wooBg_pigeon.label.return_at_my_expense;
		}

		// if ( wooBg_pigeon.operations ) {
		// 	this.operations = wooBg_pigeon.operations;
		// }
	},
	methods: {
		addParcel() {
			const parcels = this.labelData.packages || [];

			let template;

			if (parcels.length > 0) {
				template = cloneDeep(parcels[0]);

				if (template.id) {
					delete template.id;
				}
			} else {
				template = {
					seqNo: 1,
					weight: 1,
					size: {
						depth: '',
						width: '',
						height: '',
					},
				};
			}

			parcels.push(template);
			this.$set(this.labelData, 'packages', parcels);
		},
		removeParcel(index) {
			const parcels = this.labelData.packages;

			if (!Array.isArray(parcels) || parcels.length <= 1) {
				return;
			}

			parcels.splice(index, 1);

			parcels.forEach((parcel, idx) => {
				// make sure Vue keeps it reactive
				this.$set(parcel, 'seqNo', idx + 1);
			});
		},
		asyncFind: debounce( function( query ) {
			if ( !query ) {
				return;
			}

			this.isLoading = true;
			let data = {
				query,
				action: 'woo_bg_pigeon_search_address',
				state: wooBg_pigeon.cookie_data.state,
				city: wooBg_pigeon.cookie_data.city,
				nonce: wooBg_pigeon.nonce,
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
				send_from_type: this.sendFromType.id,
				send_from: this.sendFrom.id,
				send_from_address: this.sendFromAddress,
				label_data: this.labelData,
				office: this.office,
				locker: this.locker,
				street: this.street,
				other: this.other,
				paymentBy: this.paymentBy,
				testOption: this.testOption,
				returnAtMyExpense: this.returnAtMyExpense,
				cookie_data: this.cookie_data,
				orderId: wooBg_pigeon.orderId,
				declaredValue: this.declaredValue,
				action: 'woo_bg_pigeon_generate_label',
				nonce: wooBg_pigeon.nonce,
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
				orderId: wooBg_pigeon.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_pigeon_update_shipment_status',
				nonce: wooBg_pigeon.nonce,
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
				orderId: wooBg_pigeon.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_pigeon_delete_label',
				nonce: wooBg_pigeon.nonce,
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