<?php
/**
 * Displays the ip address setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/ip_address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<span>{{form_element.text}}</span>
<a target='_blank' href="#">
    <?php echo esc_html( wpinv_get_ip() ); ?>&nbsp;&nbsp;<i class='fa fa-external-link-square' aria-hidden='true'></i>
</a>
