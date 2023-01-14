import 'es6-promise/auto';
import Vue from 'vue';
import Address from './components/Address.vue';
import Office from './components/Office.vue';

export const address = Vue.extend({
	render: h => h( Address ),
});

export const office = Vue.extend({
	render: h => h( Office ),
});