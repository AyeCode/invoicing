jQuery(function($) {

    console.log( wpinvPaymentFormAdmin )

    // Init our vue app
    var vm = new Vue({

        el: '#wpinv-form-builder',

        data: $.extend(
            true,
            {
                active_tab: 'new_item',
                active_form_element: null,
            },
            wpinvPaymentFormAdmin
        ),

        methods: {

            // Highlights a field for editing.
            highlightField(field) {
                this.active_tab = 'edit_item'
                this.active_form_element = field
            },

            // Returns the data for a new field.
            getNewFieldData(field) {

                // Let's generate a unique string to use as the field key.
                var rand = Math.random() + this.form_elements.length
                var key = rand.toString(36).replace(/[^a-z]+/g, '')

                var new_field  = $.extend(true, {}, field.defaults)
                new_field.id   = key
                new_field.name = key
                new_field.type = field.type
                this.highlightField(new_field)
                
                return new_field
            },

            // Adds a field that has been dragged to the list of fields.
            addDraggedField(field) {
                return this.getNewFieldData(field)
            },

            // Pushes a field to the list of fields.
            addField(field) {
                this.form_elements.push( this.getNewFieldData(field) )
            },

        }

    })

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
