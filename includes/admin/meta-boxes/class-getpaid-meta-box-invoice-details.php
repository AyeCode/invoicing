<?php

/**
 * Invoice Details
 *
 * Display the invoice data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Details Class.
 */
class GetPaid_Meta_Box_Invoice_Details {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Nonce field.
        wp_nonce_field( 'wpinv_details', 'wpinv_details_nonce' ) ;


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

                <div class="bsui" style="margin-top: 1.5rem" id="gdmbx2-metabox-wpinv_details">

                    <?php if ( ! $invoice->is_draft() ) : ?>
                        <div class="form-group">
                            <strong><?php _e( 'Viewed by Customer:', 'invoicing' );?></strong>
                            <?php ( $invoice->get_is_viewed() ) ? _e( 'Yes', 'invoicing' ) : _e( 'No', 'invoicing' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php

                        // Date created.
                        echo aui()->input(
                            array(
                                'type'        => 'datepicker',
                                'id'          => 'wpinv_date_created',
                                'name'        => 'date_created',
                                'label'       => __( 'Invoice Date:', 'invoicing' ) . getpaid_get_help_tip( __( 'The date this invoice was created. This allows you to backdate an invoice.', 'invoicing' ) ),
                                'label_type'  => 'vertical',
                                'placeholder' => 'YYYY-MM-DD 00:00',
                                'class'       => 'form-control-sm',
                                'value'       => $invoice->get_date_created( 'edit' ),
                                'extra_attributes' => array(
                                    'data-enable-time' => 'true',
                                    'data-time_24hr'   => 'true',
                                    'data-allow-input' => 'true',
                                    'data-max-date'    => 'today',
                                ),
                            )
                        );

                        // Due date.
                        if ( $invoice->is_type( 'invoice' ) && wpinv_get_option( 'overdue_active' ) && ( $invoice->needs_payment() || $invoice->is_draft() ) ) {

                            echo aui()->input(
                                array(
                                    'type'        => 'text',
                                    'id'          => 'wpinv_due_date',
                                    'name'        => 'wpinv_due_date',
                                    'label'       => __( 'Due Date:', 'invoicing' ) . getpaid_get_help_tip( __( 'Leave blank to disable automated reminder emails for this invoice.', 'invoicing' ) ),
                                    'label_type'  => 'vertical',
                                    'placeholder' => __( 'No due date', 'invoicing' ),
                                    'class'       => 'form-control-sm',
                                    'value'       => $invoice->get_due_date( 'edit' ),
                                )
                            );

                        }

                        do_action( 'wpinv_meta_box_details_after_due_date', $invoice->get_id() );
                        
                        // Status.
                        echo aui()->select(
                            array(
                                'id'               => 'wpinv_status',
                                'name'             => 'wpinv_status',
                                'label'            => __( 'Invoice Status:', 'invoicing' ),
                                'label_type'       => 'vertical',
                                'placeholder'      => __( 'Select Status', 'invoicing' ),
                                'value'            => $invoice->get_status( 'edit' ),
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_invoice_statuses( true )
                            )
                        );

                        // Invoice number.
                        echo aui()->input(
                            array(
                                'type'        => 'text',
                                'id'          => 'wpinv_number',
                                'name'        => 'wpinv_number',
                                'label'       => __( 'Invoice Number:', 'invoicing' ) . getpaid_get_help_tip( __( 'Each invoice number must be unique.', 'invoicing' ) ),
                                'label_type'  => 'vertical',
                                'placeholder' => __( 'Autogenerate', 'invoicing' ),
                                'class'       => 'form-control-sm',
                                'value'       => $invoice->get_number( 'edit' ),
                            )
                        );

                        // Invoice cc.
                        echo aui()->input(
                            array(
                                'type'        => 'text',
                                'id'          => 'wpinv_cc',
                                'name'        => 'wpinv_cc',
                                'label'       => __( 'Email CC:', 'invoicing' ) . getpaid_get_help_tip( __( 'Enter a comma separated list of other emails that should be notified about the invoice.', 'invoicing' ) ),
                                'label_type'  => 'vertical',
                                'placeholder' => __( 'example@gmail.com, example@yahoo.com', 'invoicing' ),
                                'class'       => 'form-control-sm',
                                'value'       => $invoice->get_email_cc( 'edit' ),
                            )
                        );

                        do_action( 'wpinv_meta_box_details_inner', $invoice->get_id() );

                        // Disable taxes.
                        if ( wpinv_use_taxes() && ! ( $invoice->is_paid() || $invoice->is_refunded() ) ) {

                            echo aui()->input(
                                array(
                                    'id'          => 'wpinv_taxable',
                                    'name'        => 'disable_taxes',
                                    'type'        => 'checkbox',
                                    'label'       => __( 'Disable taxes', 'invoicing' ),
                                    'value'       => '1',
                                    'checked'     => (bool) $invoice->get_disable_taxes(),
                                )
                            );

                        }

                        // Apply a discount.
                        if ( $invoice->get_discount_code( 'edit' ) ) {

                            echo aui()->input(
                                array(
                                    'type'        => 'text',
                                    'id'          => 'wpinv_discount_code',
                                    'name'        => 'wpinv_discount_code',
                                    'label'       => __( 'Discount Code:', 'invoicing' ),
                                    'label_type'  => 'vertical',
                                    'class'       => 'form-control-sm',
                                    'value'       => $invoice->get_discount_code( 'edit' ),
                                    'extra_attributes' => array(
                                        'onclick'  => 'this.select();',
                                        'readonly' => 'true',
                                    ),
                                )
                            );

                        }

                    ?>

                </div>

        <?php
    }
}
