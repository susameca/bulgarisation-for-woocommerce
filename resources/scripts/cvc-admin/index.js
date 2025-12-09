__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/cvc-admin';

if ( $('#woo-bg--cvc-admin').length ) {
	let cvcAdmin = () => import('./apps/app.js');

	setTimeout(() => {
		cvcAdmin();
	}, 250);
}