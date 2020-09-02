jQuery(function($) {

    /**
     * Simple throttle function
     * @param function callback The callback function
     * @param int limit The number of milliseconds to wait for
     */
    function gp_throttle (callback, limit) {

        // Ensure we have a limit.
        if ( ! limit ) {
            limit = 200
        }

        // Initially, we're not waiting
        var wait = false;

        // Ensure that the last call was handled
        var did_last = true;

        // We return a throttled function
        return function () {

            // If we're not waiting
            if ( ! wait ) {

                // We did the last action.
                did_last = true;

                // Execute users function
                callback.bind(this).call();

                // Prevent future invocations
                wait = true;

                // For a period of time...
                setTimeout(function () {

                    // then allow future invocations
                    wait = false;

                }, limit);

            // If we're waiting...
            } else {

                // We did not do the last action.
                did_last = false;

                // Wait for a period of time...
                var that = this
                setTimeout(function () {

                    // then ensure that we did the last call.
                    if ( ! did_last ) {
                        callback.bind(that).call();
                        did_last = true
                    }

                }, limit);

            }

        }
    }

    // A local cache of prices.
    var cached_prices = {}

    // Fetch prices from the server.
    var get_prices = function( form, form_data ) {

        wpinvBlock(form);

        return $.post( WPInv.ajax_url, form_data + '&action=wpinv_payment_form_refresh_prices&_ajax_nonce=' + WPInv.formNonce )

            .done( function( res ) {

                // We have prices.
                if ( res.success ) {

                    // Cache the data.
                    cached_prices[ form_data ] = res.data
                    return;

                }

                // An error occured.
                form.find('.getpaid-payment-form-errors').html(res).removeClass('d-none')

            } )

            .fail( function( res ) {
                form.find('.getpaid-payment-form-errors').html(WPInv.connectionError).removeClass('d-none')
            } )

            .always(() => {
                form.unblock();
            })

        }

    /**
     * Refresh prices from the cache.
     */
    var handle_refresh = function( form, form_data ) {

        // Hide any errors.
        form.find('.getpaid-payment-form-errors').html('').addClass('d-none')

        var data = cached_prices[ form_data ]

        // Process totals.
        if ( data.totals ) {

            for ( var total in data.totals ) {
                if ( data.totals.hasOwnProperty( total ) ) {
                    form.find('.getpaid-form-cart-totals-total-' + total).html(data.totals[total])
                }
            }

        }

        // Process item sub-totals.
        if ( data.items ) {

            for ( var item in data.items ) {
                if ( data.items.hasOwnProperty( item ) ) {
                    form.find('.getpaid-form-cart-item-subtotal-' + item).html(data.items[item])
                }
            }

        }
    }

    /**
     * Refresh prices either from cache or from the server.
     */
    var refresh_prices = function( form ) {

        // Get form data.
        var form_data = form.serialize()

        // If we have the items in the cache...
        if ( cached_prices[ form_data ] ) {
            handle_refresh( form, form_data )
            return
        }

        get_prices( form, form_data ).done( function () {
            if ( cached_prices[ form_data ] ) {
                handle_refresh( form, form_data )
            }
        })

    }

    // Handles field changes.
    var on_field_change = function() {

        // Sanitize the value.
        $(this).val( $(this).val().replace(/[^0-9\.]/g,'') )

        // Ensure that we have a value.
        if ( '' == $(this).val() ) {
            $(this).val('1')
        }

        // Refresh prices.
        refresh_prices( $( this ).closest('.getpaid-payment-form') )
    }

    // Refresh when custom prices change.
    $( 'body').on( 'input', '.getpaid-item-price-input', gp_throttle( on_field_change, 500 ) );

    // Refresh when quantities change.
    $( 'body').on( 'change', '.getpaid-item-quantity-input', gp_throttle( on_field_change, 500 ) );

    // Refresh when country changes.
    $( 'body').on( 'change', '#wpinv_country', function() {

        var form = $( this ).closest('.getpaid-payment-form')
        if ( form.find('#wpinv_state').length ) {

            wpinvBlock( form.find('.wpinv_state') );

                data = {
                    action: 'wpinv_get_payment_form_states_field',
                    country: $(this).val(),
                    form: form.find('input[name="form_id"]').val()
                };

                $.get(ajaxurl, data, function( res ) {

                    if ( 'object' == typeof res ) {
                        form.find('.wpinv_state').html( res.data )
                    }

                })

                .always( function(data) {
                    form.find('.wpinv_state').unblock()
                });

        }

        refresh_prices( form )
    } );

    // Refresh when state changes.
    $( 'body').on( 'change', '#wpinv_state', function() {
        refresh_prices( $( this ).closest('.getpaid-payment-form') )
    } );

    $('body').on('click', 'input[name="wpi-gateway"]', function ( e ) {

        var form = $( this ).closest( '.getpaid-payment-form' );

        // Hide all visible payment methods.
        form
            .find('.getpaid-gateway-description-div')
            .filter(':visible')
            .slideUp(250);

        // Display checked ones.
        form
            .find( 'input[name="wpi-gateway"]:checked' )
            .closest( '.getpaid-gateways-select-gateway' )
            .find( '.getpaid-gateway-description-div' )
            .slideDown(250)

    });

    /**
     * Set's up a payment form for use.
     *
     * @param {string} form 
     */
    var setup_form = function( form ) {

        // Add the row class to gateway credit cards.
        form.find('.getpaid-gateway-description-div .form-horizontal .form-group').addClass('row')

        // Get a list of all active gateways.
        var gateways = form.find('.getpaid-payment-form-element-gateway_select input[name="wpi-gateway"]');

        // If there is one gateway, we can hide the radio input and the title.
        if ( 1 === gateways.length ) {
            gateways.eq(0).hide();
            form.find('.getpaid-gateways-select-title-div').hide()
        }

        // Hide the title if there is no gateway.
        if ( gateways.length === 0) {
            form.find('.getpaid-gateways-select-title-div').hide()
            form.find('.getpaid-payment-form-submit').prop( 'disabled', true ).css('cursor', 'not-allowed');
        }

        // If there is no gateway selected, select the first.
        if ( 0 === gateways.filter(':checked').length ) {
            gateways.eq(0).prop( 'checked', true );
        }

        // Trigger click event for selected gateway.
        gateways.filter(':checked').eq(0).trigger('click');

        // Hides items that are not in an array.
        /**
         * @param {Array} selected_items The items to display.
         */
        function filter_form_cart( selected_items ) {

            // Abort if there is no cart.
            if ( 0 == form.find( ".getpaid-payment-form-items-cart" ).length ) {
                return;
            }

            // Hide all selectable items.
            form.find('.getpaid-payment-form-items-cart-item.getpaid-selectable').each( function() {
                $( this ).find('.getpaid-item-price-input').attr( 'name', '' )
                $( this ).find('.getpaid-item-quantity-input').attr( 'name', '' )
                $( this ).hide()
            })

            // Display selected items.
            $( selected_items ).each( function( index, item_id ) {
        
                if ( item_id ) {
                    var item = form.find('.getpaid-payment-form-items-cart-item.item-' + item_id )
                    item.find('.getpaid-item-price-input').attr( 'name', 'getpaid-items[' + item_id + '][price]' )
                    item.find('.getpaid-item-quantity-input').attr( 'name', 'getpaid-items[' + item_id + '][quantity]' )
                    item.show()
                }

            })

            // Refresh prices.
            refresh_prices( form )

        }

        // Radio select items.
        if ( form.find('.getpaid-payment-form-items-radio').length ) {

            // Hides displays the checked items.
            var filter_totals = function() {
                var selected_item = form.find(".getpaid-payment-form-items-radio .form-check-input:checked").val();
                filter_form_cart([selected_item])
            }

            // Do this when the value changes.
            var radio_items = form.find('.getpaid-payment-form-items-radio .form-check-input')

            radio_items.on( 'change', filter_totals );

            // If there are none selected, select the first.
            if ( 0 === radio_items.filter(':checked').length ) {
                radio_items.eq(0).prop( 'checked', true );
            }

            // Filter on page load.
            filter_totals();
        }

        // Checkbox select items.
        if ( form.find('.getpaid-payment-form-items-checkbox').length ) {

            // Hides displays the checked items.
            var filter_totals = function() {
                var selected_items = form
                    .find('.getpaid-payment-form-items-checkbox input:checked')
                    .map( function(){
                        return $(this).val();
                    })
                    .get()

                filter_form_cart(selected_items)
            }

            // Do this when the value changes.
            var checkbox_items = form.find('.getpaid-payment-form-items-checkbox input')

            checkbox_items.on( 'change', filter_totals );

            // If there are none selected, select the first.
            if ( 0 === checkbox_items.filter(':checked').length ) {
                checkbox_items.eq(0).prop( 'checked', true );
            }

            // Filter on page load.
            filter_totals();
        }

        // "Select" select items.
        if ( form.find('.getpaid-payment-form-items-select').length ) {

            // Hides displays the selected items.
            var filter_totals = function() {
                var selected_item = form.find(".getpaid-payment-form-items-select select").val();
                filter_form_cart([selected_item])
            }

            // Do this when the value changes.
            var select_box = form.find(".getpaid-payment-form-items-select select")

            select_box.on( 'change', filter_totals );

            // If there are none selected, select the first.
            if ( ! select_box.val() ) {
                select_box.find("option:first").prop('selected','selected');
            }

            // Filter on page load.
            filter_totals();
        }

        // Discounts.
        if ( form.find('.getpaid-discount-field').length ) {

            // Refresh prices when the discount button is clicked.
            form.find('.getpaid-discount-button').on('click', function( e ) {
                e.preventDefault();
                refresh_prices( form )
            } );

            // Refresh prices when hitting enter key in the discount field.
            form.find('.getpaid-discount-field').on('keypress', function( e ) {
                if ( e.keyCode == '13' ) {
                    e.preventDefault();
                    refresh_prices( form )
                }
            } );

            // Refresh prices when the discount value changes.
            form.find('.getpaid-discount-field').on('change', function( e ) {
                refresh_prices( form )
            } );

        }

        // Submitting the payment form.
        form.on( 'submit', function( e ) {

            // Do not submit the form.
            e.preventDefault();

            // instead, display a loading indicator.
            wpinvBlock(form);

            // Hide any errors.
            form.find('.getpaid-payment-form-errors').html('').addClass('d-none')

            // Fetch the unique identifier for this form.
            var unique_key = form.data('key')

            // Save data to a global variable so that other plugins can alter it.
            var data = {
                'submit' : true,
                'delay'  : false,
                'data'   : form.serialize(),
                'form'   : form,
                'key'    : unique_key,
            }

            // Trigger submit event.
            $( 'body' ).trigger( 'getpaid_payment_form_before_submit', [data] );

            if ( ! data.submit ) {
                form.unblock();
                return;
            }

            // Handles the actual submission.
            var submit = function () {
                return $.post( WPInv.ajax_url, data.data + '&action=wpinv_payment_form&_ajax_nonce=' + WPInv.formNonce )
                    .done( function( res ) {

                        // An error occured.
                        if ( 'string' == typeof res ) {
                            form.find('.getpaid-payment-form-errors').html(res).removeClass('d-none')
                            return
                        }

                        // Redirect to the thank you page.
                        if ( res.success ) {

                            // Asume that the action is a redirect.
                            if ( ! res.data.action ) {
                                window.location.href = decodeURIComponent(res.data)
                            }

                            if ( 'auto_submit_form' == res.data.action ) {
                                form.parent().append( '<div class="getpaid-checkout-autosubmit-form">' + res.data.form + '</div>' )
                                $( '.getpaid-checkout-autosubmit-form form' ).submit()
                            }

                            return
                        }

                        form.find('.getpaid-payment-form-errors').html(res.data).removeClass('d-none')
        
                    } )

                    .fail( function( res ) {
                        form.find('.getpaid-payment-form-errors').html(WPInv.connectionError).removeClass('d-none')
                    } )

                    .always(() => {
                        form.unblock();
                    })

            }

            // Are we submitting after a delay?
            if ( data.delay ) {

                var local_submit = function() {

                    if ( ! data.submit ) {
                        form.unblock();
                    } else {
                        submit();
                    }

                    $('body').unbind( 'getpaid_payment_form_delayed_submit' + unique_key, local_submit )

                }

                $('body').bind( 'getpaid_payment_form_delayed_submit' + unique_key, local_submit )
                return;
            }

            // If not, submit immeadiately.
            submit()

        })

    }

    // Set up all active forms.
    $('.getpaid-payment-form').each( function() {
        setup_form( $( this ) );
    } )

    // Payment buttons.
    $( document ).on( 'click', '.getpaid-payment-button', function( e ) {

        // Do not submit the form.
        e.preventDefault();

        // Add the loader.
        $('#getpaid-payment-modal .modal-body')
            .html( '<div class="d-flex align-items-center justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>' )

        // Display the modal.
        $('#getpaid-payment-modal').modal()

        // Load the form via ajax.
        var data    = $( this ).data()
        data.action = 'wpinv_get_payment_form'

        $.get( WPInv.ajax_url, data, function (res) {
            $('#getpaid-payment-modal .modal-body').html( res )
            $('#getpaid-payment-modal').modal('handleUpdate')
            $('#getpaid-payment-modal .getpaid-payment-form').each( function() {
                setup_form( $( this ) );
            } )
        })

        .fail(function (res) {
            $('#getpaid-payment-modal .modal-body').html(WPInv.connectionError)
            $('#getpaid-payment-modal').modal('handleUpdate')
        })

    } )

});
