<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

?>
<p><?php _e( 'Hello, a note has just been added to your invoice:', 'invoicing' ); ?></p>
<blockquote class="wpinv-note"><?php echo wpautop( wptexturize( $customer_note ) ) ?></blockquote>
<p><?php _e( 'For your reference, your invoice details are shown below.', 'invoicing' ); ?></p>
<?php
do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );