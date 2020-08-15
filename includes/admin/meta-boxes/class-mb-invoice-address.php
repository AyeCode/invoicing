<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Billing_Details {
    public static function output( $post ) {
        global $user_ID;
        $post_id    = !empty( $post->ID ) ? $post->ID : 0;
        $invoice    = new WPInv_Invoice( $post_id );

    }
}
