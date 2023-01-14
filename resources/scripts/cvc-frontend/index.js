__webpack_public_path__ = window.__webpack_public_path__;
// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/cvc-frontend';

let cvc = import('./apps/app.js');
cvc.then( function ( promise ) {
	let instance;

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
			
			window.wooBgCvcDoUpdate = true;
			let $parent = $shipping_method.parent();
			let $target = $parent.find('.woo-bg-additional-fields');
			let type = $target.data('type');

			if ( typeof instance === 'object' ) {
				if ( '#' + $target.attr('id') == instance.$options.el ) {
					window.wooBgCvcDoUpdate = false;
					instance.$destroy();
				}
			}

			if ( type === 'address' ) {
				if ( typeof wooBg_cvc_address === "undefined" ) {
					window.location = window.location;
				}

				if ( typeof instance === 'object' ) {
					instance.$destroy();
				}

				instance = new promise.address({ el: '#' + $target.attr('id') });
			} else if ( type === 'office' ) {
				if ( typeof wooBg_cvc === "undefined" ) {
					window.location = window.location;
				}

				if ( typeof instance === 'object' ) {
					instance.$destroy();
				}

				instance = new promise.office({ el: '#' + $target.attr('id') });
			}
		} else if ( typeof instance === 'object' ) {
			instance.$destroy();
		}
	});
});