import 'es6-promise/auto';
import Vue from 'vue';
import Address from './components/Address.vue';
import Office from './components/Office.vue';
import Locker from './components/Locker.vue';

export const address = Vue.extend({
	render: h => h( Address ),
});

export const office = Vue.extend({
	render: h => h( Office ),
});

export const locker = Vue.extend({
	render: h => h( Locker ),
});