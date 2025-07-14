import axios from 'axios';
import Qs from 'qs'

$('.notice-boxnow').on('click', 'button.notice-dismiss', function(e) {
	let data = {
		action: 'woo_bg_boxnow_message_dismiss'
	};
	
	axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
		.then( function( response ) {
		});
});