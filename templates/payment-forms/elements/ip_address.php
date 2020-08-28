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

$ip_address = sanitize_text_field( wpinv_get_ip() );
$url        = esc_url( getpaid_ip_location_url( $ip_address ) );

?>
<div class="form-group getpaid-ip-info">
    <span><?php echo wp_kses_post( $text ); ?></span>
    <a target='_blank' href='<?php echo $url; ?>'><?php echo $ip_address; ?>&nbsp;&nbsp;<i class='fa fa-external-link-square' aria-hidden='true'></i></a>
</div>
