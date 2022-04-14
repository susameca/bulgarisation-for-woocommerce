import 'es6-promise/auto';
import Vue from 'vue';
import App from './components/Admin.vue';

export default new Vue({
	el: '#woo-bg--econt-admin',
	render: h => h( App )
});