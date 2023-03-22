<template>
	<div class="section__contact">
		<div v-if="message" class="section__contact--message">
			<h3 v-html="message"></h3>
		</div><!-- /.section__contact-/-message -->

		<div v-else class="section__contact--form">
			<ValidationObserver ref="form" class="form" v-slot="{ handleSubmit }">
				<form @submit.prevent="handleSubmit( submitForm )" class="ajax-container" :data-loading="loading">
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woo-bg-name">
										{{i18n.name}}
									</label>
								</th>
								<ValidationProvider 
									tag="td" rules="required" class="forminp forminp-text" 
									v-slot="{ errors }"
								>
									<input v-model="fields.name" name="woo-bg-name" type="text">

									<p class="field-error">{{ errors[0] }}</p>
								</ValidationProvider>
							</tr>

							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woo-bg-name">
										{{i18n.email}}
									</label>
								</th>
								<ValidationProvider 
									tag="td" rules="required" class="forminp forminp-text" 
									v-slot="{ errors }"
								>
									<input v-model="fields.email" name="woo-bg-name" type="text">

									<p class="field-error">{{ errors[0] }}</p>
								</ValidationProvider>
							</tr>

							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woo-bg-name">
										{{i18n.message}}
									</label>
								</th>
								<ValidationProvider 
									tag="td" rules="required" class="forminp forminp-text" 
									v-slot="{ errors }"
								>
									<textarea rows="7" v-model="fields.message"></textarea>

									<p class="field-error">{{ errors[0] }}</p>
								</ValidationProvider>
							</tr>
						</tbody>
					</table>

					<p class="submit">
						<button name="save" class="button-primary woocommerce-save-button" type="submit" :value="i18n.send">{{i18n.send}}</button>
					</p>
				</form>
			</ValidationObserver>
		</div><!-- /.section__contact-/-form -->
	</div><!-- /.section__contact -->
</template>

<script>
import axios from 'axios';
import Qs from 'qs';
import bg from 'vee-validate/dist/locale/bg.json';
import { extend, localize } from 'vee-validate';
import { required, email, confirmed, min, url } from 'vee-validate/dist/rules';

localize('bg', bg);

extend('required', {
	...required,
	message: 'Полето е задължително'
});

extend('email', {
	...email,
	message: 'Полето трябва да е валиден Email адрес'
});

export default {
	data() {
		return {
			loading: false,
			fields: {},
			i18n: wooBg_help.i18n,
			message: '',
		}
	},
	methods: {
		submitForm() {
			this.loading = true;
			let _this = this;
			let data = this.fields;

			data.action = 'woo_bg_send_request';
			data.nonce = wooBg_help.nonce;

			axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
				.then(function( response ) {
					_this.loading = false;
					_this.message = response.data.data.message;
				})
				.catch( error => {
					_this.loading = false;
					if ( error.response.data.data.errors ) {
						_this.$refs.form.setErrors( error.response.data.data.errors );
						return;
					}
				});
		}
	}
}
</script>
