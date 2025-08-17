<template>
	<div class="panel-wrap woocommerce woocommerce--econt ajax-container" :data-loading="loading">
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
								:options="Object.values( types )" 
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

							<multiselect 
								v-model="sendFrom" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose"
								:options="Object.values( sendFromAddress )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
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


						<p class="form-field form-field-wide">
							<label>
								{{i18n.shipmentType}}:
							</label>

							<multiselect 
								v-model="shipmentType" 
								deselect-label="" 
								selectLabel="" 
								track-by="id" 
								label="label" 
								:selectedLabel="i18n.selected" 
								:placeholder="i18n.choose" 
								:options="Object.values( shipmentTypes )" 
								:searchable="true" 
								:allow-empty="false"
							>
								<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
							</multiselect>
						</p>

						<p v-if="labelData.packCount" class="form-field form-field-wide">
							<label>
								{{i18n.packCount}}:
							</label>

							<input v-model="labelData.packCount" type="number">
						</p>

						<p v-if="paymentType === 'cod'" class="form-field form-field-wide">
							<label>
								{{i18n.cd}}:
							</label>

							<input v-model="labelData.services.cdAmount" type="number">
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

							<input v-model="labelData.weight" type="number" step="0.001">
						</p>

						<p class="form-field form-field-wide">
							<label>
								{{i18n.description}}:
							</label>

							<input v-model="labelData.shipmentDescription" type="text">
						</p>

						<p class="form-field form-field-wide" v-if="useInvoiceNumber">
							<label>
								{{i18n.invoiceNum}}:
							</label>

							<input v-model="labelData.services.invoiceNum" type="text">
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

						<p class="form-field form-field-wide form-field--checkbox" v-if="(!useInvoiceNumber && testOption.id !== 'no')">
							<label>
								{{i18n.partialDelivery}} :
							</label>

							<input v-model="partialDelivery" type="checkbox" class="checkbox">
						</p>

						<p v-if="( paymentBy.id === 'fixed' )" class="form-field form-field-wide">
							<label>
								{{i18n.fixedPrice}}:
							</label>

							<input v-model="labelData.paymentReceiverAmount" type="number">
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
						<h3>{{i18n.label}}: {{shipmentStatus.label.shipmentNumber}}</h3> <br>

						<div>
							<span class="woo-bg--radio">
					  			<input id="label_size_default" type="radio" name="label_size" value="">
					  			<label for="label_size_default"> {{i18n.default}}</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_10x9" type="radio" name="label_size" value="10x9" checked>
					  			<label for="label_size_10x9">10x9</label>
							</span>

							<span class="woo-bg--radio">
					  			<input id="label_size_10x15" type="radio" name="label_size" value="10x15" >
					  			<label for="label_size_10x15"> 10x15</label>
							</span>
						</div>

						<iframe id="woo-bg--econt-label-print" :src="iframeUrl"></iframe>
					</div>
				</div><!-- /.order_data_column order_data_column-/-half -->
			</div><!-- /.order_data_column_container -->

			<div class="woocommerce_order_status" v-if="statuses.length">
				<h3>{{i18n.shipmentStatus}}</h3>

				<table>
					<thead>
						<tr>
							<th> {{i18n.time}} </th>
							<th> {{i18n.event}} </th>
							<th> {{i18n.details}} </th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(status, key) in statuses">
							<th> {{status.time}} </th>
							<th> <img :src="status.image"> </th>
							<th> {{status.destination}} </th>
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
			sendFromOffice: [],
			sendFromAddress: [],
			sendFrom: '',
			sendFromType: '',
			type: '',
			types: [
				{
					id: 'office',
					label: wooBg_econt.i18n.office
				},
				{
					id: 'address',
					label: wooBg_econt.i18n.address
				}
			],
			paymentType: wooBg_econt.paymentType,
			paymentBy: '',
			paymentByTypes: [
				{
					id: 'buyer',
					label: wooBg_econt.i18n.buyer
				},
				{
					id: 'sender',
					label: wooBg_econt.i18n.sender
				},
				{
					id: 'fixed',
					label: wooBg_econt.i18n.fixedPrice
				}
			],
			size: '10x9',
			shipmentStatus : '',
			labelData : wooBg_econt.label,
			document: $( document.body ),
			shipmentType:'',
			shipmentTypes: cloneDeep( wooBg_econt.shipmentTypes ),
			office: '',
			offices: cloneDeep( wooBg_econt.offices ),
			street: '',
			streets: cloneDeep( wooBg_econt.streets ),
			testOption: '',
			testsOptions: cloneDeep( wooBg_econt.testsOptions ),
			streetNumber: '',
			other: '',
			message: '',
			i18n: wooBg_econt.i18n,
			declaredValue: '',
			useInvoiceNumber: false,
			partialDelivery: false,
		}
	},
	watch: {
		shipmentType( newValue, oldValue ){
			this.labelData.shipmentType = newValue.id;
		}
	},
	computed: {
		iframeUrl() {
			return this.shipmentStatus.label.pdfURL.replace(/^https?:/, '') + '&label=' + this.size;
		},
		labelJSON() {
			return JSON.stringify( this.labelData );
		},
		statuses() {
			let statuses = [];
			if ( this.shipmentStatus && this.shipmentStatus.label.trackingEvents.length ) {
				this.shipmentStatus.label.trackingEvents.forEach( function ( status ) {
					let image = '';
					let time = new Date( status.time );
					let destination = status.destinationDetails;
					time = time.getDate() + "/" + ( time.getMonth() + 1 ) + "/" + time.getFullYear() + " " + time.getHours() + ":" + ('0'  + time.getMinutes() ).slice(-2) + ":" + ('0'  + time.getSeconds() ).slice(-2);

					if ( status.destinationType === 'office' || status.destinationType === 'prepared' ) {
						image = "//ee.econt.com/images/icons/trace_office.png";
					} else if ( status.destinationType === 'courier_direction' ) {
						image = "//ee.econt.com/images/icons/trace_line.png";
					} else if ( status.destinationType === 'courier' ) {
						image = "//ee.econt.com/images/icons/trace_courier.png";
					} else if ( status.destinationType === 'client' ) {
						image = "//ee.econt.com/images/icons/trace_ok.png";
					} else if ( status.destinationType === 'return' ) {
						image = "//ee.econt.com/images/icons/trace_return.png";
					}

					statuses.push({
						time,
						image,
						destination,
					} );
				});

				statuses.reverse();
			}
			return statuses;
		}
	},
	mounted() {
		let _this = this;

		if ( wooBg_econt.shipmentStatus ) {
			this.shipmentStatus = wooBg_econt.shipmentStatus;
		}

	  	this.document.on('change', 'input[name="label_size"]', function () {
			_this.size = $(this).val();
		});

		this.types.forEach( function ( type ) {
			if ( type.id == wooBg_econt.cookie_data.type ) {
				_this.type = type;
			}

			if ( type.id == wooBg_econt.sendFrom.type ) {
				_this.sendFromType = type;
			}
		});


		this.sendFromOffice = wooBg_econt.sendFrom.offices;
		this.sendFromAddress = wooBg_econt.sendFrom.addresses;

		if ( this.sendFromType.id === 'office' ) {
			Object.values(this.sendFromOffice).forEach( function ( office ) {
				if ( office.id.toLowerCase() == wooBg_econt.sendFrom.currentOffice.toLowerCase() ) {
					_this.sendFrom = office;
				}
			});
		} else {
			Object.values( this.sendFromAddress ).forEach( function ( address ) {
				if ( address.id == wooBg_econt.sendFrom.currentAddress ) {
					_this.sendFrom = address;
				}
			});
		}

		this.shipmentTypes.forEach( function ( type ) {
			if ( type.id.toLowerCase() == wooBg_econt.label.shipmentType.toLowerCase() ) {
				_this.shipmentType = type;
			}
		});

		this.testsOptions.forEach( function ( option ) {
			if ( option.id == wooBg_econt.testOption ) {
				_this.testOption = option;
			}
		});

		if ( wooBg_econt.cookie_data.type == 'office' ) {
			this.offices.forEach( function ( office ) {
				if ( office.code == wooBg_econt.cookie_data.selectedOffice ) {
					_this.office = office;
				}
			});
		} else {
			this.streetNumber = wooBg_econt.cookie_data.streetNumber;
			this.other = wooBg_econt.cookie_data.other;
			this.streets.forEach( function ( street ) {
				if ( street.id == wooBg_econt.cookie_data.selectedAddress.id ) {
					_this.street = street;
				}
			});
		}

		this.paymentBy = this.paymentByTypes[1];

		if ( wooBg_econt.label.paymentReceiverAmount ) {
			this.paymentBy = this.paymentByTypes[2];
		} else if ( wooBg_econt.label.paymentReceiverMethod ) {
			this.paymentBy = this.paymentByTypes[0];
		}

		if ( wooBg_econt.label.services.declaredValueAmount ) {
			this.declaredValue = wooBg_econt.label.services.declaredValueAmount;
		}

		if ( wooBg_econt.useInvoiceNumber ) {
			this.useInvoiceNumber = true;
			
			if ( !this.labelData.services.invoiceNum ) {
				this.labelData.services.invoiceNum = wooBg_econt.invoiceNumber;
			}
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
				send_from_type: this.sendFromType.id,
				send_from: this.sendFrom.id,
				label_data: this.labelData,
				shipmentType: this.shipmentType,
				office: this.office,
				street: this.street,
				streetNumber: this.streetNumber,
				other: this.other,
				paymentBy: this.paymentBy,
				testOption: this.testOption,
				partialDelivery: this.partialDelivery,
				cookie_data: wooBg_econt.cookie_data,
				orderId: wooBg_econt.orderId,
				declaredValue: this.declaredValue,
				action: 'woo_bg_econt_generate_label',
				nonce: wooBg_econt.nonce,
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

						setTimeout(function() {
							_this.document.find('input[name="label_size"]:checked').trigger('change');
						}, 10);
					}
				});
		},
		updateShipmentStatus( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_econt.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_econt_update_shipment_status',
				nonce: wooBg_econt.nonce,
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;

					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					} else {
						_this.shipmentStatus.label = cloneDeep( response.data.data.shipmentStatus, true );
						_this.size = 'refresh';

						setTimeout(function() {
							_this.document.find('input[name="label_size"]:checked').trigger('change');
						}, 10);
					}
				});
		},
		deleteLabel( e ) {
			e.preventDefault();

			this.loading = true;
			let _this = this;
			_this.message = '';

			let data = {
				orderId: wooBg_econt.orderId,
				shipmentStatus: this.shipmentStatus,
				action: 'woo_bg_econt_delete_label',
				nonce: wooBg_econt.nonce,
			};

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {

					_this.shipmentStatus = '';
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