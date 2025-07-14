var formSelector = '.woo-bg-bgntoeur-wrapper form';
var $status = $('.woo-bg-bgntoeur-wrapper .status')
var $form = $( formSelector );

if ( $form.length ) {
	$form.on('submit', function(e) {
		e.preventDefault();

		let $this = $(this);


		let $status = $('.woo-bg-bgntoeur-wrapper .status');
		$status.show();
		$status.get(0).scrollIntoView();

		set_loading_text( $this.data('loading-text') );

		run_ajax( $this.serialize() );
	});

}

function run_ajax( args ) {
	$.ajax( {
		url: tpi.ajax,
		data: args
	} )
		.done(function( response ) {
			if ( typeof response.data.percents !== "undefined" ) {
				$('.progress-bar').css('width', response.data.percents + '%');
			}

			if ( response.data.log ) {
				set_log( response.data.log );
			}

			if ( response.data.loading_text ) {
				set_loading_text( response.data.loading_text );
			}
			
			if ( response.data.action ) {
				let data = response.data.data;
				data.action = response.data.action;
				run_ajax( data );
			}
		})
		.fail(function( error ) {
			console.log('Error:' + error);
		});
}

function set_loading_text( text ) {
	let $status = $('.woo-bg-bgntoeur-wrapper .status');

	$status.find('.progress-value .value').html( text );
}

function set_log( text ) {
	let $messages = $('.woo-bg-bgntoeur-wrapper .messages');

	$messages.prepend( text );
}