__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/admin';

if ( $('#woo-bg-settings').length ) {
	let settingsTab = () => import('./apps/settings/app.js');

	settingsTab();
}

if ( $('#woo-bg-exports').length ) {
	let exportTab = () => import('./apps/export/nra/app.js');

	exportTab();
}

if ( $('#woo-bg-exports--microinvest').length ) {
	let exportTabMicroinvest = () => import('./apps/export/microinvest/app.js');

	exportTabMicroinvest();
}

if ( $('#woo-bg-contact-form').length ) {
	let helpTab = () => import('./apps/contact-form/app.js');

	helpTab();
}