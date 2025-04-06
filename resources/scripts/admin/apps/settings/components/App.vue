<template>
  <div class="ajax-container" :data-loading="loading">
  	<ValidationObserver ref="form" v-slot="{ handleSubmit }">
		<form @submit.prevent="handleSubmit( runSubmit )">
			<div v-if="auth_errors" v-html="auth_errors" class="notice notice-error"> </div>

			<div v-for="(group, group_slug) in fields">
	  		<h3>{{ groups_titles[ group_slug ].title }}</h3>

			  <table class="form-table">
					<tbody>
						<tr v-if="field.type === 'text'" valign="top" v-for="(field, field_slug) in group">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-${field.name}`">
									{{field.title}}

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>
							<ValidationProvider 
								tag="td" :rules="field.validation_rules" class="forminp forminp-text" 
								v-slot="{ errors }"
							>
								<input v-model="fields[group_slug][field_slug].value" :name="`woo-bg-` + field.name" :type="field.subtype" :placeholder="field.title" :step="( (field.subtype === 'number' ) ? '0.01' : '' )">
								<p v-if="field.description" class="description">
									{{field.description}}
								</p>

								<p class="field-error">{{ errors[0] }}</p>
							</ValidationProvider>
						</tr>

						<tr v-else-if="field.type === 'textarea'" valign="top">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-${field.name}`">
									{{field.title}}

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>
							<ValidationProvider 
								tag="td" :rules="field.validation_rules" class="forminp forminp-text" 
								v-slot="{ errors }"
							>
								<textarea rows="5" v-model="fields[group_slug][field_slug].value" :name="`woo-bg-` + field.name" :type="field.subtype" :placeholder="field.title">
								</textarea>
								<p v-if="field.description" class="description" v-html="field.description"> </p>

								<p class="field-error">{{ errors[0] }}</p>
							</ValidationProvider>
						</tr>

						<tr v-else-if="field.type === 'select'" valign="top">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-gateway-${group_slug}-${field_slug}`">
									{{field.title}} 

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>

							<td class="forminp forminp-text">
								<multiselect v-model="fields[group_slug][field_slug].value" deselect-label="" selectLabel="" track-by="id" label="label" selectedLabel="Избрано" placeholder="Изберете" :options="Object.values( fields[group_slug][field_slug].options )" :searchable="true" :allow-empty="false">
									<template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.label }}</strong></template>
								</multiselect>

								<p v-if="field.description" class="description" v-html="field.description"></p>
							</td><!-- /.forminp forminp-text -->
						</tr>
						<tr v-else-if="field.type === 'true_false'" valign="top">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-gateway-${group_slug}-${field_slug}`">
									{{field.title}} 

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>

							<td class="forminp forminp-text">
								<toggle-button v-model="fields[group_slug][field_slug].value" color="#007cba" />

								<p v-if="field.description" class="description" v-html="field.description"></p>
							</td><!-- /.forminp forminp-text -->
						</tr>
						<tr v-else-if="field.type === 'multi'" valign="top">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-gateway-${group_slug}-${field_slug}`">
									{{field.title}} 

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>

							<td class="forminp forminp-text">
								<div class="multi-field-container">
									<span class="multi-field-row" v-for="(field_value, field_key) in fields[group_slug][field_slug].value">
										<span class="multi-field-option" >
											<input 
												type="number" 
												v-model="field_value.from" 
												:step="fields[group_slug][field_slug].fields_types.step" 
												:min="0" 
												:max="fields[group_slug][field_slug].fields_types.max"
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type"></span>
										</span>

										{{fields[group_slug][field_slug].fields_types.separator_text}}&nbsp;&nbsp;

										<span class="multi-field-option">
											<input
												type="number" 
												v-model="field_value.to"
												:step="fields[group_slug][field_slug].fields_types.step"
												:min="0" 
												:max="fields[group_slug][field_slug].fields_types.max"
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type"></span>
										</span>

										&nbsp;{{fields[group_slug][field_slug].fields_types.price_text}}&nbsp;&nbsp;

										<span class="multi-field-option">
											<input type="number" v-model="field_value.price" step="0.01" ><span v-html="fields[group_slug][field_slug].fields_types.currency"></span>
										</span>

										<a @click="removeRow( group_slug, field_slug, field_key )" class="button button-small button-secondary">-</a>
									</span>

									<div class="multi-field--add-new-row">
										<a @click="addNewRow( group_slug, field_slug )" class="button button-small button-secondary">{{fields[group_slug][field_slug].fields_types.add_row_text}}</a>
									</div>
								</div>

								<p v-if="field.description" class="description" v-html="field.description"></p>
							</td><!-- /.forminp forminp-text -->
						</tr>
						<tr v-else-if="field.type === 'multi_two'" valign="top">
							<th scope="row" class="titledesc">
								<label :for="`woo-bg-gateway-${group_slug}-${field_slug}`">
									{{field.title}} 

									<span v-if="field.help_text" class="woocommerce-help-tip" :data-tip="field.help_text"></span>
								</label>
							</th>

							<td class="forminp forminp-text">
								<div class="multi-two-field-container">
									<span class="multi-field-row" v-for="(field_value, field_key) in fields[group_slug][field_slug].value">
										<span class="multi-field-option" >
											<input 
												type="number" 
												v-model="field_value.from" 
												:step="fields[group_slug][field_slug].fields_types.step_type1" 
												:min="0" 
												:max="fields[group_slug][field_slug].fields_types.max_type1"
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type"></span>
										</span>

										{{fields[group_slug][field_slug].fields_types.separator_text}}&nbsp;&nbsp;

										<span class="multi-field-option">
											<input
												type="number" 
												v-model="field_value.to"
												:step="fields[group_slug][field_slug].fields_types.step_type1"
												:min="0" 
												:max="fields[group_slug][field_slug].fields_types.max_type1"
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type"></span>
										</span>

										{{fields[group_slug][field_slug].fields_types.and_text}}&nbsp;&nbsp

										<span class="multi-field-option" >
											<input 
												type="number" 
												v-model="field_value.from_price" 
												:step="fields[group_slug][field_slug].fields_types.step_type2" 
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type2"></span>
										</span>

										{{fields[group_slug][field_slug].fields_types.separator_text}}&nbsp;&nbsp;

										<span class="multi-field-option">
											<input
												type="number" 
												v-model="field_value.to_price"
												:step="fields[group_slug][field_slug].fields_types.step_type2"
											>
											<span v-html="fields[group_slug][field_slug].fields_types.type2"></span>
										</span>

										&nbsp;{{fields[group_slug][field_slug].fields_types.price_text}}&nbsp;&nbsp;

										<span class="multi-field-option">
											<input type="number" v-model="field_value.price" ><span v-html="fields[group_slug][field_slug].fields_types.currency"></span>
										</span>


										<a @click="removeRow( group_slug, field_slug, field_key )" class="button button-small button-secondary">-</a>
									</span>

									<div class="multi-field--add-new-row">
										<a @click="addNewRow( group_slug, field_slug )" class="button button-small button-secondary">{{fields[group_slug][field_slug].fields_types.add_row_text}}</a>
									</div>
								</div>

								<p v-if="field.description" class="description" v-html="field.description"></p>
							</td><!-- /.forminp forminp-text -->
						</tr>




					</tbody>
				</table>
			</div><!-- /.div -->

			<p class="submit">
				<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Запазване на промените">Запазване на промените</button>

				<span class="form-message">{{message}}</span>
			</p>
		</form>
	</ValidationObserver>
  </div>
</template>

<script>
import cloneDeep from 'lodash/cloneDeep';
import axios from 'axios';
import Multiselect from 'vue-multiselect';
import { extend, localize } from 'vee-validate';
import { required, email } from 'vee-validate/dist/rules';
import bg from 'vee-validate/dist/locale/bg.json';
import Qs from 'qs';

localize('bg', bg);
extend('required', {
	...required,
	message: 'Полето е задължително'
});

extend('email', {
	...email,
	message: 'Полето трябва да е коректен Email адрес'
});

export default {
	components: { Multiselect },
	data() {
		return {
			loading: false,
			fields: cloneDeep( wooBg_settings.fields ),
			groups_titles: cloneDeep( wooBg_settings.groups_titles ),
			message: '',
			auth_errors: cloneDeep( wooBg_settings.auth_errors ),
		}
	},
	mounted() {
		$( document.body ).trigger( 'init_tooltips' );
	},
	methods: {
		addNewRow( group_slug, field_slug ) {
			let newRowIndex = this.fields[group_slug][field_slug].value.length - 1;
			let newRow = cloneDeep( this.fields[group_slug][field_slug].value[ newRowIndex ] );

			for (const prop in newRow) {
			  if (newRow.hasOwnProperty(prop)) {
			    newRow[prop] = '';
			  }
			}

			this.fields[group_slug][field_slug].value.push( newRow );
		},
		removeRow( group_slug, field_slug, key ) {
			this.fields[group_slug][field_slug].value.splice( key, 1 );
		},
		runSubmit() {
			let fieldsForSubmit = {};
			for ( const [ group, fields ] of Object.entries( this.fields ) ) {
				fieldsForSubmit[group] = {};

				for ( const [ name, props ] of Object.entries( fields ) ) {
					fieldsForSubmit[group][name] = {
						value : props.value,
						type : props.type,
					}
				}
			}

			this.loading = true;
			let _this = this;
			let data = {
				action: 'woo_bg_save_settings',
				options: fieldsForSubmit,
				tab: wooBg_settings.tab,
				nonce: wooBg_settings.nonce
			}

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					}

					_this.auth_errors = response.data.data.auth_errors;

					if ( response.data.data.fields ) {
						_this.fields = {};
						_this.groups_titles = {};
						_this.fields = cloneDeep( response.data.data.fields );
						_this.groups_titles = cloneDeep( response.data.data.groups_titles );
					}

					setTimeout(function() {
						$( document.body ).trigger( 'init_tooltips' );
					}, 50);

					_this.loading = false;
				} )
				.catch( error => {
					_this.message = "Имаше проблем със запазването на настройките. За повече информация вижте конзолата.";
					console.log(error);
					_this.loading = false;
				} );
		}
	}
}
</script>