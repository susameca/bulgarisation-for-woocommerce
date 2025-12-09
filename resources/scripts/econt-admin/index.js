__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/econt-admin';

if ( $('#woo-bg--econt-admin').length ) {
	let econtAdmin = () => import('./apps/app.js');

	setTimeout(() => {
		econtAdmin();
	}, 250);
}