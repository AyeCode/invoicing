
// make sure ajaxurl is defined
if (typeof ajaxurl === 'undefined' || ajaxurl === null) {
    // variable is undefined or null
    ajaxurl = WPInv.ajax_url;
}


jQuery(function($) {
    var valid = false;
    $('#wpinv_checkout_form').on('submit', function(e) {
        var $form = $(this).closest('#wpinv_checkout_form');
        $('.wpinv_errors').remove();
        
        if (valid) {
            return true;
        }
        
        var fields = ['first_name', 'email', 'address', 'city', 'country', 'state'];
        var err = [];
        $.each(fields, function(i, field) {
            if ($('#wpinv_' + field).length && !$('#wpinv_' + field).val()) {
                err.push(field);
            }
        });

        if (err && err.length > 0) {
            $('#wpinv_' + err[0]).focus();
            return false;
        } else {
            e.preventDefault();
            wpinvBlock($form);
            
            var data = $form.serialize();
            data = wpinvRemoveQueryVar(data, 'action');
            data = wpinvRemoveQueryVar(data, 'wpinv_ajax');

            $.post(ajaxurl, data + '&action=wpinv_checkout', function (res) {
                if ( $.trim(res) == 'OK' ) {
                    valid = true;
                    $form.submit();
                } else {
                    $form.unblock();                    
                    $('#wpinv_purchase_submit', $form).before(res);
                }
            });
        }
        return false;
    });
    
    var elB = $('#wpinv-fields');
    $('#wpinv_country', elB).change(function(e){
        $('.wpinv_errors').remove();
        wpinvBlock(jQuery('#wpinv_state_box'));
        
        var $this = $(this);
        data = {
            action: 'wpinv_get_states_field',
            country: $(this).val(),
            field_name: 'wpinv_state',
        };
        
        $.post(ajaxurl, data, function (response) {
            if( 'nostates' === response ) {
                var text_field = '<input type="text" required="required" class="wpi-input required" id="wpinv_state" name="wpinv_state">';
                $('#wpinv_state', elB).replaceWith( text_field );
            } else {
                $('#wpinv_state', elB).replaceWith( response );
                
                var changeState = function() {
                    console.log('wpinv_recalculate_taxes(72)');
                    wpinv_recalculate_taxes($(this).val());
                };
                $( "#wpinv_state" ).unbind( "change", changeState );
                $( "#wpinv_state" ).bind( "change", changeState );
            }
            $('#wpinv_state', elB).find('option[value=""]').remove();
            $('#wpinv_state', elB).addClass('wpi-input required');
        }).done(function (data) {
            jQuery('#wpinv_state_box').unblock();
            console.log('wpinv_recalculate_taxes(80)');
            wpinv_recalculate_taxes();
        });

        return false;        
    });
    
    $('select#wpinv_state', elB).change(function(e){
        $('.wpinv_errors').remove();
        console.log('wpinv_recalculate_taxes(87)');
        wpinv_recalculate_taxes($(this).val());
    });
    
    var WPInv_Checkout = {
        checkout_form: $( 'form#wpinv_checkout_form' ),
        init: function() {
            if (!$(this.checkout_form).length) {
                return;
            }
            // Payment methods
            this.checkout_form.on( 'click', 'input[name="wpi-gateway"]', this.payment_method_selected );
            this.init_payment_methods();
            this.recalculate_taxes();
        },
        init_payment_methods: function() {
			var $payment_methods = $( '.wpi-payment_methods input[name="wpi-gateway"]' );

			// If there is one method, we can hide the radio input
			if ( 1 === $payment_methods.length ) {
				$payment_methods.eq(0).hide();
			}

			// If there are none selected, select the first.
			if ( 0 === $payment_methods.filter( ':checked' ).length ) {
				$payment_methods.eq(0).prop( 'checked', true );
			}

			// Trigger click event for selected method
			$payment_methods.filter( ':checked' ).eq(0).trigger( 'click' );
		},
        payment_method_selected: function() {
            if ( $( '.wpi-payment_methods input.wpi-pmethod' ).length > 1 ) {
                var target_payment_box = $( 'div.payment_box.' + $( this ).attr( 'ID' ) );

                if ( $( this ).is( ':checked' ) && ! target_payment_box.is( ':visible' ) ) {
                    $( 'div.payment_box' ).filter( ':visible' ).slideUp( 250 );

                    if ( $( this ).is( ':checked' ) ) {
                        var content = $( 'div.payment_box.' + $( this ).attr( 'ID' ) ).html();
                        content = content ? content.trim() : '';
                        if (content) {
                            $( 'div.payment_box.' + $( this ).attr( 'ID' ) ).slideDown( 250 );
                        }
                    }
                }
            } else {
                $( 'div.payment_box' ).show();
            }

            if ( $( this ).data( 'button-text' ) ) {
                $( '#wpinv-payment-button' ).val( $( this ).data( 'button-text' ) );
            } else {
                $( '#wpinv-payment-button' ).val( $( '#wpinv-payment-button' ).data( 'value' ) );
            }
        },
        recalculate_taxes: function() {
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
        }
    });
}

function wpinvRemoveQueryVar(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts= url.split('?');
    var urlparts2= url.split('&');
    
    if (urlparts.length >= 2) {
        var prefix= encodeURIComponent(parameter) + '=';
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {    
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {  
                pars.splice(i, 1);
            }
        }

        url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
        return url;
    } else if (urlparts2.length >= 2) {
        var prefix= encodeURIComponent(parameter) + '=';
        var pars= url.split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {    
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {  
                pars.splice(i, 1);
            }
        }

        url= pars.join('&');
        return url;
    } else {
        return url;
    }
}