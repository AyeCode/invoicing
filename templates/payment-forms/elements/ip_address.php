<?php
/**
 * Displays the ip address in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/ip_address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $text ) ) {
    $text = __( 'Your IP address is:', 'invoicing' );
}

$ip_address = esc_html( wpinv_get_ip() );

?>
<div class="form-group getpaid-ip-info">
    <span><?php echo wp_kses_post( $text ); ?></span>
    <strong><?php echo $ip_address; ?></strong>
</div>
