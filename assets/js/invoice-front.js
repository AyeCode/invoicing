// make sure ajaxurl is defined
if (typeof ajaxurl === 'undefined' || ajaxurl === null) {
    // variable is undefined or null
    ajaxurl = WPInv.ajax_url;
}
window.wpiSubmit = typeof window.wpiSubmit !== 'undefined' ? window.wpiSubmit : true;
jQuery(function($) {
    var valid = false;
    $('#wpinv_checkout_form').on('submit', function(e) {
        var $form = $(this).closest('#wpinv_checkout_form');
        $('.wpinv_errors').remove();
        if (valid) {
            return true;
        }
        e.preventDefault();
        wpinvBlock($form);
        var data = $form.serialize();
        data = wpinvRemoveQueryVar(data, 'action');
        data = wpinvRemoveQueryVar(data, 'wpinv_ajax');
        $.post(ajaxurl, data + '&action=wpinv_checkout', function(res) {
            if (res && typeof res == 'object' && res.success) {
                valid = true;
                var data = new Object();
                data.form = $form;
                data.totals = res.data;
                jQuery('body').trigger('wpinv_checkout_submit', data);
                if (window.wpiSubmit) {
                    $form.submit();
                }
            } else {
                $form.unblock();
                if (res && res.search("wpinv_adddress_confirm") !== -1) {
                    $('#wpinv_adddress_confirm').show();
                }
                $('#wpinv_purchase_submit', $form).before(res);
            }
        });
        return false;
    });
    var elB = $('#wpinv-fields');
    $('#wpinv_country', elB).change(function(e) {
        $('.wpinv_errors').remove();
        wpinvBlock(jQuery('#wpinv_state_box'));
        var $this = $(this);
        data = {
            action: 'wpinv_get_states_field',
            country: $(this).val(),
            field_name: 'wpinv_state',
        };
        $.post(ajaxurl, data, function(response) {
            if ('nostates' === response) {
                var text_field = '<input type="text" required="required" class="wpi-input required" id="wpinv_state" name="wpinv_state">';
                $('#wpinv_state', elB).replaceWith(text_field);
            } else {
                $('#wpinv_state', elB).replaceWith(response);
                var changeState = function() {
                    console.log('69 : wpinv_recalculate_taxes(' + $(this).val() + ')');
                    wpinv_recalculate_taxes($(this).val());
                };
                $("#wpinv_state").unbind("change", changeState);
                $("#wpinv_state").bind("change", changeState);
            }
            $('#wpinv_state', elB).find('option[value=""]').remove();
            $('#wpinv_state', elB).addClass('form-control wpi-input required');
        }).done(function(data) {
            jQuery('#wpinv_state_box').unblock();
            console.log('78 : wpinv_recalculate_taxes()');
            wpinv_recalculate_taxes();
        });
        return false;
    });
    $('select#wpinv_state', elB).change(function(e) {
        $('.wpinv_errors').remove();
        console.log('86 : wpinv_recalculate_taxes()');
        wpinv_recalculate_taxes($(this).val());
    });
    var WPInv_Checkout = {
        checkout_form: $('form#wpinv_checkout_form'),
        init: function() {
            if (!$(this.checkout_form).length) {
                return;
            }
            // Payment methods
            this.checkout_form.on('click', 'input[name="wpi-gateway"]', this.payment_method_selected);
            this.init_payment_methods();
            //this.recalculate_taxes();
        },
        init_payment_methods: function() {
            var $checkout_form = this.checkout_form;
            var $payment_methods = $('.wpi-payment_methods input[name="wpi-gateway"]');
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
            // Validate and apply a discount
            $checkout_form.on('click', '#wpi-apply-discount', this.applyDiscount);
            // Prevent the checkout form from submitting when hitting enter key in the discount field
            $checkout_form.on('keypress', '#wpinv_discount_code', function(event) {
                if (event.keyCode == '13') {
                    return false;
                }
            });
            // Apply the discount when hitting enter key in the discount field instead
            $checkout_form.on('keyup', '#wpinv_discount_code', function(event) {
                if (event.keyCode == '13') {
                    $('#wpi-apply-discount', $checkout_form).trigger('click');
                }
            });
            // Remove a discount
            $(document.body).on('click', '.wpi-discount-remove', this.removeDiscount);
        },
        payment_method_selected: function() {
            if ($('.wpi-payment_methods input.wpi-pmethod').length > 1) {
                var target_payment_box = $('div.payment_box.' + $(this).attr('ID'));
                if ($(this).is(':checked') && !target_payment_box.is(':visible')) {
                    $('div.payment_box').filter(':visible').slideUp(250);
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
            if ($(this).data('button-text')) {
                $('#wpinv-payment-button').val($(this).data('button-text'));
            } else {
                $('#wpinv-payment-button').val($('#wpinv-payment-button').data('value'));
            }
        },
        applyDiscount: function(e) {
            e.preventDefault();
            var $this = $(this),
                $box = $this.closest('.panel-body'),
                discount_code = $('#wpinv_discount_code', $box).val(),
                $msg = $('.wpinv-discount-msg', $box),
                $msgS = $('.alert-success', $msg),
                $msgF = $('.alert-error', $msg);
            if (discount_code == '') {
                $('#wpinv_discount_code', $box).focus();
                return false;
            }
            var data = {
                action: 'wpinv_apply_discount',
                code: discount_code,
                _nonce: WPInv.nonce
            };
            $('.wpinv_errors').remove();
            $msg.hide();
            $msgS.hide().find('.wpi-msg').html('');
            $msgF.hide().find('.wpi-msg').html('');
            wpinvBlock($box);
            $.ajax({
                type: "POST",
                data: data,
                dataType: "json",
                url: WPInv.ajax_url,
                xhrFields: {
                    withCredentials: true
                },
                success: function(res) {
                    wpinvUnblock($box);
                    var success = false;
                    if (res && typeof res == 'object') {
                        if (res.success) {
                            success = true;
                            jQuery('#wpinv_checkout_cart_form', $this.checkout_form).replaceWith(res.data.html);
                            jQuery('.wpinv-chdeckout-total').text(res.data.total);
                            $('#wpinv_discount_code', $box).val('');
                            //console.log('217 : wpinv_recalculate_taxes()');
                            //wpinv_recalculate_taxes();
                            if (res.data.free) {
                                $('#wpinv_payment_mode_select', $this.checkout_form).hide();
                                gw = 'manual';
                            } else {
                                $('#wpinv_payment_mode_select', $this.checkout_form).show();
                                gw = $('#wpinv_payment_mode_select', $this.checkout_form).attr('data-gateway');
                            }
                            $('.wpi-payment_methods .wpi-pmethod[value="' + gw + '"]', $this.checkout_form).prop('checked', true);
                            $(document.body).trigger('wpinv_discount_applied', [res]);
                        }
                        if (res.msg) {
                            $msg.show();
                            if (success) {
                                $msgS.show().find('.wpi-msg').html(res.msg);
                            } else {
                                $msgF.show().find('.wpi-msg').html(res.msg);
                            }
                        }
                    }
                }
            }).fail(function(res) {
                wpinvUnblock($box);
                if (window.console && window.console.log) {
                    console.log(res);
                }
            });
            return false;
        },
        removeDiscount: function(e) {
            e.preventDefault();
            var $this = $(this),
                $block = $this.closest('#wpinv_checkout_cart_wrap'),
                discount_code = $this.data('code');
            if (discount_code == '') {
                return false;
            }
            var data = {
                action: 'wpinv_remove_discount',
                code: discount_code,
                _nonce: WPInv.nonce
            };
            wpinvBlock($block);
            $.ajax({
                type: "POST",
                data: data,
                dataType: "json",
                url: WPInv.ajax_url,
                xhrFields: {
                    withCredentials: true
                },
                success: function(res) {
                    if (res && typeof res == 'object') {
                        if (res.success) {
                            jQuery('#wpinv_checkout_cart_form', $this.checkout_form).replaceWith(res.data.html);
                            jQuery('.wpinv-chdeckout-total').text(res.data.total);
                            if (res.data.free) {
                                $('#wpinv_payment_mode_select', $this.checkout_form).hide();
                                gw = 'manual';
                            } else {
                                $('#wpinv_payment_mode_select', $this.checkout_form).show();
                                gw = $('#wpinv_payment_mode_select', $this.checkout_form).attr('data-gateway');
                            }
                            $('input[name="wpi-gateway"][value="' + gw + '"]', $this.checkout_form).prop('checked', true);
                            //console.log('291 : wpinv_recalculate_taxes()');
                            //wpinv_recalculate_taxes();
                            $(document.body).trigger('wpinv_discount_removed', [res]);
                        }
                    }
                }
            }).fail(function(res) {
                wpinvUnblock($block);
                if (window.console && window.console.log) {
                    console.log(res);
                }
            });
            return false;
        },
        recalculate_taxes: function() {
            console.log('308 : wpinv_recalculate_taxes()');
            wpinv_recalculate_taxes();
        }
    }
    WPInv_Checkout.init();
});

function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? '&nbsp;' + message : '';
    el.block({
        message: '<i class="fa fa-refresh fa-spin"></i>' + message,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        },
        ignoreIfBlocked: true
    });
}

function wpinvUnblock(el) {
    el.unblock();
}

function wpinvRemoveQueryVar(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts = url.split('?');
    var urlparts2 = url.split('&');
    if (urlparts.length >= 2) {
        var prefix = encodeURIComponent(parameter) + '=';
        var pars = urlparts[1].split(/[&;]/g);
        //reverse iteration as may be destructive
        for (var i = pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }
        url = urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
        return url;
    } else if (urlparts2.length >= 2) {
        var prefix = encodeURIComponent(parameter) + '=';
        var pars = url.split(/[&;]/g);
        //reverse iteration as may be destructive
        for (var i = pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }
        url = pars.join('&');
        return url;
    } else {
        return url;
    }
}

/**
 * Allow a invoice to be created for items via ajax.
 * @param items This is a comma separated and pipe separated for quantity eg:  item_id|quantity,item_id|quantity,item_id|quantity
 */
function wpi_buy(items,$post_id){
    var $nonce = jQuery('#wpinv_buy_nonce').val();
    jQuery.ajax({
        url : ajaxurl,
        type : 'post',
        data : {
            action : 'wpinv_buy_items',
            items : items,
            post_id : $post_id,
            wpinv_buy_nonce : $nonce
        },
        success : function( res ) {
            console.log(res);
            if (typeof res == 'object' && res) {
                if (res.success) {
                    window.location.href = res.success;
                    return;
                }
                if (res.error) {
                    alert(res.error);
                }
            }
        }
    });
}