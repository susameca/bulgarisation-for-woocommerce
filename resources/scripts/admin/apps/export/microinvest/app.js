import 'es6-promise/auto';

import Vue from 'vue';
import App from './components/App.vue';
import { ValidationProvider, ValidationObserver } from 'vee-validate';

Vue.component('ValidationProvider', ValidationProvider);
Vue.component('ValidationObserver', ValidationObserver);

export default new Vue({
	el: '#woo-bg-exports--microinvest',
	render: h => h( App )
});