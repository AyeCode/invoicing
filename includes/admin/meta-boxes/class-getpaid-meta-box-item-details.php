<?php

/**
 * Item Details
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Item_Details Class.
 */
class GetPaid_Meta_Box_Item_Details {

    /**
     * Output the metabox.
     *
     * @param WP_Post $post
     */
    public static function output( $post ) {
        // Prepare the item.
        $item = new WPInv_Item( $post );

        // Nonce field.
        wp_nonce_field( 'getpaid_meta_nonce', 'getpaid_meta_nonce' );

        // Variable prices.
        $variable_prices = $item->get_variable_prices();

        // Set the currency position.
        $position = wpinv_currency_position();

        if ( $position == 'left_space' ) {
            $position = 'left';
        }

        if ( $position == 'right_space' ) {
            $position = 'right';
        }

        ?>
        <input type="hidden" id="_wpi_current_type" value="<?php echo esc_attr( $item->get_type( 'edit' ) ); ?>" />
        <style>
            #poststuff .input-group-text,
            #poststuff .form-control {
                border-color: #7e8993;
            }

            .bsui label.col-sm-3.col-form-label {
                font-weight: 600;
            }
        </style>
        <div class="bsui" style="max-width: 600px;padding-top: 10px;">

            <?php do_action( 'wpinv_item_details_metabox_before_price', $item ); ?>

            <div class="form-group mb-3 row">
                <label class="col-sm-3 col-form-label" for="wpinv_item_price"><span><?php esc_html_e( 'Item Price', 'invoicing' ); ?></span></label>
                <div class="col-sm-8">
                    <div class="row wpinv_hide_if_variable_pricing">
                        <div class="col-sm-4 getpaid-price-input mb-3">
                            <div class="input-group input-group-sm">

                                <?php if ( 'left' == $position ) : ?>
                                    <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="input-group-text">
                                            <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <input type="text" name="wpinv_item_price" id="wpinv_item_price" value="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_price( 'edit' ) ) ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 0 ) ); ?>" class="form-control wpinv-force-integer" autocomplete="off">

                                <?php if ( 'left' != $position ) : ?>
                                    <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="input-group-text">
                                            <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-4 wpinv_show_if_recurring">
                            <?php
                            esc_html_e( 'Every' );
                            echo '&nbsp;';
                            ?>
                            <input type="number" style="max-width: 60px;" value="<?php echo esc_attr( $item->get_recurring_interval( 'edit' ) ); ?>" placeholder="1" name="wpinv_recurring_interval" id="wpinv_recurring_interval" />
                        </div>
                        <div class="col-sm-4 wpinv_show_if_recurring">
                            <?php
                            aui()->select(
                                array(
                                    'id'               => 'wpinv_recurring_period',
                                    'name'             => 'wpinv_recurring_period',
                                    'label'            => __( 'Period', 'invoicing' ),
                                    'placeholder'      => __( 'Select Period', 'invoicing' ),
                                    'value'            => $item->get_recurring_period( 'edit' ),
                                    'select2'          => true,
                                    'data-allow-clear' => 'false',
                                    'options'          => array(
                                        'D' => __( 'day(s)', 'invoicing' ),
                                        'W' => __( 'week(s)', 'invoicing' ),
                                        'M' => __( 'month(s)', 'invoicing' ),
                                        'Y' => __( 'year(s)', 'invoicing' ),
                                    ),
                                ),
                                true
                            );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <?php
                            // Variable pricing.
                            do_action( 'wpinv_item_details_metabox_before_variable_pricing_checkbox', $item );

                            aui()->input(
                                array(
                                    'id'      => 'wpinv_variable_pricing',
                                    'name'    => 'wpinv_variable_pricing',
                                    'type'    => 'checkbox',
                                    'label'   => apply_filters( 'wpinv_variable_pricing_toggle_text', __( 'Enable variable pricing', 'invoicing' ) ),
                                    'value'   => '1',
                                    'checked' => $item->has_variable_pricing(),
                                    'no_wrap' => true,
                                ),
                                true
                            );

                            do_action( 'wpinv_item_details_metabox_variable_pricing_checkbox', $item );
                            ?>
                        </div>
                    </div>

                    <div class="row wpinv_hide_if_variable_pricing">
                        <div class="col-sm-12">
                            <?php

                            // Dynamic pricing.
                            if ( $item->supports_dynamic_pricing() ) {

                                do_action( 'wpinv_item_details_metabox_before_dynamic_pricing_checkbox', $item );

                                // NYP toggle.
                                aui()->input(
                                    array(
                                        'id'      => 'wpinv_name_your_price',
                                        'name'    => 'wpinv_name_your_price',
                                        'type'    => 'checkbox',
                                        'label'   => apply_filters( 'wpinv_name_your_price_toggle_text', __( 'Let customers name their price', 'invoicing' ) ),
                                        'value'   => '1',
                                        'checked' => $item->user_can_set_their_price(),
                                        'no_wrap' => true,
                                    ),
                                    true
                                );

                                do_action( 'wpinv_item_details_metabox_dynamic_pricing_checkbox', $item );
                            }

                            // Subscriptions.
                            do_action( 'wpinv_item_details_metabox_before_subscription_checkbox', $item );

                            aui()->input(
                                array(
                                    'id'      => 'wpinv_is_recurring',
                                    'name'    => 'wpinv_is_recurring',
                                    'type'    => 'checkbox',
                                    'label'   => apply_filters( 'wpinv_is_recurring_toggle_text', __( 'Charge customers a recurring amount for this item', 'invoicing' ) ),
                                    'value'   => '1',
                                    'checked' => $item->is_recurring(),
                                    'no_wrap' => true,
                                ),
                                true
                            );
                            do_action( 'wpinv_item_details_metabox_subscription_checkbox', $item );

                            ?>
                            <div class="wpinv_show_if_recurring">
                                <em><?php echo wp_kses_post( wpinv_get_recurring_gateways_text() ); ?></em>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-1 pt-2 pl-0 wpinv_hide_if_variable_pricing">
                    <span class="wpi-help-tip dashicons dashicons-editor-help wpinv_show_if_recurring" title="<?php esc_attr_e( 'Set the subscription price, billing interval and period.', 'invoicing' ); ?>"></span>
                </div>
            </div>

            <?php do_action( 'wpinv_item_details_metabox_after_price', $item ); ?>

            <?php if ( $item->supports_dynamic_pricing() ) : ?>
                <?php do_action( 'wpinv_item_details_metabox_before_minimum_price', $item ); ?>
                <div class="wpinv_show_if_dynamic wpinv_minimum_price wpinv_hide_if_variable_pricing">

                    <div class="form-group mb-3 row">
                        <label for="wpinv_minimum_price" class="col-sm-3 col-form-label">
                            <?php esc_html_e( 'Minimum Price', 'invoicing' ); ?>
                        </label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <?php if ( 'left' == $position ) : ?>
                                    <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="input-group-text">
                                            <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <input type="text" name="wpinv_minimum_price" id="wpinv_minimum_price" value="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_minimum_price( 'edit' ) ) ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 0 ) ); ?>" class="form-control wpinv-force-integer">

                                <?php if ( 'left' != $position ) : ?>
                                    <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="input-group-text">
                                            <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-sm-1 pt-2 pl-0">
                            <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Enter the minimum amount that users are allowed to set', 'invoicing' ); ?>"></span>
                        </div>
                    </div>

                </div>
                <?php do_action( 'wpinv_item_details_metabox_minimum_price', $item ); ?>
            <?php endif; ?>

            <?php do_action( 'wpinv_item_details_metabox_before_maximum_renewals', $item ); ?>
            <div class="wpinv_show_if_recurring wpinv_hide_if_variable_pricing wpinv_maximum_renewals">

                <div class="form-group mb-3 row">
                    <label for="wpinv_recurring_limit" class="col-sm-3 col-form-label">
                        <?php esc_html_e( 'Maximum Renewals', 'invoicing' ); ?>
                    </label>
                    <div class="col-sm-8">
                        <input type="number" value="<?php echo esc_attr( $item->get_recurring_limit( 'edit' ) ); ?>" placeholder="0" name="wpinv_recurring_limit" id="wpinv_recurring_limit" style="width: 100%;" />
                    </div>
                    <div class="col-sm-1 pt-2 pl-0">
                        <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Leave empty if you want the subscription to renew until it is cancelled.', 'invoicing' ); ?>"></span>
                    </div>
                </div>

            </div>
            <?php do_action( 'wpinv_item_details_metabox_maximum_renewals', $item ); ?>

            <?php do_action( 'wpinv_item_details_metabox_before_free_trial', $item ); ?>
            <div class="wpinv_show_if_recurring wpinv_hide_if_variable_pricing wpinv_free_trial">

                <div class="form-group mb-3 row">
                    <label class="col-sm-3 col-form-label" for="wpinv_trial_interval"><?php defined( 'GETPAID_PAID_TRIALS_VERSION' ) ? esc_html_e( 'Free/Paid Trial', 'invoicing' ) : esc_html_e( 'Free Trial', 'invoicing' ); ?></label>

                    <div class="col-sm-8">
                        <div class="row">
                            <div class="col-sm-6">
                                <?php $value = $item->has_free_trial() ? $item->get_trial_interval( 'edit' ) : 0; ?>

                                <div>
                                    <input type="number" name="wpinv_trial_interval" style="width: 100%;" placeholder="0" id="wpinv_trial_interval" value="<?php echo esc_attr( $value ); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <?php
                                aui()->select(
                                    array(
                                        'id'               => 'wpinv_trial_period',
                                        'name'             => 'wpinv_trial_period',
                                        'label'            => __( 'Trial Period', 'invoicing' ),
                                        'placeholder'      => __( 'Trial Period', 'invoicing' ),
                                        'value'            => $item->get_trial_period( 'edit' ),
                                        'select2'          => true,
                                        'data-allow-clear' => 'false',
                                        'no_wrap'          => true,
                                        'options'          => array(
                                            'D' => __( 'day(s)', 'invoicing' ),
                                            'W' => __( 'week(s)', 'invoicing' ),
                                            'M' => __( 'month(s)', 'invoicing' ),
                                            'Y' => __( 'year(s)', 'invoicing' ),
                                        ),
                                    ),
                                    true
                                );
                                ?>

                            </div>
                        </div>
                    </div>

                    <div class="col-sm-1 pt-2 pl-0">
                        <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'An optional period of time to wait before charging the first recurring payment.', 'invoicing' ); ?>"></span>
                    </div>

                </div>

            </div>
            <?php do_action( 'wpinv_item_details_metabox__free_trial', $item ); ?>
        </div>

        <div class="bsui">
            <?php do_action( 'wpinv_item_details_metabox_before_variable_pricing', $item ); ?>

            <div class="wpinv_show_if_variable_pricing wpinv_variable_pricing">
                
                <div id="wpinv_price_fields" class="wpinv_meta_table_wrap mb-3">
                    <div class="widefat getpaid_repeatable_table">

                        <div class="wpinv-price-option-fields getpaid-repeatables-wrap">
                            <?php
                            if ( ! empty( $variable_prices ) ) :

                                foreach ( $variable_prices as $key => $value ) :
                                    $name   = (isset( $value['name'] ) && ! empty( $value['name'] )) ? $value['name'] : '';
                                    $index  = (isset( $value['index'] ) && $value['index'] !== '') ? $value['index'] : $key;
                                    $amount = isset( $value['amount'] ) ? $value['amount'] : '';

                                    $args   = apply_filters( 'wpinv_price_row_args', compact( 'name', 'amount' ), $value );
                                    $args = wp_parse_args( $args, $value );
                                    ?>
                                    <div class="wpinv_variable_prices_wrapper getpaid_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
                                        <?php self::render_price_row( $key, $args, $item, $index ); ?>
                                    </div>
                                <?php
                                endforeach;
                            else :
                                ?>
                                <div class="wpinv_variable_prices_wrapper getpaid_repeatable_row" data-key="1">
                                    <?php self::render_price_row( 1, array(), $item, 1 ); ?>
                                </div>
                            <?php endif; ?>

                            <div class="wpinv-add-repeatable-row">
                                <div class="float-none pt-2 clear">
                                    <button type="button" class="button-secondary getpaid-add-variable-price-row"><?php _e( 'Add New Price', 'invoicing' ); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php do_action( 'wpinv_item_details_metabox_variable_pricing', $item ); ?>
        </div>

        <div class="bsui" style="max-width: 600px;">
            <?php do_action( 'wpinv_item_details_metabox_item_details', $item ); ?>
        </div>

        <script type="text/html" id="tmpl-getpaid-variable-price-row">
            <div class="wpinv_variable_prices_wrapper getpaid_repeatable_row" data-key="1">
                <?php self::render_price_row( 1, array(), $item, 1 ); ?>
            </div>
        </script>

        <script type="text/javascript">
            jQuery(function($) {

                // Inserts a new row
                $('.getpaid-add-variable-price-row').on('click', function(e) {
                    e.preventDefault();
                    const html = $('#tmpl-getpaid-variable-price-row').html();
                    const price_row = $(html);
                    const last_price_row = $(this).parents('.wpinv-price-option-fields').find('.wpinv_variable_prices_wrapper').last();

                    // Retrieve the highest current key
                    var key = highest = 1;
                    $(this).parents('.wpinv-price-option-fields').find('.getpaid_repeatable_row').each(function() {
                        var current = $(this).data('key');
                        if (parseInt(current) > highest) {
                            highest = current;
                        }
                    });
                    key = highest += 1;

                    price_row.attr('data-key', key);

                    price_row.find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        var id = $(this).attr('id');
                        if (name) {
                            name = name.replace(/\[(\d+)\]/, '[' + parseInt(key) + ']');
                            $(this).attr('name', name);
                        }

                        $(this).attr('data-key', key);
                        if (typeof id != 'undefined') {
                            id = id.replace(/(\d+)/, parseInt(key));
                            $(this).attr('id', id);
                        }
                    });

                    price_row.find('span.getpaid_price_id').each(function() {
                        $(this).text(parseInt(key));
                    });

                    price_row.find('.getpaid_repeatable_default_input').each(function() {
                        $(this).val(parseInt(key)).removeAttr('checked');
                    });

                    $(price_row).insertAfter(last_price_row)
                });

                // Remove a row.
                $(document).on('click', '.getpaid-remove-price-option-row', function(e) {
                    e.preventDefault();

                    var row = $(this).parents('.getpaid_repeatable_row'),
                        count = row.parent().find('.getpaid_repeatable_row').length,
                        price_id = parseInt(row.data("key"));

                    $('.getpaid_repeatable_condition_field option[value="' + price_id + '"]').remove()

                    if (count > 1) {
                        $('input, select', row).val('');
                        row.fadeOut('fast').remove();
                    }
                });

                $(".getpaid_repeatable_table .getpaid-repeatables-wrap").sortable({
                    handle: '.getpaid-draghandle-cursor',
                    items: '.getpaid_repeatable_row',
                    opacity: 0.6,
                    cursor: 'move',
                    axis: 'y',
                    update: function() {
                        var count = 0;
                        $(this).find('.getpaid_repeatable_row').each(function() {
                            $(this).find('input.getpaid_repeatable_index').each(function() {
                                $(this).val(count);
                            });
                            count++;
                        });
                    }
                });
            });
        </script>
    <?php
    }

    /**
     * Render a price row with advanced settings for a WPINV Item.
     *
     * This function generates HTML markup for displaying a price row with advanced settings,
     * including recurring payment options.
     *
     * @since 2.8.9
     *
     * @param string $key   The unique identifier for the price row.
     * @param array  $args  Optional. Array of arguments for customizing the price row. Default empty array.
     * @param WP_Post $item  WPINV Itemm object.
     * @param int    $index The index of the price row.
     */
    public static function render_price_row( $key, $args = array(), $item, $index ) {
        $defaults = array(
            'name'               => null,
            'amount'             => null,
            'is-recurring'       => 'no',
            'trial-interval'     => 0,
            'trial-period'       => null,
            'recurring-interval' => 1,
            'recurring-period'   => null,
            'recurring-limit'    => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $default_price_id = $item->get_default_price_id();
        $is_recurring = ('yes' === $args['is-recurring']) ? true : false;
        $position = wpinv_currency_position();

        $extra_attributes = array(
            'min'   => 0,
            'step'  => 1,
            'size'  => 1,
            'style' => 'width: 70px',
        );

        if ( ! $is_recurring ) {
            $extra_attributes['disabled'] = true;
        }
        ?>
        <div class="getpaid-repeatable-row-header getpaid-draghandle-cursor">

            <span class="getpaid-repeatable-row-title" title="<?php _e( 'Click and drag to re-order price options', 'invoicing' ); ?>">
                <?php printf( __( 'Price ID: %s', 'invoicing' ), '<span class="getpaid_price_id">' . $key . '</span>' ); ?>
                <input type="hidden" name="wpinv_variable_prices[<?php echo $key; ?>][index]" class="getpaid_repeatable_index" value="<?php echo $index; ?>"/>
            </span>

            <span class="getpaid-repeatable-row-actions">
                <a href="#" class="wpinv-toggle-custom-price-option-settings" data-show="<?php _e( 'Show advanced settings', 'invoicing' ); ?>" data-hide="<?php _e( 'Hide advanced settings', 'invoicing' ); ?>"><?php _e( 'Show advanced settings', 'invoicing' ); ?></a> 
                &nbsp;&#124;&nbsp;
                <a class="getpaid-remove-price-option-row text-danger">
                    <?php _e( 'Remove', 'invoicing' ); ?> <span class="screen-reader-text"><?php printf( __( 'Remove price option %s', 'invoicing' ), esc_attr( $key ) ); ?></span>
                </a>
            </span>
        </div>

        <div class="getpaid-repeatable-row-standard-fields">

            <div class="getpaid-option-name">
                <label class="form-label"><?php _e( 'Option Name', 'invoicing' ); ?></label>
                <?php
                aui()->input(
                    array(
                        'name'        => 'wpinv_variable_prices[' . $key . '][name]',
                        'placeholder' => __( 'Option Name', 'invoicing' ),
                        'value'       => esc_attr( $args['name'] ),
                        'class'       => 'wpinv_variable_price_name form-control-sm',
                        'no_wrap'     => true,
                    ),
                    true
                );
                ?>
            </div>

            <div class="getpaid-option-price">
                <label class="form-label"><?php _e( 'Price', 'invoicing' ); ?></label>
                <div class="input-group input-group-sm">
                    <?php if ( 'left' == $position ) : ?>
                        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                            </div>
                        <?php else : ?>
                            <span class="input-group-text">
                                <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <input type="text" name="wpinv_variable_prices[<?php echo $key; ?>][amount]" id="wpinv_variable_prices[<?php echo $key; ?>][amount]" value="<?php echo esc_attr( getpaid_unstandardize_amount( $args['amount'] ) ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 9.99 ) ); ?>" class="form-control form-control-sm wpinv-force-integer getpaid-price-field" autocomplete="off">

                    <?php if ( 'left' != $position ) : ?>
                        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                            <div class="input-group-append">
                                <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
                            </div>
                        <?php else : ?>
                            <span class="input-group-text">
                                <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="getpaid_repeatable_default getpaid_repeatable_default_wrapper">
                <label class="form-label d-block"><?php _e( 'Default', 'invoicing' ); ?></label>
                <label class="getpaid-default-price">
                    <input type="radio" <?php checked( $default_price_id, $key, true ); ?> class="getpaid_repeatable_default_input" name="_wpinv_default_price_id" value="<?php echo $key; ?>" />
                    <span class="screen-reader-text"><?php printf( __( 'Set ID %s as default price', 'invoicing' ), $key ); ?></span>
                </label>
            </div>

        </div>

        <div class="wpinv-custom-price-option-settings-wrap">
            <div class="wpinv-custom-price-option-settings">
                <div class="wpinv-custom-price-option-setting">

                    <div class="wpinv-custom-price-option-setting-title"><?php _e( 'Recurring Payments Settings', 'invoicing' ); ?></div>
                    
                    <div class="wpinv-recurring-enabled">
                        <label class="form-label"><?php _e( 'Recurring', 'invoicing' ); ?></label>
                        <?php
                        aui()->select(
                            array(
                                'name'    => 'wpinv_variable_prices[' . $key . '][is-recurring]',
                                'value'   => esc_attr( $args['is-recurring'] ),
                                'class'   => 'custom-select-sm',
                                'no_wrap' => true,
                                'options' => array(
                                    'no'  => __( 'No', 'invoicing' ),
                                    'yes' => __( 'Yes', 'invoicing' ),
                                ),
                            ),
                            true
                        );
                        ?>
                    </div>

                    <div class="wpinv-recurring-free-trial">
                        <label class="form-label"><?php _e( 'Free Trial', 'invoicing' ); ?></label>
                        <?php
                        aui()->input(
                            array(
                                'type'             => 'number',
                                'id'               => 'wpinv_variable_prices[' . $key . '][trial-interval]',
                                'name'             => 'wpinv_variable_prices[' . $key . '][trial-interval]',
                                'value'            => absint( $args['trial-interval'] ),
                                'class'            => 'form-control-sm d-inline-block',
                                'no_wrap'          => true,
                                'extra_attributes' => $extra_attributes,
                            ),
                            true
                        );
                        ?>

                        <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_variable_prices[' . $key . '][trial-period]',
                                'name'             => 'wpinv_variable_prices[' . $key . '][trial-period]',
                                'value'            => esc_attr( $args['trial-period'] ),
                                'class'            => 'custom-select-sm w-auto d-inline-block',
                                'no_wrap'          => true,
                                'extra_attributes' => ( ! $is_recurring ? array( 'disabled' => true ) : array()),
                                'options'          => array(
                                    'D' => __( 'Day(s)', 'invoicing' ),
                                    'W' => __( 'Week(s)', 'invoicing' ),
                                    'M' => __( 'Month(s)', 'invoicing' ),
                                    'Y' => __( 'Year(s)', 'invoicing' ),
                                ),
                            ),
                            true
                        );
                        ?>
                    </div>

                    <div class="wpinv-recurring-interval">
                        <label class="form-label"><?php _e( 'Every', 'invoicing' ); ?></label>
                        <?php
                        aui()->input(
                            array(
                                'type'             => 'number',
                                'id'               => 'wpinv_variable_prices[' . $key . '][recurring-interval]',
                                'name'             => 'wpinv_variable_prices[' . $key . '][recurring-interval]',
                                'value'            => absint( $args['recurring-interval'] ),
                                'class'            => 'form-control-sm',
                                'no_wrap'          => true,
                                'extra_attributes' => $extra_attributes,
                            ),
                            true
                        );
                        ?>
                    </div>

                    <div class="wpinv-recurring-period">
                        <label class="form-label"><?php _e( 'Period', 'invoicing' ); ?></label>
                        <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_variable_prices[' . $key . '][recurring-period]',
                                'name'             => 'wpinv_variable_prices[' . $key . '][recurring-period]',
                                'value'            => esc_attr( $args['recurring-period'] ),
                                'class'            => 'custom-select-sm',
                                'extra_attributes' => ( ! $is_recurring ? array( 'disabled' => true ) : array()),
                                'no_wrap'          => true,
                                'options'          => array(
                                    'D' => __( 'Day(s)', 'invoicing' ),
                                    'W' => __( 'Week(s)', 'invoicing' ),
                                    'M' => __( 'Month(s)', 'invoicing' ),
                                    'Y' => __( 'Year(s)', 'invoicing' ),
                                ),
                            ),
                            true
                        );
                        ?>
                    </div>

                    <div class="wpinv-recurring-limit">
                        <label class="form-label"><?php _e( 'Maximum Renewals', 'invoicing' ); ?></label>
                        <?php
                        aui()->input(
                            array(
                                'type'             => 'number',
                                'id'               => 'wpinv_variable_prices[' . $key . '][recurring-limit]',
                                'name'             => 'wpinv_variable_prices[' . $key . '][recurring-limit]',
                                'value'            => esc_attr( $args['recurring-limit'] ),
                                'class'            => 'form-control-sm',
                                'no_wrap'          => true,
                                'extra_attributes' => array_merge(
                                    $extra_attributes,
                                    array( 'size' => 4 )
                                ),
							),
                            true
                        );
                        ?>
                    </div>
                </div>

                <?php do_action( 'wpinv_download_price_option_row', $item->ID, $key, $args ); ?>
            </div>
        </div>
    <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id
     */
    public static function save( $post_id ) {

        // Prepare the item.
        $item = new WPInv_Item( $post_id );

        $is_dynamic_pricing = ! empty( $_POST['wpinv_name_your_price'] );
        $is_recurring = ! empty( $_POST['wpinv_is_recurring'] );
        $is_free_trial = isset( $_POST['wpinv_trial_interval'] ) ? (0 != (int) $_POST['wpinv_trial_interval']) : null;

        $has_variable_pricing = ! empty( $_POST['wpinv_variable_pricing'] );
        if ( true === $has_variable_pricing ) {
            $is_dynamic_pricing = $is_recurring = $is_free_trial = false;
        }

        // Load new data.
        $item->set_props(
            array(
                'price'                => isset( $_POST['wpinv_item_price'] ) ? getpaid_standardize_amount( $_POST['wpinv_item_price'] ) : null,
                'vat_rule'             => isset( $_POST['wpinv_vat_rules'] ) ? wpinv_clean( $_POST['wpinv_vat_rules'] ) : null,
                'vat_class'            => isset( $_POST['wpinv_vat_class'] ) ? wpinv_clean( $_POST['wpinv_vat_class'] ) : null,
                'type'                 => isset( $_POST['wpinv_item_type'] ) ? wpinv_clean( $_POST['wpinv_item_type'] ) : null,
                'is_dynamic_pricing'   => $is_dynamic_pricing,
                'minimum_price'        => isset( $_POST['wpinv_minimum_price'] ) ? getpaid_standardize_amount( $_POST['wpinv_minimum_price'] ) : null,
                'has_variable_pricing' => $has_variable_pricing,
                'default_price_id'     => isset( $_POST['_wpinv_default_price_id'] ) ? absint( $_POST['_wpinv_default_price_id'] ) : null,
                'variable_prices'      => isset( $_POST['wpinv_variable_prices'] ) ? wpinv_clean( $_POST['wpinv_variable_prices'] ) : array(),
                'is_recurring'         => $is_recurring,
                'recurring_period'     => isset( $_POST['wpinv_recurring_period'] ) ? wpinv_clean( $_POST['wpinv_recurring_period'] ) : null,
                'recurring_interval'   => isset( $_POST['wpinv_recurring_interval'] ) ? (int) $_POST['wpinv_recurring_interval'] : 1,
                'recurring_limit'      => isset( $_POST['wpinv_recurring_limit'] ) ? (int) $_POST['wpinv_recurring_limit'] : null,
                'is_free_trial'        => $is_free_trial,
                'trial_period'         => isset( $_POST['wpinv_trial_period'] ) ? wpinv_clean( $_POST['wpinv_trial_period'] ) : null,
                'trial_interval'       => isset( $_POST['wpinv_trial_interval'] ) ? (int) $_POST['wpinv_trial_interval'] : null,
            )
        );

        $item->save();
        do_action( 'getpaid_item_metabox_save', $post_id, $item );
    }
}
