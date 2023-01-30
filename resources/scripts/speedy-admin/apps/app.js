import 'es6-promise/auto';
import Vue from 'vue';
import App from './components/Admin.vue';
import VueClipboard from 'vue-clipboard2';
Vue.use(VueClipboard);

export default new Vue({
	el: '#woo-bg--speedy-admin',
	render: h => h( App )
});