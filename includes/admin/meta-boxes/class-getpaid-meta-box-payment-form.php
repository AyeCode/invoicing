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
                                        <button class="button btn">
                                            <span v-if="element.icon" class="dashicons dashicon-" :class="'dashicon-' + element.icon"></span>
                                            {{element.name}}
                                        </button>
                                    </li>
                                </draggable>
                            </div>
                        </div>

                        <!-- Edit an element -->
                        <div class="wpinv-form-builder-tab-pane" v-if="active_tab=='edit_item'" style="font-size: 16px;">
                            <div class="wpinv-form-builder-edit-field-wrapper">
                                <?php do_action( 'wpinv_payment_form_edit_element_template', 'active_form_element', $post ); ?>
                                <div>
                                    <button type="button" class="button button-link button-link-delete" @click.prevent="removeField(active_form_element)" v-show="! active_form_element.premade"><?php _e( 'Delete Element', 'invoicing' ); ?></button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="col-sm-8">
                    <small class='form-text text-muted' v-if='form_elements.length'><?php _e( 'Click on any element to edit or delete it.', 'invoicing' ); ?></small>
                    <p class='form-text text-muted' v-if='! form_elements.length'><?php _e( 'This form is empty. Add new elements by dragging them from the right.', 'invoicing' ); ?></p>

                    <draggable class="section bsui" v-model="form_elements" @add="highlightLastDroppedField" group="fields" tag="div" style="min-height: 100%; font-size: 16px;">
                        <div v-for="form_element in form_elements" class="wpinv-form-builder-element-preview" :class="{ active: active_form_element==form_element &&  active_tab=='edit_item' }" @click="active_tab = 'edit_item'; active_form_element = form_element">
                            <?php do_action( 'wpinv_payment_form_render_element_template', 'form_element', $post ); ?>
                        </div>
                    </draggable>

                    <textarea style="display:none;" name="wpinv_form_elements" v-model="elementString"></textarea>
                    <textarea style="display:none;" name="wpinv_form_items" v-model="itemString"></textarea>
                </div>

            </div>
        </div>
        <?php

        wp_nonce_field( 'wpinv_save_payment_form', 'wpinv_save_payment_form' ) ;
    }

    /**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {

        // verify nonce
        if ( ! isset( $_POST['wpinv_save_payment_form'] ) || ! wp_verify_nonce( $_POST['wpinv_save_payment_form'], 'wpinv_save_payment_form' ) ) {
            return;
        }

        // Prepare the form.
        $form = new GetPaid_Payment_Form( $post_id );

        // Fetch form items.
        $form_items = json_decode( wp_unslash( $_POST['wpinv_form_items'] ), true );

        // Ensure that we have an array...
        if ( empty( $form_items ) ) {
            $form_items = array();
        }

        // ... and that new items are saved to the db.
        $form_items = self::maybe_save_items( $form_items );

        // Add it to the form.
        $form->set_items( $form_items );

        // Save form elements.
        $form_elements = json_decode( wp_unslash( $_POST['wpinv_form_elements'] ), true );
        if ( empty( $form_elements ) ) {
            $form_elements = array();
        }

        $form->set_elements( $form_elements );

        // Persist data to the datastore.
        $form->save();
        do_action( 'getpaid_payment_form_metabox_save', $post_id, $form );

    }

    /**
     * Saves unsaved form items.
     */
    public static function maybe_save_items( $items ) {

        $saved = array();

        foreach( $items as $item ) {

            if ( is_numeric( $item['id'] ) ) {
                $saved[] = $item;
                continue;
            }

            $new_item = new WPInv_Item();

            // Save post data.
            $new_item->set_props(
                array(
                    'name'        => sanitize_text_field( $item['title'] ),
                    'description' => wp_kses_post( $item['description'] ),
                    'status'      => 'publish',
                    'type'        => empty( $item['type'] ) ? 'custom' : $item['type'],
                    'price'       => wpinv_sanitize_amount( $item['price'] ),
                    'vat_rule'    => empty( $item['rule'] ) ? 'digital' : $item['rule'],
                    'vat_class'   => empty( $item['class'] ) ? '_standard' : $item['class'],
                )
            );

            // Save the item.
            $new_item->save();

            if ( $new_item->get_id() ) {
                $item['id'] = $new_item->get_id();
                unset( $item['new'] );
                unset( $item['type'] );
                unset( $item['class'] );
                unset( $item['rule'] );
                unset( $item['price'] );
                unset( $item['title'] );
                $saved[] = $item;
            }

        }

        return $saved;

    }

}
