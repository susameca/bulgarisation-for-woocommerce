import axios from 'axios';
import Qs from 'qs';

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