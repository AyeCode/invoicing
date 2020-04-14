<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Form_Items {

    /**
     * Outputs the payment forms items select.
     *
     * @param WP_Post $post
     */
    public static function output( $post ) {
        global $wpinv_euvat, $ajax_cart_details;

        $post_id             = ! empty( $post->ID ) ? $post->ID : 0;
        $items               = get_post_meta( $post->ID, 'wpinv_payment_items', true );
        $supports_discounts  = (int) get_post_meta( $post->ID, 'wpinv_form_supports_discounts', true );
        $supports_quantities = (int) get_post_meta( $post->ID, 'wpinv_form_supports_quantities', true );
        $enable_taxes        = (int) get_post_meta( $post->ID, 'wpinv_form_supports_quantities', true );
        $item_types          = apply_filters( 'wpinv_item_types_for_quick_add_item', wpinv_get_item_types(), $post );

        if ( empty( $items ) || ! is_array( $items ) ) {
            $items = array();
        }

        ?>

        <div class="wpinv-items-wrap-pending" id="wpinv_items_wrap">
            <table
                id="wpinv_items"
                class="wpinv-items"
                cellspacing="0"
                cellpadding="0"
                data-supports-discouts="<?php echo $supports_discounts; ?>"
                data-supports-discouts="<?php echo $supports_quantities; ?>"
                data-supports-discouts="<?php echo $enable_taxes; ?>"
                data-decimal-places="<?php echo esc_attr( wpinv_decimals() ); ?>"
                data-currency-symbol="<?php echo esc_attr( wpinv_currency_symbol() ); ?>"
                data-currency-position="<?php echo esc_attr( wpinv_currency_position() ); ?>"
            >

                <thead>
                    <tr>
                        <th class="id"><?php _e( 'ID', 'invoicing' );?></th>
                        <th class="title"><?php _e( 'Name', 'invoicing' );?></th>
                        <th class="desc"><?php _e( 'Description', 'invoicing' );?></th>
                        <th class="price"><?php _e( 'Price', 'invoicing' );?></th>
                        <th class="action"></th>
                    </tr>
                </thead>

                <tbody class="wpinv-line-items">
                    <?php

                        foreach ( $items as $item ) {
                            $id            = isset( $item['id'] ) ? (int) $item['id'] : 0;
                            $name          = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : __( 'No name', 'invoicing' );
                            $price         = isset( $item['price'] ) ? wpinv_format_amount( $item['price'] ) : 0.00;
                            $description   = isset( $item['description'] ) ? esc_textarea( $item['description'] ) : '';

                    ?>
                    
                    <tr class="item" data-item-id="<?php echo $id; ?>">
                        <td class="id"><?php echo $id; ?></td>
                        <td class="title">
                            <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>" target="_blank"><?php echo $name ; ?></a>
                            <?php echo wpinv_get_item_suffix( $id ); ?>
                        </td>
                        <td class="meta">
                            <?php echo $description ; ?>
                        </td>
                        <td class="price">
                            <?php echo $price ; ?>
                        </td>
                    </tr>

                    <?php } ?>

                </tbody>
            </table>
            <div id="wpinv-quick-add" style="display: none;">
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="id">
                        </td>
                        <td class="title">
                            <input type="text" class="regular-text" placeholder="<?php _e( 'Item Name', 'invoicing' ); ?>" value="" name="_wpinv_quick[name]">

                            <div class="wp-clearfix">
                                <label class="wpi-item-type">
                                    <span class="title"><?php _e( 'Item type', 'invoicing' );?></span>
                                </label>
                            </div>

                            <div class="wp-clearfix">
                                <?php 
                                    echo wpinv_html_textarea( array(
                                        'name'  => '_wpinv_quick[excerpt]',
                                        'id'    => '_wpinv_quick_excerpt',
                                        'value' => '',
                                        'class' => 'large-text',
                                        'label' => __( 'Item description', 'invoicing' ),
                                    ) ); 
                                ?>
                            </div>

                            <div class="wp-clearfix">
                                <label class="wpi-item-actions">
                                    <span class="input-text-wrap">
                                        <input type="button" value="<?php esc_attr_e( 'Add', 'invoicing' ); ?>" class="button button-primary" id="wpinv-save-item"><input type="button" value="Cancel" class="button button-secondary" id="wpinv-cancel-item">
                                    </span>
                                </label>
                            </div>
                        </td>

                        <td class="price">
                            <input type="text" placeholder="0.00" class="wpi-field-price wpi-price" name="_wpinv_quick[price]" />
                        </td>

                        <td class="action"></td>
                    </tr>
                </table>
            </div>
            <div class="wpinv-actions">

                <?php

                    echo wpinv_item_dropdown( array(
                        'name'             => 'wpinv_invoice_item',
                        'id'               => 'wpinv_invoice_item',
                        'show_recurring'   => true,
                        'class'            => 'wpi_select2',
                    ) );

                ?>

                <input type="button" value="<?php esc_attr_e( 'Add item to form', 'invoicing'); ?>" class="button button-primary" id="wpinv-add-item" />
                <input type="button" value="<?php esc_attr_e( 'Create new item', 'invoicing' );?>" class="button button-primary" id="wpinv-new-item" />

            </div>
        </div>
        <?php
    }

     /**
     * Outputs the payment options.
     *
     * @param WP_Post $post
     */
    public static function output_options( $post ) {

        $post_id             = ! empty( $post->ID ) ? $post->ID : 0;
        $success_page        = get_post_meta( $post->ID, 'wpinv_success_page', true );
        $button_text         = get_post_meta( $post->ID, 'wpinv_button_text', true );
        $processing_text     = get_post_meta( $post->ID, 'wpinv_processing_text', true );
        $supports_quantities = (int) get_post_meta( $post->ID, 'wpinv_form_supports_quantities', true );
        $supports_discounts  = (int) get_post_meta( $post->ID, 'wpinv_form_supports_discounts', true );
        $enable_taxes        = (int) get_post_meta( $post->ID, 'wpinv_form_supports_quantities', true );

        $values = array(
            'success_page'         => empty( $success_page ) ? wpinv_get_success_page_uri() : $success_page,
            'button_text'          => empty( $button_text ) ? __( 'Pay Now', 'invoicing' ) : $button_text,
            'processing_text'      => empty( $processing_text ) ? __( 'Processing', 'invoicing' ) : $processing_text,
            'supports_quantities'  => empty( $supports_quantities ) ? 0 : 1,
            'enable_taxes'         => empty( $enable_taxes ) ? 0 : 1,
            'supports_discounts'   => empty( $supports_discounts ) ? 0 : 1,
        );

        ?>

        <div class="wpinv-items-wrap-pending" id="wpinv_items_wrap">
            <table class="form-table">
                <tbody>

                <tr class="form-field-supports_quantities">
                        <th scope="row"></th>
                        <td>
                            <div>
                                <label>
                                    <input type="checkbox" name="supports_quantities" id="field_supports_quantities" value="1" <?php checked( $values['supports_quantities'], 1 ); ?>>
                                    <span><?php _e( 'Let users set custom item quantities', 'invoicing' ); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field-enable_taxes">
                        <th scope="row"></th>
                        <td>
                            <div>
                                <label>
                                    <input type="checkbox" name="enable_taxes" id="field_enable_taxes" value="1" <?php checked( $values['enable_taxes'], 1 ); ?>>
                                    <span><?php _e( 'Enable tax calculations', 'invoicing' ); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field-supports_discounts">
                        <th scope="row"></th>
                        <td>
                            <div>
                                <label>
                                    <input type="checkbox" name="supports_discounts" id="field_supports_discounts" value="1" <?php checked( $values['supports_discounts'], 1 ); ?>>
                                    <span><?php _e( 'Enable coupon codes', 'invoicing' ); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field-success_page">
                        <th scope="row"><label for="field_success_page"><?php _e( 'Success Page', 'invoicing' ); ?></label></th>
                        <td>
                            <div>
                                <input type="text" class="regular-text" name="success_page" id="field_success_page" value="<?php echo esc_attr( $values['success_page'] ); ?>">
                                <p class="description"><?php _e( 'Where should we redirect users after successfuly completing their payment?', 'invoicing' ); ?></p>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field-button_text">
                        <th scope="row"><label for="field_button_text"><?php _e( 'Button Text', 'invoicing' ); ?></label></th>
                        <td>
                            <div>
                                <input type="text" class="regular-text" name="button_text" id="field_button_text" value="<?php echo esc_attr( $values['button_text'] ); ?>">
                                <p class="description"><?php _e( 'Payment button text', 'invoicing' ); ?></p>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field-processing_text">
                        <th scope="row"><label for="field_processing_text"><?php _e( 'Processing Text', 'invoicing' ); ?></label></th>
                        <td>
                            <div>
                                <input type="text" class="regular-text" name="processing_text" id="field_processing_text" value="<?php echo esc_attr( $values['processing_text'] ); ?>">
                                <p class="description"><?php _e( 'Processing payment button text', 'invoicing' ); ?></p>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        <?php
    }

    public static function save( $post_id, $data, $post ) {

    }
}
