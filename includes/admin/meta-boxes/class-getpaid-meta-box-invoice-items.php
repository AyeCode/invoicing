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

    public static function get_columns( $invoice ) {
        $use_taxes          = $invoice->is_taxable() && wpinv_use_taxes();
        $columns            = array(
            'id'     => __( 'ID', 'invoicing' ),
            'title'  => __( 'Item', 'invoicing' ),
            'price'  => sprintf(
                '<span class="getpaid-hide-if-hours getpaid-hide-if-quantity">%s</span>
                <span class="getpaid-hide-if-hours hide-if-amount">%s</span>
                <span class="getpaid-hide-if-quantity hide-if-amount">%s</span>',
                __( 'Amount', 'invoicing' ),
                __( 'Price', 'invoicing' ),
                __( 'Rate', 'invoicing' )
            ),
            'qty'    => sprintf(
                '<span class="getpaid-hide-if-hours">%s</span><span class="getpaid-hide-if-quantity">%s</span>',
                __( 'Quantity', 'invoicing' ),
                __( 'Hours', 'invoicing' )
            ),
            'total'  => __( 'Total', 'invoicing' ),
            'tax'    => __( 'Tax (%)', 'invoicing' ),
            'action' => '',
        );

        if ( ! $use_taxes ) {
            unset( $columns['tax'] );
        }

        return $columns;
    }

    public static function output( $post, $invoice = false ) {

        if ( apply_filters( 'getpaid_use_new_invoice_items_metabox', false ) ) {
            return self::output2( $post );
        }

        $post_id            = !empty( $post->ID ) ? $post->ID : 0;
        $invoice            = $invoice instanceof WPInv_Invoice ? $invoice : new WPInv_Invoice( $post_id );
        $use_taxes          = $invoice->is_taxable() && wpinv_use_taxes();
        $item_types         = apply_filters( 'wpinv_item_types_for_quick_add_item', wpinv_get_item_types(), $post );
        $columns            = self::get_columns( $invoice );
        $cols               = count( $columns );
        $class              = '';

        unset( $item_types['adv'] );
        unset( $item_types['package'] );

        if ( $invoice->is_paid() ) {
            $class .= ' wpinv-paid';
        }

        if ( $invoice->is_refunded() ) {
            $class .= ' wpinv-refunded';
        }

        if ( $invoice->is_recurring() ) {
            $class .= ' wpi-recurring';
        }

    ?>

        <div class="wpinv-items-wrap<?php echo $class; ?>" id="wpinv_items_wrap" data-status="<?php echo esc_attr( $invoice->get_status() ); ?>">
            <table id="wpinv_items" class="wpinv-items" cellspacing="0" cellpadding="0">

                <thead>
                    <tr>
                        <?php foreach ( $columns as $key => $label ) : ?>
                            <th class="<?php echo esc_attr( $key ); echo 'total' == $key || 'qty' == $key ? ' hide-if-amount' : '' ?>"><?php echo wp_kses_post( $label ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody class="wpinv-line-items">
                    <?php
                        foreach ( $invoice->get_items() as $int => $item ) {
                            self::output_row( $columns, $item, $invoice, $int % 2 == 0 ? 'even' : 'odd' );
                        }
                    ?>
                </tbody>

                <tfoot class="wpinv-totals">
                    <tr>
                        <td colspan="<?php echo $cols; ?>" style="padding:0;border:0">
                            <div id="wpinv-quick-add">
                                <table cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="id">&nbsp;</td>
                                        <td class="title">

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-name">
                                                    <span class="input-text-wrap">
                                                        <input type="text" style="width: 100%" placeholder="<?php esc_attr_e( 'Item Name', 'invoicing' );?>" class="wpinv-quick-item-name" name="_wpinv_quick[name]">
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-price">
                                                    <span class="input-text-wrap">
                                                    <input type="text" style="width: 200px" placeholder="<?php esc_attr_e( 'Item Price', 'invoicing' );?>" class="wpinv-quick-item-price" name="_wpinv_quick[price]">
                                                        &times; <input type="text" style="width: 140px" placeholder="<?php esc_attr_e( 'Item Quantity', 'invoicing' );?>" class="wpinv-quick-item-qty" name="_wpinv_quick[qty]">
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-name">
                                                    <span class="input-text-wrap">
                                                        <textarea rows="4" style="width: 100%" placeholder="<?php esc_attr_e( 'Item Description', 'invoicing' );?>" class="wpinv-quick-item-description" name="_wpinv_quick[description]"></textarea>
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-type">
                                                    <span class="input-text-wrap">
                                                        <?php echo wpinv_html_select( array(
                                                            'options'          => $item_types,
                                                            'name'             => '_wpinv_quick[type]',
                                                            'id'               => '_wpinv_quick_type',
                                                            'selected'         => 'custom',
                                                            'show_option_all'  => false,
                                                            'show_option_none' => false,
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-type',
                                                        ) ); ?>
                                                    </span>
                                                </label>
                                            </div>

                                            <?php if ( $use_taxes ) : ?>
                                                <div class="wp-clearfix">
                                                    <label class="wpi-vat-rule">
                                                        <span class="input-text-wrap">
                                                            <?php
                                                                echo wpinv_html_select( array(
                                                                    'options'          => array_merge(
                                                                        array( '' => __( 'Select VAT Rule', 'invoicing' ) ),
                                                                        getpaid_get_tax_rules()
                                                                    ),
                                                                    'name'             => '_wpinv_quick[vat_rule]',
                                                                    'id'               => '_wpinv_quick_vat_rule',
                                                                    'show_option_all'  => false,
                                                                    'show_option_none' => false,
                                                                    'class'            => 'gdmbx2-text-medium wpinv-quick-vat-rule',
                                                                ) );
                                                            ?>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="wp-clearfix">
                                                    <label class="wpi-vat-class">
                                                        <span class="input-text-wrap">
                                                            <?php
                                                                echo wpinv_html_select( array(
                                                                    'options'          => array_merge(
                                                                        array( '' => __( 'Select VAT Class', 'invoicing' ) ),
                                                                        getpaid_get_tax_classes()
                                                                    ),
                                                                    'name'             => '_wpinv_quick[vat_class]',
                                                                    'id'               => '_wpinv_quick_vat_class',
                                                                    'show_option_all'  => false,
                                                                    'show_option_none' => false,
                                                                    'class'            => 'gdmbx2-text-medium wpinv-quick-vat-class',
                                                                ) );
                                                            ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endif; ?>

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-actions">
                                                    <span class="input-text-wrap">
                                                        <input type="button" value="Save" class="button button-primary" id="wpinv-save-item"><input type="button" value="Cancel" class="button button-secondary" id="wpinv-cancel-item">
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr class="totals">
                        <td colspan="<?php echo ( $cols - 4 ); ?>"></td>
                        <td colspan="4">
                            <table cellspacing="0" cellpadding="0">
                                <tr class="subtotal">
                                    <td class="name"><?php _e( 'Sub Total:', 'invoicing' );?></td>
                                    <td class="total"><?php echo wpinv_price( $invoice->get_subtotal(), $invoice->get_currency() );?></td>
                                    <td class="action"></td>
                                </tr>
                                <tr class="discount">
                                    <td class="name"><?php _e( 'Discount:', 'invoicing' ) ; ?></td>
                                    <td class="total"><?php echo wpinv_price( $invoice->get_total_discount(), $invoice->get_currency() );?></td>
                                    <td class="action"></td>
                                </tr>
                                <?php if ( $use_taxes ) : ?>
                                <tr class="tax">
                                    <td class="name"><?php _e( 'Tax:', 'invoicing' );?></td>
                                    <td class="total"><?php echo wpinv_price( $invoice->get_total_tax(), $invoice->get_currency() );?></td>
                                    <td class="action"></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="total">
                                    <td class="name"><?php _e( 'Total:', 'invoicing' );?></td>
                                    <td class="total"><?php echo wpinv_price( $invoice->get_total(), $invoice->get_currency() );?></td>
                                    <td class="action"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>

            </table>
            <div class="wpinv-actions">
                <?php
                    if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {
                        echo wpinv_item_dropdown(
                            array(
                                'name'             => 'wpinv_invoice_item',
                                'id'               => 'wpinv_invoice_item',
                                'show_recurring'   => true,
                                'class'            => 'wpi_select2',
                            )
                        );

                        echo "&nbsp;" . '<button class="button button-primary" id="wpinv-add-item">' . sprintf( esc_html__( 'Add item to %s', 'invoicing' ), $invoice->get_label() ) . '</button>';
                        echo "&nbsp;" . '<button class="button button-primary" id="wpinv-new-item">' . esc_html__( 'Create new item', 'invoicing' ) . '</button>';
                        echo "&nbsp;" . '<button class="button button-primary wpinv-flr" id="wpinv-recalc-totals">' . esc_html__( 'Recalculate Totals', 'invoicing' ) . '</button>';

                    }
                ?>
                <?php do_action( 'wpinv_invoice_items_actions', $invoice ); ?>
            </div>
        </div>
        <?php
    }

    public static function output_row( $columns, $item, $invoice, $class='even' ) {

    ?>
        <tr class="item item-<?php echo esc_attr( $class ); ?>" data-item-id="<?php echo esc_attr( $item->get_id() ); ?>">
            <?php foreach ( array_keys( $columns ) as $column ) : ?>
                <td class="<?php echo esc_attr( $column ); echo 'total' == $column || 'qty' == $column ? ' hide-if-amount' : '' ?>">
                    <?php
                        switch ( $column ) {
                            case 'id':
                                echo (int) $item->get_id();
                                break;
                            case 'title':
                                printf(
                                    '<a href="%s" target="_blank">%s</a>',
                                    get_edit_post_link( $item->get_id() ),
                                    esc_html( $item->get_raw_name() )
                                );

                                $summary = apply_filters( 'getpaid_admin_invoice_line_item_summary', $item->get_description(), $item, $invoice );
                                if ( $summary !== '' ) {
                                    printf(
                                        '<span class="meta">%s</span>',
                                        wpautop( wp_kses_post( $summary ) )
                                    );
                                }

                                printf(
                                    '<input type="hidden" value="%s" name="getpaid_items[%s][name]" class="getpaid-recalculate-prices-on-change" />',
                                    esc_attr( $item->get_raw_name() ),
                                    (int) $item->get_id()
                                );

                                printf(
                                    '<textarea style="display: none;" name="getpaid_items[%s][description]" class="getpaid-recalculate-prices-on-change">%s</textarea>',
                                    (int) $item->get_id(),
                                    esc_attr( $item->get_description() )
                                );

                                break;
                            case 'price':
                                printf(
                                    '<input type="text" value="%s" name="getpaid_items[%s][price]" style="width: 100px;" class="getpaid-admin-invoice-item-price getpaid-recalculate-prices-on-change" />',
                                    esc_attr( getpaid_unstandardize_amount( $item->get_price() ) ),
                                    (int) $item->get_id()
                                );

                                break;
                            case 'qty':
                                printf(
                                    '<input type="text" style="width: 100px;" value="%s" name="getpaid_items[%s][quantity]" class="getpaid-admin-invoice-item-quantity getpaid-recalculate-prices-on-change" />',
                                    floatval( $item->get_quantity() ),
                                    (int) $item->get_id()
                                );

                                break;
                            case 'total':
                                echo wpinv_price( $item->get_sub_total(), $invoice->get_currency() );

                                break;
                            case 'tax':
                                echo wpinv_round_amount( getpaid_get_invoice_tax_rate( $invoice, $item ), 2 ) . '%';

                                break;
                            case 'action':
                                if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {
                                    echo '<i class="fa fa-trash wpinv-item-remove"></i>';
                                }
                                break;
                        }
                        do_action( 'getpaid_admin_edit_invoice_item_' . $column, $item, $invoice );
                    ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php
    }

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output2( $post ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Invoice items.
        $items = $invoice->get_items();

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
                'value' => wpinv_price( $invoice->get_total(), $invoice->get_currency() ),
            )
        );

        if ( ! wpinv_use_taxes() ) {
            unset( $totals['tax'] );
        }

        $item_args = array(
            'post_type'      => 'wpi_item',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish' ),
            'meta_query'     => array(
                array(
                    'key'       => '_wpinv_type',
                    'compare'   => '!=',
                    'value'     => 'package'
                )
            )
        );

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

                <div class="bsui getpaid-invoice-items-inner <?php echo empty( $items ) ? 'no-items' : 'has-items'; ?> <?php echo $invoice->is_paid() || $invoice->is_refunded() ? 'not-editable' : 'editable'; ?>" style="margin-top: 1.5rem; padding: 0 12px 12px;">

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
                                    <button type="button" class="button button-secondary create-invoice-item" data-toggle="modal" data-target="#getpaid-create-invoice-item"><?php _e( 'Create New Item', 'invoicing' ) ?></button>
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
                                                <td class="label"><?php echo esc_html( $data['label'] ) ?>:</td>
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
                                <button type="button" class="button button-primary add-invoice-item" data-toggle="modal" data-target="#getpaid-add-items-to-invoice"><?php _e( 'Add Existing Item', 'invoicing' ) ?></button>
                                <button type="button" class="button button-secondary create-invoice-item" data-toggle="modal" data-target="#getpaid-create-invoice-item"><?php _e( 'Create New Item', 'invoicing' ) ?></button>
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
                                                    <select class="regular-text getpaid-add-invoice-item-select">
                                                        <option value="" selected="selected" disabled><?php esc_html_e( 'Select an item…', 'invoicing' ); ?></option>
                                                        <?php foreach ( get_posts( $item_args ) as $item ) : ?>
                                                        <option value="<?php echo (int) $item->ID; ?>"><?php echo strip_tags( $item->post_title ); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
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

                    <!-- Create invoice item -->
                    <div class="modal fade" id="getpaid-create-invoice-item" tabindex="-1" role="dialog" aria-labelledby="getpaid-create-invoice-item-label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="getpaid-create-invoice-item-label"><?php _e( "Create Item", 'invoicing' ); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php _e( "Close", 'invoicing' ); ?>">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="getpaid-create-item-div">
                                        <input type="hidden" name="id" value="new" class="form-control form-control-sm item-id">
                                        <label class="form-group w-100">
                                            <span><?php _e( 'Name', 'invoicing' ); ?></span>
                                            <input type="text" name="name" placeholder="<?php esc_attr_e( 'Item Name', 'invoicing' ); ?>" class="form-control form-control-sm item-name">
                                        </label>
                                        <label class="form-group w-100">
                                            <span class="getpaid-hide-if-hours getpaid-hide-if-quantity item-price"><?php _e( 'Amount', 'invoicing' ); ?></span>
                                            <span class="hide-if-amount"><?php _e( 'Price', 'invoicing' ); ?></span>
                                            <input type="text" name="price" placeholder="<?php echo wpinv_sanitize_amount( 0 ); ?>" class="form-control form-control-sm item-price">
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
                                    <button type="button" class="btn btn-primary getpaid-save" data-dismiss="modal"><?php _e( 'Create', 'invoicing' ); ?></button>
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
