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

        // Invoice items.
        $items = $invoice->get_items();

        // New item url.
        $new_item = admin_url( 'post-new.php?post_type=wpi_item' );

        // Totals.
        $total = wpinv_price( $invoice->get_total(), $invoice->get_currency() );

        if ( $invoice->is_recurring() && $invoice->is_parent() && $invoice->get_total() != $invoice->get_recurring_total() ) {
            $recurring_total = wpinv_price( $invoice->get_recurring_total(), $invoice->get_currency() );
            $total          .= '<small class="form-text text-muted">' . sprintf( __( 'Recurring Price: %s', 'invoicing' ), $recurring_total ) . '</small>';
        }

        $totals = array(

            'subtotal'  => array(
                'label' => __( 'Items Subtotal', 'invoicing' ),
                'value' => wpinv_price( $invoice->get_subtotal(), $invoice->get_currency() ),
            ),

            'discount'  => array(
                'label' => __( 'Total Discount', 'invoicing' ),
                'value' => wpinv_price( $invoice->get_total_discount(), $invoice->get_currency() ),
            ),

            'tax'       => array(
                'label' => __( 'Total Tax', 'invoicing' ),
                'value' => wpinv_price( $invoice->get_total_tax(), $invoice->get_currency() ),
            ),

            'total'     => array(
                'label' => __( 'Invoice Total', 'invoicing' ),
                'value' => $total,
            )
        );


        if ( ! wpinv_use_taxes() ) {
            unset( $totals['tax'] );
        }
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

                <div class="bsui getpaid-invoice-items-inner <?php echo sanitize_html_class( $invoice->get_template( 'edit' ) ); ?> <?php echo empty( $items ) ? 'no-items' : 'has-items'; ?> <?php echo $invoice->is_paid() || $invoice->is_refunded() ? 'not-editable' : 'editable'; ?>" style="margin-top: 1.5rem">

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

                    <table cellpadding="0" cellspacing="0" class="getpaid_invoice_items">
                        <thead>
                            <tr>
                                <th class="getpaid-item" colspan="2"><?php _e( 'Item', 'invoicing' ) ?></th>
                                <th class="getpaid-quantity hide-if-amount text-right">
                                    <span class="getpaid-hide-if-hours"><?php _e( 'Quantity', 'invoicing' ) ?></span>
                                    <span class="getpaid-hide-if-quantity"><?php _e( 'Hours', 'invoicing' ) ?></span>
                                </th>
                                <th class="getpaid-price hide-if-amount text-right">
                                    <span class="getpaid-hide-if-hours"><?php _e( 'Price', 'invoicing' ) ?></span>
                                    <span class="getpaid-hide-if-quantity"><?php _e( 'Rate', 'invoicing' ) ?></span>
                                </th>
                                <th class="getpaid-item-subtotal text-right">
                                    <span class="getpaid-hide-if-hours getpaid-hide-if-quantity"><?php _e( 'Amount', 'invoicing' ) ?></span>
                                    <span class="hide-if-amount"><?php _e( 'Total', 'invoicing' ) ?></span>
                                </th>
                                <th class="getpaid-item-actions hide-if-not-editable" width="70px">&nbsp;</th>
                            </tr>
                        </thead>
		                <tbody class="getpaid_invoice_line_items">
                            <tr class="hide-if-has-items hide-if-not-editable">
                                <td colspan="2" class="pt-4 pb-4">
                                    <button type="button" class="button button-primary add-invoice-item" data-toggle="modal" data-target="#getpaid-add-items-to-invoice"><?php _e( 'Add Existing Items', 'invoicing' ) ?></button>
	                                <a href="<?php echo esc_url( $new_item ); ?>" target="_blank" class="button button-secondary"><?php _e( 'Create New Item', 'invoicing' ) ?></a>
                                </td>
                                <td class="hide-if-amount">&nbsp;</th>
                                <td class="hide-if-amount">&nbsp;</th>
                                <td>&nbsp;</th>
                                <td width="1%">&nbsp;</th>
                            </tr>
                            <tr class="getpaid-invoice-item-template d-none">
                                <td class="getpaid-item" colspan="2">
                                    <span class='item-name'></span>
                                    <small class="form-text text-muted item-description"></small>
                                </td>
                                <td class="getpaid-quantity hide-if-amount text-right item-quantity"></td>
                                <td class="getpaid-price hide-if-amount text-right item-price"></td>
                                <td class="getpaid-item-subtotal text-right">
                                    <span class="getpaid-hide-if-hours getpaid-hide-if-quantity item-price"></span>
                                    <span class="hide-if-amount item-total"></span>
                                </td>
                                <td class="getpaid-item-actions hide-if-not-editable" width="70px">
                                    <span class="dashicons dashicons-edit"></span>
                                    <span class="dashicons dashicons-trash"></span>
                                </td>
                            </tr>

                        </tbody>
                    </table>

                    <div class="getpaid-invoice-totals-row">
                        <div class="row">
                            <div class="col-12 col-sm-6 offset-sm-6">
                                <table class="getpaid-invoice-totals text-right w-100">
                                    <tbody>
                                        <?php foreach ( apply_filters( 'getpaid_invoice_subtotal_rows', $totals, $invoice ) as $key => $data ) : ?>
                                            <tr class="getpaid-totals-<?php echo sanitize_html_class( $key ); ?>">
                                                <td class="label"><?php echo sanitize_text_field( $data['label'] ) ?>:</td>
                                                <td width="1%"></td>
                                                <td class="value"><?php echo wp_kses_post( $data['value'] ) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="getpaid-invoice-item-actions hide-if-no-items hide-if-not-editable">
                        <div class="row">
                            <div class="text-left col-12 col-sm-8">
                                <button type="button" class="button add-invoice-item" data-toggle="modal" data-target="#getpaid-add-items-to-invoice"><?php _e( 'Add Existing Item', 'invoicing' ) ?></button>
                                <a href="<?php echo esc_url( $new_item ); ?>" target="_blank" class="button button-secondary"><?php _e( 'Create New Item', 'invoicing' ) ?></a>
                                <?php do_action( 'getpaid-invoice-items-actions', $invoice ); ?>
                            </div>
                            <div class="text-right col-12 col-sm-4">
                                <button type="button" class="button button-primary recalculate-totals-button"><?php _e( 'Recalculate Totals', 'invoicing' ) ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="getpaid-invoice-item-actions hide-if-editable">
                        <p class="description m-2 text-right text-muted"><?php _e( 'This invoice is no longer editable', 'invoicing' ); ?></p>
                    </div>

                    <!-- Add items to an invoice -->
                    <div class="modal fade" id="getpaid-add-items-to-invoice" tabindex="-1" role="dialog" aria-labelledby="getpaid-add-item-to-invoice-label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="getpaid-add-item-to-invoice-label"><?php _e( "Add Item(s)", 'invoicing' ); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php _e( "Close", 'invoicing' ); ?>">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <table class="widefat">
                                        <thead>
                                            <tr>
                                                <th class="pl-0 text-left"><?php _e( 'Item', 'invoicing' ) ?></th>
                                                <th class="pr-0 text-right hide-if-amount">
                                                    <span class="getpaid-hide-if-hours"><?php _e( 'Quantity', 'invoicing' ) ?></span>
                                                    <span class="getpaid-hide-if-quantity"><?php _e( 'Hours', 'invoicing' ) ?></span>
                                                </th>
                                            </tr>
                                        </thead>
										<tbody>
								            <tr>
									            <td class="pl-0 text-left">
                                                    <select class="getpaid-item-search regular-text" data-placeholder="<?php esc_attr_e( 'Search for an item…', 'invoicing' ); ?>"></select>
                                                </td>
									            <td class="pr-0 text-right hide-if-amount">
                                                    <input type="number" class="w100" step="1" min="1" autocomplete="off" value="1" placeholder="1">
                                                </td>
                                            </tr>
							            </tbody>
						            </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary getpaid-cancel" data-dismiss="modal"><?php _e( 'Cancel', 'invoicing' ); ?></button>
                                    <button type="button" class="btn btn-primary getpaid-add" data-dismiss="modal"><?php _e( 'Add', 'invoicing' ); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit invoice item -->
                    <div class="modal fade" id="getpaid-edit-invoice-item" tabindex="-1" role="dialog" aria-labelledby="getpaid-edit-invoice-item-label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="getpaid-edit-invoice-item-label"><?php _e( "Edit Item", 'invoicing' ); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php _e( "Close", 'invoicing' ); ?>">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="getpaid-edit-item-div">
                                        <input type="hidden" name="id" class="form-control form-control-sm item-id">
                                        <label class="form-group w-100">
                                            <span><?php _e( 'Name', 'invoicing' ); ?></span>
                                            <input type="text" name="name" placeholder="<?php esc_attr_e( 'Item Name', 'invoicing' ); ?>" class="form-control form-control-sm item-name">
                                        </label>
                                        <label class="form-group w-100">
                                            <span class="getpaid-hide-if-hours getpaid-hide-if-quantity item-price"><?php _e( 'Amount', 'invoicing' ); ?></span>
                                            <span class="hide-if-amount"><?php _e( 'Price', 'invoicing' ); ?></span>
                                            <input type="text" name="price" placeholder="<?php wpinv_sanitize_amount( 0 ); ?>" class="form-control form-control-sm item-price">
                                        </label>
                                        <label class="form-group w-100 hide-if-amount">
                                            <span><?php _e( 'Quantity', 'invoicing' ); ?></span>
                                            <input type="text" name="quantity" placeholder="1" class="form-control form-control-sm item-quantity">
                                        </label>
                                        <label class="form-group w-100">
                                            <span><?php _e( 'Item Description', 'invoicing' ); ?></span>
                                            <textarea name="description" placeholder="<?php esc_attr_e( 'Enter a description for this item', 'invoicing' ); ?>" class="form-control item-description"></textarea>
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary getpaid-cancel" data-dismiss="modal"><?php _e( 'Cancel', 'invoicing' ); ?></button>
                                    <button type="button" class="btn btn-primary getpaid-save" data-dismiss="modal"><?php _e( 'Save', 'invoicing' ); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        <?php
    }
}
