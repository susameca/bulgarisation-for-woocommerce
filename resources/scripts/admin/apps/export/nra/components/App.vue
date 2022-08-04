<template>
  <div class="ajax-container" :data-loading="loading">
  	<ValidationObserver ref="form" v-slot="{ handleSubmit }">
		<form @submit.prevent="handleSubmit( runSubmit )">
  			<div v-if="i18n.description" v-html="i18n.description"></div>

		  	<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="woo-bg-company-name">{{i18n.choose_month}}</label>
						</th>

						<ValidationProvider 
							tag="td" rules="required" class="forminp forminp-text" 
							v-slot="{ errors }"
						>
							<date-picker v-model="year" type="month" value-type="format"></date-picker>

							<p class="field-error">{{ errors[0] }}</p>
						</ValidationProvider>
					</tr>
				</tbody>
			</table>

			<div>
				<input id="woo-bg-generate_files" type="checkbox" name="generate_files" v-model="generate_files">
				<label for="woo-bg-generate_files">{{i18n.generated_files}}</label>
			</div>

			<p class="submit">
				<button name="save" class="button-primary woocommerce-save-button" type="submit" :value="i18n.download">{{i18n.download}}</button>

				<span class="form-message" v-if="message" v-html="message"></span>
			</p>
		</form>
	</ValidationObserver>
  </div>
</template>

<script>
import axios from 'axios';
import { extend, localize } from 'vee-validate';
import { required } from 'vee-validate/dist/rules';
import bg from 'vee-validate/dist/locale/bg.json';
import Qs from 'qs';
import DatePicker from 'vue2-datepicker';
import 'vue2-datepicker/locale/bg';

localize('bg', bg);
extend('required', {
	...required,
	message: 'Полето е задължително'
});

export default {
	components: { DatePicker },
	data() {
		return {
			loading: false,
			year: wooBg_export.year,
			i18n: wooBg_export.i18n,
			generate_files: false,
			message: '',
		}
	},
	mounted() {
		$( document.body ).trigger( 'init_tooltips' );
	},
	methods: {
		runSubmit() {
			this.loading = true;
			let _this = this;
			let data = {
				action: 'woo_bg_export_nap',
				year: this.year,
				generate_files: this.generate_files,
				nonce: wooBg_export.nonce
			}

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					if ( response.data.data.message ) {
						_this.message = response.data.data.message;
					}

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
