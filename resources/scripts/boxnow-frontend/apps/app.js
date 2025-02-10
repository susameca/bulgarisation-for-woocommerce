import 'es6-promise/auto';
import Vue from 'vue';
import Apm from './components/Apm.vue';

export const apm = Vue.extend({
	render: h => h( Apm ),
});