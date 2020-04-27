<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Payment_Form {

    /**
     * Output payment form details.
     *
     * @param WP_Post $post
     */
    public static function output_details( $post ) {
        $details = get_post_meta( $post->ID, 'payment_form_data', true );

        if ( ! is_array( $details ) ) {
            return;
        }

        echo '<div class="gdmbx2-wrap form-table"> <div class="gdmbx2-metabox gdmbx-field-list">';

        foreach ( $details as $key => $value ) {
            $key = sanitize_text_field( $key );

            if ( is_array( $value ) ) {
                $value = implode( ',', $value );
            }

            $value = esc_html( $value );

            echo "<div class='gdmbx-row gdmbx-type-select'>";
            echo "<div class='gdmbx-th'><label>$key:</label></div>";
            echo "<div class='gdmbx-td'>$value</div></div>";
        }

        echo "</div></div>";

    }

    /**
     * Output fields.
     *
     * @param WP_Post $post
     */
    public static function output_shortcode( $post ) {

        if ( ! is_numeric( $post ) ) {
            $post = $post->ID;
        }

        echo "<input type='text' style='min-width: 220px;' value='[wpinv_payment_form form=$post]' disabled>";

    }
    /**
     * Output fields.
     *
     * @param WP_Post $post
     */
    public static function output ( $post ) {
        $success_page        = get_post_meta( $post->ID, 'wpinv_success_page', true );
        $success_page        = empty( $success_page ) ? wpinv_get_success_page_uri() : $success_page
        ?>

        <div id="wpinv-form-builder" style="display: flex; flex-flow: wrap;">

            <div class="wpinv-form-builder-left bsui" style="flex: 0 0 320px;">
                <ul class="wpinv-form-builder-tabs  nav nav-tabs">
                    <li class='nav-item' v-if="active_tab!='new_item'">
                        <a @click.prevent="active_tab='new_item'" class="nav-link p-3" :class="{ 'active': active_tab=='new_item' }" href="#"><?php _e( 'Add new element', 'invoicing' ); ?></a>
                    </li>
                    <li class='nav-item' v-if='false'>
                        <a @click.prevent="active_tab='edit_item'" class="nav-link p-3" :class="{ 'active': active_tab=='edit_item' }" href="#"><?php _e( 'Edit element', 'invoicing' ); ?></a>
                    </li>
                    <li class='nav-item' v-if='false'>
                        <a @click.prevent="active_tab='settings'" class="nav-link p-3" :class="{ 'active': active_tab=='settings' }" href="#"><?php _e( 'Settings', 'invoicing' ); ?></a>
                    </li>
                </ul>

                <div class="wpinv-form-builder-tab-content bsui" style="margin-top: 16px;">
                    <div class="wpinv-form-builder-tab-pane" v-if="active_tab=='new_item'">
                        <div class="wpinv-form-builder-add-field-types">
                            <small class='form-text text-muted'><?php _e( 'Add an element by dragging it to the payment form.', 'invoicing' ); ?></small>
                            <draggable class="section mt-2" style="display: flex; flex-flow: wrap; justify-content: space-between;" v-model="elements" :group="{ name: 'fields', pull: 'clone', put: false }" :sort="false" :clone="addDraggedField" tag="ul" filter=".wpinv-undraggable">
                                <li v-for="element in elements" style="width: 49%; background-color: #fafafa; margin-bottom: 9px; cursor: move; border: 1px solid #eeeeee;" @click.prevent="addField(element)" :class="{ 'd-none': element.defaults.premade }">
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
                                <button type="button" class="button button-link button-link-delete" @click.prevent="removeField(active_form_element)" v-show="! active_form_element.premade"><?php _e( 'Delete Field', 'invoicing' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="wpinv-form-builder-tab-pane bsui" v-if="active_tab=='settings'">
                        <div class="wpinv-form-builder-settings-wrapper">
                        
                            <div class='form-group'>
                                <label for="wpi-success-page"><?php _e( 'Success Page', 'invoicing' ); ?></label>
                                <input  placeholder="https://" id='wpi-success-page' value="<?php echo esc_url( $success_page ); ?>" class='form-control' type='text'>
                                <small class='form-text text-muted'><?php _e( 'Where should we redirect users after successfuly completing their payment?', 'invoicing' ); ?></small>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <div class="wpinv-form-builder-right" style="flex: 1; padding-top: 40px;border-left: 1px solid #ddd;padding-left: 20px;min-height: 520px;margin-left: 10px;">

                <small class='form-text text-muted' v-if='form_elements.length'><?php _e( 'Click on any element to edit or delete it.', 'invoicing' ); ?></small>
                <p class='form-text text-muted' v-if='! form_elements.length'><?php _e( 'This form is empty. Add new elements by dragging them from the right.', 'invoicing' ); ?></p>

                <draggable class="section bsui" v-model="form_elements" @add="highlightLastDroppedField" group="fields" tag="div" style="min-height: 100%; font-size: 16px;">
                    <div v-for="form_element in form_elements" class="wpinv-form-builder-element-preview" :class="{ active: active_form_element==form_element &&  active_tab=='edit_item' }" @click="active_tab = 'edit_item'; active_form_element = form_element">
                        <?php do_action( 'wpinv_payment_form_render_element_template', 'form_element', $post ); ?>
                    </div>
                </draggable>
            </div>

            <textarea style="display:none;" name="wpinv_form_elements" v-model="elementString"></textarea>
            <textarea style="display:none;" name="wpinv_form_items" v-model="itemString"></textarea>
        </div>
        
        <?php wp_nonce_field( 'wpinv_save_payment_form', 'wpinv_save_payment_form' ) ;?>

        <?php
    }

    /**
     * Saves our payment forms.
     */
    public static function save( $post_id, $post ) {

        remove_action( 'save_post', 'WPInv_Meta_Box_Payment_Form::save' );

        // $post_id and $post are required.
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }
        
        // Ensure that this user can edit the post.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Do not save for ajax requests.
        if ( ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
            return;
        }

        // Confirm the security nonce.
        if ( empty( $_POST['wpinv_save_payment_form'] ) || ! wp_verify_nonce( $_POST['wpinv_save_payment_form'], 'wpinv_save_payment_form' ) ) {
            return;
        }

        // Save form items.
        $form_items = json_decode( wp_unslash( $_POST['wpinv_form_items'] ), true );

        if ( empty( $form_items ) ) {
            $form_items = array();
        }

        update_post_meta( $post_id, 'wpinv_form_items', self::maybe_save_items( $form_items ) );

        // Save form elements.
        $wpinv_form_elements = json_decode( wp_unslash( $_POST['wpinv_form_elements'] ), true );
        if ( empty( $wpinv_form_elements ) ) {
            $wpinv_form_elements = array();
        }

        update_post_meta( $post_id, 'wpinv_form_elements', $wpinv_form_elements );
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

            $data = array(
                'post_title'   => sanitize_text_field( $item['title'] ),
                'post_excerpt' => wp_kses_post( $item['description'] ),
                'post_status'  => 'publish',
                'meta'         => array(
                    'type'      => 'custom',
                    'price'     => wpinv_sanitize_amount( $item['price'] ),
                    'vat_rule'  => 'digital',
                    'vat_class' => '_standard',
                )
            );
            
            $new_item  = new WPInv_Item();
            $new_item->create( $data );
    
            if ( ! empty( $new_item ) ) {
                $item['id'] = $new_item->ID;
                $saved[] = $item;
            }

        }

        return $saved;

    }

}

add_action( 'save_post_wpi_payment_form', 'WPInv_Meta_Box_Payment_Form::save', 10, 2 );

