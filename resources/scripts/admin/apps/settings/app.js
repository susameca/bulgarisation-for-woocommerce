import 'es6-promise/auto';

import Vue from 'vue';
import App from './components/App.vue';
import { ValidationProvider, ValidationObserver } from 'vee-validate';
import { ToggleButton } from 'vue-js-toggle-button'

Vue.component('ValidationProvider', ValidationProvider);
Vue.component('ValidationObserver', ValidationObserver);
Vue.component('ToggleButton', ToggleButton)

export default new Vue({
	el: '#woo-bg-settings',
	render: h => h( App )
});