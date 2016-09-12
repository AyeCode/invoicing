jQuery(function($) {
    //'use strict';
    
    var wpiGlobalTax = WPInv_Admin.tax != 0 ? WPInv_Admin.tax : 0;
    var wpiGlobalDiscount = WPInv_Admin.discount != 0 ? WPInv_Admin.discount : 0;
    var wpiSymbol = WPInv_Admin.currency_symbol;
    var wpiPosition = WPInv_Admin.currency_pos;
    var wpiThousandSep = WPInv_Admin.thousand_sep;
    var wpiDecimalSep = WPInv_Admin.decimal_sep;
    var wpiDecimals = WPInv_Admin.decimals;
    
    $('input.wpi-price').on("contextmenu",function(e) {
        return false;
    });
    $(document).on('keypress', 'input.wpi-price', function(e) {
        var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
        
        if ($.inArray(e.key, ["0","1","2","3","4","5","6","7","8","9",",","."]) !== -1) {
           return true;
        } else if (e.ctrlKey || e.shiftKey) {
           return false;
        } else if ($.inArray(key, [8,35,36,37,39,46]) !== -1) {
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
     * calculate the totals on the fly when editing or adding a quote or invoice
     */
    function wpinvInvoiceTotals() {
        // work out the line total
        var sum = $.map($('.wpinv input.item_amount'), function(item) {
            var group = $(item).parents('.gdmbx-repeatable-grouping');
            var index = group.data('iterator');
            var amount = wpinvRawNumber(item.value);
            // work out the line totals and taxes/discounts
            var item_sub_total = amount; // 100 
            var item_total = item_sub_total; // 110
            // display 0 instead of NaN
            
            if (isNaN(item_total)) {
                item_total = 0;
            }
            
            // display the calculated amount
            $(item).parents('.gdmbx-type-text-money').find('.item_total').html(wpinvFormatAmount(item_total));
            
            return parseFloat(item_total);
        }).reduce(function(a, b) {
            return a + b;
        }, 0);
        
        wpinvRefreshFeesTotal();
        
        // display 0 instead of NaN
        if (isNaN(sum)) {
            sum = 0;
        }
                
        if (typeof $('#wpinv_discount').val() !== 'undefined') {
            wpiGlobalDiscount = wpinvRawNumber($('#wpinv_discount').val());
        }
        
        if (typeof $('#wpinv_tax').val() !== 'undefined') {
            wpiGlobalTax = wpinvRawNumber($('#wpinv_tax').val());
        }
        
        wpinvGlobalFee = 0;
        $('.wpinv-fees .wpinv-fee-amount input[type="text"]').each(function() { 
            wpinvGlobalFee += wpinvRawNumber($(this).val());
        });
        
        if ( !sum > 0 ) {
            wpiGlobalTax = 0;
            wpiGlobalDiscount = 0;
            wpinvGlobalFee = 0;
        }
        
        // add global tax if any
        if (wpiGlobalTax > 0) {
            var raw_tax = wpiGlobalTax;
            var raw_total = sum + raw_tax;
        } else {
            var raw_tax = 0;
            var raw_total = sum;
        }
        
        if (wpinvGlobalFee > 0) {
            raw_total = raw_total + wpinvGlobalFee;
        }
        
        // add global tax if any
        if (wpiGlobalDiscount > 0) {
            var raw_discount = wpiGlobalDiscount;
            raw_total = raw_total - raw_discount;
        } else {
            var raw_discount = 0;
        }
        
        raw_total = Math.max(raw_total, 0);
        
        $("#wpinv-items #wpinv_sub_total").html(wpinvFormatAmount(sum));
        $("#wpinv-items #wpinv_tax").html(wpinvFormatAmount(raw_tax));
        $("#wpinv-items #wpinv_discount").html(wpinvFormatAmount(raw_discount));
        $("#wpinv-items #wpinv_total").html(wpinvFormatAmount(raw_total));
        $("input#wpinv_totals_for_ordering").val(wpinvFormatAmount(raw_total));
    };

    function wpinvClearItemTotal() {
        var lastRow = $(this).closest('.gdmbx-row').prev();
        var index = $(lastRow).data('iterator');
        
        $(lastRow).find('.item_total').html(wpinvFormatAmount('0'));
    };
    
    $(document).on('click', '.gdmbx-add-group-row', wpinvClearItemTotal);
    $(document).on('click', '.gdmbx-remove-group-row', wpinvInvoiceTotals);
    
    //$(document).on('keyup change', '.wpinv input.item_amount, #wpinv_discount, #wpinv_tax', function() {
    $(document).on('keyup change', '.wpinv input.item_amount, #wpinv_discount, .wpinv-fee-name, .wpinv-fee-amount', function() {
        wpinvInvoiceTotals();
    });
    
    /**
     * on page load
     */
    $(function() {
        wpinvInvoiceTotals();
    });
    
    /**
     * Invoice Notes Panel
     */
    var wpinv_meta_boxes_notes = {
        init: function() {
            $('#wpinv-notes')
                .on('click', 'a.add_note', this.add_invoice_note)
                .on('click', 'a.delete_note', this.delete_invoice_note);
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
                $('ul.invoice_notes').prepend(response);
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
    var invDetails = jQuery('#gdmbx2-metabox-wpinv_details').html();
    if (invDetails) {
        jQuery('#submitpost', jQuery('.wpinv')).detach().appendTo(jQuery('#wpinv-details'));
        jQuery('#submitdiv', jQuery('.wpinv')).hide();
        jQuery('#major-publishing-actions', '#wpinv-details').find('input[type=submit]').attr('name', 'save_invoice').val(WPInv_Admin.save_invoice);
        
        if (jQuery('#hidden_post_status', '#wpinv-details').val() == 'draft') {
            jQuery('#hidden_post_status', '#wpinv-details').val('pending');
        }
        
        if (jQuery('#post_status', '#wpinv-details').val() == 'draft') {
            jQuery('#post_status', '#wpinv-details').val('pending');
        }
    }
    
    var invBilling = jQuery('#wpinv-address.postbox').html();
    if (invBilling) {
        jQuery('#post_author_override', '#authordiv').addClass('gdmbx2-text-medium').detach().prependTo(jQuery('.gdmbx-customer-div'));
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
        
        var pending_count = jQuery('.pending', wpi_stat_links).find('.count').text();
        jQuery('.pending', wpi_stat_links).find('a').html(WPInv_Admin.status_pending + ' <span class="count">' + pending_count + '</span>');
    }
    
    // Update tax rate state field based on selected rate country
    $( document.body ).on('change', '#wpinv_tax_rates select.wpinv-tax-country', function() {
        var $this = $(this);
        data = {
            action: 'wpinv_get_states_field',
            country: $(this).val(),
            field_name: $this.attr('name').replace('country', 'state')
        };
        $.post(ajaxurl, data, function (response) {
            if( 'nostates' == response ) {
                var text_field = '<input type="text" name="' + data.field_name + '" value=""/>';
                $this.parent().next().find('select').replaceWith( text_field );
            } else {
                $this.parent().next().find('input,select').show();
                $this.parent().next().find('input,select').replaceWith( response );
            }
        });

        return false;
    });

    // Insert new tax rate row
    $('#wpinv_add_tax_rate').on('click', function() {
        var row = $('#wpinv_tax_rates tbody tr:last');
        var clone = row.clone();
        var count = row.parent().find( 'tr' ).length;
        clone.find( 'td input' ).not(':input[type=checkbox]').val( '' );
        clone.find( 'td [type="checkbox"]' ).attr('checked', false);
        clone.find( 'input, select' ).each(function() {
            var name = $( this ).attr( 'name' );
            name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
            $( this ).attr( 'name', name ).attr( 'id', name );
        });
        clone.find( 'label' ).each(function() {
            var name = $( this ).attr( 'for' );
            name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
            $( this ).attr( 'for', name );
        });
        clone.insertAfter( row );
        return false;
    });
    
    // Remove tax row
    $( document.body ).on('click', '#wpinv_tax_rates .wpinv_remove_tax_rate', function() {
        var tax_rates = $('#wpinv_tax_rates tbody tr:visible');
        var count     = tax_rates.length;

        if( count === 1 ) {
            $('#wpinv_tax_rates select').val('');
            $('#wpinv_tax_rates input[type="text"]').val('');
            $('#wpinv_tax_rates input[type="number"]').val('');
            $('#wpinv_tax_rates input[type="checkbox"]').attr('checked', false);
        } else {
            $(this).closest('tr').remove();
        }

        /* re-index after deleting */
        $('#wpinv_tax_rates tbody tr').each( function( rowIndex ) {
            $(this).children().find( 'input, select' ).each(function() {
                var name = $( this ).attr( 'name' );
                name = name.replace( /\[(\d+)\]/, '[' + ( rowIndex - 1 ) + ']');
                $( this ).attr( 'name', name ).attr( 'id', name );
            });
        });
        return false;
    });
    
    var elB = $('#wpinv-address');
    $('#wpinv_country', elB).change(function(e){
        var $this = $(this);
        data = {
            action: 'wpinv_get_states_field',
            country: $(this).val(),
            field_name: 'wpinv_state',
        };
        
        $this.after('&nbsp;<i class="fa fa-refresh fa-spin"></i>');
        $('#wpinv_state', elB).css({'opacity':'.5'});
        
        $.post(ajaxurl, data, function (response) {
            var selected = typeof $this.data('state') !== 'undefined' ? $this.data('state') : "";
            
            if( 'nostates' === response ) {
                var text_field = '<input type="text" required="required" value="' + selected + '" id="wpinv_state" name="wpinv_state" />';
                $('#wpinv_state', elB).replaceWith( text_field );
            } else {
                $('#wpinv_state', elB).replaceWith( response );
                $('#wpinv_state', elB).find('option[value="' + selected + '"]').attr('selected', 'selected');
                $('#wpinv_state', elB).find('option[value=""]').remove();
            }
            
            $('#wpinv_state', elB).addClass('gdmbx2-text-large');
            
            if (typeof $this.data('change') === '1') {
                $('#wpinv_state', elB).change();
            }
            
            $this.next('.fa-refresh').remove();
            $('#wpinv_state', elB).css({'opacity':'1'});
        });

        return false;        
    });
    
    
    $('#wpinv-fill-user-details').click(function(e){
        var metaBox = $(this).closest('.inside');
        var user_id = $('select[name="post_author_override"]', metaBox).val();
        if ( !user_id ) {
            return false;
        }
        
        if ( window.confirm( WPInv_Admin.FillBillingDetails ) ) {
            var data = {
                action: 'wpinv_get_billing_details',
                user_id: user_id,
                _nonce: WPInv_Admin.billing_details_nonce
            };

            wpinvBlock(metaBox);

            $.post( WPInv_Admin.ajax_url, data, function( response ) {
                var elCountry = $( '#wpinv_country', metaBox);
                elCountry.removeAttr('data-state').removeAttr('data-change');
                
                if (response && typeof response == 'object') {
                    if (response.success === true && typeof response.data.billing_details == 'object') {
                        var state = false;
                        var country = false;
                        $.each( response.data.billing_details, function( key, value ) {
                            if (key == 'state') {
                                state = value;
                            } else if (key == 'country') {
                                country = value;
                            } else {
                                $( '#wpinv_' + key, metaBox).val(value).change();
                            }
                        });
                        
                        if (country !== false) {
                            if (state !== false) {
                                elCountry.data('state', state).data('change', '1');
                            }
                            elCountry.val(country).change();
                        }
                    }
                }
                wpinvUnblock(metaBox);
            });
        }
    });
    
    $(document).on('click', '#wpinv-add-fee', function(e) {
        $('.wpinv_fees .wpinv-fees').append($('#wpinv-fee-format').html());
        
        wpinvRefreshFees();
    });
    
    $(document).on('click', '.wpinv-fees .wpinv-fee .wpinv-fee-remove', function(e) {
        jQuery(this).closest('tr').remove();
        
        wpinvRefreshFees();
    });
    
    function wpinvRefreshFees() {        
        var i = 0;
        jQuery('.wpinv-fees .wpinv-fee').each(function() {
            jQuery(this).find('.wpinv-fee-name').attr("name", "invoice_fees[" + i + "][label]").attr("id", "wpinv_fee_name_" + i);
            jQuery(this).find('.wpinv-fee-amount').attr("name", "invoice_fees[" + i + "][amount]").attr("id", "wpinv_fee_amount_" + i);
            i++;
        });
        
        wpinvInvoiceTotals();
    }
    
    function wpinvRefreshFeesTotal() {
        jQuery("#wpinv-items .wpinv_totals .fee").remove();
        
        var fees = '';
        jQuery('.wpinv-fees .wpinv-fee').each(function() { 
            var label = jQuery(this).find('.wpinv-fee-name').val();
            if (label && label !== '' && label.trim()) {
                var fee = wpinvRawNumber(jQuery(this).find('.wpinv-fee-amount').val());
                fee = wpinvFormatAmount(Math.max(fee, 0));
                fees += '<div class="fee">' + label + '<span class="alignright"><span id="wpinv_fee">' + fee + '</span></span></div>';
            }
        });
        
        jQuery("#wpinv-items .wpinv_totals .tax").after(fees);
    }
    
    var WPInv = {
        init : function() {
            this.preSetup();
            this.prices();
            this.remove_item();
            this.add_item();
            this.recalculateTotals();
        },
        preSetup : function() {
            var wpinvColorPicker = $('.wpinv-color-picker');

            if ( wpinvColorPicker.length ) {
                wpinvColorPicker.wpColorPicker();
            }
            
            var no_states = $('select.wpinv-no-states');
            if( no_states.length ) {
                no_states.closest('tr').hide();
            }

            // Update base state field based on selected base country
            $('select[name="wpinv_settings[default_country]"]').change(function() {
                var $this = $(this), $tr = $this.closest('tr');
                
                data = {
                    action: 'wpinv_get_states_field',
                    country: $(this).val(),
                    field_name: 'wpinv_settings[default_state]'
                };
                
                $.post(ajaxurl, data, function (response) {
                    if( 'nostates' == response ) {
                        $tr.next().hide();
                    } else {
                        $tr.next().show();
                        $tr.next().find('select').replaceWith( response );
                    }
                });
                
                return false;
            });
            
            $('#wpinv-address').on('click', '.wpinv-new-user', function(e) {
                e.preventDefault();
                
                var val, bL, iL, mBox = $('#wpinv-address');
                
                if ($('#wpinv_new_user', mBox).val()) {
                    val = '';
                    bL = $(this).data('blabel-new');
                    iL = $(this).data('ilabel-new');
                    $('.gdmbx-wpinv-email .gdmbx-th label', mBox).show();
                    $('#wpinv_email', mBox).detach().appendTo($('.gdmbx-wpinv-email .gdmbx-td'));
                    $('#post_author_override', mBox).show();
                    $('#wpinv-fill-user-details', mBox).show();
                } else {
                    val = '1';
                    bL = $(this).data('blabel-cancel');
                    iL = $(this).data('ilabel-cancel');
                    $('#post_author_override', mBox).hide();
                    $('#wpinv-fill-user-details', mBox).hide();
                    $('.gdmbx-wpinv-email .gdmbx-th label', mBox).hide();
                    $('#wpinv_email', mBox).detach().appendTo($('.gdmbx-customer-div'));
                }
                
                $(this).text(bL);
                $('#wpinv_new_user', mBox).val(val);
                $('[data-ilabel="user"]', mBox).text(iL);
            });
            
            $('.gdmbx-customer-div #wpinv_email').live('change', function(e) {
                var metaBox = $(this).closest('.inside');
                wpinvBlock(metaBox);
                
                var data = {
                    action: 'wpinv_check_email',
                    email: $(this).val(),
                    _nonce: WPInv_Admin.wpinv_nonce
                };
                
                $.post( WPInv_Admin.ajax_url, data, function(response) {
                    var elCountry = $( '#wpinv_country', metaBox);
                    elCountry.removeAttr('data-state').removeAttr('data-change');
                    
                    if (response && typeof response == 'object') {
                        if (response.success === true && typeof response.data.billing_details == 'object') {
                            if (!$('#post_author_override [value="' + response.data.id + '"]', metaBox).val()) {
                                $('#post_author_override', metaBox).prepend('<option value="' + response.data.id + '">' + response.data.name + ' (' + response.data.login + ')</option>');
                            }
                            $('#post_author_override', metaBox).val(response.data.id);
                            $('.wpinv-new-user', metaBox).click();
                            
                            var state = false;
                            var country = false;
                            $.each( response.data.billing_details, function( key, value ) {
                                if (key == 'state') {
                                    state = value;
                                } else if (key == 'country') {
                                    country = value;
                                } else {
                                    $( '#wpinv_' + key, metaBox).val(value).change();
                                }
                            });
                            
                            if (country !== false) {
                                if (state !== false) {
                                    elCountry.data('state', state).data('change', '1');
                                }
                                elCountry.val(country).change();
                            }
                        }
                    }
                    wpinvUnblock(metaBox);
                });
            });
        },
        remove_item : function() {
            // Remove a remove from a purchase
            $('#wpinv_items').on('click', '.wpinv-item-remove', function(e) {
                var item = $(this).closest('.item');
                var count = $(document.body).find( '.wpinv-line-items > .item' ).length;

                if ( count === 1 ) {
                    alert( WPInv_Admin.OneItemMin );
                    return false;
                }

                if ( confirm( WPInv_Admin.DeleteInvoiceItem ) ) {
                    e.preventDefault();
                
                    var metaBox = $('#wpinv_items_wrap');
                    var gdTotals = $( '.wpinv-totals', metaBox );
                    var item_id = item.data('item-id');
                    var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                    var index = $(item).index();
                    
                    if( !( item_id > 0 && invoice_id > 0 ) ) {
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

                    $.post( WPInv_Admin.ajax_url, data, function( response ) {
                        item.remove();
                        wpinvUnblock(metaBox);
                        
                        if (response && typeof response == 'object') {
                            if (response.success === true) {
                                WPInv.update_inline_items(response.data, metaBox, gdTotals);
                            }
                        }
                    });
                }
            });

        },
        add_item : function() {
            // Add a New Item from the Add Items to Items Box
            $('.wpinv-actions').on('click', '#wpinv-add-item', function(e) {
                e.preventDefault();
                
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $( '.wpinv-totals', metaBox );
                var item_id = $( '#wpinv_invoice_item' ).val();
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                
                if( !( item_id > 0 && invoice_id > 0 ) ) {
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
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }

                $.post( WPInv_Admin.ajax_url, data, function( response ) {
                    wpinvUnblock(metaBox);
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            WPInv.update_inline_items(response.data, metaBox, gdTotals);
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
                return false;
            });
            
            $('#wpinv-quick-add').on('click', '#wpinv-save-item', function(e) {
                e.preventDefault();
                
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $( '.wpinv-totals', metaBox );
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                var item_title = $('[name="_wpinv_quick[name]"]', metaBox).val();
                var item_price = $('[name="_wpinv_quick[price]"]', metaBox).val();
                
                if ( !( invoice_id > 0 ) ) {
                    return false;
                }
                if ( !item_title ) {
                    $('[name="_wpinv_quick[name]"]', metaBox).focus();
                    return false;
                }
                if ( item_price === '' ) {
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
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }

                $.post( WPInv_Admin.ajax_url, data, function( response ) {
                    wpinvUnblock(metaBox);
                    if (response && typeof response == 'object') {
                        if (response.success === true) {
                            $('[name="_wpinv_quick[name]"]', metaBox).val('');
                            $('[name="_wpinv_quick[price]"]', metaBox).val('');
                            WPInv.update_inline_items(response.data, metaBox, gdTotals);
                        }
                    }
                });
            });
        },
        recalculateTotals : function() {
            $('.wpinv-actions').on('click', '#wpinv-recalc-totals', function(e) {
                e.preventDefault();
                
                var metaBox = $('#wpinv_items_wrap');
                var gdTotals = $( '.wpinv-totals', metaBox );
                var invoice_id = metaBox.closest('form[name="post"]').find('input#post_ID').val();
                
                if ( !invoice_id > 0 ) {
                    return false;
                }
                
                if ( !window.confirm( WPInv_Admin.confirmCalcTotals ) ) {
                    return false;
                }
                
                wpinvBlock(metaBox);
                
                var data = {
                    action: 'wpinv_admin_recalculate_totals',
                    invoice_id: invoice_id,
                    _nonce: WPInv_Admin.wpinv_nonce
                };
                
                var user_id, country, state;
                if (user_id = $('[name="post_author_override"]').val()) {
                    data.user_id = user_id;
                }
                if (country = $('#wpinv-address [name="wpinv_country"]').val()) {
                    data.country = country;
                }
                if (state = $('#wpinv-address [name="wpinv_state"]').val()) {
                    data.state = state;
                }

                $.post( WPInv_Admin.ajax_url, data, function( response ) {
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
            $( '.wpinv-line-items', metaBox ).html( data.items );
            $( '.subtotal .total', gdTotals ).html( data.subtotalf );
            $( '.tax .total', gdTotals ).html( data.taxf );
            $( '.discount .total', gdTotals ).html( data.discountf );
            $( '.total .total', gdTotals ).html( data.totalf );
            $('#wpinv-details input[name="wpinv_discount"]').val( data.discount );
            $('#wpinv-details input[name="wpinv_tax"]').val( data.tax );
        },
        prices: function() {
            var $this = this;
            
            $this.check_recurring('#wpinv_is_recurring');
            
            $(document.body).on('change', '#wpinv_is_recurring', function(e) {
                $this.check_recurring(this);
            });
            
            $(document.body).on('change', '#wpinv_recurring_period', function(e) {
                $this.recurring_period($(this).val());
            });
        },
        check_recurring: function (el) {
            var $obj = $('.wpinv-row-recurring-fields');
            
            this.recurring_period($('#wpinv_recurring_period').val());
            
            if ($(el).is(':checked')) {
                $obj.removeClass('wpinv-recurring-n').addClass('wpinv-recurring-y');
                $('input', $obj).prop('disabled', false);
                $('select', $obj).prop('disabled', false);
            } else {
                $obj.removeClass('wpinv-recurring-y').addClass('wpinv-recurring-n');
                $('input', $obj).prop('disabled', true);
                $('select', $obj).prop('disabled', true);
            }
        },
        recurring_period: function (val) {
            var txt = '';
            if (typeof val != 'undefined') {
                txt = $('#wpinv_recurring_period').find('option[value="' + val + '"]').data('text');
                txt = txt !== 'undefined' ? txt : '';
            }
            
            $('#wpinv_interval_text').text(txt);
            
            this.recurring_interval(val);
        },
        recurring_interval: function (period) {
            var limit;
            
            switch (period) {
                case 'W':
                    limit = 52;
                break;
                case 'M':
                    limit = 24;
                break;
                case 'Y':
                    limit = 5;
                break;
                default:
                case 'D':
                    limit = 90;
                break;
            }
            
            var optioins = '';
            for ( i = 1; i <= limit; i++ ) {
                optioins += '<option value="' + i + '">' + i + '</option>';
            }
            
            var $el = $('#wpinv_interval');
            
            var val = $el.val();
            $el.find('option').remove();
            $el.append(optioins);
            $el.val(val);
            $el.find('option[value="' + val + '"]').attr('selected', 'selected');
        }
    };
    
    WPInv.init();
});

function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? message : '';
    el.block({
        message: '<i class="fa fa-refresh fa-spin"></i>' + message,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        }
    });
}

function wpinvUnblock(el) {
    el.unblock();
}