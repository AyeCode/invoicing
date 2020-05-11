jQuery(function($) {

    // Custom prices
    $( 'body').on( 'input', '.wpinv-item-price-input', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )
            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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

    // Custom quantities
    $( 'body').on( 'input', '.wpinv-item-quantity-input', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )
            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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
                .find('input:not(.wpinv-item-quantity-input)')
                .attr('name', '')
        })

        form
            .find( '*[data-id="' + val +'"]' )
            .removeClass('d-none')
            .find('input:not(.wpinv-item-quantity-input)')
            .attr('name', 'wpinv-items[' + val + ']')

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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

    $( 'body').on( 'change', '.wpinv-items-select-selector', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0
        var val   = $( this ).val()

        form.find( '.wpinv-items-select-selector option' ).each( function() {
            var id = $(this).val()
            form.find( '*[data-id="' + id +'"]' )
                .addClass('d-none')
                .find('input:not(.wpinv-item-quantity-input)')
                .attr('name', '')
        })

        form
            .find( '*[data-id="' + val +'"]' )
            .removeClass('d-none')
            .find('input:not(.wpinv-item-quantity-input)')
            .attr('name', 'wpinv-items[' + val + ']')

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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

    $( 'body').on( 'change', '.wpinv-items-multiselect-selector', function( e ) {

        var form  = $( this ).closest('.wpinv_payment_form')
        var total = 0.0

        form.find( '.wpinv-items-select-selector option' ).each( function() {
            var id = $(this).val()
            form.find( '*[data-id="' + id +'"]' )
                .addClass('d-none')
                .find('input:not(.wpinv-item-quantity-input)')
                .attr('name', '')
        })

        var val = $( this ).val()

        if ( val ) {
            $( val ).each( function( key, _val ) {

                form
                    .find( '*[data-id="' + _val +'"]' )
                    .removeClass('d-none')
                    .find('input:not(.wpinv-item-quantity-input)')
                    .attr('name', 'wpinv-items[' + _val + ']')

            })
        }

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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
                .find('input:not(.wpinv-item-quantity-input)')
                .attr('name', '')
        })

        form.find('.wpi-payment-form-items-select-checkbox:checked').each(function(){
            var val = $(this).val()
            
            form
                .find( '*[data-id="' + val +'"]' )
                .removeClass('d-none')
                .find('input:not(.wpinv-item-quantity-input)')
                .attr('name', 'wpinv-items[' + val + ']')
        });

        

        // Calculate the total of all items.
        form.find( '.wpinv-item-price-input' ).each( function() {

            if ( 0 == $( this ).attr('name').length ) {
                return;
            }

            var value = parseFloat( $(this).val() )

            var quantity = parseInt( $(this).closest('.item_totals_item').find('.wpinv-item-quantity-input').val() )

            if ( isNaN( quantity ) || 1 > quantity ) {
                quantity = 1;
            }

            if ( ! isNaN( value ) ) {
                total = total + ( value * quantity );
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

    window.wpinvPaymentFormSubmt = true
    window.wpinvPaymentFormDelaySubmit = false
    window.wpinvPaymentFormData = ''
    $( document ).on( 'submit', '.wpinv_payment_form', function( e ) {
        
        // Do not submit the form.
        e.preventDefault();

        // Set defaults
        wpinvPaymentFormSubmt = true
        wpinvPaymentFormDelaySubmit = false
        wpinvPaymentFormData = ''

        // instead, display a loading indicator.
        var form = $( this )
        wpinvBlock(form);

        // Then hide the errors
        var errors_el = form.find( '.wpinv_payment_form_errors' )
        errors_el.html('').addClass('d-none')

        // And submit the form to create an invoice.
        var data = form.serialize();
        wpinvPaymentFormData = data

        window.wp.hooks.applyFilters( 'wpinv_payment_form_data', data, form )

        if ( ! window.wpinvPaymentFormSubmt ) {
            form.unblock();
            return;
        }

        var submit = function () {
            return $.post(WPInv.ajax_url, wpinvPaymentFormData + '&action=wpinv_payment_form', function (res) {

                if ('string' == typeof res) {
                    errors_el.html(res).removeClass('d-none')
                    return
                }

                if (res.success) {
                    window.location.href = decodeURIComponent(res.data)
                    return
                }

                errors_el.html(res.data).removeClass('d-none')

            })

                .fail(function (res) {
                    errors_el.html('Could not establish a connection to the server.').removeClass('d-none')
                })

                .always(() => {
                    form.unblock();
                })
        }

        if ( wpinvPaymentFormDelaySubmit ) {
            var local_submit = function( e, data ) {

                if ( ! window.wpinvPaymentFormSubmt ) {
                    form.unblock();
                } else {
                    submit()
                }

                $('body').unbind( 'wpinv_payment_form_delayed_submit', local_submit )

            }
            $('body').bind( 'wpinv_payment_form_delayed_submit', local_submit )

        } else {
            submit()
        }

    })

    $('.wpinv_payment_form').on('click', 'input[name="wpi-gateway"]', function ( e ) {

        var form = $( this ).closest( '.wpinv_payment_form' );
        var is_checked = $(this).is(':checked')

        if ($('.wpi-payment_methods input.wpi-pmethod').length > 1) {

            var target_payment_box = form.find('div.payment_box.' + $(this).attr('ID'));
            if ( is_checked && !target_payment_box.is(':visible') ) {

                // Hide all visible payment methods.
                form.find('div.payment_box').filter(':visible').slideUp(250);
                if ($(this).is(':checked')) {
                    var content = $('div.payment_box.' + $(this).attr('ID')).html();
                    content = content ? content.trim() : '';
                    if (content) {
                        $('div.payment_box.' + $(this).attr('ID')).slideDown(250);
                    }
                }
            }

        } else {

            $('div.payment_box').show();

        }
        $('#wpinv_payment_mode_select').attr('data-gateway', $(this).val());
        wpinvSetPaymentBtnText($(this), $('#wpinv_payment_mode_select').data('free'));
    });

    $('.wpinv_payment_form').find('.payment_box .form-horizontal .form-group').addClass('row')

    $('.wpinv_payment_form').each( function() {

        var $checkout_form = $( this );
        var $payment_methods = $checkout_form.find('.wpi-payment_methods input[name="wpi-gateway"]');

        // If there is one method, we can hide the radio input
        if (1 === $payment_methods.length) {
            $payment_methods.eq(0).hide();
        }

        // If there are none selected, select the first.
        if (0 === $payment_methods.filter(':checked').length) {
            $payment_methods.eq(0).prop('checked', true);
        }

        // Trigger click event for selected method
        $payment_methods.filter(':checked').eq(0).trigger('click');

    } )

});
