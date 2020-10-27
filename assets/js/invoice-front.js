"use strict";

function wpinvBlock(el, message) {
    message = typeof message != 'undefined' && message !== '' ? '&nbsp;' + message : '';
    jQuery( el ).block({

        message: '<i class="fa fa-sync-alt fa-lg fa-spin"></i>' + message,

        overlayCSS: {
            background: '#ffffff',
            opacity: 0.6
        },

        css: { 
            padding:        0, 
            margin:         0, 
            width:          '30%',
            textAlign:      'center', 
            color:          '#263238;', 
            border:         'none', 
            cursor:         'wait' 
        },

        ignoreIfBlocked: true

    });
}

function wpinvUnblock(el) {
    el.unblock();
}
