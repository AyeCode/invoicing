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

// The current invoice id.
$invoice_id     = 0;
$chosen_gateway = wpinv_get_default_gateway();

if ( ! empty( $form->invoice ) ) {
    $invoice_id = $form->invoice->get_id();
    $chosen_gateway = $form->invoice->get_gateway();
}

?>

    <?php do_action( 'getpaid_before_payment_form_gateway_select', $form ); ?>
    <div class="mt-4 mb-4 getpaid-gateways">

        <?php do_action( 'wpinv_payment_mode_top', $invoice_id, $chosen_gateway, $gateways, $form ); ?>

        <div class="getpaid-select-gateway-title-div">
            <h6><?php echo esc_html( $text ); ?></h6>
        </div>

        <div class="getpaid-available-gateways-div">

            <?php foreach ( array_keys( $gateways ) as $gateway ) : ?>

                <div class="pt-1 pb-1 getpaid-gateway getpaid-gateway-<?php echo sanitize_html_class( $gateway ) ;?>" data-checkout-label='<?php echo esc_attr( apply_filters( "getpaid_gateway_{$gateway}_checkout_button_label", '' ) ); ?>'>

                    <label class="d-block w-100 getpaid-gateway-radio">
                        <input type="radio" value="<?php echo esc_attr( $gateway ) ;?>" <?php checked( $gateway, $chosen_gateway ) ;?> name="wpi-gateway">
                        <span><?php echo esc_html( wpinv_get_gateway_checkout_label( $gateway ) ); ?></span>
                    </label>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="getpaid-gateway-descriptions-div">

            <?php foreach ( array_keys( $gateways ) as $gateway ) : ?>

                <div class="my-2 p-3 bg-light border getpaid-gateway-description getpaid-description-<?php echo sanitize_html_class( $gateway ) ;?>" style="display: none;">
                    <?php

                        $description = wpinv_get_gateway_description( $gateway );

                        if ( wpinv_is_test_mode( $gateway ) ) {
                            $sandbox_notice = apply_filters( "getpaid_{$gateway}_sandbox_notice", __( 'SANDBOX ENABLED: No real payments will occur.', 'invoicing' ) );
                            $description = "$description $sandbox_notice";
                        }

                        echo wpautop( wp_kses_post( $description ) );

                        do_action( 'wpinv_' . $gateway . '_checkout_fields', $invoice_id ) ;
                        do_action( 'wpinv_' . $gateway . '_cc_form', $invoice_id, $form ) ;

                    ?>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="getpaid-no-recurring-gateways d-none">
            <?php
                echo aui()->alert(
                    array(
                        'content'     => __( 'None of the available payment gateways support purchasing recurring items.', 'invoicing' ),
                        'type'        => 'danger',
                    )
                );
            ?>
        </div>

        <div class="getpaid-no-subscription-group-gateways d-none">
            <?php
                echo aui()->alert(
                    array(
                        'content'     => __( 'None of the available payment gateways support purchasing multiple subscriptions in a single order.', 'invoicing' ),
                        'type'        => 'danger',
                    )
                );
            ?>
        </div>

        <div class="getpaid-no-multiple-subscription-group-gateways d-none">
            <?php
                echo aui()->alert(
                    array(
                        'content'     => __( 'None of the available payment gateways support purchasing multiple subscriptions with different billing schedules in a single order.', 'invoicing' ),
                        'type'        => 'danger',
                    )
                );
            ?>
        </div>

        <div class="getpaid-no-active-gateways d-none">
            <?php
                echo aui()->alert(
                    array(
                        'content'     => __( 'There is no active payment gateway available to process your request.', 'invoicing' ),
                        'type'        => 'danger',
                    )
                );
            ?>
        </div>

        <?php do_action( 'wpinv_payment_mode_bottom', $invoice_id, $chosen_gateway, $gateways, $form ); ?>

    </div>
    <?php do_action( 'getpaid_after_payment_form_gateway_select', $form ); ?>
