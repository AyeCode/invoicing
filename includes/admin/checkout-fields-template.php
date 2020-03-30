<?php

// This template is responsible for editing the checkout fields.
$sanitize_id = wpinv_sanitize_key( $args['id'] );

?>
<div class="wpinv-field-editor-main-wrapper">
    <div class="wpinv-field-types-editor-wrapper">

        <!-- Available field types header -->
        <h3 class="wpinv-field-types-editor-header">
            <span><?php _e( 'Available Fields', 'invoicing' ); ?></span>
            <button class="toggle-icon" @click.prevent="toggleFieldTypes">
                <span class="dashicons dashicons-arrow-down"></span>
                <span class="dashicons dashicons-arrow-up" style="display:none"></span>
            </button>
        </h3>

        <!-- List of available field types -->
        <div class="wpinv-field-types-editor-inside">
            <p class="description">
                <?php _e( 'To add a field drag it to the list on the right or click on it. Only one of each predefined fields can be added.', 'invoicing' ); ?>
            </p>
            <draggable :list="fieldTypeKeys" :group="{ name: 'fields', pull: 'clone', put: false }" :sort="false"
                :clone="addDraggedField" tag="ul" filter=".wpinv-undraggable">
                <li v-for="(fieldType, index) in fieldTypes" @click.prevent="addField(index)" :key="fieldType.name"
                    class="wpinv-field-editor-field-type" :class="fieldTypeDragClass(fieldType, index)">
                    <span>{{fieldType.name}}</span>
            </draggable>
        </div>
    </div>
    <div class="wpinv-fields-editor-wrapper">
        <h3 class="wpinv-field-editor-header">
            <span><?php _e( 'List of fields that will appear on checkout pages', 'invoicing' ); ?></span></h3>
        <p><?php _e( 'Click to expand and view field related settings. You may drag and drop to arrange fields order on the checkout form.', 'invoicing' ); ?>
        </p>

        <div class="wpinv-field-editor-inside">
            <div class="wpinv-field-editor-fields-wrapper">
                <div class="wpinv-field-editor-fields">
                    <draggable v-model="fields" group="fields">
                        <div v-for="field in fields" :key="field.id" :id="'wpinv-field-editor-field-' + field.key"
                            class="wpinv-field-editor-field">
                            <div class="wpinv-field-editor-field-header" @click.prevent="togglePanel(field.key)">
                                <span class="label">{{field.field_label}} <span class="wpinv-required"
                                        v-if="field.field_required">&nbsp;*</span></span>
                                <span class="type">({{fieldTypeLabel(field.field_type)}})</span>
                                <span class="toggle-icon">
                                    <span class="dashicons dashicons-arrow-down"></span>
                                    <span class="dashicons dashicons-arrow-up" style="display:none"></span>
                                </span>
                            </div>
                            <div class="wpinv-field-editor-field-body">
                                <div class="wpinv-field-editor-field-body-inner">

                                    <label class="wpinv-field-editor-text-wrapper">
                                        <div class="label-text"><?php _e( 'Field label', 'invoicing' ); ?></div>
                                        <div class="input-field-wrapper">
                                            <input type="text" v-model="field.field_label">
                                        </div>
                                    </label>

                                    <label class="wpinv-field-editor-text-wrapper">
                                        <div class="label-text"><?php _e( 'Field key', 'invoicing' ); ?></div>
                                        <div class="input-field-wrapper">
                                            <input type="text" v-model="field.key" @input="syncKey(field)"
                                                :disabled="isPredefined(field)">
                                            <p v-if="isDuplicateKey(field.key)" class="description wpinv-error">
                                                <?php _e( 'Another field with this key already exists. All your fields must have a unique key.', 'invoicing' ); ?>
                                            </p>
                                        </div>
                                    </label>

                                    <label class="wpinv-field-editor-checkbox-wrapper">
                                        <input type="checkbox" v-model="field.field_required">
                                        <span
                                            class="label-text"><?php _e( 'Is this field required?', 'invoicing' ); ?></span>
                                    </label>

                                    <label class="wpinv-field-editor-text-wrapper" v-if="field.field_required">
                                        <div class="label-text"><?php _e( 'Required message', 'invoicing' ); ?></div>
                                        <div class="input-field-wrapper">
                                            <input type="text" v-model="field.field_required_msg">
                                        </div>
                                    </label>

                                    <div class="wpinv-advanced" style="display:none">
                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Field name', 'invoicing' ); ?></div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.name" disabled>
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Field id', 'invoicing' ); ?></div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.id" disabled>
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Field placeholder', 'invoicing' ); ?>
                                            </div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.placeholder">
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Field description', 'invoicing' ); ?>
                                            </div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.field_description">
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Input Class', 'invoicing' ); ?></div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.input_class">
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Label Class', 'invoicing' ); ?></div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.label_class">
                                            </div>
                                        </label>

                                        <label class="wpinv-field-editor-text-wrapper">
                                            <div class="label-text"><?php _e( 'Wrapper Class', 'invoicing' ); ?></div>
                                            <div class="input-field-wrapper">
                                                <input type="text" v-model="field.wrapper_class">
                                            </div>
                                        </label>
                                    </div>
                                    <div class="wpinv-field-editor-actions">
                                        <a href="#" class="wpinv-delete"
                                            @click.prevent="deleteField(field)"><?php _e( 'Delete', 'invoicing' ); ?></a>
                                        |
                                        <a href="#" class="wpinv-done"
                                            @click.prevent="togglePanel(field.key)"><?php _e( 'Done', 'invoicing' ); ?></a>

                                        <button class="wpinv-toggle-advanced button button-primary"
                                            @click.prevent="showingAdvanced = !showingAdvanced">
                                            <span
                                                v-if="showingAdvanced"><?php _e( 'Hide Advanced', 'invoicing' ); ?></span>
                                            <span
                                                v-if="!showingAdvanced"><?php _e( 'Show Advanced', 'invoicing' ); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </draggable>
                    <div style="margin-top: 10px;">
                        <button class="button button-secondary"
                            @click.prevent="resetFields"><?php _e( 'Reset Fields', 'invoicing' ); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <textarea id="wpinv_settings[<?php echo esc_attr( $sanitize_id ); ?>]"
            name="wpinv_settings[<?php echo esc_attr( $sanitize_id ); ?>]"
            v-model="fieldString"
            style="display: none !important;"></textarea>
    </div>
</div>