<?php
/**
 * Displays a gateway select input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/gateway_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// The payment methods select title.
if ( empty( $text ) ) {
    $text = __( 'Select Payment Method', 'invoicing' );
}

// An array of active payment methods.
$gateways = wpinv_get_enabled_payment_gateways( true );

// Make it possible to filter active gateways.
$gateways = apply_filters( 'getpaid_payment_form_gateways', $gateways, $form );

//@deprecated
$gateways = apply_filters( 'wpinv_payment_gateways_on_cart', $gateways );

// The current invoice id.
$invoice_id     = 0;
$chosen_gateway = wpinv_get_default_gateway();

if ( ! empty( $form->invoice ) ) {
    $invoice_id = $form->invoice->get_id();
    $chosen_gateway = $form->invoice->get_gateway();
}

?>

    <div class="mt-4 mb-4">

        <?php do_action( 'wpinv_payment_mode_top', $invoice_id, $chosen_gateway, $gateways, $form ); ?>

        <div class="getpaid-gateways-select-title-div">
            <h6><?php echo sanitize_text_field( $text ); ?></h6>
        </div>

        
        <div class="getpaid-gateways-select-gateways-div">

            <?php foreach ( array_keys( $gateways ) as $gateway ) : ?>

                <div class="pt-2 pb-2 getpaid-gateways-select-gateway getpaid-gateways-select-gateway-<?php echo sanitize_html_class( $gateway ) ;?>" data-checkout-label='<?php echo esc_attr( apply_filters( "getpaid_gateway_{$gateway}_checkout_button_label", '' ) ); ?>'>

                    <div class="getpaid-gateway-radio-div">
                        <label>
                            <input type="radio" value="<?php echo esc_attr( $gateway ) ;?>" <?php checked( $gateway, $chosen_gateway ) ;?> name="wpi-gateway">
                            <span><?php echo sanitize_text_field( wpinv_get_gateway_checkout_label( $gateway ) ); ?></span>
                        </label>
                    </div>

                    <div class="getpaid-gateway-description-div" style="display: none;">
                        <?php do_action( 'wpinv_' . $gateway . '_checkout_fields', $invoice_id ) ;?>
                        <?php if ( wpinv_get_gateway_description( $gateway ) ) : ?>
                            <div class="getpaid-gateway-description">
                                <?php echo wpinv_get_gateway_description( $gateway ); ?>
                            </div>
                        <?php endif; ?>

                        <?php do_action( 'getpaid_after_gateway_description', $invoice_id, $gateway, $form ) ;?>
                        <?php do_action( 'wpinv_' . $gateway . '_cc_form', $invoice_id, $form ) ;?>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php


                if ( empty( $gateways ) ) {

                    $enabled_gateways = wpinv_get_enabled_payment_gateways( true );

                    if ( ! empty( $enabled_gateways ) && $form->is_recurring() ) {


                        echo aui()->alert(
                            array(
                                'content'     => __( 'Non of the available payment gateways support subscriptions.', 'invoicing' ),
                                'type'        => 'danger',
                            )
                        );


                    } else {

                        echo aui()->alert(
                            array(
                                'content'     => __( 'No active payment gateway available.', 'invoicing' ),
                                'type'        => 'danger',
                            )
                        );

                    }
                    

                }

            ?>


        </div>

        <?php do_action( 'wpinv_payment_mode_bottom', $invoice_id, $chosen_gateway, $gateways, $form ); ?>

    </div>

