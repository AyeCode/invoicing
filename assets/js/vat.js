jQuery(function($) {
    $('body').bind("wpinv_taxes_recalculated", function(event, taxdata) {        
        var states = WPInv_VAT.getEUStates();
        var vat_state = (states.indexOf(taxdata.postdata.wpinv_country) >= 0 || states.indexOf(taxdata.postdata.country) >= 0);
        var ip_country = $('#wpi-ip-country');

        ip_country.css('display', 'none');
        if (WPInv_VAT_Vars.ApplyVATRules) {
            var buyer_and_billing_outside_eu = !vat_state && states.indexOf(ip_country.attr('value')) === -1;
            var no_vat_number = $('#wpinv_vat_number').val().trim().length === 0; 
            var billing_and_ip_countries_same = taxdata.postdata.country === ip_country.attr('value');

            if (!buyer_and_billing_outside_eu && no_vat_number && !billing_and_ip_countries_same) {
                ip_country .css('display', 'block');
            }
        }
    });
    
    var WPInv_VAT_Config = {
        init: function() {
            this.taxes(this);
            
            var me = this;
        },
        taxes: function(config) {
            var has_vat = $('#wpi_vat_info').is(':visible');
            var eu_states = WPInv_VAT.getEUStates();
            
            $('body').bind("wpinv_taxes_recalculated", function(event, taxdata) {
                var wpi_errors = $('.wpinv_errors');
                if (wpi_errors) {
                    wpi_errors.html("");
                    wpi_errors.css('display', "none");
                }

                if (taxdata.postdata.wpinv_country === 'UK') {
                    taxdata.postdata.wpinv_country = 'GB';
                }

                var states = eu_states;
                var vat_state = (states.indexOf(taxdata.postdata.wpinv_country) >= 0 || states.indexOf(taxdata.postdata.country) >= 0);
                if ( vat_state && WPInv_VAT_Vars.disableVATSameCountry && ( wpinv_is_base_country(taxdata.postdata.country) || wpinv_is_base_country(taxdata.postdata.wpinv_country) ) ) {
                    vat_state = false;
                }

                var vat_info = $('#wpi_vat_info');
                if (vat_state) {
                    vat_info.parent('.wpi-vat-details').show();
                }
                vat_info.css('display', vat_state ? "block" : "none");
                
                if (has_vat == vat_state) {
                    if (vat_state) {
                        config.reset(config, $('#wpinv_vat_reset'), false);
                    }
                    return;
                }
                has_vat = vat_state;
                wpinv_recalculate_taxes();
            });
            
            $('#wpi_add_eu_states').on('click', function() {
                var rate = $('#wpinv_settings_rates_vat_eu_states').val();
                if (rate === null || rate === '') {
                    if (!confirm(WPInv_VAT_Vars.NoRateSet)) return;
                }
                
                $('#wpi_remove_eu_states').trigger('click');
                
                var row = $('#wpinv_tax_rates tbody tr:last');
                var clone = row.clone();
                var body = row.parent();
                var count = $('#wpinv_tax_rates tbody tr').length; 
                $.each(eu_states, function(i, state) {
                    row = clone.clone();
                    row.find('td input').val('');
                    row.find('input, select').each(function() {
                        var name = $(this).attr('name');
                        name = name.replace(/\[(\d+)\]/, '[' + parseInt(count) + ']');
                        $(this).attr('name', name).attr('id', name);
                    });
                    row.find('#tax_rates\\[' + count + '\\]\\[rate\\]').val(rate);
                    row.find('#tax_rates\\[' + count + '\\]\\[country\\]').val(state);
                    row.find('#tax_rates\\[' + count + '\\]\\[state\\]').replaceWith('<input type="text" class="regular-text" value="" id="tax_rates[' + count + '][state]" name="tax_rates[' + count + '][state]">');
                    row.find('#tax_rates\\[' + count + '\\]\\[global\\]').prop('checked', true);
                    row.find('#tax_rates\\[' + count + '\\]\\[global\\]').val(1);
                    body.append(row);
                    count++;
                });
                return false;
            });
            
            $('#wpi_remove_eu_states').on('click', function() {
                $('#wpinv_tax_rates select.wpinv-tax-country').each( function( i ) {
                    if (jQuery(this).val() && jQuery.inArray(jQuery(this).val(), eu_states) !== -1) {
                        if( $('#wpinv_tax_rates tbody tr').length === 1 ) {
                            $('#wpinv_tax_rates select').val('');
                            $('#wpinv_tax_rates select').trigger('change');
                            $('#wpinv_tax_rates input[type="text"]').val('');
                            $('#wpinv_tax_rates input[type="number"]').val('');
                            $('#wpinv_tax_rates input[type="checkbox"]').attr('checked', false);
                        } else {
                            jQuery(this).closest('tr').remove();
                        }
                    }
                });
            });
            
            jQuery('#wpi_vat_get_rates').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.blur();
                
                WPInv_VAT_Config.disableButtons();
                
                var $loading = $this.closest('span').find('.fa-refresh');
                $loading.show();
                
                var data = {
                    action: 'wpinv_update_vat_rates',
                    group: 'standard'
                };
                
                jQuery.post(ajaxurl, data, function(response) {                    
                    try {
                        if (!response) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.RateRequestResponseInvalid);
                            return;
                        }
                        var vat_rates_array = response;
                        
                        if (vat_rates_array.success !== true) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.GetRateRequestFailed + (vat_rates_array.error ? vat_rates_array.error : 'reason unknown'));
                            return;
                        }
                        if (!vat_rates_array.data) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.NoRateInformationInResponse);
                            return;
                        }
                                                
                        var vat_rates = vat_rates_array.data;
                        var countries = jQuery('#wpinv_tax_rates select.wpinv-tax-country');
                        
                        countries.each(function(i, country_td) {
                            var country_el = jQuery(country_td);
                            var code = country_el.val();
                            
                            if (!vat_rates.rates[code]) {
                                return;
                            }
                            
                            var rate_el = country_el.closest('tr').find('.wpinv_tax_rate input');
                            var rate_class = country_el.closest('tr').find('.wpinv_tax_rate input');
                            
                            var rate = vat_rates.rates[code].standard;
                            rate_el.val(rate);
                        });
                        
                        $loading.hide();
                        WPInv_VAT_Config.showError(WPInv_VAT_Vars.RatesUpdated);
                    } catch (e) {
                        $loading.hide();
                        WPInv_VAT_Config.showError(e.message);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    $loading.hide();
                    $this.removeAttr('disabled', 'disabled');
                });
            });
            
            jQuery('#wpi_vat_get_rates_group').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.blur();
                
                WPInv_VAT_Config.disableButtons();
                
                var $loading = $this.closest('span').find('.fa-refresh');
                $loading.show();
                
                var data = {
                    action: 'wpinv_update_vat_rates'
                };
                
                jQuery.post(ajaxurl, data, function(response) {                    
                    try {
                        if (!response) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.RateRequestResponseInvalid);
                            return;
                        }
                        var vat_rates_array = response;
                        
                        if (vat_rates_array.success !== true) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.GetRateRequestFailed + (vat_rates_array.error ? vat_rates_array.error : 'reason unknown'));
                            return;
                        }
                        if (!vat_rates_array.data) {
                            WPInv_VAT_Config.showError(WPInv_VAT_Vars.NoRateInformationInResponse);
                            return;
                        }
                                                
                        var vat_rates = vat_rates_array.data;
                        jQuery.each( vat_rates.rates, function(sCode, oRate) {
                            if (sCode) {
                                var sGroup = jQuery('.wpinv_vat_group select[name="vat_rates[' + sCode + '][group]"]').val();
                                if (sGroup && typeof oRate[sGroup] !== 'undefined') {
                                    jQuery('.wpinv_vat_rate input[name="vat_rates[' + sCode + '][rate]"]').val(parseFloat(oRate[sGroup]));
                                }
                            }
                        });
                        
                        $loading.hide();
                        WPInv_VAT_Config.showError(WPInv_VAT_Vars.RatesUpdated);
                    } catch (e) {
                        $loading.hide();
                        WPInv_VAT_Config.showError(e.message);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    $loading.hide();
                    $this.removeAttr('disabled', 'disabled');
                });
            });
            
            $('#wpi_vat_rate_add').on('click', function() {
                var $n = $('#wpinv_settings\\[vat_rate_name\\]');
                var $d = $('#wpinv_settings\\[vat_rate_desc\\]');
                var $r = $('.wpi-vat-rate-actions .fa-refresh');
                var n = $.trim($n.val());
                var d = $.trim($d.val());
                
                if (!n) {
                    $n.focus();
                    return false;
                }
                var me = $(this);
                me.attr('disabled', 'disabled');
                $r.show();
                
                var data = {
                    action: 'wpinv_add_vat_class',
                    name: n,
                    desc: d
                };
                
                $.post(ajaxurl, data, function(json) {
                    var error = '';
                    var message = '';
                    if (json && typeof json == 'object') {
                        if (json.success === true) {
                            $r.removeClass('fa-refresh fa-spin').addClass('fa-check-circle orange');
                            window.location = json.redirect;
                            return;
                        } else {
                            error = json.error ? json.error : '';
                        }
                    }
                    me.removeAttr('disabled');
                    $r.hide();
                    if (error) {
                        alert(error);
                    }
                    return;
                })
                .fail(function() {
                    me.removeAttr('disabled');
                    $r.hide();
                });
            });
            
            $('#wpi_vat_rate_delete').on('click', function() {
                var $c = $('#wpinv_settings\\[vat_rates_class\\]');
                var $r = $('.wpi-vat-rate-actions .fa-refresh');
                var c = $.trim($c.val());
                
                if (!confirm(WPInv_VAT_Vars.ConfirmDeleteClass)) {
                    return;
                }
                
                var me = $(this);
                me.attr('disabled', 'disabled');
                $r.show();
                
                var data = {
                    action: 'wpinv_delete_vat_class',
                    class: c,
                };
                
                $.post(ajaxurl, data, function(json) {
                    var error = '';
                    var message = '';
                    if (json && typeof json == 'object') {
                        if (json.success === true) {
                            $r.removeClass('fa-refresh fa-spin').addClass('fa-check-circle orange');
                            window.location = json.redirect;
                            return;
                        } else {
                            error = json.error ? json.error : '';
                        }
                    }
                    me.removeAttr('disabled');
                    $r.hide();
                    if (error) {
                        alert(error);
                    }
                    return;
                })
                .fail(function() {
                    me.removeAttr('disabled');
                    $r.hide();
                });
            });
                        
            jQuery('#wpi_download_geoip2').on('click', function(e) {
                e.preventDefault();
                var me = $(this);
                me.blur();
                me.attr('disabled', 'disabled');
                action = me.attr('action');
                me.after('&nbsp;<i id="wpi-downloading" class="fa fa-refresh fa-spin"></i>');
                $('#wpi-downloading').show();
                
                var data = {
                    action: 'wpinv_download_geoip2',
                };
                jQuery.post(ajaxurl, data, 
                    function(response) {
                        try {
                            if (!response) {
                                WPInv_VAT_Config.showDownloadError(WPInv_VAT_Vars.RateRequestResponseInvalid, me);
                                return;
                            }
                            WPInv_VAT_Config.showDownloadError(response + (action == 'download' ? " " + WPInv_VAT_Vars.PageRefresh : ""), me, action == 'download');
                        } catch (e) {
                            WPInv_VAT_Config.showDownloadError(e.message, me);
                        }
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        WPInv_VAT_Config.showDownloadError("Oops!" + textStatus, me);
                    })
            });
            
            $('#wpinv_vat_reset').on('click', function(e) {
                e.preventDefault();
                WPInv_VAT_Config.reset(WPInv_VAT_Config, $(this), true);
            });
            
            if (WPInv_VAT_Vars.isFront) {
                var elErr = $('.wpi-cart-field-actions .wpi-vat-box-error');
                var elInfo = $('.wpi-cart-field-actions .wpi-vat-box-info');
                
                $('#wpinv_vat_validate').on('click', function() {
                    elErr.hide();
                    elInfo.hide();
                    var companyEl = $('#wpinv_checkout_form #wpinv_company');
                    var company = companyEl.val();
                    
                    var numberEl = $('#wpinv_checkout_form #wpinv_vat_number');
                    var vat_number = numberEl.val();
                    
                    
                    if ((company && (company.length > 0)) || (vat_number && (vat_number.length > 0))) {
                        if (!(company && (company.length > 0))) {
                            WPInv_VAT_Config.displayMessage(WPInv_VAT_Vars.EmptyCompany, 'error');
                            return false;
                        }
                        
                        if (!(vat_number && (vat_number.length > 0))) {
                            WPInv_VAT_Config.displayMessage(WPInv_VAT_Vars.EmptyVAT, 'error');
                            return false;
                        }
                    }
                    if (!WPInv_VAT_Vars.disableVATSimpleCheck && (vat_number && (vat_number.length > 0)) && !WPInv_VAT.validateVATID(numberEl, false)) {
                        WPInv_VAT_Config.displayMessage(WPInv_VAT_Vars.ErrorValidatingVATID, 'error');
                        return false;
                    }
                    
                    var number = numberEl.val();
                    var nonce = $('input[name=_wpi_nonce]').val();
                    var me = $(this);
                    me.attr('disabled', 'disabled');
                    $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-0 wpinv-vat-stat-1').addClass('wpinv-vat-stat-2');
                    $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatValidating);
                    
                    var data = {
                        action: 'wpinv_vat_validate',
                        company: company,
                        number: number,
                        country: $('[name=wpinv_country]').val(),
                        source: 'checkout',
                        _wpi_nonce: nonce
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
                                error = json.error ? json.error : json.message;
                            }
                        }
                        
                        me.removeAttr('disabled');
                        if (validated) {
                            if (number.length === 0) {
                            } else {
                                me.hide();
                                $('#wpinv_vat_reset').show();
                            }
                            $('.wpinv-vat-stat font').html(message ? message : WPInv_VAT_Vars.VatValidated);
                            $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-1');
                        } else {
                            $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                            $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                        }

                        $('#wpi-ip-country').css('display', number.length > 0 || $('#wpinv_country').val() === $('#wpi-ip-country').attr('value') ? "none" : "block");

                        if (error) {
                            config.displayMessage(error + '<br>' + WPInv_VAT_Vars.TotalsRefreshed, 'error');
                        } else {
                            config.displayMessage(WPInv_VAT_Vars.TotalsRefreshed, 'info');
                        }
                        wpinv_recalculate_taxes();
                        return;
                    })
                    .fail(function() {
                        me.removeAttr('disabled');
                        $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                        $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                        WPInv_VAT_Config.displayMessage(WPInv_VAT_Vars.ErrorValidatingVATID, 'error');
                    });
                });
            } else {
                $('#wpinv_vat_validate').on('click', function() {
                    var companyEl = $('#wpinv_settings\\[vat_company_name\\]');
                    var company = companyEl.val();
                    
                    if (company.length == 0) {
                        alert(WPInv_VAT_Vars.EmptyCompany);
                        return false;
                    }
                    
                    var numberEl = $('#wpinv_settings\\[vat_number\\]');
                    if (!WPInv_VAT.validateVATID(numberEl, true)) {
                        return false;
                    }
                    
                    var number = numberEl.val();
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
                                $('#wpi_vat_company_original').val(company);
                                $('#wpi_vat_number_original').val(number);
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
                        alert(WPInv_VAT_Vars.ErrorValidatingVATID);
                    });
                });
            }
        },
        clearBox: function(){
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

            $('#wpi_vat_info').parent('.wpi-vat-details').show();
            $('#wpi_vat_info').show();

            if (!updateTaxes) {
                return;
            }

            var numberEl = $('#wpinv_vat_number');
            var number = numberEl.val();

            if (number.length === 0 && $('.wpinv-vat-stat').hasClass('wpinv-vat-stat-1')) {
                return;
            }

            var nonce  = $('input[name=_wpi_nonce]').val();
            
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

            $.post(ajaxurl, data, function (response) {
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

                config.displayMessage(json === undefined || json.message === undefined ? WPInv_VAT_Vars.ErrorInvalidResponse : json.message, 'error');
            })
            .fail(function(jqXHR, textStatus, errorThrown){
                me.removeAttr('disabled');
                me.show();
                $('.wpinv-vat-stat font').html(WPInv_VAT_Vars.VatNotValidated);
                $('.wpinv-vat-stat').removeClass('wpinv-vat-stat-2').addClass('wpinv-vat-stat-0');
                config.displayMessage(WPInv_VAT_Vars.ErrorResettingVATID + " (" + textStatus +  " - " + errorThrown + ")", 'error');
            })
        },
        showDownloadError: function(message, button, reload) {
            button.removeAttr('disabled');
            
            jQuery('#wpi-downloading').hide();
            jQuery('#wpinv-geoip2-errors').html(message).show();
            
            setTimeout(function() {
                if (reload)
                    window.location.reload();
                else
                    jQuery('#wpinv-geoip2-errors').hide();
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
        displayMessage: function(m, c){
            var box = $('.wpi-vat-box.wpi-vat-box-' + c + ' #text');
            if (box) {
                box.html(m);
                box.parent().show().slideDown().css("display","inline-block");
            }
        }
    };
    WPInv_VAT_Config.init();
});

function wpinv_recalculate_taxes( state ) {
    var $address = jQuery('#wpi-billing');

    if( !state ) {
        state = $address.find('#wpinv_state').val();
    }

    var postData = {
        action: 'wpinv_recalculate_tax',
        nonce: WPInv_VAT_Vars.checkoutNonce,
        country: $address.find('#wpinv_country').val(),
        state: state
    };

    wpinvBlock(jQuery('#wpinv_checkout_cart_wrap'));
    jQuery.ajax({
        type: "POST",
        data: postData,
        dataType: "json",
        url: ajaxurl,
        success: function (res) {
            jQuery('#wpinv_checkout_cart_wrap').unblock();
            
            if (res && typeof res == 'object') {
                jQuery('#wpinv_checkout_cart_form').replaceWith(res.html);
                jQuery('.wpinv-chdeckout-total', jQuery('#wpinv_checkout_form_wrap')).text(res.total);

                var tax_data = new Object();
                tax_data.postdata = postData;
                tax_data.response = res;
                tax_data.recalculated = true;
                jQuery('body').trigger('wpinv_taxes_recalculated', [ tax_data ]);
                jQuery('body').trigger('wpinv_vat_recalculated', [ tax_data ]);
            }

            setTimeout( function() { 
                var texts = jQuery('.wpi-vat-box #text');
                texts.html('');
                var boxes = texts.parents(".wpi-vat-box")
                boxes.fadeOut('fast'); }
            , 15000 );
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        jQuery('#wpinv_checkout_cart_wrap').unblock();
        console.log(errorThrown);
    });
}

function wpinv_is_base_country( country ) {
    var baseCountry = WPInv_VAT_Vars.baseCountry;
    if ( baseCountry === 'UK' ) {
        baseCountry = 'GB';
    }
    if ( country == 'UK' ) {
        country = 'GB';
    }
    
    return ( country && country === baseCountry ) ? true : false;
}