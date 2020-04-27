jQuery(function($) {

    // Apply discounts.
    $( '.wpinv-payment-form-coupon-button').on( 'click', function( e ) {

        // Prevent default behaviour...
        e.preventDefault();

        // ... then display a loading indicator.
        var form = $( this ).closest('.wpinv_payment_form')
        wpinvBlock(form);

        // Then hide the errors
        var errors_el = form.find( '.wpinv_payment_form_coupon_errors' )
        errors_el.html('').addClass('d-none')

        // And submit the form to create an invoice and apply the discount.
        var data = form.serialize();

        $.post( WPInv.ajax_url, data + '&action=wpinv_payment_form_discount', function(res) {
            
            if ( res.success ) {
                form.find('.item_totals_total .col-4 strong').text( res.data )
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
