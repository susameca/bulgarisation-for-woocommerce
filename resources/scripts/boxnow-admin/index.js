__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/boxnow-admin';

if ( $('#woo-bg--boxnow-admin').length ) {
	let boxNowAdmin = () => import('./apps/app.js');
	
	setTimeout(() => {
		boxNowAdmin();
	}, 250);
}