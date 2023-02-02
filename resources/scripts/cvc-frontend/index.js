__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/cvc-frontend';

let cvc = import('./apps/app.js');

cvc.then( function ( promise ) {
	let instance;
	window.cvcAddressInitialUpdate = true;
	window.cvcOfficeInitialUpdate = true;

	$( document.body ).on( 'updated_checkout', function( data ){
		let $shipping_method = $( 'input[name^="shipping_method"]:checked' );

		if ( !$shipping_method.length ) {
			let $methods = $('ul#shipping_method li');

			if ( $methods.length = 1 ) {
				$shipping_method = $('input[name="shipping_method[0]"]');
			}
		}
		
		if ( $shipping_method.length && $shipping_method.val().includes('woo_bg_cvc') ) {
			$('form.checkout').on('change', 'input[name="payment_method"]', function(){
				$(document.body).trigger('update_checkout');
			});
			
			let $parent = $shipping_method.parent();
			let $target = $parent.find('.woo-bg-additional-fields');
			let type = $target.data('type');

			if ( typeof instance === 'object' ) {
				instance.$destroy();
			}

			if ( type === 'address' ) {
				window.cvcOfficeInitialUpdate = true;
				instance = new promise.address({ el: '#' + $target.attr('id') });
			} else if ( type === 'office' ) {
				window.cvcAddressInitialUpdate = true;
				instance = new promise.office({ el: '#' + $target.attr('id') });
			}
		} else if ( typeof instance === 'object' ) {
			window.cvcAddressInitialUpdate = true;
			window.cvcOfficeInitialUpdate = true;

			instance.$destroy();
		}
	});
});