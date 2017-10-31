<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

if(isset($message_body) && !empty($message_body)) {
    echo wpautop(wptexturize($message_body));
}

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );