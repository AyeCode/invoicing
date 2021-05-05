"use strict";function _typeof(t){"@babel/helpers - typeof";return(_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function wpinvBlock(t,e){e=void 0!==e&&""!==e?e:WPInv.loading;var a=jQuery(t);1!=a.data("GetPaidIsBlocked")&&(a.data("GetPaidIsBlocked",1),a.data("GetPaidWasRelative",a.hasClass("position-relative")),a.addClass("position-relative"),a.append('<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;"><div class="spinner-border" role="status"><span class="sr-only">'+e+"</span></div></div>"))}function wpinvUnblock(t){var e=jQuery(t);1==e.data("GetPaidIsBlocked")&&(e.data("GetPaidIsBlocked",0),e.data("GetPaidWasRelative")||e.removeClass("position-relative"),e.children(".getpaid-block-ui").remove())}jQuery(function(t){window.getpaid_form=function(e){return{fetched_initial_state:0,cached_states:{},form:e,show_error:function(t,a){e.find(".getpaid-payment-form-errors, .getpaid-custom-payment-form-errors").html("").addClass("d-none"),a&&e.find(a).length?e.find(a).html(t).removeClass("d-none"):e.find(".getpaid-payment-form-errors").html(t).removeClass("d-none")},hide_error:function(){e.find(".getpaid-payment-form-errors, .getpaid-custom-payment-form-errors").html("").addClass("d-none")},cache_state:function(t,e){this.cached_states[t]=e},current_state_key:function(){return this.form.serialize()},is_current_state_cached:function(){return this.cached_states.hasOwnProperty(this.current_state_key())},switch_state:function(){this.hide_error();var t=this.cached_states[this.current_state_key()];if(!t)return this.fetch_state();if(t.totals)for(var e in t.totals)t.totals.hasOwnProperty(e)&&this.form.find(".getpaid-form-cart-totals-total-"+e).html(t.totals[e]);if(Array.isArray(t.fees)?this.form.find(".getpaid-form-cart-totals-fees").addClass("d-none"):this.form.find(".getpaid-form-cart-totals-fees").removeClass("d-none"),Array.isArray(t.discounts)?this.form.find(".getpaid-form-cart-totals-discount").addClass("d-none"):this.form.find(".getpaid-form-cart-totals-discount").removeClass("d-none"),t.items)for(var a in t.items)t.items.hasOwnProperty(a)&&this.form.find(".getpaid-form-cart-item-subtotal-"+a).html(t.items[a]);if(t.texts)for(var i in t.texts)t.texts.hasOwnProperty(i)&&this.form.find(i).html(t.texts[i]);t.gateways&&this.process_gateways(t.gateways,t),t.js_data&&this.form.data("getpaid_js_data",t.js_data),this.form.trigger("getpaid_payment_form_changed_state",[t])},refresh_state:function(){if(this.is_current_state_cached())return this.switch_state();this.fetch_state()},fetch_state:function(){var e=this;wpinvBlock(this.form);var a=this.current_state_key();return t.post(WPInv.ajax_url,a+"&action=wpinv_payment_form_refresh_prices&_ajax_nonce="+WPInv.formNonce+"&initial_state="+this.fetched_initial_state).done(function(t){if(t.success)return e.fetched_initial_state=1,e.cache_state(a,t.data),e.switch_state();!1!==t.success?e.show_error(t):e.show_error(t.data.error,t.data.code)}).fail(function(){e.show_error(WPInv.connectionError)}).always(function(){wpinvUnblock(e.form)})},update_state_field:function(e){if((e=t(e)).find(".wpinv_state").length){var a=e.find(".getpaid-address-field-wrapper__state");wpinvBlock(a);var i={action:"wpinv_get_payment_form_states_field",country:e.find(".wpinv_country").val(),form:this.form.find('input[name="form_id"]').val(),name:a.find(".wpinv_state").attr("name"),_ajax_nonce:WPInv.formNonce};t.get(WPInv.ajax_url,i,function(t){"object"==_typeof(t)&&a.replaceWith(t.data)}).always(function(){wpinvUnblock(e.find(".getpaid-address-field-wrapper__state"))})}},attach_events:function(){var a=this,i=this,n=function(t,e){e||(e=200);var a=!1,i=!0;return function(){if(a){i=!1;var n=this;setTimeout(function(){i||(t.bind(n).call(),i=!0)},e)}else i=!0,t.bind(this).call(),a=!0,setTimeout(function(){a=!1},e)}}(function(){i.refresh_state()},500);this.form.on("change",".getpaid-refresh-on-change",n),this.form.on("input",".getpaid-payment-form-element-price_select :input:not(.getpaid-refresh-on-change)",n),this.form.on("change",".getpaid-item-quantity-input",n),this.form.on("change",'[name="getpaid-payment-form-selected-item"]',n),this.form.on("change",".getpaid-item-price-input",function(){t(this).hasClass("is-invalid")||n()}),this.form.on("change",".getpaid-shipping-address-wrapper .wpinv_country",function(){a.update_state_field(".getpaid-shipping-address-wrapper")}),this.form.on("change",".getpaid-billing-address-wrapper .wpinv_country",function(){a.update_state_field(".getpaid-billing-address-wrapper"),a.form.find(".getpaid-billing-address-wrapper .wpinv_country").val()!=a.form.find(".getpaid-billing-address-wrapper .wpinv_country").data("ipCountry")?a.form.find(".getpaid-address-field-wrapper__address-confirm").removeClass("d-none"):a.form.find(".getpaid-address-field-wrapper__address-confirm").addClass("d-none"),n()}),this.form.on("change",".getpaid-billing-address-wrapper .wpinv_state, .getpaid-billing-address-wrapper .wpinv_vat_number",function(){n()}),this.form.on("click",'.getpaid-vat-number-validate, [name="confirm-address"]',function(){n()}),this.form.on("change",".getpaid-billing-address-wrapper .wpinv_vat_number",function(){var e=t(this).parent().find(".getpaid-vat-number-validate");e.text(e.data("validate"))}),this.form.find(".getpaid-discount-field").length&&(this.form.find(".getpaid-discount-button").on("click",function(t){t.preventDefault(),n()}),this.form.find(".getpaid-discount-field").on("keypress",function(t){"13"==t.keyCode&&(t.preventDefault(),n())}),this.form.find(".getpaid-discount-field").on("change",function(t){n()})),this.form.on("change",".getpaid-gateway-radio input",function(){var t=a.form.find(".getpaid-gateway-radio input:checked").val();e.find(".getpaid-gateway-description").slideUp(),e.find(".getpaid-description-".concat(t)).slideDown()})},process_gateways:function(e,a){var i=this;this.form.data("initial_amt",a.initial_amt),this.form.data("currency",a.currency);var n=this.form.find(".getpaid-payment-form-submit"),d=n.data("free").replace(/%price%/gi,a.totals.raw_total),o=n.data("pay").replace(/%price%/gi,a.totals.raw_total);return n.prop("disabled",!1).css("cursor","pointer"),a.is_free?(n.val(d),this.form.find(".getpaid-gateways").slideUp(),void this.form.data("isFree","yes")):(this.form.data("isFree","no"),this.form.find(".getpaid-gateways").slideDown(),n.val(o),this.form.find(".getpaid-no-recurring-gateways, .getpaid-no-subscription-group-gateways, .getpaid-no-multiple-subscription-group-gateways, .getpaid-no-active-gateways").addClass("d-none"),this.form.find(".getpaid-select-gateway-title-div, .getpaid-available-gateways-div, .getpaid-gateway-descriptions-div").removeClass("d-none"),e.length<1?(this.form.find(".getpaid-select-gateway-title-div, .getpaid-available-gateways-div, .getpaid-gateway-descriptions-div").addClass("d-none"),n.prop("disabled",!0).css("cursor","not-allowed"),a.has_multiple_subscription_groups?void this.form.find(".getpaid-no-multiple-subscription-group-gateways").removeClass("d-none"):a.has_subscription_group?void this.form.find(".getpaid-no-subscription-group-gateways").removeClass("d-none"):a.has_recurring?void this.form.find(".getpaid-no-recurring-gateways").removeClass("d-none"):void this.form.find(".getpaid-no-active-gateways").removeClass("d-none")):(1==e.length?(this.form.find(".getpaid-select-gateway-title-div").addClass("d-none"),this.form.find(".getpaid-gateway-radio input").addClass("d-none")):this.form.find(".getpaid-gateway-radio input").removeClass("d-none"),this.form.find(".getpaid-gateway").addClass("d-none"),t.each(e,function(t,e){i.form.find(".getpaid-gateway-".concat(e)).removeClass("d-none")}),0===this.form.find(".getpaid-gateway:visible input:checked").length&&this.form.find(".getpaid-gateway:visible .getpaid-gateway-radio input").eq(0).prop("checked",!0),void(0===this.form.find(".getpaid-gateway-description:visible").length&&this.form.find(".getpaid-gateway-radio input:checked").trigger("change"))))},setup_saved_payment_tokens:function(){this.form.find(".getpaid-saved-payment-methods").each(function(){var e=t(this);t("input",e).on("change",function(){t(this).closest("li").hasClass("getpaid-new-payment-method")?e.closest(".getpaid-gateway-description").find(".getpaid-new-payment-method-form").slideDown():e.closest(".getpaid-gateway-description").find(".getpaid-new-payment-method-form").slideUp()}),"0"==e.data("count")&&e.hide(),0===t("input",e).filter(":checked").length&&t("input",e).eq(0).prop("checked",!0),t("input",e).filter(":checked").trigger("change")})},handleAddressToggle:function(e){var a=e.closest(".getpaid-payment-form-element-address");a.find(".getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper").addClass("d-none"),e.on("change",function(){t(this).is(":checked")?(a.find(".getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper").addClass("d-none"),a.find(".getpaid-shipping-billing-address-title").removeClass("d-none")):(a.find(".getpaid-billing-address-title, .getpaid-shipping-address-title, .getpaid-shipping-address-wrapper").removeClass("d-none"),a.find(".getpaid-shipping-billing-address-title").addClass("d-none"))})},init:function(){this.setup_saved_payment_tokens(),this.attach_events(),this.refresh_state(),this.form.find(".getpaid-payment-form-element-billing_email span.d-none").closest(".col-12").addClass("d-none"),this.form.find(".getpaid-gateway-description:not(:has(*))").remove();var e=this.form.find('[name ="same-shipping-address"]');e.length>0&&this.handleAddressToggle(e),t("body").trigger("getpaid_setup_payment_form",[this.form])}}};var e=function(e){function a(a){0!=e.find(".getpaid-payment-form-items-cart").length&&(e.find(".getpaid-payment-form-items-cart-item.getpaid-selectable").each(function(){t(this).find(".getpaid-item-price-input").attr("name",""),t(this).find(".getpaid-item-quantity-input").attr("name",""),t(this).hide()}),t(a).each(function(t,a){if(a){var i=e.find(".getpaid-payment-form-items-cart-item.item-"+a);i.find(".getpaid-item-price-input").attr("name","getpaid-items["+a+"][price]"),i.find(".getpaid-item-quantity-input").attr("name","getpaid-items["+a+"][quantity]"),i.show()}}))}if(e.find(".getpaid-gateway-descriptions-div .form-horizontal .form-group").addClass("row"),e.find(".getpaid-payment-form-items-radio").length){var i=function(){a([e.find(".getpaid-payment-form-items-radio .form-check-input:checked").val()])},n=e.find(".getpaid-payment-form-items-radio .form-check-input");n.on("change",i),0===n.filter(":checked").length&&n.eq(0).prop("checked",!0),i()}if(e.find(".getpaid-payment-form-items-checkbox").length){i=function(){a(e.find(".getpaid-payment-form-items-checkbox input:checked").map(function(){return t(this).val()}).get())};var d=e.find(".getpaid-payment-form-items-checkbox input");d.on("change",i),0===d.filter(":checked").length&&d.eq(0).prop("checked",!0),i()}if(e.find(".getpaid-payment-form-items-select").length){i=function(){a([e.find(".getpaid-payment-form-items-select select").val()])};var o=e.find(".getpaid-payment-form-items-select select");o.on("change",i),o.val()||o.find("option:first").prop("selected","selected"),i()}getpaid_form(e).init(),e.on("submit",function(a){a.preventDefault(),wpinvBlock(e),e.find(".getpaid-payment-form-errors, .getpaid-custom-payment-form-errors").html("").addClass("d-none");var i=e.data("key"),n={submit:!0,delay:!1,data:e.serialize(),form:e,key:i};if("no"==e.data("isFree")&&t("body").trigger("getpaid_payment_form_before_submit",[n]),n.submit){var d=function(){return t.post(WPInv.ajax_url,n.data+"&action=wpinv_payment_form&_ajax_nonce="+WPInv.formNonce).done(function(a){if("string"!=typeof a){if(a.success)return a.data.action||(window.location.href=decodeURIComponent(a.data)),void("auto_submit_form"==a.data.action&&(e.parent().append('<div class="getpaid-checkout-autosubmit-form">'+a.data.form+"</div>"),t(".getpaid-checkout-autosubmit-form form").submit()));e.find(".getpaid-payment-form-errors").html(a.data).removeClass("d-none"),e.find(".getpaid-payment-form-remove-on-error").remove()}else e.find(".getpaid-payment-form-errors").html(a).removeClass("d-none")}).fail(function(t){e.find(".getpaid-payment-form-errors").html(WPInv.connectionError).removeClass("d-none"),e.find(".getpaid-payment-form-remove-on-error").remove()}).always(function(){wpinvUnblock(e)})};if(n.delay){t("body").bind("getpaid_payment_form_delayed_submit"+i,function a(){n.submit?d():wpinvUnblock(e),t("body").unbind("getpaid_payment_form_delayed_submit"+i,a)})}else d()}else wpinvUnblock(e)})};t(".getpaid-payment-form").each(function(){e(t(this))}),t(document).on("click",".getpaid-payment-button",function(a){a.preventDefault(),t("#getpaid-payment-modal .modal-body-wrapper").html('<div class="d-flex align-items-center justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'),t("#getpaid-payment-modal").modal();var i=t(this).data();i.action="wpinv_get_payment_form",i._ajax_nonce=WPInv.formNonce,t.get(WPInv.ajax_url,i,function(a){t("#getpaid-payment-modal .modal-body-wrapper").html(a),t("#getpaid-payment-modal").modal("handleUpdate"),t("#getpaid-payment-modal .getpaid-payment-form").each(function(){e(t(this))})}).fail(function(e){t("#getpaid-payment-modal .modal-body-wrapper").html(WPInv.connectionError),t("#getpaid-payment-modal").modal("handleUpdate")})}),t(document).on("click",'a[href^="#getpaid-form-"], a[href^="#getpaid-item-"]',function(a){var i=t(this).attr("href");if(-1!=i.indexOf("#getpaid-form-"))var n={form:i.replace("#getpaid-form-","")};else{if(-1==i.indexOf("#getpaid-item-"))return;n={item:i.replace("#getpaid-item-","")}}a.preventDefault(),t("#getpaid-payment-modal .modal-body-wrapper").html('<div class="d-flex align-items-center justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'),t("#getpaid-payment-modal").modal(),n.action="wpinv_get_payment_form",n._ajax_nonce=WPInv.formNonce,t.get(WPInv.ajax_url,n,function(a){t("#getpaid-payment-modal .modal-body-wrapper").html(a),t("#getpaid-payment-modal").modal("handleUpdate"),t("#getpaid-payment-modal .getpaid-payment-form").each(function(){e(t(this))})}).fail(function(e){t("#getpaid-payment-modal .modal-body-wrapper").html(WPInv.connectionError),t("#getpaid-payment-modal").modal("handleUpdate")})}),t(document).on("change",".getpaid-address-edit-form #wpinv-country",function(e){var a=t(this).closest(".getpaid-address-edit-form").find(".wpinv_state");if(a.length){wpinvBlock(a.parent());var i={action:"wpinv_get_aui_states_field",country:t(this).val(),state:a.val(),class:"wpinv_state",name:"state",_ajax_nonce:WPInv.nonce};t.get(WPInv.ajax_url,i,function(t){"object"==_typeof(t)&&a.parent().replaceWith(t.data.html)}).always(function(){wpinvUnblock(a.parent())})}}),t(document).on("input",".getpaid-validate-minimum-amount",function(e){isNaN(parseFloat(t(this).val()))?t(this).data("minimum-amount")?t(this).val(t(this).data("minimum-amount")):t(this).val(0):t(this).val(parseFloat(t(this).val())),t(this).data("minimum-amount")&&t(this).val()<t(this).data("minimum-amount")?t(this).addClass("is-invalid"):t(this).removeClass("is-invalid")})});