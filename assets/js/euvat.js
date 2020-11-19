jQuery(function($) {

    var WPInv_VAT_Config = {
        init: function() {
            this.taxes(this);
            var me = this;
        },
        checkVATNumber: function(el, err) {
            try {
                if (el) {
                    var valid = false;
                    var msg = '';
                    var value = el.val();
                    if (value.length > 0) {
                        if (checkVATNumber(value)) {
                            valid = true;
                        } else {
                            msg = WPInv_VAT_Vars.ErrInvalidVat;
                        }
                    } else {
                        msg = WPInv_VAT_Vars.EmptyVAT;
                    }
                    if (valid) {
                        return true;
                    } else if (err && msg) {
                        alert(msg);
                    }
                    return false;
                }
                return;
            } catch (e) {
                if (err) {
                    alert(WPInv_VAT_Vars.ErrValidateVAT + ": " + e.message);
                }
                return false;
            }
        },
        taxes: function(config) {

            jQuery('#wpi_geoip2').on('click', function(e) {
                e.preventDefault();
                var el = $(this);
                var action = el.attr('action');
                el.blur().attr('disabled', 'disabled');
                el.after('&nbsp;<i id="wpi-downloading" class="fa fa-refresh fa-spin"></i>');
                $('#wpi-downloading').show();
                var data = {
                    action: 'wpinv_geoip2',
                };
                jQuery.post(ajaxurl, data,
                    function(response) {
                        var msg = '';
                        var reload = false;
                        try {
                            if (response) {
                                reload = action == 'download' ? true : false;
                                msg = response + (action == 'download' ? ' ' + WPInv_VAT_Vars.PageRefresh : '');
                            } else {
                                msg = WPInv_VAT_Vars.ErrResponse;
                            }
                        } catch (e) {
                            msg = e.message;
                        }
                        WPInv_VAT_Config.showGeoIP2Error(msg, el, reload);
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                    WPInv_VAT_Config.showGeoIP2Error("Status: " + textStatus, el);
                })
            });
            $('#wpinv_vat_reset').on('click', function(e) {
                e.preventDefault();
                WPInv_VAT_Config.reset(WPInv_VAT_Config, $(this), true);
            });
            if ( ! WPInv_VAT_Vars.isFront) {
                $('#wpinv_vat_validate').on('click', function() {
                    var companyEl = $('#wpinv_settings\\[vat_company_name\\]');
                    var company = companyEl.val();
                    if (company.length == 0) {
                        alert(WPInv_VAT_Vars.EmptyCompany);
                        return false;
                    }
                    var vatEl = $('#wpinv_settings\\[vat_number\\]');
                    var number = vatEl.val();
                    if (!WPInv_VAT_Vars.disableVATSimpleCheck && !WPInv_VAT_Config.checkVATNumber(vatEl, true)) {
                        return false;
                    }
                    var nonce = $('input[name=_wpi_nonce]').val();
                    var me = $(this);
                    me.attr('disabled', 'disabled');
                    $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-0 wpinv-vat-stat-1').addClass('wpinv-vat-stat-2');
                    $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatValidating);
                    var data = {
                        action: 'wpinv_vat_validate',
                        company: company,
                        number: number,
                        _wpi_nonce: nonce,
                        source: 'admin'
                    };
                    $.post(ajaxurl, data, function(json) {
                            var validated = false;
                            var error = '';
                            var message = '';
                            if (json && typeof json == 'object') {
                                if (json.success === true) {
                                    validated = true;
                                    message = json.message ? json.message : '';
                                } else {
                                    error = json.message ? json.message : json.error;
                                }
                            }
                            if (validated) {
                                $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatValidated);
                                $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-1');
                            } else {
                                me.removeAttr('disabled');
                                $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                                $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                                if (error) {
                                    alert(error);
                                }
                            }
                            return;
                        })
                        .fail(function() {
                            me.removeAttr('disabled');
                            $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                            $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                            alert(WPInv_VAT_Vars.ErrValidateVAT);
                        });
                });
            }
        },
        clearBox: function() {
            var texts = $('.wpi-vat-box #text');
            texts.html('');
            var boxes = texts.parents(".wpi-vat-box")
            boxes.fadeOut('fast');
        },
        reset: function(config, me, updateTaxes) {
            var tax = parseFloat($('#wpinv_checkout_form .wpinv_cart_tax_amount').attr('data-tax'));
            var total = parseFloat($('#wpinv_checkout_form .wpinv_cart_amount').attr('data-total'));
            var wpiCCaddressEl = $('#wpinv-fields .wpi-billing');
            var countryEl = wpiCCaddressEl.find('#wpinv_country').val();
            if (total === "0") {
                $('#wpi_vat_info').hide();
                return;
            }
            if (tax !== "0" && countryEl === undefined) {
                window.location.reload()
            }

            if (!updateTaxes) {
                return;
            }
            var numberEl = $('#wpinv_vat_number');
            var number = numberEl.val();
            if (number.length === 0 && $('.wpinv-vat-stat').hasClass('wpinv-vat-stat-1')) {
                return;
            }
            var nonce = $('input[name=_wpi_nonce]').val();
            me.attr('disabled', 'disabled');
            $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-0 wpinv-vat-stat-1').addClass('wpinv-vat-stat-2');
            $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatReseting);
            var validateButton = $('#wpinv_vat_validate');
            validateButton.hide();
            var data = {
                action: 'wpinv_vat_reset',
                _wpi_nonce: nonce,
                source: 'checkout'
            };
            $.post(ajaxurl, data, function(response) {
                    var json = response;
                    $('#wpinv_company').val("");
                    $('#wpinv_vat_number').val("");
                    $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                    $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                    validateButton.show();
                    me.removeAttr('disabled');
                    me.hide();
                    if (json.success) {
                        $('#wpinv_company').val(json.data.company); // TODO
                        $('#wpinv_vat_number').val(json.data.number); // TODO
                        if (updateTaxes) {
                            wpinv_recalculate_taxes();
                        }
                        return;
                    }
                    config.displayMessage(json === undefined || json.message === undefined ? WPInv_VAT_Vars.ErrInvalidResponse : json.message, 'error');
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    me.removeAttr('disabled');
                    me.show();
                    $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                    $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                    config.displayMessage(WPInv_VAT_Vars.ErrResetVAT + " (" + textStatus + " - " + errorThrown + ")", 'error');
                })
        },
        showGeoIP2Error: function(msg, el, reload) {
            el.removeAttr('disabled');
            jQuery('#wpi-downloading').hide();
            jQuery('#wpinv-geoip2-errors').html(msg).show();
            setTimeout(function() {
                if (reload) {
                    window.location.reload();
                } else {
                    jQuery('#wpinv-geoip2-errors').hide();
                }
            }, 10000);
        },
        showError: function(message) {
            WPInv_VAT_Config.enableButtons();
            var errorEl = jQuery('#wpinv-rates-error-wrap');
            errorEl.html(message);
            errorEl.css("display", "block");
            setTimeout(function() {
                errorEl.hide();
            }, 10000);
        },
        disableButtons: function() {
            $('#wpi_vat_get_rates').attr('disabled', 'disabled');
            $('#wpi_add_eu_states').attr('disabled', 'disabled');
            $('#wpi_remove_eu_states').attr('disabled', 'disabled');
            $('#wpi_vat_get_rates_group').attr('disabled', 'disabled');
        },
        enableButtons: function() {
            $('#wpi_vat_get_rates').removeAttr('disabled');
            $('#wpi_add_eu_states').removeAttr('disabled');
            $('#wpi_remove_eu_states').removeAttr('disabled');
            $('#wpi_vat_get_rates_group').removeAttr('disabled');
        },
        displayMessage: function(m, c) {
            var box = $('.wpi-vat-box.wpi-vat-box-' + c + ' #text');
            if (box) {
                box.html(m);
                box.parent().show().slideDown().css("display", "inline-block");
            }
        }
    };
    WPInv_VAT_Config.init();
});
