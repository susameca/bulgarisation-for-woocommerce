import axios from 'axios';
import Qs from 'qs'

$('.notice-pigeon-express').on('click', 'button.notice-dismiss', function(e) {
	let data = {
		action: 'woo_bg_pigeon_express_message_dismiss'
	};
	
	axios.post( woocommerce_admin.ajax_url, Qs.stringify( data ) )
		.then( function( response ) {
		});
});