<?php

/**
 * Invoice Payment Meta
 *
 * Display the invoice data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Payment_Meta Class.
 */
class GetPaid_Meta_Box_Invoice_Payment_Meta {

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
            #wpinv-payment-meta label {
                margin-bottom: 3px;
                font-weight: 600;
            }
        </style>
                <div class="bsui" style="margin-top: 1.5rem">

                    <div class="wpinv-payment-meta">

                    <?php

                        if ( $invoice->is_draft() ) {

							// Set gateway.
							aui()->select(
								array(
									'id'               => 'wpinv_gateway',
									'name'             => 'wpinv_gateway',
									'label'            => __( 'Gateway:', 'invoicing' ),
									'label_type'       => 'vertical',
									'placeholder'      => __( 'Select Gateway', 'invoicing' ),
									'value'            => wpinv_get_default_gateway(),
									'select2'          => true,
									'data-allow-clear' => 'false',
									'options'          => wp_list_pluck( wpinv_get_enabled_payment_gateways( true ), 'admin_label' ),
								),
								true
							);

                        } else {

							// Invoice key.
							aui()->input(
								array(
									'type'             => 'text',
									'id'               => 'wpinv_key',
									'name'             => 'wpinv_key',
									'label'            => sprintf(
										// translators: %s: Invoice type.
										__( '%s Key:', 'invoicing' ),
										ucfirst( $invoice->get_invoice_quote_type() )
									),
									'label_type'       => 'vertical',
									'class'            => 'form-control-sm',
									'value'            => $invoice->get_key( 'edit' ),
									'extra_attributes' => array(
										'onclick'  => 'this.select();',
										'readonly' => 'true',
									),
								),
								true
							);

							// View URL.
							aui()->input(
								array(
									'type'             => 'text',
									'id'               => 'wpinv_view_url',
									'name'             => 'wpinv_view_url',
									'label'            => sprintf(
										// translators: %s: Invoice type.
										__( '%s URL:', 'invoicing' ),
										ucfirst( $invoice->get_invoice_quote_type() )
									) . '&nbsp;<a href="' . esc_url_raw( $invoice->get_view_url() ) . '" title="' . __( 'View invoice', 'invoicing' ) . '" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i></a>',
									'label_type'       => 'vertical',
									'class'            => 'form-control-sm',
									'value'            => $invoice->get_view_url(),
									'extra_attributes' => array(
										'onclick'  => 'this.select();',
										'readonly' => 'true',
									),
								),
								true
							);

							// If the invoice is paid...
							if ( $invoice->is_paid() || $invoice->is_refunded() ) {

								// Gateway.
								aui()->input(
									array(
										'type'             => 'text',
										'id'               => 'wpinv_gateway',
										'name'             => '',
										'label'            => __( 'Gateway:', 'invoicing' ),
										'label_type'       => 'vertical',
										'class'            => 'form-control-sm',
										'value'            => wpinv_get_gateway_admin_label( $invoice->get_gateway( 'edit' ) ),
										'extra_attributes' => array(
											'onclick'  => 'this.select();',
											'readonly' => 'true',
										),
									),
									true
								);

								// Transaction ID.
								$transaction_url = $invoice->get_transaction_url();
								aui()->input(
									array(
										'type'             => 'text',
										'id'               => 'wpinv_transaction_id',
										'name'             => 'wpinv_transaction_id',
										'label'            => __( 'Transaction ID:', 'invoicing' ) . ( $transaction_url ? '&nbsp;<a href="' . esc_url( $transaction_url ) . '" title="' . __( 'View details', 'invoicing' ) . '" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i></a>' : '' ),
										'label_type'       => 'vertical',
										'class'            => 'form-control-sm',
										'value'            => $invoice->get_transaction_id( 'edit' ),
										'help_text'        => apply_filters( 'wpinv_invoice_transaction_link_' . $invoice->get_gateway( 'edit' ), '', $invoice->get_transaction_id(), $invoice ),
										'extra_attributes' => array(
											'onclick'  => 'this.select();',
											'readonly' => 'true',
										),
									),
									true
								);

								// Currency.
								aui()->input(
									array(
										'type'             => 'text',
										'id'               => 'wpinv_currency',
										'name'             => 'wpinv_currency',
										'label'            => __( 'Currency:', 'invoicing' ),
										'label_type'       => 'vertical',
										'class'            => 'form-control-sm',
										'value'            => $invoice->get_currency( 'edit' ),
										'extra_attributes' => array(
											'onclick'  => 'this.select();',
											'readonly' => 'true',
										),
									),
									true
								);

							} else {

								if ( 'wpi_invoice' === $invoice->get_post_type() ) {

									// Payment URL.
									aui()->input(
										array(
											'type'             => 'text',
											'id'               => 'wpinv_payment_url',
											'name'             => 'wpinv_payment_url',
											'label'            => __( 'Payment URL:', 'invoicing' ),
											'label_type'       => 'vertical',
											'class'            => 'form-control-sm',
											'value'            => $invoice->get_checkout_payment_url(),
											'extra_attributes' => array(
												'onclick'  => 'this.select();',
												'readonly' => 'true',
											),
										),
										true
									);

									// Set gateway.
									aui()->select(
										array(
											'id'               => 'wpinv_gateway',
											'name'             => 'wpinv_gateway',
											'label'            => __( 'Gateway:', 'invoicing' ),
											'label_type'       => 'vertical',
											'placeholder'      => __( 'Select Gateway', 'invoicing' ),
											'value'            => $invoice->get_gateway( 'edit' ),
											'select2'          => true,
											'data-allow-clear' => 'false',
											'options'          => wp_list_pluck( wpinv_get_enabled_payment_gateways( true ), 'admin_label' ),
										),
										true
									);

								}
							}
                        }
                    ?>
                    </div>
                </div>

        <?php
    }
}
