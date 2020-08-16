<?php

/**
 * Invoice Items
 *
 * Display the invoice items meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Items Class.
 */
class GetPaid_Meta_Box_Invoice_Items {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post );

        ?>

        <style>
            #poststuff .input-group-text,
            #poststuff .form-control {
                border-color: #7e8993;
            }

            #wpinv-details label {
                margin-bottom: 3px;
                font-weight: 600;
            }
        </style>

                <div class="bsui" style="margin-top: 1.5rem">

                    <?php if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) : ?>
                        <?php do_action( 'wpinv_meta_box_before_invoice_template_row', $invoice->get_id() ); ?>

                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <?php
                                    echo aui()->select(
                                        array(
                                            'id'          => 'wpinv_template',
                                            'name'        => 'wpinv_template',
                                            'label'       => __( 'Template', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => __( 'Choose a template', 'invoicing' ),
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_template( 'edit' ),
                                            'options'     => array(
                                                'quantity' => __( 'Quantity', 'invoicing' ),
                                                'hours'    => __( 'Hours', 'invoicing' ),
                                                'amount'   => __( 'Amount Only', 'invoicing' ),
                                            ),
                                            'data-allow-clear' => 'false',
                                            'select2'          => true,
                                        )
                                    );
                                ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?php

                                    // Set currency.
                                    echo aui()->select(
                                        array(
                                            'id'          => 'wpinv_currency',
                                            'name'        => 'wpinv_currency',
                                            'label'       => __( 'Currency', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => __( 'Select Invoice Currency', 'invoicing' ),
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_currency( 'edit' ),
                                            'required'    => false,
                                            'data-allow-clear' => 'false',
                                            'select2'          => true,
                                            'options'     => wpinv_get_currencies(),
                                        )
                                    );

                                ?>
                            </div>
                        </div>

                        <?php do_action( 'wpinv_meta_box_invoice_template_row', $invoice->get_id() ); ?>
                    <?php endif; ?>

                </div>

        <?php
    }
}
