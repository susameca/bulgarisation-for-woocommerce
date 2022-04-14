import 'es6-promise/auto';

import { ValidationProvider, ValidationObserver } from 'vee-validate';
import Vue from 'vue';
import App from './components/App.vue';


Vue.component('ValidationProvider', ValidationProvider);
Vue.component('ValidationObserver', ValidationObserver);

export default new Vue({
	el: '#woo-bg-contact-form',
	render: h => h( App )
});
