<template>
  <div class="ajax-container" :data-loading="loading">
  	<ValidationObserver ref="form" v-slot="{ handleSubmit }">
		<form @submit.prevent="handleSubmit( runSubmit )">
  			<div v-if="i18n.invoiceArchiveDescription" v-html="i18n.invoiceArchiveDescription"></div>

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

			<p class="submit">
				<button name="save" class="button-primary woocommerce-save-button" type="submit" :value="i18n.export_documents">{{i18n.export_documents}}</button>

				<span class="form-message" v-if="message" v-html="message"></span>
			</p>
		</form>
	</ValidationObserver>
  </div>
</template>

<script>
import { extend, localize } from 'vee-validate';
import { required } from 'vee-validate/dist/rules';
import bg from 'vee-validate/dist/locale/bg.json';
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
			message: '',
		}
	},
	mounted() {
		$( document.body ).trigger( 'init_tooltips' );
	},
	methods: {
		runSubmit() {
			var url = new URL( woocommerce_admin.ajax_url );
			url.searchParams.append('action', 'woo_bg_export_invoice_archive' );
			url.searchParams.append('year', this.year );
			url.searchParams.append('nonce', wooBg_export.nonce );

			window.location = url;
		}
	}
}
</script>
