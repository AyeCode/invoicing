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

                <div class="bsui" style="margin-top: 1.5rem">

                    <?php do_action( 'getpaid_invoice_edit_before_viewed_by_customer', $invoice ); ?>
                    <?php if ( ! $invoice->is_draft() ) : ?>
                        <div class="form-group">
                            <strong><?php _e( 'Viewed by Customer:', 'invoicing' );?></strong>
                            <?php ( $invoice->get_is_viewed() ) ? _e( 'Yes', 'invoicing' ) : _e( 'No', 'invoicing' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php

                        // Date created.
                        $label = sprintf(
                            __( '%s Date:', 'invoicing' ),
                            ucfirst( $invoice->get_invoice_quote_type() )
                        );

                        $info  = sprintf(
                            __( 'The date this %s was created.', 'invoicing' ),
                            strtolower( $invoice->get_invoice_quote_type() )
                        );

                        echo aui()->input(
                            array(
                                'type'        => 'datepicker',
                                'id'          => 'wpinv_date_created',
                                'name'        => 'date_created',
                                'label'       => $label . getpaid_get_help_tip( $info ),
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

                        // Date paid.
                        $date_paid = $invoice->get_date_completed( 'edit' );
                        if ( ! empty( $date_paid ) && $invoice->is_paid() ) {

                            echo aui()->input(
                                array(
                                    'type'        => 'text',
                                    'id'          => 'wpinv_date_completed',
                                    'name'        => 'wpinv_date_completed',
                                    'label'       => __( 'Date Completed:', 'invoicing' ),
                                    'label_type'  => 'vertical',
                                    'class'       => 'form-control-sm',
                                    'value'       => $date_paid,
                                )
                            );

                        }

                        // Due date.
                        if ( $invoice->is_type( 'invoice' ) && wpinv_get_option( 'overdue_active' ) && ( ! $invoice->is_paid() || $invoice->is_draft() ) ) {

                            echo aui()->input(
                                array(
                                    'type'        => 'datepicker',
                                    'id'          => 'wpinv_due_date',
                                    'name'        => 'wpinv_due_date',
                                    'label'       => __( 'Due Date:', 'invoicing' ) . getpaid_get_help_tip( __( 'Leave blank to disable automated reminder emails for this invoice.', 'invoicing' ) ),
                                    'label_type'  => 'vertical',
                                    'placeholder' => __( 'No due date', 'invoicing' ),
                                    'class'       => 'form-control-sm',
                                    'value'       => $invoice->get_due_date( 'edit' ),
                                    'extra_attributes' => array(
                                        'data-enable-time' => 'true',
                                        'data-time_24hr'   => 'true',
                                        'data-allow-input' => 'true',
                                        'data-min-date'    => 'today',
                                    ),
                                )
                            );

                        }

                        do_action( 'wpinv_meta_box_details_after_due_date', $invoice->get_id() );
                        do_action( 'getpaid_metabox_after_due_date', $invoice );

                        // Status.
                        $label = sprintf(
                            __( '%s Status:', 'invoicing' ),
                            ucfirst( $invoice->get_invoice_quote_type() )
                        );

                        $status = $invoice->get_status( 'edit' );
                        echo aui()->select(
                            array(
                                'id'               => 'wpinv_status',
                                'name'             => 'wpinv_status',
                                'label'            => $label,
                                'label_type'       => 'vertical',
                                'placeholder'      => __( 'Select Status', 'invoicing' ),
                                'value'            => array_key_exists( $status, $invoice->get_all_statuses() ) ? $status : $invoice->get_default_status(),
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_invoice_statuses( true, false, $invoice )
                            )
                        );

                        // Invoice number.
                        $label = sprintf(
                            __( '%s Number:', 'invoicing' ),
                            ucfirst( $invoice->get_invoice_quote_type() )
                        );

                        $info  = sprintf(
                            __( 'Each %s number must be unique.', 'invoicing' ),
                            strtolower( $invoice->get_invoice_quote_type() )
                        );

                        echo aui()->input(
                            array(
                                'type'        => 'text',
                                'id'          => 'wpinv_number',
                                'name'        => 'wpinv_number',
                                'label'       => $label . getpaid_get_help_tip( $info ),
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

                        if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {

                            // Apply a discount.
                            echo aui()->input(
                                array(
                                    'type'        => 'text',
                                    'id'          => 'wpinv_discount_code',
                                    'name'        => 'wpinv_discount_code',
                                    'label'       => __( 'Discount Code:', 'invoicing' ),
                                    'placeholder' => __( 'Apply Discount', 'invoicing' ),
                                    'label_type'  => 'vertical',
                                    'class'       => 'form-control-sm getpaid-recalculate-prices-on-change',
                                    'value'       => $invoice->get_discount_code( 'edit' ),
                                )
                            );

                        } else if ( $invoice->get_discount_code( 'edit' ) ) {

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
                                    'class'       => 'getpaid-recalculate-prices-on-change',
                                )
                            );

                        }

                        if ( $invoice->is_type( 'invoice' ) ) {

                            // Send to customer.
                            echo aui()->input(
                                array(
                                    'id'          => 'wpinv_send_to_customer',
                                    'name'        => 'send_to_customer',
                                    'type'        => 'checkbox',
                                    'label'       => __( 'Send invoice to customer after saving', 'invoicing' ),
                                    'value'       => '1',
                                    'checked'     => $invoice->is_draft() && (bool) wpinv_get_option( 'email_user_invoice_active', true ),
                                )
                            );

                        }

                        do_action( 'getpaid_metabox_after_invoice_details', $invoice );

                    ?>

                </div>

        <?php
    }
}
