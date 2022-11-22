<?php

/**
 * Discount Details
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Discount_Details Class.
 */
class GetPaid_Meta_Box_Discount_Details {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the discount.
        $discount = new WPInv_Discount( $post );

        // Nonce field.
        wp_nonce_field( 'getpaid_meta_nonce', 'getpaid_meta_nonce' );

        do_action( 'wpinv_discount_form_top', $discount );

        // Set the currency position.
        $position = wpinv_currency_position();

        if ( $position == 'left_space' ) {
            $position = 'left';
        }

        if ( $position == 'right_space' ) {
            $position = 'right';
        }

        ?>

        <style>
            #poststuff .input-group-text,
            #poststuff .form-control {
                border-color: #7e8993;
            }
        </style>
        <div class='bsui' style='max-width: 600px;padding-top: 10px;'>

            <?php do_action( 'wpinv_discount_form_first', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_code', $discount ); ?>

            <div class="form-group mb-3 row">
                <label for="wpinv_discount_code" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Discount Code', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <div class="row">
                        <div class="col-sm-12 form-group mb-3">
                            <input type="text" value="<?php echo esc_attr( $discount->get_code( 'edit' ) ); ?>" placeholder="SUMMER_SALE" name="wpinv_discount_code" id="wpinv_discount_code" style="width: 100%;" />
                        </div>
                        <div class="col-sm-12">
                            <?php
                                do_action( 'wpinv_discount_form_before_single_use', $discount );

                                aui()->input(
                                    array(
                                        'id'      => 'wpinv_discount_single_use',
                                        'name'    => 'wpinv_discount_single_use',
                                        'type'    => 'checkbox',
                                        'label'   => __( 'Each customer can only use this discount once', 'invoicing' ),
                                        'value'   => '1',
                                        'checked' => $discount->is_single_use(),
                                    ),
                                    true
                                );

                                do_action( 'wpinv_discount_form_single_use', $discount );
                            ?>
                        </div>
                        <div class="col-sm-12">
                            <?php
                                do_action( 'wpinv_discount_form_before_recurring', $discount );

                                aui()->input(
                                    array(
                                        'id'      => 'wpinv_discount_recurring',
                                        'name'    => 'wpinv_discount_recurring',
                                        'type'    => 'checkbox',
                                        'label'   => __( 'Apply this discount to all recurring payments for subscriptions', 'invoicing' ),
                                        'value'   => '1',
                                        'checked' => $discount->is_recurring(),
                                    ),
                                    true
                                );

                                do_action( 'wpinv_discount_form_recurring', $discount );
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Enter a discount code such as 10OFF.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_code', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_type', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_type" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Discount Type', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_discount_type',
                                'name'             => 'wpinv_discount_type',
                                'label'            => __( 'Discount Type', 'invoicing' ),
                                'placeholder'      => __( 'Select Discount Type', 'invoicing' ),
                                'value'            => $discount->get_type( 'edit' ),
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_discount_types(),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Discount type.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_type', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_amount', $discount ); ?>
            <div class="form-group mb-3 row <?php echo esc_attr( $discount->get_type( 'edit' ) ); ?>" id="wpinv_discount_amount_wrap">
                <label for="wpinv_discount_amount" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Discount Amount', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <div class="input-group input-group-sm">

                        <?php if ( 'left' == $position ) : ?>
                            <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                <div class="input-group-prepend left wpinv-if-flat">
                                    <span class="input-group-text">
                                        <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                    </span>
                                </div>
                            <?php else : ?>
                                <span class="input-group-text left wpinv-if-flat">
                                    <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <input type="text" name="wpinv_discount_amount" id="wpinv_discount_amount" value="<?php echo esc_attr( $discount->get_amount( 'edit' ) ); ?>" placeholder="0" class="form-control">

                        <?php if ( 'right' == $position ) : ?>
                            <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                                <div class="input-group-append right wpinv-if-flat">
                                    <span class="input-group-text">
                                        <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                    </span>
                                </div>
                            <?php else : ?>
                                <span class="input-group-text left wpinv-if-flat">
                                    <?php echo wp_kses_post( wpinv_currency_symbol() ); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                            <div class="input-group-append right wpinv-if-percent">
                                <span class="input-group-text">%</span>
                            </div>
                        <?php else : ?>
                            <span class="input-group-text right wpinv-if-percent">%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Enter the discount value. Ex: 10', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_amount', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_items', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_items" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Items', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_discount_items',
                                'name'             => 'wpinv_discount_items[]',
                                'label'            => __( 'Items', 'invoicing' ),
                                'placeholder'      => __( 'Select Items', 'invoicing' ),
                                'value'            => $discount->get_items( 'edit' ),
                                'select2'          => true,
                                'multiple'         => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_published_items_for_dropdown(),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Select the items that are allowed to use this discount or leave blank to use this discount all items.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_items', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_excluded_items', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_excluded_items" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Excluded Items', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_discount_excluded_items',
                                'name'             => 'wpinv_discount_excluded_items[]',
                                'label'            => __( 'Excluded Items', 'invoicing' ),
                                'placeholder'      => __( 'Select Items', 'invoicing' ),
                                'value'            => $discount->get_excluded_items( 'edit' ),
                                'select2'          => true,
                                'multiple'         => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_published_items_for_dropdown(),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Select all the items that are not allowed to use this discount.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_excluded_items', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_required_items', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_required_items" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Required Items', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->select(
                            array(
                                'id'               => 'wpinv_discount_required_items',
                                'name'             => 'wpinv_discount_required_items[]',
                                'label'            => __( 'Required Items', 'invoicing' ),
                                'placeholder'      => __( 'Select Items', 'invoicing' ),
                                'value'            => $discount->get_required_items( 'edit' ),
                                'select2'          => true,
                                'multiple'         => true,
                                'data-allow-clear' => 'false',
                                'options'          => wpinv_get_published_items_for_dropdown(),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Select all the items that are required to be in the cart before using this discount.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_required_items', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_start', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_start" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Start Date', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->input(
                            array(
                                'type'             => 'datepicker',
                                'id'               => 'wpinv_discount_start',
                                'name'             => 'wpinv_discount_start',
                                'label'            => __( 'Start Date', 'invoicing' ),
                                'placeholder'      => 'YYYY-MM-DD 00:00',
                                'class'            => 'form-control-sm',
                                'value'            => $discount->get_start_date( 'edit' ),
                                'extra_attributes' => array(
                                    'data-enable-time' => 'true',
                                    'data-time_24hr'   => 'true',
                                    'data-allow-input' => 'true',
                                ),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'For no start date, leave blank. If entered, the discount can only be used after or on this date.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_start', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_expiration', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_expiration" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Expiration Date', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <?php
                        aui()->input(
                            array(
                                'type'             => 'datepicker',
                                'id'               => 'wpinv_discount_expiration',
                                'name'             => 'wpinv_discount_expiration',
                                'label'            => __( 'Expiration Date', 'invoicing' ),
                                'placeholder'      => 'YYYY-MM-DD 00:00',
                                'class'            => 'form-control-sm',
                                'value'            => $discount->get_end_date( 'edit' ),
                                'extra_attributes' => array(
                                    'data-enable-time' => 'true',
                                    'data-time_24hr'   => 'true',
                                    'data-min-date'    => 'today',
                                    'data-allow-input' => 'true',
                                    'data-input'       => 'true',
                                ),
                            ),
                            true
                        );
                    ?>
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Optionally set the date after which the discount will expire.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_expiration', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_min_total', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_min_total" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Minimum Amount', 'invoicing' ); ?>
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

                        <input type="text" name="wpinv_discount_min_total" id="wpinv_discount_min_total" value="<?php echo esc_attr( $discount->get_minimum_total( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'No minimum', 'invoicing' ); ?>" class="form-control">

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
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Optionally set the minimum amount (including taxes) required to use this discount.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_min_total', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_max_total', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_max_total" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Maximum Amount', 'invoicing' ); ?>
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

                        <input type="text" name="wpinv_discount_max_total" id="wpinv_discount_max_total" value="<?php echo esc_attr( $discount->get_maximum_total( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'No maximum', 'invoicing' ); ?>" class="form-control">

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
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Optionally set the maximum amount (including taxes) allowed when using this discount.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_before_max_total', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_before_max_uses', $discount ); ?>
            <div class="form-group mb-3 row">
                <label for="wpinv_discount_max_uses" class="col-sm-3 col-form-label">
                    <?php esc_html_e( 'Maximum Uses', 'invoicing' ); ?>
                </label>
                <div class="col-sm-8">
                    <input type="text" value="<?php echo esc_attr( $discount->get_max_uses( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'invoicing' ); ?>" name="wpinv_discount_max_uses" id="wpinv_discount_max_uses" style="width: 100%;" />
                </div>
                <div class="col-sm-1 pt-2 pl-0">
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Optionally set the maximum number of times that this discount code can be used.', 'invoicing' ); ?>"></span>
                </div>
            </div>
            <?php do_action( 'wpinv_discount_form_max_uses', $discount ); ?>

            <?php do_action( 'wpinv_discount_form_last', $discount ); ?>

        </div>
        <?php
        do_action( 'wpinv_discount_form_bottom', $post );
    }

    /**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {

        // Prepare the discount.
        $discount = new WPInv_Discount( $post_id );

        // Load new data.
        $discount->set_props(
			array(
				'code'           => isset( $_POST['wpinv_discount_code'] ) ? wpinv_clean( $_POST['wpinv_discount_code'] ) : null,
				'amount'         => isset( $_POST['wpinv_discount_amount'] ) ? floatval( $_POST['wpinv_discount_amount'] ) : null,
				'start'          => isset( $_POST['wpinv_discount_start'] ) ? wpinv_clean( $_POST['wpinv_discount_start'] ) : null,
				'expiration'     => isset( $_POST['wpinv_discount_expiration'] ) ? wpinv_clean( $_POST['wpinv_discount_expiration'] ) : null,
				'is_single_use'  => ! empty( $_POST['wpinv_discount_single_use'] ),
                'type'           => isset( $_POST['wpinv_discount_type'] ) ? wpinv_clean( $_POST['wpinv_discount_type'] ) : null,
				'is_recurring'   => ! empty( $_POST['wpinv_discount_recurring'] ),
				'items'          => isset( $_POST['wpinv_discount_items'] ) ? wpinv_clean( $_POST['wpinv_discount_items'] ) : array(),
				'excluded_items' => isset( $_POST['wpinv_discount_excluded_items'] ) ? wpinv_clean( $_POST['wpinv_discount_excluded_items'] ) : array(),
                'required_items' => isset( $_POST['wpinv_discount_required_items'] ) ? wpinv_clean( $_POST['wpinv_discount_required_items'] ) : array(),
				'max_uses'       => isset( $_POST['wpinv_discount_max_uses'] ) ? intval( $_POST['wpinv_discount_max_uses'] ) : null,
				'min_total'      => isset( $_POST['wpinv_discount_min_total'] ) ? floatval( $_POST['wpinv_discount_min_total'] ) : null,
				'max_total'      => isset( $_POST['wpinv_discount_max_total'] ) ? floatval( $_POST['wpinv_discount_max_total'] ) : null,
			)
        );

		$discount->save();
		do_action( 'getpaid_discount_metabox_save', $post_id, $discount );
	}
}
