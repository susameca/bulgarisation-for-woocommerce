import axios from 'axios';
import Qs from 'qs'

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