<?php
/**
 * Payment Form
 *
 * Displays the payment form editing meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Payment_Form Class.
 */
class GetPaid_Meta_Box_Payment_Form {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
        ?>
        <style>
            .wpinv-form-builder-edit-field-wrapper label.d-block > span:first-child{
                display: inline-block;
                margin-bottom: .5rem;
            }
        </style>
        <div id="wpinv-form-builder" class="bsui">
            <div class="row">
                <div class="col-sm-4">

                    <!-- Builder tabs -->
                    <button class="button button-primary" v-if="active_tab!='new_item'" @click.prevent="active_tab='new_item'"><?php _e( 'Go Back', 'invoicing' ); ?></button>

                    <!-- Builder tab content -->
                    <div class="mt-4">

                        <!-- Available builder elements -->
                        <div class="wpinv-form-builder-tab-pane" v-if="active_tab=='new_item'">
                            <div class="wpinv-form-builder-add-field-types">
                                <small class='form-text text-muted'><?php _e( 'Add an element by dragging it to the payment form.', 'invoicing' ); ?></small>
                                <draggable class="section mt-2" style="display: flex; flex-flow: wrap; justify-content: space-between;" v-model="elements" :group="{ name: 'fields', pull: 'clone', put: false }" :sort="false" :clone="addDraggedField" tag="ul" filter=".wpinv-undraggable">
                                    <li v-for="element in elements" class= "wpinv-payment-form-left-fields-field" @click.prevent="addField(element)" :class="{ 'd-none': element.defaults.premade }">
                                        <button class="button btn text-dark">
                                            <span v-if="element.icon" class="dashicons dashicon-" :class="'dashicon-' + element.icon"></span>
                                            {{element.name}}
                                        </button>
                                    </li>
                                </draggable>
                            </div>
                        </div>

                        <!-- Edit an element -->
                        <div class="wpinv-form-builder-tab-pane" v-if="active_tab=='edit_item'" style="font-size: 14px;">
                            <div class="wpinv-form-builder-edit-field-wrapper">
                                <?php do_action( 'wpinv_payment_form_edit_element_template', 'active_form_element', $post ); ?>
                                <?php do_action( 'getpaid_payment_form_edit_element_template', $post ); ?>
                                <div class='form-group'>
                                    <label :for="active_form_element.id + '_grid_width'"><?php esc_html_e( 'Width', 'invoicing' ) ?></label>
                                    <select class='form-control custom-select' :id="active_form_element.id + '_grid_width'" v-model='gridWidth'>
                                        <option value='full'><?php esc_html_e( 'Full Width', 'invoicing' ); ?></option>
                                        <option value='half'><?php esc_html_e( 'Half Width', 'invoicing' ); ?></option>
                                        <option value='third'><?php esc_html_e( '1/3 Width', 'invoicing' ); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" class="button button-link button-link-delete" @click.prevent="removeField(active_form_element)" v-show="! active_form_element.premade"><?php _e( 'Delete Element', 'invoicing' ); ?></button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="col-sm-8 border-left">
                    <small class='form-text text-muted' v-if='form_elements.length'><?php _e( 'Click on any element to edit or delete it.', 'invoicing' ); ?></small>
                    <p class='form-text text-muted' v-if='! form_elements.length'><?php _e( 'This form is empty. Add new elements by dragging them from the right.', 'invoicing' ); ?></p>

                    <div class="container-fluid">
                        <draggable class="section row" v-model="form_elements" @add="highlightLastDroppedField" group="fields" tag="div" style="min-height: 100%; font-size: 14px;">
                            <div v-for="form_element in form_elements" class="wpinv-form-builder-element-preview" :class="[{ active: active_form_element==form_element &&  active_tab=='edit_item' }, form_element.type, grid_class( form_element ) ]" @click="active_tab = 'edit_item'; active_form_element = form_element">
                                <div class="wpinv-form-builder-element-preview-inner">
                                    <div class="wpinv-payment-form-field-preview-overlay"></div>
                                    <?php do_action( 'wpinv_payment_form_render_element_template', 'form_element', $post ); ?>
                                </div>
                            </div>
                        </draggable>
                    </div>

                    <textarea style="display:none;" name="wpinv_form_elements" v-model="elementString"></textarea>
                    <textarea style="display:none;" name="wpinv_form_items" v-model="itemString"></textarea>
                </div>

            </div>
        </div>
        <script type="text/x-template" id="gpselect2-template">
            <select>
                <slot></slot>
            </select>
        </script>
        <?php

        wp_nonce_field( 'getpaid_meta_nonce', 'getpaid_meta_nonce' );
    }

    /**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {

        // Prepare the form.
        $form = new GetPaid_Payment_Form( $post_id );

        // Fetch form items.
        $form_items = json_decode( wp_unslash( $_POST['wpinv_form_items'] ), true );

        // Ensure that we have an array...
        if ( empty( $form_items ) ) {
            $form_items = array();
        }

        // Add it to the form.
        $form->set_items( self::item_to_objects( wp_kses_post_deep( $form_items ) ) );

        // Save form elements.
        $form_elements = json_decode( wp_unslash( $_POST['wpinv_form_elements'] ), true );
        if ( empty( $form_elements ) ) {
            $form_elements = array();
        }

        $form->set_elements( wp_kses_post_deep( $form_elements ) );

        // Persist data to the datastore.
        $form->save();
        do_action( 'getpaid_payment_form_metabox_save', $post_id, $form );

    }

    /**
	 * Converts an array fo form items to objects.
	 *
	 * @param array $items
	 */
	public static function item_to_objects( $items ) {

        $objects = array();

        foreach ( $items as $item ) {
            $_item = new GetPaid_Form_Item( $item['id'] );
            $_item->set_allow_quantities( (bool) $item['allow_quantities'] );
            $_item->set_is_required( (bool) $item['required'] );
            $objects[] = $_item;
        }

        return $objects;
    }

}
