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

        $items = get_post_meta( $post->ID, 'wpinv_payment_form_items', true );

        if ( empty( $items ) || ! is_array( $items ) ) {
            $items = array();
        }

        ?>

        <div class="wpinv-items-wrap-pending" id="wpinv_items_wrap">
            <table id="wpinv_items" class="wpinv-items" cellspacing="0" cellpadding="0">

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

                        foreach ( $items as $item_data ) {

                            $id   = isset( $item_data['id'] ) ? (int) $item_data['id'] : 0;
                            $item = new WPInv_Item( $id );

                            if ( empty( $item ) || $item->post_type != 'wpi_item' ) {
                                continue;
                            }
                            
                            $name          = isset( $item_data['name'] ) ? sanitize_text_field( $item_data['name'] ) : $item->get_name();
                            $price         = isset( $item_data['price'] ) ? wpinv_format_amount( $item_data['price'] ) : $item->get_price();
                            $description   = isset( $item_data['description'] ) ? esc_textarea( $item_data['description'] ) : $item->get_summary();

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

                    <tr class="item create-item" style="display:none;" id="wpinv-payment-form-quick-add">
                        <td class="id"></td>

                        <td class="title">
                            <input type="text" class="regular-text" placeholder="<?php _e( 'Item Name', 'invoicing' ); ?>" value="" id="wpinv_create_payment_form_item_name" />

                            <div class="wp-clearfix">
                                <label class="wpi-item-actions">
                                    <span class="input-text-wrap">
                                        <input type="button" value="<?php esc_attr_e( 'Add', 'invoicing' ); ?>" class="button button-primary" id="wpinv-payment-form-save-item">
                                        <input type="button" value="Cancel" class="button button-secondary" id="wpinv-payment-form-cancel-item">
                                    </span>
                                </label>
                            </div>
                        </td>

                        <td class="meta">
                            <textarea placeholder="<?php esc_attr_e( 'Item description', 'invoicing' ) ?>" id="wpinv_create_payment_form_item_description" class="large-text" rows="3"></textarea>
                        </td>

                        <td class="price">
                            <input type="text" placeholder="0.00" class="wpi-field-price wpi-price" id="wpinv_create_payment_form_item_price" />
                        </td>

                    </tr>
                </tbody>
            </table>

            <div class="wpinv-actions">

                <?php

                    echo wpinv_item_dropdown( array(
                        'name'             => 'wpinv_payment_form_item',
                        'id'               => 'wpinv_payment_form_item',
                        'show_recurring'   => true,
                        'class'            => 'wpi_select2',
                    ) );

                ?>

                <input type="button" value="<?php esc_attr_e( 'Add item to form', 'invoicing'); ?>" class="button button-primary" id="wpinv-payment-form-add-item" />
                <input type="button" value="<?php esc_attr_e( 'Create new item', 'invoicing' );?>" class="button button-primary" id="wpinv-payment-form-new-item" />

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

        <div class="wpinv-items-wrap-pending" id="wpinv_options_wrap">
            <table class="form-table">
                <tbody>

                    <tr class="form-field-success_page">
                        <th scope="row"><label for="field_success_page"><?php _e( 'Success Page', 'invoicing' ); ?></label></th>
                        <td>
                            <div>
                                <input type="text" class="regular-text" name="success_page" id="field_success_page" value="<?php echo esc_attr( $values['success_page'] ); ?>">
                                <p class="description"><?php _e( 'Where should we redirect users after successfuly completing their payment?', 'invoicing' ); ?></p>
                            </div>
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Output fields.
     *
     * @param WP_Post $post
     */
    public static function form_design ( $post ) {
        ?>

        <div id="wpinv-form-builder" style="display: flex; flex-flow: wrap;">

            <div class="wpinv-form-builder-left" style="flex: 0 0 320px;">
                <div class="wpinv-form-builder-tabs nav-tab-wrapper">
                    <a @click.prevent="active_tab='new_item'" class="nav-tab" :class="{ 'nav-tab-active': active_tab=='new_item' }" href="#"><?php _e( 'Add new element', 'invoicing' ); ?></a>
                    <a @click.prevent="active_tab='edit_item'" class="nav-tab" :class="{ 'nav-tab-active': active_tab=='edit_item' }" href="#"><?php _e( 'Edit element', 'invoicing' ); ?></a>
                </div>

                <div class="wpinv-form-builder-tab-content bsui" style="margin-top: 16px;">
                    <div class="wpinv-form-builder-tab-pane" v-if="active_tab=='new_item'">
                        <div class="wpinv-form-builder-add-field-types">

                            <draggable class="section" style="display: flex; flex-flow: wrap; justify-content: space-between;" v-model="elements" :group="{ name: 'fields', pull: 'clone', put: false }" :sort="false" :clone="addDraggedField" tag="ul" filter=".wpinv-undraggable">
                                <li v-for="element in elements" style="width: 49%; background-color: #fafafa; margin-bottom: 9px; cursor: move; border: 1px solid #eeeeee;" @click.prevent="addField(element)">
                                    <button class="button btn" style="width: 100%; cursor: move;">
                                        <span v-if="element.icon" class="dashicons dashicon-" :class="'dashicon-' + element.icon"></span>
                                        {{element.name}}
                                    </button>
                                </li>
                            </draggable>

                        </div>
                    </div>

                <div class="wpinv-form-builder-tab-pane bsui" v-if="active_tab=='edit_item'" style="font-size: 16px;">
                    <div class="wpinv-form-builder-edit-field-wrapper">
                        <?php do_action( 'wpinv_payment_form_edit_element_template', 'active_form_element', $post ); ?>
                        <div>
                            <button type="button" class="button button-link button-link-delete" @click.prevent="removeField(active_form_element)"><?php _e( 'Delete Field', 'invoicing' ); ?></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="wpinv-form-builder-right" style="flex: 1; padding-top: 40px;border-left: 1px solid #ddd;padding-left: 20px;min-height: 400px;margin-left: 10px;">

            <draggable class="section bsui" v-model="form_elements" @add="highlightLastDroppedField" group="fields" tag="div" style="min-height: 100%; font-size: 16px;">
                <div v-for="form_element in form_elements" class="wpinv-form-builder-element-preview" :class="{ active: active_form_element==form_element &&  active_tab=='edit_item' }" @click="active_tab = 'edit_item'; active_form_element = form_element">
                    <?php do_action( 'wpinv_payment_form_render_element_template', 'form_element', $post ); ?>
                </div>
            </draggable>
        </div>

        </div>
        
        <?php
    }

    public static function save( $post_id, $data, $post ) {

    }
}
