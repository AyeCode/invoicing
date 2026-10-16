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

                <div class="pt-1 pb-1 getpaid-gateway getpaid-gateway-<?php echo esc_attr( $gateway ); ?>" data-checkout-label='<?php echo esc_attr( apply_filters( "getpaid_gateway_{$gateway}_checkout_button_label", '' ) ); ?>'>

                    <label class="form-label d-block w-100 getpaid-gateway-radio">
                        <input type="radio" value="<?php echo esc_attr( $gateway ); ?>" <?php checked( $gateway, $chosen_gateway ); ?> name="wpi-gateway">
                        <span><?php echo esc_html( wpinv_get_gateway_checkout_label( $gateway ) ); ?></span>
                    </label>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="getpaid-gateway-descriptions-div">

            <?php foreach ( array_keys( $gateways ) as $gateway ) : ?>

                <div class="my-2 p-3 bg-light border getpaid-gateway-description getpaid-description-<?php echo esc_attr( $gateway ); ?>" style="display: none;">
                    <?php

                        $description = wpinv_get_gateway_description( $gateway );

                        if ( wpinv_is_test_mode( $gateway ) ) {
						$sandbox_notice = apply_filters( "getpaid_{$gateway}_sandbox_notice", __( 'SANDBOX ENABLED: No real payments will occur.', 'invoicing' ) );
						$description = "$description $sandbox_notice";
                        }

                        echo wp_kses_post( wpautop( $description ) );

                        do_action( 'wpinv_' . $gateway . '_checkout_fields', $invoice_id );
                        do_action( 'wpinv_' . $gateway . '_cc_form', $invoice_id, $form );

                    ?>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="getpaid-no-recurring-gateways d-none">
            <?php
                aui()->alert(
                    array(
                        'content' => __( 'None of the available payment gateways support purchasing recurring items.', 'invoicing' ),
                        'type'    => 'danger',
                    ),
                    true
                );
            ?>
        </div>

        <div class="getpaid-no-subscription-group-gateways d-none">
            <?php
                aui()->alert(
                    array(
                        'content' => __( 'None of the available payment gateways support purchasing multiple subscriptions in a single order.', 'invoicing' ),
                        'type'    => 'danger',
                    ),
                    true
                );
            ?>
        </div>

        <div class="getpaid-no-multiple-subscription-group-gateways d-none">
            <?php
                aui()->alert(
                    array(
                        'content' => __( 'None of the available payment gateways support purchasing multiple subscriptions with different billing schedules in a single order.', 'invoicing' ),
                        'type'    => 'danger',
                    ),
                    true
                );
            ?>
        </div>

        <div class="getpaid-no-active-gateways d-none">
            <?php
                // Non-admins see nothing when the admin-only Test Gateway is the only one enabled.
                $test_gateway_only = (int) wpinv_get_option( 'manual_active', true ) === 1
                    && (bool) wpinv_get_option( 'manual_admins_only', true )
                    && ! wpinv_current_user_can_manage_invoicing();

                if ( $test_gateway_only ) {
                    foreach ( array_keys( wpinv_get_payment_gateways() ) as $gateway_id ) {
                        if ( 'manual' !== $gateway_id && (int) wpinv_get_option( "{$gateway_id}_active", false ) === 1 ) {
                            $test_gateway_only = false;
                            break;
                        }
                    }
                }

                if ( $test_gateway_only ) {
                    $settings_url  = add_query_arg(
                        array(
                            'page'    => 'wpinv-settings',
                            'tab'     => 'gateways',
                            'section' => 'manual',
                        ),
                        admin_url( 'admin.php' )
                    );
                    $restrict_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Restrict to admins', 'invoicing' ) . '</a>';
                    /* translators: %s: link to the "Restrict to admins" setting. */
                    $content = sprintf( __( 'The Test Gateway is currently restricted to admins. Turn off %s or log in as an administrator to use it.', 'invoicing' ), $restrict_link );
                    $type    = 'info';
                } else {
                    $content = __( 'There is no active payment gateway available to process your request.', 'invoicing' );
                    $type    = 'danger';
                }

                aui()->alert(
                    array(
                        'content' => $content,
                        'type'    => $type,
                    ),
                    true
                );
            ?>
        </div>

        <?php do_action( 'wpinv_payment_mode_bottom', $invoice_id, $chosen_gateway, $gateways, $form ); ?>

    </div>
    <?php do_action( 'getpaid_after_payment_form_gateway_select', $form ); ?>
