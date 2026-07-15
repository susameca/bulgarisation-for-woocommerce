__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/admin';

const wooBgTabs = document.querySelector('.woo-nav-tab-wrapper');

if (wooBgTabs) {
	const adminMenu = document.querySelector('#adminmenu .wp-submenu');
	const adminMenuLink = document.querySelector('#adminmenu a.menu-top');
	const activeAdminMenuLink = document.querySelector('#adminmenu .wp-has-current-submenu > a.menu-top, #adminmenu .current > a.menu-top');

	if (adminMenu && adminMenuLink && activeAdminMenuLink) {
		const menuStyles = window.getComputedStyle(adminMenu);
		const linkStyles = window.getComputedStyle(adminMenuLink);
		const activeLinkStyles = window.getComputedStyle(activeAdminMenuLink);

		wooBgTabs.style.setProperty('--woo-bg-admin-menu-background', menuStyles.backgroundColor);
		wooBgTabs.style.setProperty('--woo-bg-admin-menu-color', linkStyles.color);
		wooBgTabs.style.setProperty('--woo-bg-admin-menu-active-background', activeLinkStyles.backgroundColor);
		wooBgTabs.style.setProperty('--woo-bg-admin-menu-active-color', activeLinkStyles.color);
	}
}

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

if ( $('#woo-bg-exports--invoice-archive').length ) {
	let exportTabInvoiceArchive = () => import('./apps/export/invoiceArchive/app.js');

	exportTabInvoiceArchive();
}

if ( $('#woo-bg-contact-form').length ) {
	let helpTab = () => import('./apps/contact-form/app.js');

	helpTab();
}

import './modules/boxnow-notice';
import './modules/pigeon-notice';
import './modules/generate-label';
import './modules/impossible-prices';
import './modules/impossible-prices-scan';
