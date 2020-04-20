jQuery(function($) {

    $( document ).on( 'submit', '.wpinv_payment_form', function( e ) {
        
        // Do not submit the form.
        e.preventDefault();

        // instead, display a loading indicator.
        var form = $( this )
        wpinvBlock(form);

        // Then hide the errors
        var errors_el = form.find( '.wpinv_payment_form_errors' )
        errors_el.html('').addClass('d-none')

        // And submit the form to create an invoice.
        var data = form.serialize();

        $.post( WPInv.ajax_url, data + '&action=wpinv_payment_form', function(res) {
            
            if ( res.success ) {
                window.location.href = res.data
            } else {
                errors_el.text(res.data).removeClass('d-none')
            }
        })

        .fail( function( res ) {
            errors_el.html('Could not establish a connection to the server.').removeClass('d-none')
        } )

        .always(() => {
            form.unblock();
        })
        
    })

});
