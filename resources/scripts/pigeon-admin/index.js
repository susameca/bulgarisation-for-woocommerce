__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/pigeon-admin';

if ( $('#woo-bg--pigeon-admin').length ) {
	let pigeonAdmin = () => import('./apps/app.js');

	setTimeout(() => {
		pigeonAdmin();
	}, 250);
}