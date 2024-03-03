// eslint-disable-next-line no-unused-vars
import config from '@config';
import '@styles/frontend';
let $doc = $(document.body);

$doc.on('update_checkout', function() {
	$( '.woo-bg-company-info' ).hide();

	if ( $( '#woo-billing-to-company' ).is( ':checked' ) ) {
		$( '#woo-billing-to-company' ).val('1');
		$( '.woo-bg-company-info' ).show();
	} else {
		$( '#woo-billing-to-company' ).val('0');
	}
});

$('#woo-billing-to-company').on( 'change', function() {
	$doc.trigger("update_checkout");
});
