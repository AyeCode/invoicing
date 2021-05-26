<?php
/**
 * Displays a form items setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/items.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div v-if="!is_default">

    <small class='form-text text-muted mb-2'>
        <?php esc_html_e( 'This section allows you to add an existing item to the form that users can then buy. If you do not add an item, ensure that you add a price select or input field to the form.', 'invoicing' ); ?>
    </small>

    <label class='form-group'>
        <input v-model='active_form_element.hide_cart' type='checkbox' />
        <span class='form-check-label'><?php esc_html_e( 'Hide cart details', 'invoicing' ); ?></span>
    </label>

    <div class="mb-1">
        <?php esc_html_e( 'Form Items', 'invoicing' ); ?>
    </div>

    <draggable v-model='form_items' group='selectable_form_items'>
        <div class='wpinv-available-items-editor' v-for='(item, index) in form_items' :class="'item_' + item.id" :key="item.id">

            <div class='wpinv-available-items-editor-header' @click.prevent='togglePanel(item.id)'>
                <span class='label'>{{item.title}}</span>
                <span class='price'>({{formatPrice(item.price)}})</span>
                <span class='toggle-icon'>
                    <span class='dashicons dashicons-arrow-down'></span>
                    <span class='dashicons dashicons-arrow-up' style='display:none'></span>
                </span>
            </div>

            <div class='wpinv-available-items-editor-body'>
                <div class='p-3'>

                    <span class='form-text'>
                        <a target="_blank" :href="'<?php echo esc_url( admin_url( '/post.php?action=edit&post' ) ) ?>=' + item.id">
                            <?php _e( 'Edit the item name, price and other details', 'invoicing' ); ?>
                        </a>
                    </span>

                    <label class='form-group d-block'>
                        <input v-model='item.allow_quantities' type='checkbox' />
                        <span><?php _e( 'Allow users to buy several quantities', 'invoicing' ); ?></span>
                    </label>

                    <label class='form-group d-block'>
                        <input v-model='item.required' type='checkbox' />
                        <span><?php _e( 'This item is required', 'invoicing' ); ?></span>
                    </label>

                    <button type='button' class='button button-link button-link-delete' @click.prevent='removeItem(item)'><?php _e( 'Delete Item', 'invoicing' ); ?></button>

                </div>
            </div>

        </div>
    </draggable>

    <small v-if='! form_items.length' class='form-text text-danger'><?php _e( 'You have not set up any items. Please select an item below or create a new item.', 'invoicing' ); ?></small>

    <div class="mt-4 mb-4">

        <div class="mb-2">
            <select class='w-100' v-init-item-search>
                <option value="" selected="selected"><?php _e( 'Select an item to add...', 'invoicing' ) ?></option>
            </select>

        </div>

        <button type="button" @click.prevent='addSelectedItem' class="button button-primary"><?php _e( 'Add Selected Item', 'invoicing' ) ?></button>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_item' ) ); ?>" target="_blank" class="button button-secondary"><?php _e( 'Create New Item.', 'invoicing' ) ?></a>

    </div>
</div>

<div class='form-group mt-5' v-if="!is_default">

    <label class="w-100 d-block">

        <span><?php esc_html_e( 'Let customers...', 'invoicing' ) ?></span>

        <select class='w-100' style="padding: 6px 24px 6px 8px; border-color: #e0e0e0;" v-model='active_form_element.items_type'>
            <option value='total'><?php _e( 'Buy all items on the list', 'invoicing' ); ?></option>
            <option value='radio'><?php _e( 'Select a single item from the list', 'invoicing' ); ?></option>
            <option value='checkbox'><?php _e( 'Select one or more items on the list', 'invoicing' ) ;?></option>
            <option value='select'><?php _e( 'Select a single item from a dropdown', 'invoicing' ); ?></option>
        </select>

    </label>

</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
        <small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
    </label>
</div>
