<?php
/**
 * Displays a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/form.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Make sure that the form is active.
if ( ! $form->is_active() ) {
    echo aui()->alert(
        array(
            'type'    => 'warning',
            'content' => __( 'This payment form is no longer active', 'invoicing' ),
        )
    );
    return;
}

// Require login to checkout.
if ( wpinv_require_login_to_checkout() && ! get_current_user_id() ) {

    echo aui()->alert(
        array(
            'type'    => 'danger',
            'content' => __( 'You must be logged in to checkout.', 'invoicing' ),
        )
    );
    return;

}

// Fires before displaying a payment form.
do_action( 'getpaid_before_payment_form', $form );
?>

<form class='getpaid-payment-form getpaid-payment-form-<?php echo absint( $form->get_id() ); ?> bsui position-relative' method='POST' data-key='<?php echo uniqid('gpf'); ?>' data-currency='<?php echo esc_attr( empty( $form->invoice ) ? wpinv_get_currency() : $form->invoice->get_currency() ); ?>' novalidate>

    <?php
    
        // Fires when printing the top of a payment form.
        do_action( 'getpaid_payment_form_top', $form );

        // And the optional invoice id.
        if ( ! empty( $form->invoice ) ) {
            echo getpaid_hidden_field( 'invoice_id', $form->invoice->get_id() );
        }

        // We also want to include the form id.
        echo getpaid_hidden_field( 'form_id', $form->get_id() );

        // And an indication that this is a payment form submission.
        echo getpaid_hidden_field( 'getpaid_payment_form_submission', '1' );

        // Fires before displaying payment form elements.
        do_action( 'getpaid_payment_form_before_elements', $form );

        // Display the elements.
        ?>
        <div class="container-fluid">
            <div class="row">
                <?php

                    foreach ( $form->get_elements() as $element ) {

                        if ( isset( $element['type'] ) ) {
                            $grid_class = esc_attr( getpaid_get_form_element_grid_class( $element ) );
                            echo "<div class='$grid_class'>";
                            do_action( 'getpaid_payment_form_element', $element, $form );
                            do_action( "getpaid_payment_form_element_{$element['type']}_template", $element, $form );
                            echo "</div>";
                        }

                    }

                ?>
            </div>
        </div>

        <?php
        // Fires after displaying payment form elements.
        do_action( 'getpaid_payment_form_after_elements', $form );

        echo "<div class='getpaid-payment-form-errors alert alert-danger d-none'></div>";

        if ( wpinv_current_user_can_manage_invoicing() ) {

            edit_post_link(
                __( 'Edit this form.', 'invoicing' ),
                '<small class="form-text text-muted">',
                '&nbsp;' . __( 'This is only visible to website administators.', 'invoicing' ) . '</small>',
                $form->get_id(),
                'text-danger'
            );

        }

        echo $extra_markup;
    ?>

    <div class="loading_div overlay overlay-black position-absolute row m-0 rounded overflow-hidden" style="height: 100%;width: 100%;top: 0px;z-index: 2;display:none;">
        <div class="spinner-border mx-auto align-self-center text-white" role="status">
            <span class="sr-only"><?php _e( 'Loading...', 'invoicing' );?></span>
        </div>
    </div>

</form>

<?php

// Fires after displaying a payment form.
do_action( 'getpaid_after_payment_form', $form );
