__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/speedy-admin';

if ( $('#woo-bg--speedy-admin').length ) {
	let speedyAdmin = () => import('./apps/app.js');

	setTimeout(() => {
		speedyAdmin();
	}, 250);
}