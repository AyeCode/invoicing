jQuery(function($) {

    // Custom prices
    $( 'body').on( 'input', '.wpinv-item-price-input', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {
            var value = parseFloat( $(this).val() )

            if ( ! isNaN( value ) ) {
                total = total + value;
            }
            
        })

        // Format the total.
        var total = total.toFixed(2) + '';
        var parts = total.toString().split('.');
		parts[0]  = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		total =  parts.join('.');

        var totals = form.find( '.wpinv-items-total' )

        if ( 'left' == totals.data('currency-position') ) {
            totals.text( totals.data('currency') + total )
        } else {
            totals.text( total + totals.data('currency') )
        }

    })

    $( 'body').on( 'input', '.wpinv-items-selector', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0
        var val   = $( this ).val()

        form.find( '.wpinv-items-selector' ).each( function() {
            var id = $(this).val()
            form.find( '*[data-id="' + id +'"]' )
                .addClass('d-none')
                .find('input')
                .attr('name', '')
        })

        form
            .find( '*[data-id="' + val +'"]' )
            .removeClass('d-none')
            .find('input')
            .attr('name', 'wpinv-items[' + val + ']')

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            if ( ! isNaN( value ) ) {
                total = total + value;
            }
            
        })

        // Format the total.
        total = total.toFixed(2) + '';
        var parts = total.toString().split('.');
		parts[0]  = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		total =  parts.join('.');

        var totals = form.find( '.wpinv-items-total' )

        if ( 'left' == totals.data('currency-position') ) {
            totals.text( totals.data('currency') + total )
        } else {
            totals.text( total + totals.data('currency') )
        }

    })

    $( 'body').on( 'change', '.wpi-payment-form-items-select-checkbox', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0

        form.find( '.wpi-payment-form-items-select-checkbox' ).each( function() {
            var id = $(this).val()
            form.find( '*[data-id="' + id +'"]' )
                .addClass('d-none')
                .find('input')
                .attr('name', '')
        })

        form.find('.wpi-payment-form-items-select-checkbox:checked').each(function(){
            var val = $(this).val()
            
            form
                .find( '*[data-id="' + val +'"]' )
                .removeClass('d-none')
                .find('input')
                .attr('name', 'wpinv-items[' + val + ']')
        });

        

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            if ( ! isNaN( value ) ) {
                total = total + value;
            }

        })

        // Format the total.
        total = total.toFixed(2) + '';
        var parts = total.toString().split('.');
		parts[0]  = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		total =  parts.join('.');

        var totals = form.find( '.wpinv-items-total' )

        if ( 'left' == totals.data('currency-position') ) {
            totals.text( totals.data('currency') + total )
        } else {
            totals.text( total + totals.data('currency') )
        }

    })

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
