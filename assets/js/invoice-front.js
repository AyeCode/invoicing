"use strict";

function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? '&nbsp;' + message : '';
    jQuery( el ).find('.loading_div .sr-only').html(message); //@todo can this use text and not html?
    jQuery( el ).find('.loading_div').show();
}

function wpinvUnblock(el) {
    jQuery( el ).find('.loading_div').hide();
}
