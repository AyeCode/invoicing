jQuery(function($) {

    var update_inline_items = function( html ) {
        $('#wpinv_items_wrap').replaceWith( html )
    }

    // Add an existing item to the payment form.
    $('body').on('click', '#wpinv-payment-form-add-item', function(e) {
        e.preventDefault();

        wpinvBlock( $('#wpinv_items_wrap') );

        var data = {
            action: 'wpinv_add_payment_form_item',
            form_id: $('#post_ID').val(),
            item_id: $('#wpinv_payment_form_item').val(),
            _nonce: WPInv_Admin.invoice_item_nonce
        };

        $.post(WPInv_Admin.ajax_url, data, function(response) {

            wpinvUnblock( $('#wpinv_items_wrap') );

            if ( response && typeof response == 'object' ) {

                if (response.success === true) {
                    update_inline_items(response.data);
                } else {
                    alert(response.data);
                }

            }

        })

        .fail( function(response) {
            wpinvUnblock( $('#wpinv_items_wrap') );
        })

    });

    // Toggle the create item metabox.
    $('body').on('click', '#wpinv-payment-form-new-item', function(e) {
        e.preventDefault();
        $('#wpinv-payment-form-quick-add').toggle()
    })

    // Hides the create item metabox.
    $('body').on('click', '#wpinv-payment-form-cancel-item', function(e) {
        e.preventDefault();
        $('#wpinv-payment-form-quick-add').hide()
        $('#wpinv-payment-form-quick-add :input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('')
    })
    
    // Creates a new item and adds it to the payment form.
    $('body').on('click', '#wpinv-payment-form-save-item', function(e) {
        e.preventDefault();

        wpinvBlock( $('#wpinv_items_wrap') );

        var data = {
            action: 'wpinv_create_payment_form_item',
            form_id: $('#post_ID').val(),
            item_name: $('#wpinv_create_payment_form_item_name').val(),
            item_description: $('#wpinv_create_payment_form_item_description').val(),
            item_price: $('#wpinv_create_payment_form_item_price').val(),
            _nonce: WPInv_Admin.invoice_item_nonce
        };

        $.post(WPInv_Admin.ajax_url, data, function(response) {

            wpinvUnblock( $('#wpinv_items_wrap') );

            if ( response && typeof response == 'object' ) {

                if (response.success === true) {
                    update_inline_items(response.data);
                } else {
                    alert(response.data);
                }

            }

        })

        .fail( function(response) {
            wpinvUnblock( $('#wpinv_items_wrap') );
        })

    });

});
