(function($) {
    // we create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;

    // and then we overwrite the function with our own code
    inlineEditPost.edit = function( id ) {
        // "call" the original WP edit function
        // we don't want to leave WordPress hanging
        $wp_inline_edit.apply( this, arguments );
        
        // get the post ID
        var $post_id = typeof( id ) == 'object' ? parseInt( this.getId( id ) ) : 0;
            
        if ( $post_id > 0 ) {
            var $wpinvInlineData = $( '#wpinv_inline-' + $post_id );

            var price       = $wpinvInlineData.find( '.price' ).text(),
                vat_rule    = $wpinvInlineData.find( '.vat_rule' ).text(),
                vat_class   = $wpinvInlineData.find( '.vat_class' ).text(),
                item_type   = $wpinvInlineData.find( '.type' ).text();

            $( 'input[name="_wpinv_item_price"]', '.inline-edit-row' ).val( price );
            $( 'select[name="_wpinv_vat_rules"] option[value="' + vat_rule + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
            $( 'select[name="_wpinv_vat_class"] option[value="' + vat_class + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
            $( 'select[name="_wpinv_item_type"] option[value="' + item_type + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
        }
    };
})(jQuery);