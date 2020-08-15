<?php

/**
 * Invoice Address
 *
 * Display the invoice address meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Address Class.
 */
class GetPaid_Meta_Box_Invoice_Address {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post );

        wp_nonce_field( 'wpinv_save_invoice', 'wpinv_save_invoice' )

        ?>

        <style>
            #gdmbx2-metabox-wpinv_address label {
                margin-bottom: 3px;
                font-weight: 600;
            }
        </style>
            <div class="bsui" style="margin-top: 1.5rem; max-width: 820px;">
                <div id="gdmbx2-metabox-wpinv_address">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="gdmbx-wpinv-user-id form-group">
                                <div>
                                    <label for="post_author_override"><?php _e( 'Customer', 'invoicing' );?></label>
                                </div>
                                <?php 
                                    wpinv_dropdown_users(
                                        array(
                                            'name'             => 'post_author_override',
                                            'selected'         => $invoice->get_id() ? $invoice->get_user_id( 'edit' ) : get_current_user_id(),
                                            'include_selected' => true,
                                            'show'             => 'display_name_with_email',
                                            'orderby'          => 'user_email',
                                            'class'            => 'wpi_select2 form-control'
                                        )
                                    );
                                ?>
                            </div>

                            <div class="gdmbx-wpinv-email" style="display: none;">
                                <input type="hidden" id="wpinv_new_user" name="wpinv_new_user" value="" />
                                <?php
                                    echo aui()->input(
                                        array(
                                            'type'        => 'email',
                                            'id'          => 'wpinv_email',
                                            'name'        => 'wpinv_email',
                                            'label'       => __( 'Email', 'invoicing' ) . '<span class="required">*</span>',
                                            'label_type'  => 'vertical',
                                            'placeholder' => 'john@doe.com',
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_state( 'edit' ),
                                        )
                                    );
                                ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 form-group mt-sm-4">
                            <?php if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) : ?>
                                <a id="wpinv-fill-user-details" class="button button-small button-secondary" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-refresh"></i>
                                    <?php _e( 'Fill User Details', 'invoicing' );?>
                                </a>
                                <a class="wpinv-new-user button button-small button-secondary" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-plus"></i>
                                    <?php _e( 'Add New User', 'invoicing' );?>
                                </a>
                                <a style="display:none" class="wpinv-new-cancel button button-small button-secondary" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-close"></i>
                                    <?php _e( 'Cancel', 'invoicing' );?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_first_name',
                                        'name'        => 'wpinv_first_name',
                                        'label'       => __( 'First Name', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Jane',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_first_name( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_last_name',
                                        'name'        => 'wpinv_last_name',
                                        'label'       => __( 'Last Name', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Doe',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_last_name( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_company',
                                        'name'        => 'wpinv_company',
                                        'label'       => __( 'Company', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Acme Corporation',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_company( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_vat_number',
                                        'name'        => 'wpinv_vat_number',
                                        'label'       => __( 'Vat Number', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '1234567890',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_vat_number( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_address',
                                        'name'        => 'wpinv_address',
                                        'label'       => __( 'Address', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Blekersdijk 295',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_address( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_city',
                                        'name'        => 'wpinv_city',
                                        'label'       => __( 'City', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Dolembreux',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_vat_number( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->select(
                                    array(
                                        'id'          => 'wpinv_country',
                                        'name'        => 'wpinv_country',
                                        'label'       => __( 'Country', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => __( 'Choose a country', 'invoicing' ),
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_country( 'edit' ),
                                        'options'     => wpinv_get_country_list(),
                                        'data-allow-clear' => 'false',
                                        'select2'          => true,
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php

                                $states = wpinv_get_country_states( $invoice->get_country( 'edit' ) );

                                if ( empty( $states ) ) {

                                    echo aui()->input(
                                        array(
                                            'type'        => 'text',
                                            'id'          => 'wpinv_state',
                                            'name'        => 'wpinv_state',
                                            'label'       => __( 'State', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => 'Liège',
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_state( 'edit' ),
                                        )
                                    );

                                } else {

                                    echo aui()->select(
                                        array(
                                            'id'          => 'wpinv_state',
                                            'name'        => 'wpinv_state',
                                            'label'       => __( 'State', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => __( 'Select a state', 'invoicing' ),
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_state( 'edit' ),
                                            'options'     => $states,
                                            'data-allow-clear' => 'false',
                                            'select2'          => true,
                                        )
                                    );

                                }
                                
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_zip',
                                        'name'        => 'wpinv_zip',
                                        'label'       => __( 'Zip / Postal Code', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '4140',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_zip( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_phone',
                                        'name'        => 'wpinv_phone',
                                        'label'       => __( 'Phone', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '0493 18 45822',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_phone( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php
    }
}
