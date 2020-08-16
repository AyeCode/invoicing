jQuery(function($) {
    //'use strict';

    // Tooltips
    $('.wpi-help-tip').tooltip({
        content: function() {
            return $(this).prop('title');
        },
        tooltipClass: 'wpi-ui-tooltip',
        position: {
            my: 'center top',
            at: 'center bottom+10',
            collision: 'flipfit'
        },
        hide: {
            duration: 200
        },
        show: {
            duration: 200
        }
    });

    // Init select 2.
    wpi_select2();
    function wpi_select2() {
        if (jQuery("select.wpi_select2").length > 0) {
            jQuery("select.wpi_select2").select2();
            jQuery("select.wpi_select2_nostd").select2({
                allow_single_deselect: 'true'
            });
        }
    }

    // Subscription items.
    if ( $('#wpinv_is_recurring').length ) {

        // Toggles the 'getpaid_is_subscription_item' class on the body.
        var watch_subscription_change = function() {
            $('body').toggleClass( 'getpaid_is_subscription_item', $('#wpinv_is_recurring').is(':checked') )
            $('body').toggleClass( 'getpaid_is_not_subscription_item', ! $('#wpinv_is_recurring').is(':checked') )

            $('.getpaid-price-input').toggleClass( 'col-sm-4', $('#wpinv_is_recurring').is(':checked') )
            $('.getpaid-price-input').toggleClass( 'col-sm-12', ! $('#wpinv_is_recurring').is(':checked') )

        }

        // Toggle the class when the document is loaded...
        watch_subscription_change();

        // ... and whenever the checkbox changes.
        $(document).on('change', '#wpinv_is_recurring', watch_subscription_change);

    }

    // Dynamic items.
    if ( $('#wpinv_name_your_price').length ) {

        // Toggles the 'getpaid_is_dynamic_item' class on the body.
        var watch_dynamic_change = function() {
            $('body').toggleClass( 'getpaid_is_dynamic_item', $('#wpinv_name_your_price').is(':checked') )
            $('body').toggleClass( 'getpaid_is_not_dynamic_item', ! $('#wpinv_name_your_price').is(':checked') )
        }

        // Toggle the class when the document is loaded...
        watch_dynamic_change();

        // ... and whenever the checkbox changes.
        $(document).on('change', '#wpinv_name_your_price', watch_dynamic_change);

    }

    // Non-editable items.
    $('.wpi-editable-n #post :input').attr( 'disabled', true );

    // Rename excerpt to 'description'
    $('body.post-type-wpi_item #postexcerpt h2.hndle').text( WPInv_Admin.item_description )
    $('body.post-type-wpi_discount #postexcerpt h2.hndle').text( WPInv_Admin.discount_description )
    $('body.post-type-wpi_invoice #postexcerpt h2.hndle').text( WPInv_Admin.invoice_description )
    $('body.post-type-wpi_invoice #postexcerpt p, body.post-type-wpi_item #postexcerpt p, body.post-type-wpi_discount #postexcerpt p').hide()

    // Discount types.
    $(document).on('change', '#wpinv_discount_type', function() {
        $('#wpinv_discount_amount_wrap').removeClass('flat percent')
        $('#wpinv_discount_amount_wrap').addClass( $( this ).val() )
    });

    // Fill in user information.
    $('#getpaid-invoice-fill-user-details').on( 'click', function(e) {
        e.preventDefault()

        var metabox = $(this).closest('.inside');
        var user_id = metabox.find('#post_author_override').val()

        // Ensure that we have a user id and that we are not adding a new user.
        if ( ! user_id || $(this).attr('disabled') ) {
            return;
        }

        // Let the user know that the billing details will be replaced.
        if ( ! window.confirm( WPInv_Admin.FillBillingDetails ) ) {
            return;
        }

        // Block the metabox.
        wpinvBlock( metabox )

        // Retrieve the user's billing address.
        var data = {
            action: 'wpinv_get_billing_details',
            user_id: user_id,
            _ajax_nonce: WPInv_Admin.wpinv_nonce
        }

        $.get( WPInv_Admin.ajax_url, data )

            .done( function( response ) {

                if ( response.success ) {

                    $.each(response.data, function(key, value) {

                        // Retrieve the associated input.
                        var el = $( '#wpinv_' + key )

                        // If it exists...
                        if ( el.length ) {
                            el.val( value ).change()
                        }

                    });
                }
            })

            .always( function( response ) {
                wpinvUnblock( metabox );
            })

    })

    // When clicking the create a new user button...
    $('#getpaid-invoice-create-new-user-button').on('click', function(e) {
        e.preventDefault()

        // Hide the button and the customer select div.
        $( '#getpaid-invoice-user-id-wrapper, #getpaid-invoice-create-new-user-button' ).addClass( 'd-none' )

        // Display the email input and the cancel button.
        $( '#getpaid-invoice-cancel-create-new-user, #getpaid-invoice-email-wrapper' ).removeClass( 'd-none' )

        // Disable the fill user details button.
        $( '#getpaid-invoice-fill-user-details' ).attr( 'disabled', true );

        // Indicate that we will be creating a new user.
        $( '#getpaid-invoice-create-new-user' ).val(1);

        // The email field is now required.
        $( '#getpaid-invoice-new-user-email' ).prop('required', 'required');
 
    });

    // When clicking the "cancel new user" button...
    $( '#getpaid-invoice-cancel-create-new-user') .on('click', function(e) {
        e.preventDefault();

        // Hide the button and the email input divs.
        $( '#getpaid-invoice-cancel-create-new-user, #getpaid-invoice-email-wrapper' ).addClass( 'd-none' )

        // Display the add new user button and select customer divs.
        $( '#getpaid-invoice-user-id-wrapper, #getpaid-invoice-create-new-user-button' ).removeClass( 'd-none' )

        // Enable the fill user details button.
        $( '#getpaid-invoice-fill-user-details' ).attr( 'disabled', false );

        // We are no longer creating a new user.
        $( '#getpaid-invoice-create-new-user' ).val(0);
        $( '#getpaid-invoice-new-user-email' ).prop('required', false);

    });

    // When the new user's email changes...
    $( '#getpaid-invoice-new-user-email' ).on('change', function(e) {
        e.preventDefault();

        // Hide any error messages.
        $( this )
            .removeClass( 'is-invalid' )
            .parent()
            .find('.invalid-feedback')
            .remove()

        var metabox = $(this).closest('.inside');
        var email   = $(this).val()

        // Block the metabox.
        wpinvBlock( metabox )

        // Ensure the email is unique.
        var data = {
            action: 'wpinv_check_new_user_email',
            email: email,
            _ajax_nonce: WPInv_Admin.wpinv_nonce
        }

        $.get( WPInv_Admin.ajax_url, data )

            .done( function( response ) {
                if ( ! response.success ) {
                    // Show error messages.
                    $( '#getpaid-invoice-new-user-email' )
                    .addClass( 'is-invalid' )
                    .parent()
                    .append('<div class="invalid-feedback">' + response +'</div>')
                }
            } )

            .always( function( response ) {
                wpinvUnblock( metabox );
            })

    });

    // When the country changes, load the states field.
    $( '.post-type-wpi_invoice' ).on( 'change', '#wpinv_country', function(e) {

        // Ensure that we have the states field.
        if ( ! $( '#wpinv_state' ).length ) {
            return
        }

        var row = $(this).closest('.row');

        // Block the row.
        wpinvBlock( row )

        // Fetch the states field.
        var data = {
            action: 'wpinv_get_aui_states_field',
            country: $( '#wpinv_country' ).val(),
            state: $( '#wpinv_state' ).val(),
            _ajax_nonce: WPInv_Admin.wpinv_nonce
        }

        // Fetch new states field.
        $.get( WPInv_Admin.ajax_url, data )

            .done( function( response ) {
                if ( response.success ) {
                    $( '#wpinv_state' ).closest('.form-group').replaceWith(response.data.html)

                    if ( response.data.select ) {
                        $( '#wpinv_state' ).select2()
                    }
                }
            } )

            .always( function( response ) {
                wpinvUnblock( row );
            })
    })

    var wpiGlobalTax = WPInv_Admin.tax != 0 ? WPInv_Admin.tax : 0;
    var wpiGlobalDiscount = WPInv_Admin.discount != 0 ? WPInv_Admin.discount : 0;
    var wpiSymbol = WPInv_Admin.currency_symbol;
    var wpiPosition = WPInv_Admin.currency_pos;
    var wpiThousandSep = WPInv_Admin.thousand_sep;
    var wpiDecimalSep = WPInv_Admin.decimal_sep;
    var wpiDecimals = WPInv_Admin.decimals;
    var $postForm = $('.post-type-wpi_invoice form#post');
    if ($('[name="wpinv_status"]', $postForm).length) {
        var origStatus = $('[name="wpinv_status"]', $postForm).val();
        $('[name="original_post_status"]', $postForm).val(origStatus);
        $('[name="hidden_post_status"]', $postForm).val(origStatus);
        $('[name="post_status"]', $postForm).replaceWith('<input type="hidden" value="' + origStatus + '" id="post_status" name="post_status">');
        $postForm.on('change', '[name="wpinv_status"]', function(e) {
            e.preventDefault();
            $('[name="post_status"]', $postForm).replaceWith('<input type="hidden" value="' + $(this).val() + '" id="post_status" name="post_status">');
        });
    }
    $('input.wpi-price').on("contextmenu", function(e) {
        return false;
    });
    $(document).on('keypress', 'input.wpi-price', function(e) {
        var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
        if ($.inArray(e.key, ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ",", "."]) !== -1) {
            return true;
        } else if (e.ctrlKey || e.shiftKey) {
            return false;
        } else if ($.inArray(key, [8, 35, 36, 37, 39, 46]) !== -1) {
            return true;
        }
        return false;
    });
    // sorts out the number to enable calculations
    function wpinvRawNumber(x) {
        // removes the thousand seperator
        var parts = x.toString().split(wpiThousandSep);
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '');
        var amount = parts.join('');
        // makes the decimal seperator a period
        var output = amount.toString().replace(/\,/g, '.');
        output = parseFloat(output);
        if (isNaN(output)) {
            output = 0;
        }
        if (output && output < 0) {
            output = output * (-1);
        }
        return output;
    }
    // formats number into users format
    function wpinvFormatNumber(nStr) {
        var num = nStr.split('.');
        var x1 = num[0];
        var x2 = num.length > 1 ? wpiDecimalSep + num[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + wpiThousandSep + '$2');
        }
        return x1 + x2;
    }
    // format the amounts
    function wpinvFormatAmount(amount) {
        if (typeof amount !== 'number') {
            amount = parseFloat(amount);
        }
        // do the symbol position formatting   
        var formatted = 0;
        var amount = amount.toFixed(wpiDecimals);
        switch (wpiPosition) {
            case 'left':
                formatted = wpiSymbol + wpinvFormatNumber(amount);
                break;
            case 'right':
                formatted = wpinvFormatNumber(amount) + wpiSymbol;
                break;
            case 'left_space':
                formatted = wpiSymbol + ' ' + wpinvFormatNumber(amount);
                break;
            case 'right_space':
                formatted = wpinvFormatNumber(amount) + ' ' + wpiSymbol;
                break;
            default:
                formatted = wpiSymbol + wpinvFormatNumber(amount);
                break;
        }
        return formatted;
    }
    /**
     * Invoice Notes Panel
     */
    var wpinv_meta_boxes_notes = {
        init: function() {
            $('#wpinv-notes')
                .on('click', 'a.add_note', this.add_invoice_note)
                .on('click', 'a.delete_note', this.delete_invoice_note);
            if ($('ul.invoice_notes')[0]) {
                $('ul.invoice_notes')[0].scrollTop = $('ul.invoice_notes')[0].scrollHeight;
            }
        },
        add_invoice_note: function() {
            if (!$('textarea#add_invoice_note').val()) {
                return;
            }
            $('#wpinv-notes').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            var data = {
                action: 'wpinv_add_note',
                post_id: WPInv_Admin.post_ID,
                note: $('textarea#add_invoice_note').val(),
                note_type: $('select#invoice_note_type').val(),
                _nonce: WPInv_Admin.add_invoice_note_nonce
            };
            $.post(WPInv_Admin.ajax_url, data, function(response) {
                $('ul.invoice_notes').append(response);
                $('ul.invoice_notes')[0].scrollTop = $('ul.invoice_notes')[0].scrollHeight;
                $('#wpinv-notes').unblock();
                $('#add_invoice_note').val('');
            });
            return false;
        },
        delete_invoice_note: function() {
            var note = $(this).closest('li.note');
            $(note).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            var data = {
                action: 'wpinv_delete_note',
                note_id: $(note).attr('rel'),
                _nonce: WPInv_Admin.delete_invoice_note_nonce
            };
            $.post(WPInv_Admin.ajax_url, data, function() {
                $(note).remove();
            });
            return false;
        }
    };
    wpinv_meta_boxes_notes.init();
    $('.post-type-wpi_invoice [name="post"] #submitpost [type="submit"]').on('click', function(e) {
        if (parseInt($(document.body).find('.wpinv-line-items > .item').length) < 1) {
            alert(WPInv_Admin.emptyInvoice);
            $('#wpinv_invoice_item').focus();
            return false;
        }
    });
    var invDetails = jQuery('#gdmbx2-metabox-wpinv_details').html();
    if (invDetails) {
        jQuery('#submitpost', jQuery('.wpinv')).detach().appendTo(jQuery('#wpinv-details'));
        jQuery('#submitdiv', jQuery('.wpinv')).remove();
        jQuery('#publishing-action', '#wpinv-details').find('input[type=submit]').attr('name', 'save_invoice').val(WPInv_Admin.save_invoice);
    }
    var invBilling = jQuery('#wpinv-address.postbox').html();
    if (invBilling) {
        jQuery('#post_author_override', '#authordiv').remove(); //.addClass('gdmbx2-text-medium').detach().prependTo(jQuery('.gdmbx-customer-div'));
        jQuery('#authordiv', jQuery('.wpinv')).hide();
    }
    var wpinvNumber;
    if (!jQuery('#post input[name="post_title"]').val() && (wpinvNumber = jQuery('#wpinv-details input[name="wpinv_number"]').val())) {
        jQuery('#post input[name="post_title"]').val(wpinvNumber);
    }
    var wpi_stat_links = jQuery('.post-type-wpi_invoice .subsubsub');
    if (wpi_stat_links.is(':visible')) {
        var publish_count = jQuery('.publish', wpi_stat_links).find('.count').text();
        jQuery('.publish', wpi_stat_links).find('a').html(WPInv_Admin.status_publish + ' <span class="count">' + publish_count + '</span>');
        var pending_count = jQuery('.wpi-pending', wpi_stat_links).find('.count').text();
        jQuery('.pending', wpi_stat_links).find('a').html(WPInv_Admin.status_pending + ' <span class="count">' + pending_count + '</span>');
    }
    // Update tax rate state field based on selected rate country
    $(document.body).on('change', '#wpinv_tax_rates select.wpinv-tax-country', function() {
        var $this = $(this);
        data = {
            action: 'wpinv_get_states_field',
            country: $(this).val(),
            field_name: $this.attr('name').replace('country', 'state')
        };
        $.post(ajaxurl, data, function(response) {
            if ('nostates' == response) {
                var text_field = '<input type="text" name="' + data.field_name + '" value=""/>';
                $this.parent().next().find('select').replaceWith(text_field);
            } else {
                $this.parent().next().find('input,select').show();
                $this.parent().next().find('input,select').replaceWith(response);
            }
        });
        return false;
    });

    // Update state field based on selected country
    var getpaid_user_edit_sync_state_and_country = function() {

        // Ensure that we have both fields.
        if ( ! $('.getpaid_js_field-country').length || ! $('.getpaid_js_field-state').length ) {
            return
        }

        // fade the state field.
        $('.getpaid_js_field-state').fadeTo(1000, 0.4);

        // Prepare data.
        data = {
            action: 'wpinv_get_states_field',
            country: $('.getpaid_js_field-country').val(),
            field_name: $('.getpaid_js_field-country').attr('name').replace( 'country', 'state' )
        };

        // Fetch new states field.
        $.post( ajaxurl, data )

        .done( function( response ) {

            var value = $('.getpaid_js_field-state').val()

            if ( 'nostates' == response ) {
                var text_field = '<input type="text" name="' + data.field_name + '" value="" class="getpaid_js_field-state regular-text"/>';
                $('.getpaid_js_field-state').replaceWith(text_field);
            } else {
                var response = $(response)
                response.addClass('getpaid_js_field-state regular-text')
                response.attr( 'id', data.field_name)
                $('.getpaid_js_field-state').replaceWith( response )
            }

            $('.getpaid_js_field-state').val( value )

        })

        .fail( function() {
            var text_field = '<input type="text" name="' + data.field_name + '" value="" class="getpaid_js_field-state regular-text"/>';
            $('.getpaid_js_field-state').replaceWith(text_field);
        })

        .always( function() {
            // unfade the state field.
            $('.getpaid_js_field-state').fadeTo(1000, 1);
        })


    }

    // Sync on load.
    getpaid_user_edit_sync_state_and_country();

    // Sync on changes.
    $(document.body).on('change', '.getpaid_js_field-country', getpaid_user_edit_sync_state_and_country);

    // Insert new tax rate row
    $('#wpinv_add_tax_rate').on('click', function() {
        var row = $('#wpinv_tax_rates tbody tr:last');
        row.find('.wpi_select2').each(function() {
            $(this).select2('destroy');
        });
        var clone = row.clone();
        var count = row.parent().find('tr').length;
        clone.find('td input').not(':input[type=checkbox]').val('');
        clone.find('td [type="checkbox"]').attr('checked', false);
        clone.find('input, select').each(function() {
            var name = $(this).attr('name');
            name = name.replace(/\[(\d+)\]/, '[' + parseInt(count) + ']');
            $(this).attr('name', name).attr('id', name);
        });
        clone.find('label').each(function() {
            var name = $(this).attr('for');
            name = name.replace(/\[(\d+)\]/, '[' + parseInt(count) + ']');
            $(this).attr('for', name);
        });
        clone.insertAfter(row);
        wpi_select2();
        return false;
    });
    // Remove tax row
    $(document.body).on('click', '#wpinv_tax_rates .wpinv_remove_tax_rate', function() {
        var tax_rates = $('#wpinv_tax_rates tbody tr:visible');
        var count = tax_rates.length;
        if (count === 1) {
            $('#wpinv_tax_rates select').val('');
            $('#wpinv_tax_rates input[type="text"]').val('');
            $('#wpinv_tax_rates input[type="number"]').val('');
            $('#wpinv_tax_rates input[type="checkbox"]').attr('checked', false);
        } else {
            $(this).closest('tr').remove();
        }
        /* re-index after deleting */
        $('#wpinv_tax_rates tbody tr').each(function(rowIndex) {
            $(this).children().find('input, select').each(function() {
                var name = $(this).attr('name');
                name = name.replace(/\[(\d+)\]/, '[' + (rowIndex) + ']');
                $(this).attr('name', name).attr('id', name);
            });
        });
        return false;
    });
    var elB = $('#wpinv-address');

    $('#wpinv_state', elB).on('change', function(e) {
        window.wpiConfirmed = true;
        $('#wpinv-recalc-totals').click();
        window.wpiConfirmed = false;
    });
    $('#wpinv_taxable').on('change', function(e) {
        window.wpiConfirmed = true;
        $('#wpinv-recalc-totals').click();
        window.wpiConfirmed = false;
    });

    var WPInv = {
        init: function() {
            this.preSetup();
            this.remove_item();
            this.add_item();
            this.recalculateTotals();
            this.setup_tools();
        },
        preSetup: function() {
            $('.wpiDatepicker').each(function(e) {
                var $this = $(this);
                var args = {};
                if ($this.attr('data-changeMonth')) {
                    args.changeMonth = true;
                }
                if ($this.attr('data-changeYear')) {
                    args.changeYear = true;
                }
                if ($this.attr('data-dateFormat')) {
                    args.dateFormat = $this.attr('data-dateformat');
                }
                if ($this.attr('data-minDate')) {
                    args.minDate = $this.attr('data-minDate');
                }
                $(this).datepicker(args);
            });
            var wpinvColorPicker = $('.wpinv-color-picker');
            if (wpinvColorPicker.length) {
                wpinvColorPicker.wpColorPicker();
            }
            var no_states = $('select.wpinv-no-states');
            if (no_states.length) {
                no_states.closest('tr').hide();
            }
            // Update base state field based on selected base country
            $('select[name="wpinv_settings[default_country]"]').change(function() {
                var $this = $(this),
                    $tr = $this.closest('tr');
                data = {
                    action: 'wpinv_get_states_field',
                    country: $(this).val(),
                    field_name: 'wpinv_settings[default_state]'
                };
                $.post(ajaxurl, data, function(response) {
                    if ('nostates' == response) {
                        $tr.next().hide();
                    } else {
                        $tr.next().show();
                        $tr.next().find('select').replaceWith(response);
                    }
                });
                return false;
            });
            
            $('#wpinv-apply-code').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $form = $(this).closest('form[name="post"]');
                var invoice_id = parseInt($form.find('input#post_ID').val());
                if (!invoice_id > 0) {
                    return false;
                }
                
                if (!parseInt($(document.body).find('.wpinv-line-items > .item').length) > 0) {
                    alert(WPInv_Admin.emptyInvoice);
                    $('#wpinv_invoice_item').focus();
                    return false;
                }
                
                var discount_code = $('#wpinv_discount', $form).val();
                
                if (!discount_code) {
                    $('#wpinv_discount', $form).focus();
                    return false;
                }
                
                $this.attr('disabled', true);
                $this.after('<span class="wpi-refresh">&nbsp;&nbsp;<i class="fa fa-spin fa-refresh"></i></span>');

                var data = {
                    action: 'wpinv_admin_apply_discount',
                    invoice_id: invoice_id,
                    code: discount_code,
                    _nonce: WPInv_Admin.wpinv_nonce
                };
                
                $.post(WPInv_Admin.ajax_url, data, function(response) {
                    var msg, success;
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            success = true;
                            
                            $('#wpinv_discount', $form).attr('readonly', true);
                            $this.removeClass('wpi-inlineb').addClass('wpi-hide');
                            $('#wpinv-remove-code', $form).removeClass('wpi-hide').addClass('wpi-inlineb');
                        }
                        
                        if (response.msg) {
                            msg = response.msg;
                        }
                    }
                    
                    $this.attr('disabled', false);
                    $this.closest('div').find('.wpi-refresh').remove();
                    
                    if (success) {
                        console.log(success);
                        window.wpiConfirmed = true;
                        $('#wpinv-recalc-totals').click();
                        window.wpiConfirmed = false;
                    }
                    
                    if (msg) {
                        alert(msg);
                    }
                });
            });
            
            $('#wpinv-remove-code').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $form = $(this).closest('form[name="post"]');
                var invoice_id = parseInt($form.find('input#post_ID').val());
                var discount_code = $('#wpinv_discount', $form).val();
                if (!invoice_id > 0) {
                    return false;
                }
                
                if (!invoice_id > 0 || !parseInt($(document.body).find('.wpinv-line-items > .item').length) > 0 || !discount_code) {
                    $this.removeClass('wpi-inlineb').addClass('wpi-hide');
                    $('#wpinv_discount', $form).attr('readonly', false).val('');
                    $('#wpinv-apply-code', $form).removeClass('wpi-hide').addClass('wpi-inlineb');
                    return false;
                }
                
                $this.attr('disabled', true);
                $this.after('<span class="wpi-refresh">&nbsp;&nbsp;<i class="fa fa-spin fa-refresh"></i></span>');

                var data = {
                    action: 'wpinv_admin_remove_discount',
                    invoice_id: invoice_id,
                    code: discount_code,
                    _nonce: WPInv_Admin.wpinv_nonce
                };
                
                $.post(WPInv_Admin.ajax_url, data, function(response) {
                    var msg, success;
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            success = true;
                            
                            $this.removeClass('wpi-inlineb').addClass('wpi-hide');
                            $('#wpinv_discount', $form).attr('readonly', false).val('');
                            $('#wpinv-apply-code', $form).removeClass('wpi-hide').addClass('wpi-inlineb');
                        }
                        
                        if (response.msg) {
                            msg = response.msg;
                        }
                    }
                    
                    $this.attr('disabled', false);
                    $this.closest('div').find('.wpi-refresh').remove();
                    
                    if (success) {
                        window.wpiConfirmed = true;
                        $('#wpinv-recalc-totals').click();
                        window.wpiConfirmed = false;
                    }
                    
                    if (msg) {
                        alert(msg);
                    }
                });
            });
        },
        remove_item: function() {
            // Remove a remove from a purchase
            $('#wpinv_items').on('click', '.wpinv-item-remove', function(e) {
                var item = $(this).closest('.item');
                var count = $(document.body).find('.wpinv-line-items > .item').length;
                var qty = parseInt($('.qty', item).data('quantity'));
                qty = qty > 0 ? qty : 1;
                if (count === 1 && qty == 1) {
                    //alert(WPInv_Admin.OneItemMin);
                    //return false;
                }
                if (confirm(WPInv_Admin.DeleteInvoiceItem)) {
                    e.preventDefault();
                    var metaBox = $('#wpinv_items_wrap');
                    var gdTotals = $('.wpinv-totals', metaBox);
                    var item_id = item.data('item-id');
                    var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                    var index = $(item).index();
                    if (!(item_id > 0 && invoice_id > 0)) {
                        return false;
                    }
                    wpinvBlock(metaBox);
                    var data = {
                        action: 'wpinv_remove_invoice_item',
                        invoice_id: invoice_id,
                        item_id: item_id,
                        index: index,
                        _nonce: WPInv_Admin.invoice_item_nonce
                    };
                    $.post(WPInv_Admin.ajax_url, data, function(response) {
                        item.remove();
                        wpinvUnblock(metaBox);
                        if (response && typeof response == 'object') {
                            if (response.success === true) {
                                WPInv.update_inline_items(response.data, metaBox, gdTotals);
                            } else if (response.msg) {
                                alert(response.msg);
                            }
                        }
                    });
                }
            });
        },
        add_item: function() {
            // Add a New Item from the Add Items to Items Box
            $('.wpinv-actions').on('click', '#wpinv-add-item', function(e) {
                e.preventDefault();
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $('.wpinv-totals', metaBox);
                var item_id = $('#wpinv_invoice_item').val();
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                if (!(item_id > 0 && invoice_id > 0)) {
                    return false;
                }
                wpinvBlock(metaBox);
                var data = {
                    action: 'wpinv_add_invoice_item',
                    invoice_id: invoice_id,
                    item_id: item_id,
                    _nonce: WPInv_Admin.invoice_item_nonce
                };
                var user_id, country, state;
                if (user_id = $('[name="post_author_override"]').val()) {
                    data.user_id = user_id;
                }
                if (parseInt($('#wpinv_new_user').val()) == 1) {
                    data.new_user = true;
                }
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }
                $.post(WPInv_Admin.ajax_url, data, function(response) {
                    wpinvUnblock(metaBox);
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            WPInv.update_inline_items(response.data, metaBox, gdTotals);
                        } else if (response.msg) {
                            alert(response.msg);
                        }
                    }
                });
            });
            $('.wpinv-actions').on('click', '#wpinv-new-item', function(e) {
                e.preventDefault();
                var $quickAdd = $('#wpinv-quick-add');
                if ($quickAdd.is(':visible')) {
                    $quickAdd.slideUp('fast');
                } else {
                    $quickAdd.slideDown('fast');
                }
                return false;
            });
            $('#wpinv-quick-add').on('click', '#wpinv-cancel-item', function(e) {
                e.preventDefault();
                var $quickAdd = $('#wpinv-quick-add');
                if ($quickAdd.is(':visible')) {
                    $quickAdd.slideUp('fast');
                } else {
                    $quickAdd.slideDown('fast');
                }
                $('[name="_wpinv_quick[name]"]', $quickAdd).val('');
                $('#_wpinv_quickexcerpt', $quickAdd).val('');
                $('[name="_wpinv_quick[price]"]', $quickAdd).val('');
                $('[name="_wpinv_quick[qty]"]', $quickAdd).val(1);
                $('[name="_wpinv_quick[type]"]', $quickAdd).prop('selectedIndex',0);
                return false;
            });
            $('#wpinv-quick-add').on('click', '#wpinv-save-item', function(e) {
                e.preventDefault();
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $('.wpinv-totals', metaBox);
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                var item_title = $('[name="_wpinv_quick[name]"]', metaBox).val();
                var item_price = $('[name="_wpinv_quick[price]"]', metaBox).val();
                if (!(invoice_id > 0)) {
                    return false;
                }
                if (!item_title) {
                    $('[name="_wpinv_quick[name]"]', metaBox).focus();
                    return false;
                }
                if (item_price === '') {
                    $('[name="_wpinv_quick[price]"]', metaBox).focus();
                    return false;
                }
                wpinvBlock(metaBox);
                var data = {
                    action: 'wpinv_create_invoice_item',
                    invoice_id: invoice_id,
                    _nonce: WPInv_Admin.invoice_item_nonce
                };
                var fields = $('[name^="_wpinv_quick["]');
                for (var i in fields) {
                    data[fields[i]['name']] = fields[i]['value'];
                }
                var user_id, country, state;
                if (user_id = $('[name="post_author_override"]').val()) {
                    data.user_id = user_id;
                }
                if (parseInt($('#wpinv_new_user').val()) == 1) {
                    data.new_user = true;
                }
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }
                $.post(WPInv_Admin.ajax_url, data, function(response) {
                    wpinvUnblock(metaBox);
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            $('[name="_wpinv_quick[name]"]', metaBox).val('');
                            $('[name="_wpinv_quick[price]"]', metaBox).val('');
                            $('#_wpinv_quickexcerpt', metaBox).val('');
                            WPInv.update_inline_items(response.data, metaBox, gdTotals);
                            $('#wpinv-quick-add').slideUp('slow');
                        } else if (response.msg) {
                            alert(response.msg);
                        }
                    }
                });
            });
        },
        recalculateTotals: function() {
            $('.wpinv-actions').on('click', '#wpinv-recalc-totals', function(e) {
                e.preventDefault();
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $('.wpinv-totals', metaBox);
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                var disable_taxes = 0

                if ( $('#wpinv_taxable:checked').length > 0 ) {
                    disable_taxes = 1
                }

                if (!invoice_id > 0) {
                    return false;
                }
                if (!parseInt($(document.body).find('.wpinv-line-items > .item').length) > 0) {
                    if (!window.wpiConfirmed) {
                        alert(WPInv_Admin.emptyInvoice);
                        $('#wpinv_invoice_item').focus();
                    }
                    return false;
                }
                if (!window.wpiConfirmed && !window.confirm(WPInv_Admin.confirmCalcTotals)) {
                    return false;
                }
                wpinvBlock(metaBox);
                var data = {
                    action: 'wpinv_admin_recalculate_totals',
                    invoice_id: invoice_id,
                    _nonce: WPInv_Admin.wpinv_nonce,
                    disable_taxes: disable_taxes,
                };
                var user_id, country, state;
                if (user_id = $('[name="post_author_override"]').val()) {
                    data.user_id = user_id;
                }
                if (parseInt($('#wpinv_new_user').val()) == 1) {
                    data.new_user = true;
                }
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }
                $.post(WPInv_Admin.ajax_url, data, function(response) {
                    wpinvUnblock(metaBox);
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            WPInv.update_inline_items(response.data, metaBox, gdTotals);
                        }
                    }
                });
            });
        },
        update_inline_items: function(data, metaBox, gdTotals) {
            if (data.discount > 0) {
                data.discountf = '&ndash;' + data.discountf;
            }
            $('.wpinv-line-items', metaBox).html(data.items);
            $('.subtotal .total', gdTotals).html(data.subtotalf);
            $('.tax .total', gdTotals).html(data.taxf);
            $('.discount .total', gdTotals).html(data.discountf);
            $('.total .total', gdTotals).html(data.totalf);
            $('#wpinv-details input[name="wpinv_discount"]').val(data.discount);
            $('#wpinv-details input[name="wpinv_tax"]').val(data.tax);
        },

        setup_tools: function() {
            $('#wpinv_tools_table').on('click', '.wpinv-tool', function(e) {
                var $this = $(this);
                e.preventDefault();
                var mBox = $this.closest('tr');
                if (!confirm(WPInv_Admin.AreYouSure)) {
                    return false;
                }
                var tool = $this.data('tool');
                $(this).prop('disabled', true);
                if (!tool) {
                    return false;
                }
                $('.wpinv-run-' + tool).remove();
                if (!mBox.hasClass('wpinv-tool-' + tool)) {
                    mBox.addClass('wpinv-tool-' + tool);
                }
                mBox.addClass('wpinv-tool-active');
                mBox.after('<tr class="wpinv-tool-loader wpinv-run-' + tool + '"><td colspan="3"><span class="wpinv-i-loader"><i class="fa fa-spin fa-refresh"></i></span></td></tr>');
                var data = {
                    action: 'wpinv_run_tool',
                    tool: tool,
                    _nonce: WPInv_Admin.wpinv_nonce
                };
                $.post(WPInv_Admin.ajax_url, data, function(res) {
                    mBox.removeClass('wpinv-tool-active');
                    $this.prop('disabled', false);
                    var msg = prem = '';
                    if (res && typeof res == 'object') {
                        msg = res.data ? res.data.message : '';
                        if (res.success === false) {
                            prem = '<span class="wpinv-i-check wpinv-i-error"><i class="fa fa-exclamation-circle"></i></span>';
                        } else {
                            prem = '<span class="wpinv-i-check"><i class="fa fa-check-circle"></i></span>';
                        }
                    }
                    if (msg) {
                        $('.wpinv-run-' + tool).addClass('wpinv-tool-done').find('td').html(prem + msg + '<span class="wpinv-i-close"><i class="fa fa-close"></i></span>');
                    } else {
                        $('.wpinv-run-' + tool).remove();
                    }
                });
            });
            $('#wpinv_tools_table').on('click', '.wpinv-i-close', function(e) {
                $(this).closest('tr').fadeOut();
            });
        }
    };
    $('.post-type-wpi_invoice form#post #titlediv [name="post_title"]').attr('readonly', true);
    $('.post-type-wpi_item.wpi-editable-n form#post').attr('action', 'javascript:void(0)');
    $('.post-type-wpi_item.wpi-editable-n #submitdiv #publishing-action').remove();
    $('.post-type-wpi_item.wpi-editable-n #submitdiv #misc-publishing-actions a.edit-post-status').remove();
    $('.post-type-wpi_item .posts .wpi-editable-n').each(function(e) {
        $('.check-column [type="checkbox"]', $(this)).attr('disabled', true);
    });
    WPInv.init();
    var WPInv_Export = {
        init: function() {
            this.submit();
            this.clearMessage();
        },
        submit: function() {
            var $this = this;
            $('.wpi-export-form').submit(function(e) {
                e.preventDefault();
                var $form = $(this);
                var submitBtn = $form.find('input[type="submit"]');
                if (!submitBtn.attr('disabled')) {
                    var data = $form.serialize();
                    submitBtn.attr('disabled', true);
                    $form.find('.wpi-msg-wrap').remove();
                    $form.append('<div class="wpi-msg-wrap"><div class="wpi-progress"><div></div><span>0%</span></div><span class="wpi-export-loader"><i class="fa fa-spin fa-spinner"></i></span></div>');
                    // start the process
                    $this.step(1, data, $form, $this);
                }
            });
        },
        step: function(step, data, $form, $this) {
            var message = $form.find('.wpi-msg-wrap');
            var post_data = {
                action: 'wpinv_ajax_export',
                step: step,
                data: data,
            };
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                cache: false,
                dataType: 'json',
                data: post_data,
                beforeSend: function(jqXHR, settings) {},
                success: function(res) {
                    if (res && typeof res == 'object') {
                        if (res.success) {
                            if ('done' == res.data.step || res.data.done >= 100) {
                                $form.find('input[type="submit"]').removeAttr('disabled');
                                $('.wpi-progress > span').text(parseInt(res.data.done) + '%');
                                $('.wpi-progress div').animate({
                                    width: res.data.done + '%'
                                }, 100, function() {});
                                if (res.msg) {
                                    message.html('<div id="wpi-export-success" class="updated notice is-dismissible"><p>' + msg + '<span class="notice-dismiss"></span></p></div>');
                                }
                                if (res.data.file && res.data.file.u) {
                                    message.append('<span class="wpi-export-file"><a href="' + res.data.file.u + '" target="_blank"><i class="fa fa-download"></i> ' + res.data.file.u + '</a><span> - ' + res.data.file.s + '<span><span>');
                                }
                                message.find('.wpi-export-loader').html('<i class="fa fa-check-circle"></i>');
                            } else {
                                var next = parseInt(res.data.step) > 0 ? parseInt(res.data.step) : 1;
                                $('.wpi-progress > span').text(parseInt(res.data.done) + '%');
                                $('.wpi-progress div').animate({
                                    width: res.data.done + '%'
                                }, 100, function() {});
                                $this.step(parseInt(next), data, $form, $this);
                            }
                        } else {
                            $form.find('input[type="submit"]').removeAttr('disabled');
                            if (res.msg) {
                                message.html('<div class="updated error"><p>' + res.msg + '</p></div>');
                            }
                        }
                    } else {
                        $form.find('input[type="submit"]').removeAttr('disabled');
                        message.html('<div class="updated error">' + res + '</div>');
                    }
                }
            }).fail(function(res) {
                if (window.console && window.console.log) {
                    console.log(res);
                }
            });
        },
        clearMessage: function() {
            $('body').on('click', '#wpi-export-success .notice-dismiss', function() {
                $(this).closest('#wpi-export-success').parent().slideUp('fast');
            });
        }
    };
    WPInv_Export.init();

});

/**
 * Blocks an HTML element to prevent interaction.
 * 
 * @param {String} el The element to block
 * @param {String} message an optional message to display alongside the spinner
 */
function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? message : '';
    el.block({
        message: '<i class="fa fa-spinner fa-pulse fa-2x"></i>' + message,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        }
    });
}

/**
 * Un-locks an HTML element to allow interaction.
 * 
 * @param {String} el The element to unblock
 */
function wpinvUnblock(el) {
    el.unblock();
}