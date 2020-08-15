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
        global $wpinv_euvat;

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

                    <div class="form-group">
                        <strong><?php _e( 'Viewed by Customer:', 'invoicing' );?></strong>
                        <?php ( $invoice->get_is_viewed() ) ? _e( 'Yes', 'invoicing' ) : _e( 'No', 'invoicing' ); ?>
                    </div>

                    <?php

                        // Date created.
                        echo aui()->input(
                            array(
                                'type'        => 'datepicker',
                                'id'          => 'wpinv_date_created',
                                'name'        => 'date_created',
                                'label'       => __( 'Invoice Date:', 'invoicing' ),
                                'label_type'  => 'vertical',
                                'placeholder' => 'YYYY-MM-DD 00:00',
                                'class'       => 'form-control-sm',
                                'value'       => $invoice->get_date_created( 'edit' ),
                                'extra_attributes' => array(
                                    'data-enable-time' => 'true',
                                    'data-time_24hr'   => 'true',
                                    'data-allow-input' => 'true',
                                ),
                            )
                        );

                        // Due date.
                        if ( $invoice->is_type( 'invoice' ) && wpinv_get_option( 'overdue_active' ) && ( $invoice->needs_payment() || $invoice->has_status( array( 'auto-draft', 'draft' ) ) ) ) {

                            echo aui()->input(
                                array(
                                    'type'        => 'text',
                                    'id'          => 'wpinv_due_date',
                                    'name'        => 'wpinv_due_date',
                                    'label'       => __( 'Due Date:', 'invoicing' ),
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
                                'label'       => __( 'Invoice Number:', 'invoicing' ),
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
                                'label'       => __( 'Email CC:', 'invoicing' ),
                                'label_type'  => 'vertical',
                                'placeholder' => __( 'example@gmail.com, example@yahoo.com', 'invoicing' ),
                                'class'       => 'form-control-sm',
                                'value'       => $invoice->get_email_cc( 'edit' ),
                            )
                        );

                        do_action( 'wpinv_meta_box_details_inner', $invoice->get_id() );

                        // Disable taxes.
                        if ( $wpinv_euvat->allow_vat_rules() && ! ( $invoice->is_paid() || $invoice->is_refunded() ) ) {

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

                        // Send email.
                        echo aui()->select(
                            array(
                                'id'               => 'wpi_save_send',
                                'name'             => 'wpi_save_send',
                                'label'            => __( 'Send Email:', 'invoicing' ),
                                'label_type'       => 'vertical',
                                'placeholder'      => __( 'Select action', 'invoicing' ),
                                'help_text'        => apply_filters('wpinv_metabox_mail_notice', __( 'After saving invoice, send a copy of the invoice to the customer.', 'invoicing' ), $invoice),
                                'value'            => '',
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => array(
                                    '1' => __( 'Yes', 'invoicing' ),
                                    ''  => __( 'No', 'invoicing' ),
                                )
                            )
                        );
                    ?>

                </div>

        <?php
    }
}
