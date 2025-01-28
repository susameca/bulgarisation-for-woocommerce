__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/admin';
import axios from 'axios';
import Qs from 'qs'

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

$(document).on('click', '.woo-bg--generate-label', function(e) {
	e.preventDefault();

	let $element = $(this);
	$element.addClass( 'js-ajax-loading' );

	let data = {
		action: 'woo_bg_generate_label_from_listing_page',
		orderId: $element.data('orderid'),
		method: $element.data('method'),
		nonce: $element.data('nonce'),
	};

	axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
		.then(function( response ) {
			$element.replaceWith( response.data.html );
			$element.removeClass( 'js-ajax-loading' );
		});
});

$('#woo_bg_nekorekten_reports form').on('submit', function( e ) {
	e.preventDefault();

	var $form = $(this);
	$form.attr('data-loading', 'true');
	$form.find('p.message').remove();

	axios.post( woocommerce_admin.ajax_url, $form.serialize() )
		.then( function( response ) {
			$form.attr('data-loading', 'false');
			$form.append('<p class="message">' + response.data.data.message + '</p>');
			$form.trigger('reset');
		});
});