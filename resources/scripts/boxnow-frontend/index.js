__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import { getCookie, setCookie } from '../utils';
import '@styles/boxnow-frontend';

let boxnow = import('./apps/app.js');
const boxnowCookieName = 'woo-bg--boxnow-apm';

$( document.body ).on( 'change', 'input[name="payment_method"]', function() {
	let payment = $( 'input[name="payment_method"]:checked' ).val();

	if ( !payment ) {
		return;
	}

	let cookie = {};
	let cookieData = getCookie( boxnowCookieName );

	if ( cookieData ) {
		try {
			cookie = JSON.parse( cookieData );
		} catch ( error ) {
			if ( error ) {
				cookie = {};
			}
		}
	}

	cookie.payment = payment;
	cookie = encodeURIComponent( JSON.stringify( cookie ) );

	setCookie( boxnowCookieName, cookie, 1 );
} );

boxnow.then( function ( promise ) {
	let instance;
	window.boxnowApmInitialUpdate = true;

	$( document.body ).on( 'updated_checkout', function( data ){
		let $shipping_method = $( 'input[name^="shipping_method"]:checked' );

		if ( !$shipping_method.length ) {
			let $methods = $('ul#shipping_method li');

			if ( $methods.length == 1 ) {
				$shipping_method = $('input[name="shipping_method[0]"]');
			}
		}
		
		if ( $shipping_method.length && $shipping_method.val().includes('woo_bg_boxnow') ) {
			$('form.checkout').on('change', 'input[name="payment_method"]', function(){
				$(document.body).trigger('update_checkout');
			});
			
			let $parent = $shipping_method.closest('li');
			let $target = $parent.find('.woo-bg-additional-fields');

			if ( typeof instance === 'object' ) {
				instance.$destroy();
			}

			setTimeout( function() {
				instance = new promise.apm({ el: '#' + $target.attr('id') });
			}, 5 );
		} else if ( typeof instance === 'object' ) {
			window.boxnowApmInitialUpdate = true;
			
			instance.$destroy();
		}
	});
});