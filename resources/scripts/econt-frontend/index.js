__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/econt-frontend';

let econt = import('./apps/app.js');

econt.then( function ( promise ) {
	let instance;
	window.econtAddressInitialUpdate = true;
	window.econtOfficeInitialUpdate = true;

	$( document.body ).on( 'updated_checkout', function( data ){
		let $shipping_method = $( 'input[name^="shipping_method"]:checked' );

		if ( !$shipping_method.length ) {
			let $methods = $('ul#shipping_method li');

			if ( $methods.length = 1 ) {
				$shipping_method = $('input[name="shipping_method[0]"]');
			}
		}
		
		if ( $shipping_method.length && $shipping_method.val().includes('woo_bg_econt') ) {
			$('form.checkout').on('change', 'input[name="payment_method"]', function(){
				$(document.body).trigger('update_checkout');
			});
			
			let $parent = $shipping_method.closest('li');
			let $target = $parent.find('.woo-bg-additional-fields');
			let type = $target.data('type');

			if ( typeof instance === 'object' ) {
				instance.$destroy();
			}

			if ( type === 'address' ) {
				window.econtOfficeInitialUpdate = true;
				instance = new promise.address({ el: '#' + $target.attr('id') });
			} else if ( type === 'office' ) {
				window.econtAddressInitialUpdate = true;
				instance = new promise.office({ el: '#' + $target.attr('id') });
			}
		} else if ( typeof instance === 'object' ) {
			window.econtAddressInitialUpdate = true;
			window.econtOfficeInitialUpdate = true;

			instance.$destroy();
		}
	});
});