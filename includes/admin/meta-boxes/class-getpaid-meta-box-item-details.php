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
        <div class='bsui' style='max-width: 600px;padding-top: 10px;'>

            <?php do_action( 'wpinv_item_details_metabox_before_price', $item ); ?>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="wpinv_item_price"><span><?php _e( 'Item Price', 'invoicing' )?></span></label>
                <div class="col-sm-8">
                    <div class="row">
                        <div class="col-sm-4 getpaid-price-input">
                            <div class="input-group input-group-sm">
                                <?php if( 'left' == $position ) : ?>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="wpinv_item_price_symbol"><?php echo wpinv_currency_symbol(); ?></span>
                                </div>
                                <?php endif; ?>
                                <input type="text" name="wpinv_item_price" id="wpinv_item_price" value="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_price( 'edit' ) ) ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 0 ) ); ?>" class="form-control">

                                <?php if( 'left' != $position ) : ?>
                                <div class="input-group-append">
                                    <span class="input-group-text" id="wpinv_item_price_symbol"><?php echo wpinv_currency_symbol(); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="col-sm-4 wpinv_show_if_recurring">
                            <?php
                                _e( 'every' );
                                echo "&nbsp;";
                            ?>
                            <input type="number" style="max-width: 60px;" value="<?php echo esc_attr( $item->get_recurring_interval( 'edit' ) ); ?>" placeholder="1" name="wpinv_recurring_interval" id="wpinv_recurring_interval" />
                        </div>
                        <div class="col-sm-4 wpinv_show_if_recurring">
                            <?php
                                echo aui()->select(
                                    array(
                                        'id'               => 'wpinv_recurring_period',
                                        'name'             => 'wpinv_recurring_period',
                                        'label'            => __( 'Period', 'invoicing' ),
                                        'placeholder'      => __( 'Select Period', 'invoicing' ),
                                        'value'            => $item->get_recurring_period( 'edit' ),
                                        'select2'          => true,
                                        'data-allow-clear' => 'false',
                                        'options'     => array(
                                            'D'  => __( 'day(s)', 'invoicing' ),
                                            'W'  => __( 'week(s)', 'invoicing' ),
                                            'M'  => __( 'month(s)', 'invoicing' ),
                                            'Y'  => __( 'year(s)', 'invoicing' ),
                                        )
                                    )
                                );
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <?php

                                // Dynamic pricing.
                                if( $item->supports_dynamic_pricing() ) {

                                    do_action( 'wpinv_item_details_metabox_before_dynamic_pricing_checkbox', $item );

                                    // NYP toggle.
                                    echo aui()->input(
                                        array(
                                            'id'          => 'wpinv_name_your_price',
                                            'name'        => 'wpinv_name_your_price',
                                            'type'        => 'checkbox',
                                            'label'       => apply_filters( 'wpinv_name_your_price_toggle_text', __( 'Let customers name their price', 'invoicing' ) ),
                                            'value'       => '1',
                                            'checked'     => $item->user_can_set_their_price(),
                                            'no_wrap'     => true,
                                        )
                                    );

                                    do_action( 'wpinv_item_details_metabox_dynamic_pricing_checkbox', $item );

                                }

                                // Subscriptions.
                                do_action( 'wpinv_item_details_metabox_before_subscription_checkbox', $item );
                                echo aui()->input(
                                    array(
                                        'id'          => 'wpinv_is_recurring',
                                        'name'        => 'wpinv_is_recurring',
                                        'type'        => 'checkbox',
                                        'label'       => apply_filters( 'wpinv_is_recurring_toggle_text', __( 'Charge customers a recurring amount for this item', 'invoicing' ) ),
                                        'value'       => '1',
                                        'checked'     => $item->is_recurring(),
                                        'no_wrap'     => true,
                                    )
                                );
                                do_action( 'wpinv_item_details_metabox_subscription_checkbox', $item );

                            ?>
                            <div class="wpinv_show_if_recurring">
                                <em><?php echo wpinv_get_recurring_gateways_text(); ?></em>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help wpinv_show_if_recurring" title="<?php esc_attr_e( 'Set the subscription price, billing interval and period.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_item_details_metabox_after_price', $item ); ?>

            <?php if( $item->supports_dynamic_pricing() ) : ?>
                <?php do_action( 'wpinv_item_details_metabox_before_minimum_price', $item ); ?>
                <div class="wpinv_show_if_dynamic wpinv_minimum_price">

                    <div class="form-group row">
                        <label for="wpinv_minimum_price" class="col-sm-3 col-form-label">
                            <?php _e( 'Minimum Price', 'invoicing' );?>
                        </label>
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <?php if( 'left' == $position ) : ?>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="wpinv_item_minimum_price_symbol"><?php echo wpinv_currency_symbol(); ?></span>
                                    </div>
                                <?php endif; ?>

                                <input type="text" name="wpinv_minimum_price" id="wpinv_minimum_price" value="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_minimum_price( 'edit' ) ) ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 0 ) ); ?>" class="form-control">

                                <?php if( 'left' != $position ) : ?>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="wpinv_item_minimum_price_symbol"><?php echo wpinv_currency_symbol(); ?></span>
                                    </div>
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
            <div class="wpinv_show_if_recurring wpinv_maximum_renewals">

                <div class="form-group row">
                    <label for="wpinv_recurring_limit" class="col-sm-3 col-form-label">
                        <?php _e( 'Maximum Renewals', 'invoicing' );?>
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
            <div class="wpinv_show_if_recurring wpinv_free_trial">

                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="wpinv_trial_interval"><?php defined( 'GETPAID_PAID_TRIALS_VERSION' ) ? _e( 'Free/Paid Trial', 'invoicing' ) : _e( 'Free Trial', 'invoicing' )?></label>

                    <div class="col-sm-8">
                        <div class="row">
                            <div class="col-sm-6">
                                <?php $value = $item->has_free_trial() ? $item->get_trial_interval( 'edit' ) : 0;?>

                                <div>
                                    <input type="number" name="wpinv_trial_interval" style="width: 100%;" placeholder="0" id="wpinv_trial_interval" value="<?php echo esc_attr( $value ); ?>" >
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <?php
                                    echo aui()->select(
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
                                                'D'  => __( 'day(s)', 'invoicing' ),
                                                'W'  => __( 'week(s)', 'invoicing' ),
                                                'M'  => __( 'month(s)', 'invoicing' ),
                                                'Y'  => __( 'year(s)', 'invoicing' ),
                                            )
                                        )
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

            <?php do_action( 'wpinv_item_details_metabox_item_details', $item ); ?>
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

        // Load new data.
        $item->set_props(
			array(
				'price'                => isset( $_POST['wpinv_item_price'] ) ? getpaid_standardize_amount( $_POST['wpinv_item_price'] ) : null,
				'vat_rule'             => isset( $_POST['wpinv_vat_rules'] ) ? wpinv_clean( $_POST['wpinv_vat_rules'] ) : null,
				'vat_class'            => isset( $_POST['wpinv_vat_class'] ) ? wpinv_clean( $_POST['wpinv_vat_class'] ) : null,
				'type'                 => isset( $_POST['wpinv_item_type'] ) ? wpinv_clean( $_POST['wpinv_item_type'] ) : null,
				'is_dynamic_pricing'   => isset( $_POST['wpinv_name_your_price'] ),
                'minimum_price'        => isset( $_POST['wpinv_minimum_price'] ) ? getpaid_standardize_amount( $_POST['wpinv_minimum_price'] ) : null,
				'is_recurring'         => isset( $_POST['wpinv_is_recurring'] ),
				'recurring_period'     => isset( $_POST['wpinv_recurring_period'] ) ? wpinv_clean( $_POST['wpinv_recurring_period'] ) : null,
				'recurring_interval'   => isset( $_POST['wpinv_recurring_interval'] ) ? (int) $_POST['wpinv_recurring_interval'] : 1,
				'recurring_limit'      => isset( $_POST['wpinv_recurring_limit'] ) ? (int) $_POST['wpinv_recurring_limit'] : null,
				'is_free_trial'        => isset( $_POST['wpinv_trial_interval'] ) ? ( 0 != (int) $_POST['wpinv_trial_interval'] ) : null,
				'trial_period'         => isset( $_POST['wpinv_trial_period'] ) ? wpinv_clean( $_POST['wpinv_trial_period'] ) : null,
				'trial_interval'       => isset( $_POST['wpinv_trial_interval'] ) ? (int) $_POST['wpinv_trial_interval'] : null,
			)
        );

		$item->save();
		do_action( 'getpaid_item_metabox_save', $post_id, $item );
	}
}
