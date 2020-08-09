<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Payment_Form_Elements {

    /**
     * @param array payment form elements
     */
    protected $elements;

    public function __construct() {

        foreach( $this->get_elements() as $element ) {
            $element = $element['type'];

            if ( method_exists( $this, "render_{$element}_template" ) ) {
                add_action( 'wpinv_payment_form_render_element_template', array( $this, "render_{$element}_template" ), 10, 2 );
            }

            if ( method_exists( $this, "edit_{$element}_template" ) ) {
                add_action( 'wpinv_payment_form_edit_element_template', array( $this, "edit_{$element}_template" ), 10, 2 );
            }


        }
        
    }

    /**
     * Returns all the elements that can be added to a form.
     */
    public function get_elements() {

        if ( ! empty( $this->elements ) ) {
            return $this->elements;
        }

        $this->elements = wpinv_get_data( 'payment-form-elements' );

        $this->elements = apply_filters( 'wpinv_filter_core_payment_form_elements', $this->elements );
        return $this->elements;
    }

    /**
     * Returns the restrict markup.
     */
    public function get_restrict_markup( $field, $field_type ) {
        $restrict = "$field.type=='$field_type'";
        return "v-if=\"$restrict\"";
    }

    /**
     * Renders the title element template.
     */
    public function render_heading_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'heading' );
        echo "<component :is='$field.level' $restrict v-html='$field.text'></component>";
    }

    /**
     * Renders the edit title element template.
     */
    public function edit_heading_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'heading' );
        $label    = __( 'Heading', 'invoicing' );
        $label2   = __( 'Select Heading Level', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $id2      = $field . '.id + "_edit2"';

        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input class='form-control' :id='$id' v-model='$field.text' type='text' />
                </div>

                <div class='form-group'>
                    <label :for='$id2'>$label2</label>

                    <select class='form-control custom-select' :id='$id2' v-model='$field.level'>
                        <option value='h1'>H1</option>
                        <option value='h2'>H2</option>
                        <option value='h3'>H3</option>
                        <option value='h4'>H4</option>
                        <option value='h5'>H5</option>
                        <option value='h6'>H6</option>
                        <option value='h7'>H7</option>
                    </select>
                </div>
            </div>
        ";

    }

    /**
     * Renders a paragraph element template.
     */
    public function render_paragraph_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'paragraph' );
        $label    = "$field.text";
        echo "<p $restrict v-html='$label' style='font-size: 16px;'></p>";
    }

    /**
     * Renders the edit paragraph element template.
     */
    public function edit_paragraph_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'paragraph' );
        $label    = __( 'Enter your text', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <textarea :id='$id' v-model='$field.text' class='form-control' rows='3'></textarea>
                </div>
            </div>
        ";

    }

    /**
     * Renders the text element template.
     */
    public function render_text_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'text' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='text'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit price select element template.
     */
    public function edit_price_select_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'price_select' );
        
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label6   = __( 'Options', 'invoicing' );
        $id6      = $field . '.id + "_edit5"';
        ?>
            <div <?php echo $restrict; ?>>
                <small class='form-text text-muted mb-2'><?php _e( 'This amount will be added to the total amount for this form', 'invoicing' ); ?></small>
                <div class='form-group'>
                    <label class="d-block">
                        <span><?php _e( 'Field Label', 'invoicing' ); ?></span>
                        <input v-model='<?php echo $field; ?>.label' class='form-control' />
                    </label>
                </div>

                <div class='form-group' v-if="<?php echo $field; ?>.select_type=='select'">
                    <label class="d-block">
                        <span><?php _e( 'Placeholder text', 'invoicing' ); ?></span>
                        <input v-model='<?php echo $field; ?>.placeholder' class='form-control' />
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php _e( 'Select Type', 'invoicing' ); ?></span>
                        <select class='form-control custom-select' v-model='<?php echo $field; ?>.select_type'>
                            <option value='select'><?php _e( 'Dropdown', 'invoicing' ) ?></option>
                            <option value='checkboxes'><?php _e( 'Checkboxes', 'invoicing' ) ?></option>
                            <option value='radios'><?php _e( 'Radio Buttons', 'invoicing' ) ?></option>
                            <option value='buttons'><?php _e( 'Buttons', 'invoicing' ) ?></option>
                            <option value='circles'><?php _e( 'Circles', 'invoicing' ) ?></option>
                        </select>
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php _e( 'Options', 'invoicing' ); ?></span>
                        <textarea placeholder='Basic|10,Pro|99,Business|199' v-model='<?php echo $field; ?>.options' class='form-control' rows='3'></textarea>
                        <small class='form-text text-muted mb-2'><?php _e( 'Use commas to separate options and pipes to separate a label and its price. Do not include a currency symbol in the price.', 'invoicing' ); ?></small>
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php _e( 'Help Text', 'invoicing' ); ?></span>
                        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='<?php echo $field; ?>.description' class='form-control' rows='3'></textarea>
                    </label>
                </div>
            </div>
        <?php

    }

    /**
     * Renders the price select element template.
     */
    public function render_price_select_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'price_select' );
        ?>
            <div <?php echo $restrict; ?> class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>

                <label>{{<?php echo $field; ?>.label}}</label>

                <!-- Buttons -->
                <div v-if='<?php echo esc_attr( $field ); ?>.select_type=="buttons"' class="getpaid-price-buttons">
                    <span v-for="(option, index) in <?php echo esc_attr( $field ); ?>.options.split(',')" :key="index">
                        <input type="radio" :id="<?php echo esc_attr( $field ); ?>.id + index" :checked="index==0" />
                        <label :for="<?php echo esc_attr( $field ); ?>.id + index" class="rounded">{{option | optionize}}</label>
                    </span>
                </div>

                <!-- Circles -->
                <div v-if='<?php echo esc_attr( $field ); ?>.select_type=="circles"' class="getpaid-price-buttons getpaid-price-circles">
                    <span v-for="(option, index) in <?php echo esc_attr( $field ); ?>.options.split(',')" :key="index">
                        <input type="radio" :id="<?php echo esc_attr( $field ); ?>.id + index" :checked="index==0" />
                        <label :for="<?php echo esc_attr( $field ); ?>.id + index"><span>{{option | optionize}}</span></label>
                    </span>
                </div>

                <!-- Radios -->
                <div v-if='<?php echo esc_attr( $field ); ?>.select_type=="radios"'>
                    <div v-for="(option, index) in <?php echo esc_attr( $field ); ?>.options.split(',')" :key="index">
                        <label>
                            <input type="radio" :checked="index==0" />
                            <span>{{option | optionize}}</span>
                        </label>
                    </div>
                </div>

                <!-- Checkboxes -->
                <div v-if='<?php echo esc_attr( $field ); ?>.select_type=="checkboxes"'>
                    <div v-for="(option, index) in <?php echo esc_attr( $field ); ?>.options.split(',')" :key="index">
                        <label>
                            <input type="checkbox" :checked="index==0" />
                            <span>{{option | optionize}}</span>
                        </label>
                    </div>
                </div>

                <!-- Select -->
                <select v-if='<?php echo esc_attr( $field ); ?>.select_type=="select"' class='form-control custom-select'>
                    <option v-if="<?php echo esc_attr( $field ); ?>.placeholder" selected="selected">
                        {{<?php echo esc_attr( $field ); ?>.placeholder}}
                    </option>
                    <option v-for="(option, index) in <?php echo esc_attr( $field ); ?>.options.split(',')" :key="index">
                        {{option | optionize}}
                    </option>
                </select>
                <small v-if='<?php echo esc_attr( $field ); ?>.description' class='form-text text-muted' v-html='<?php echo esc_attr( $field ); ?>.description'></small>
            </div>

        <?php
    }

    /**
     * Renders the edit price input element template.
     */
    public function edit_price_input_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'price_input' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'The amount that users add to this field will be added to the total amount', 'invoicing' );
        $label6   = __( 'Default Amount', 'invoicing' );
        $id6      = $field . '.id + "_edit5"';
        echo "
            <div $restrict>
                <small class='form-text text-muted mb-2'>$label5</small>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id6'>$label6</label>
                    <input :id='$id6' v-model='$field.value' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
            </div>
        ";
    }

    /**
     * Renders the edit text element template.
     */
    public function edit_text_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'text' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the textarea element template.
     */
    public function render_textarea_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'textarea' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <textarea  :placeholder='$field.placeholder' :id='$field.id' class='form-control' rows='3'></textarea>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit textarea element template.
     */
    public function edit_textarea_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'textarea' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the select element template.
     */
    public function render_select_template( $field ) {
        $restrict    = $this->get_restrict_markup( $field, 'select' );
        $label       = "$field.label";
        $placeholder = "$field.placeholder";
        $id          = $field . '.id';
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$id'>{{" . $label . "}}</label>
                <select id='$id' class='form-control custom-select'  v-model='$field.value'>
                    <option v-if='$placeholder' value='' disabled>{{" . $placeholder . "}}</option>
                    <option v-for='option in $field.options' value='option'>{{option}}</option>
                </select>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>
        ";
    }

    /**
     * Renders the edit select element template.
     */
    public function edit_select_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'select' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        $label6   = __( 'Available Options', 'invoicing' );
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
                <hr class='featurette-divider mt-4'>
                <h5>$label6</h5>
                <div class='form-group input-group' v-for='(option, index) in $field.options'>
                    <input type='text' class='form-control' v-model='$field.options[index]'>
                    <div class='input-group-append'>
                        <button class='button button-secondary border' type='button' @click.prevent='$field.options.splice(index, 1)'><span class='dashicons dashicons-trash'></span></button>
                    </div>
                </div>
                <div class='form-group'>
                    <button class='button button-secondary' type='button' @click.prevent='$field.options.push(\"\")'>Add Option</button>
                </div>
            </div>
        ";

    }

    /**
     * Renders the checkbox element template.
     */
    public function render_checkbox_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'checkbox' );
        echo "
            <div class='form-check' $restrict>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <input  :id='$field.id' class='form-check-input' type='checkbox' />
                <label class='form-check-label' :for='$field.id' v-html='$field.label'></label>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit checkbox element template.
     */
    public function edit_checkbox_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'checkbox' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Help text', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label4   = __( 'Is this field required?', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <textarea placeholder='$label3' :id='$id2' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id3' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id3'>$label4</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the radio element template.
     */
    public function render_radio_template( $field ) {
        $restrict    = $this->get_restrict_markup( $field, 'radio' );
        $label       = "$field.label";
        $id          = $field . '.id';
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <legend class='col-form-label' v-if='$label'>{{" . $label . "}}</legend>
                <div class='form-check' v-for='(option, index) in $field.options'>
                    <input class='form-check-input' type='radio' :id='$id + index'>
                    <label class='form-check-label' :for='$id + index'>{{option}}</label>
                </div>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>
        ";
    }

    /**
     * Renders the edit radio element template.
     */
    public function edit_radio_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'radio' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Help text', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id2      = $field . '.id + "_edit3"';
        $label4   = __( 'Is this field required?', 'invoicing' );
        $id3      = $field . '.id + "_edit4"';
        $label5   = __( 'Available Options', 'invoicing' );
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <textarea placeholder='$label3' :id='$id2' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id3' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id3'>$label4</label>
                </div>
                <hr class='featurette-divider mt-4'>
                <h5>$label5</h5>
                <div class='form-group input-group' v-for='(option, index) in $field.options'>
                    <input type='text' class='form-control' v-model='$field.options[index]'>
                    <div class='input-group-append'>
                        <button class='button button-secondary border' type='button' @click.prevent='$field.options.splice(index, 1)'><span class='dashicons dashicons-trash'></span></button>
                    </div>
                </div>
                <div class='form-group'>
                    <button class='button button-secondary' type='button' @click.prevent='$field.options.push(\"\")'>Add Option</button>
                </div>
            </div>
        ";

    }

    /**
     * Renders the address element template.
     */
    public function render_address_template( $field ) {
        $restrict    = $this->get_restrict_markup( $field, 'address' );

        echo "
            <div class='wpinv-address-wrapper' $restrict>
                <draggable v-model='$field.fields' group='address_fields_preview'>
                    <div class='form-group address-field-preview wpinv-payment-form-field-preview' v-for='(field, index) in $field.fields' :key='field.name' v-show='field.visible'>
                        <div class='wpinv-payment-form-field-preview-overlay'></div>
                        <label :for='field.name'>{{field.label}}<span class='text-danger' v-if='field.required'> *</span></label>
                        <input v-if='field.name !== \"wpinv_country\" && field.name !== \"wpinv_state\"' class='form-control' type='text' :id='field.name' :placeholder='field.placeholder'>
                        <select v-else class='form-control' :id='field.name'>
                            <option v-if='field.placeholder'>{{field.placeholder}}</option>
                        </select>
                        <small v-if='field.description' class='form-text text-muted' v-html='field.description'></small>
                    </div>
                </draggable>
            </div>
        ";
    }

    /**
     * Renders the edit address element template.
     */
    public function edit_address_template( $field ) {
        $restrict  = $this->get_restrict_markup( $field, 'address' );
        $label     = __( 'Field Label', 'invoicing' );
        $label2    = __( 'Placeholder', 'invoicing' );
        $label3    = __( 'Description', 'invoicing' );
        $label4    = __( 'Is required', 'invoicing' );
        $label5    = __( 'Is visible', 'invoicing' );
        $id        = $field . '.id + "_edit_label"';
        $id2       = $field . '.id + "_edit_placeholder"';
        $id3       = $field . '.id + "_edit_description"';
        $id4       = $field . '.id + "_edit_required"';
        $id5       = $field . '.id + "_edit_visible"';
        $id5       = $field . '.id + "_edit_visible"';
        $id_main   = $field . '.id';

        echo "
            <div $restrict :id='$id_main'>
                <draggable v-model='$field.fields' group='address_fields'>
                    <div class='wpinv-form-address-field-editor' v-for='(field, index) in $field.fields' :class=\"[field.name, { 'visible' : field.visible }]\" :key='field.name'>

                        <div class='wpinv-form-address-field-editor-header' @click.prevent='toggleAddressPanel($id_main, field.name)'>
                            <span class='label'>{{field.label}}</span>
                            <span class='toggle-visibility-icon' @click.stop='field.visible = !field.visible;'>
                                <span class='dashicons dashicons-hidden'></span>
                                <span class='dashicons dashicons-visibility'></span>
                            </span>
                            <span class='toggle-icon'>
                                <span class='dashicons dashicons-arrow-down'></span>
                                <span class='dashicons dashicons-arrow-up' style='display:none'></span>
                            </span>
                        </div>

                        <div class='wpinv-form-address-field-editor-editor-body'>
                            <div class='p-2'>

                                <div class='form-group'>
                                    <label :for='$id + index'>$label</label>
                                    <input :id='$id + index' v-model='field.label' class='form-control' />
                                </div>

                                <div class='form-group'>
                                    <label :for='$id2 + index'>$label2</label>
                                    <input :id='$id2 + index' v-model='field.placeholder' class='form-control' />
                                </div>

                                <div class='form-group'>
                                    <label :for='$id3 + index'>$label3</label>
                                    <textarea :id='$id3 + index' v-model='field.description' class='form-control'></textarea>
                                </div>

                                <div class='form-group form-check'>
                                    <input :id='$id4 + index' v-model='field.required' type='checkbox' class='form-check-input' />
                                    <label class='form-check-label' :for='$id4 + index'>$label4</label>
                                </div>

                                <div class='form-group form-check'>
                                    <input :id='$id5 + index' v-model='field.visible' type='checkbox' class='form-check-input' />
                                    <label class='form-check-label' :for='$id5 + index'>$label5</label>
                                </div>

                            </div>
                        </div>

                    </div>
                </draggable>

            </div>
        ";

    }

    /**
     * Renders the email element template.
     */
    public function render_email_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'email' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='email'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the billing_email element template.
     */
    public function render_billing_email_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'billing_email' );
        $label    = "$field.label";
        echo "
            <div $restrict>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='email'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit email element template.
     */
    public function edit_email_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'email' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the edit billing_email element template.
     */
    public function edit_billing_email_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'billing_email' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
            </div>
        ";

    }

    /**
     * Renders the website element template.
     */
    public function render_website_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'website' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='url'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit website element template.
     */
    public function edit_website_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'website' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the date element template.
     */
    public function render_date_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'date' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='date'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit date element template.
     */
    public function edit_date_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'date' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the time element template.
     */
    public function render_time_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'time' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='time'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit time element template.
     */
    public function edit_time_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'time' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the number element template.
     */
    public function render_number_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'number' );
        $label    = "$field.label";
        echo "
            <div $restrict class='wpinv-payment-form-field-preview'>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='number'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the edit number element template.
     */
    public function edit_number_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'number' );
        $label    = __( 'Field Label', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Placeholder text', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label3   = __( 'Help text', 'invoicing' );
        $label4   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label5   = __( 'Is this field required?', 'invoicing' );
        $id4      = $field . '.id + "_edit4"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <input :id='$id2' v-model='$field.placeholder' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label3</label>
                    <textarea placeholder='$label4' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id4' v-model='$field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id4'>$label5</label>
                </div>
            </div>
        ";

    }

    /**
     * Renders the separator element template.
     */
    public function render_separator_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'separator' );
        echo "<hr class='featurette-divider' $restrict>";
    }

    /**
     * Renders the pay button element template.
     */
    public function render_pay_button_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'pay_button' );
        $label    = "$field.label";
        echo "
            <div $restrict>
                <button class='form-control btn submit-button' :class='$field.class' type='submit' @click.prevent=''>{{" . $label . "}}</button>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the pay button element template.
     */
    public function edit_pay_button_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'pay_button' );
        $label    = __( 'Button Text', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label2   = __( 'Help text', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label4   = esc_attr__( 'Button Type', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        $label4   = esc_attr__( 'Select Gateway Text', 'invoicing' );
        $id3      = $field . '.id + "_edit4"';

        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.label' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label4</label>
                    <input :id='$id3' v-model='$field.gateway_select' class='form-control' />
                </div>
                <div class='form-group'>
                    <label :for='$id2'>$label2</label>
                    <textarea placeholder='$label3' :id='$id2' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label4</label>

                    <select class='form-control custom-select' :id='$id3' v-model='$field.class'>
                        <option value='btn-primary'>"   . __( 'Primary', 'invoicing' ) ."</option>
                        <option value='btn-secondary'>" . __( 'Secondary', 'invoicing' ) ."</option>
                        <option value='btn-success'>"   . __( 'Success', 'invoicing' ) ."</option>
                        <option value='btn-danger'>"    . __( 'Danger', 'invoicing' ) ."</option>
                        <option value='btn-warning'>"   . __( 'Warning', 'invoicing' ) ."</option>
                        <option value='btn-info'>"      . __( 'Info', 'invoicing' ) ."</option>
                        <option value='btn-light'>"     . __( 'Light', 'invoicing' ) ."</option>
                        <option value='btn-dark'>"      . __( 'Dark', 'invoicing' ) ."</option>
                        <option value='btn-link'>"      . __( 'Link', 'invoicing' ) ."</option>
                    </select>
                </div>
            </div>
        ";

    }

    /**
     * Renders the alert element template.
     */
    public function render_alert_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'alert' );
        $text     = "$field.text";
        echo "
            <div $restrict class='alert' :class='$field.class' role='alert'>
                <span v-html='$text'></span>
                <button v-if='$field.dismissible' type='button' class='close' @click.prevent=''>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>    
        ";
    }

    /**
     * Renders the alert element template.
     */
    public function edit_alert_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'alert' );
        $label    = __( 'Alert Text', 'invoicing' );
        $label2   = esc_attr__( 'Enter your alert text here', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $label3   = __( 'Is Dismissible?', 'invoicing' );
        $id2      = $field . '.id + "_edit2"';
        $label4   = esc_attr__( 'Alert Type', 'invoicing' );
        $id3      = $field . '.id + "_edit3"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <textarea placeholder='$label2' :id='$id' v-model='$field.text' class='form-control' rows='3'></textarea>
                </div>
                <div class='form-group form-check'>
                    <input :id='$id2' v-model='$field.dismissible' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for='$id2'>$label3</label>
                </div>
                <div class='form-group'>
                    <label :for='$id3'>$label4</label>

                    <select class='form-control custom-select' :id='$id3' v-model='$field.class'>
                        <option value='alert-primary'>"   . __( 'Primary', 'invoicing' ) ."</option>
                        <option value='alert-secondary'>" . __( 'Secondary', 'invoicing' ) ."</option>
                        <option value='alert-success'>"   . __( 'Success', 'invoicing' ) ."</option>
                        <option value='alert-danger'>"    . __( 'Danger', 'invoicing' ) ."</option>
                        <option value='alert-warning'>"   . __( 'Warning', 'invoicing' ) ."</option>
                        <option value='alert-info'>"      . __( 'Info', 'invoicing' ) ."</option>
                        <option value='alert-light'>"     . __( 'Light', 'invoicing' ) ."</option>
                        <option value='alert-dark'>"      . __( 'Dark', 'invoicing' ) ."</option>
                    </select>
                </div>
            </div>
        ";

    }

    /**
     * Renders the discount element template.
     */
    public function render_discount_template( $field ) {
        $restrict  = $this->get_restrict_markup( $field, 'discount' );
        ?>

            <div <?php echo $restrict; ?> class="discount_field border rounded p-3 wpinv-payment-form-field-preview">
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <div class="discount_field_inner d-flex flex-column flex-md-row">
                    <input  :placeholder="<?php echo $field ?>.input_label" class="form-control mr-2 mb-2" style="flex: 1;" type="text">
                    <button class="btn btn-secondary submit-button mb-2" type="submit" @click.prevent="">{{<?php echo $field; ?>.button_label}}</button>
                </div>
                <small v-if='<?php echo $field ?>.description' class='form-text text-muted' v-html='<?php echo $field ?>.description'></small>
            </div>

        <?php
    }

    /**
     * Renders the discount element template.
     */
    public function edit_discount_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'discount' );
        $label    = __( 'Discount Input Placeholder', 'invoicing' );
        $label2   = __( 'Help Text', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this field', 'invoicing' );
        $label4   = __( 'Button Text', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $id2      = $field . '.id + "_edit2"';
        $id3      = $field . '.id + "_edit3"';
        echo "
            <div $restrict>
                <div class='form-group'>
                    <label :for='$id'>$label</label>
                    <input :id='$id' v-model='$field.input_label' class='form-control' />
                </div>

                <div class='form-group'>
                    <label :for='$id2'>$label4</label>
                    <input :id='$id2' v-model='$field.button_label' class='form-control' />
                </div>

                <div class='form-group'>
                    <label :for='$id3'>$label2</label>
                    <textarea placeholder='$label3' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>

            </div>
        ";

    }

    /**
     * Renders the items element template.
     */
    public function render_items_template( $field ) {
        $restrict  = $this->get_restrict_markup( $field, 'items' );
        ?>

        <div <?php echo $restrict; ?> class='item_totals text-center'>
            <div v-if='!is_default'>
                <div v-if='! canCheckoutSeveralSubscriptions(<?php echo $field; ?>)' class='p-4 bg-warning text-light'><?php _e( 'Item totals will appear here. Click to set items.', 'invoicing' ) ?></div>
                <div v-if='canCheckoutSeveralSubscriptions(<?php echo $field; ?>)' class='p-4 bg-danger'><?php _e( 'Your form allows customers to buy several recurring items. This is not supported and will lead to unexpected behaviour.', 'invoicing' ); _e( 'To prevent this, limit customers to selecting a single item.', 'invoicing' ); ?></div>
            </div>
                <div v-if='is_default'>
                    <div class='p-4 bg-warning'><?php _e( 'Item totals will appear here.', 'invoicing' ) ?></div>
                </div>
            </div>

        <?php
    }

    /**
     * Renders the items element on the frontend.
     */
    public function frontend_render_items_template( $field, $items ) {
        $tax       = 0;
        $sub_total = 0;
        $total     = 0;

        ?>

        <?php
        echo "<div class='form-group item_totals'>";
        
        $id = esc_attr( $field['id'] );

        if ( empty( $field[ 'items_type' ] ) ) {
            $field[ 'items_type' ] = 'total';
        }

        if ( 'radio' == $field[ 'items_type' ] ) { ?>
            <div class="item_totals_type_radio">

                <?php
                    foreach( $items as $index => $item ) {

                        if ( ! empty( $item['required'] ) ) {
                            continue;
                        }
                ?>
                    <div  class="form-check">
                        <input class='form-check-input wpinv-items-selector' <?php checked( ! isset( $selected_radio_item ) ); $selected_radio_item = 1; ?> type='radio' value='<?php echo $item['id']; ?>' id='<?php echo $id . $index; ?>' name='wpinv-payment-form-selected-item'>
                        <label class='form-check-label' for='<?php echo $id . $index; ?>'><?php echo sanitize_text_field( $item['title'] ); ?>&nbsp;<strong><?php echo wpinv_price( wpinv_format_amount( (float) sanitize_text_field(  $item['price'] ) ) ); ?></strong></label>
                    </div>
                    <?php if ( ! empty( $item['description'] )) { ?>
                        <small class='form-text text-muted pl-4 pr-2 m-0'><?php echo wp_kses_post( $item['description'] ); ?></small>
                    <?php } ?>
                <?php } ?>

                <div class="mt-3 border item_totals_type_radio_totals">

                    <?php

                        $total     = 0;
                        $tax       = 0;
                        $sub_total = 0;

                        foreach ( $items as $item ) {

                            $class  = 'col-8';
                            $class2 = '';

                            if ( ! empty( $item['allow_quantities'] ) ) {
                                $class = 'col-6 pt-2';
                                $class2 = 'pt-2';
                            }

                            if ( ! empty( $item['custom_price'] ) ) {
                                $class .= ' pt-2';
                            }

                            $class3 = 'd-none';
                            $name   = '';
                            if ( ! empty( $item['required'] ) || ! isset( $totals_selected_radio_item ) ) {

                                $amount = floatval( $item['price'] );

                                if ( wpinv_use_taxes() ) {

                                    $rate = wpinv_get_tax_rate( wpinv_get_default_country(), false, (int) $item['id'] );

                                    if ( wpinv_prices_include_tax() ) {
                                        $pre_tax  = ( $amount - $amount * $rate * 0.01 );
                                        $item_tax = $amount - $pre_tax;
                                    } else {
                                        $pre_tax  = $amount;
                                        $item_tax = $amount * $rate * 0.01;
                                    }

                                    $tax       = $tax + $item_tax;
                                    $sub_total = $sub_total + $pre_tax;
                                    $total     = $sub_total + $tax;

                                } else {
                                    $total  = $total + $amount;
                                }

                                $class3 = '';
                                $name   = "wpinv-items[{$item['id']}]";

                                if ( empty( $item['required'] ) ) {
                                    $totals_selected_radio_item = 1;
                                }

                            }

                            $class3 .= " wpinv_item_{$item['id']}";

                    ?>

                    <div  class="item_totals_item <?php echo $class3; ?>" data-id="<?php echo (int) $item['id']; ?>">
                        <div class='row pl-2 pr-2 pt-2'>
                            <div class='<?php echo $class; ?>'><?php echo sanitize_text_field( $item['title'] ) ?></div>

                            <?php  if ( ! empty( $item['allow_quantities'] ) ) { ?>

                                <div class='col-2'>
                                    <input name='wpinv-item-<?php echo (int) $item['id']; ?>-quantity' type='number' class='form-control wpinv-item-quantity-input pr-1' value='1' min='1' required>
                                </div>

                            <?php } else { ?>
                                <input type='hidden' class='wpinv-item-quantity-input' value='1'>
                            <?php } if ( empty( $item['custom_price'] ) ) { ?>

                                <div class='col-4 <?php echo $class2; ?>'>
                                    <?php echo wpinv_price( wpinv_format_amount( $item['price'] ) ) ?>
                                    <input name='<?php echo $name; ?>' type='hidden' class='wpinv-item-price-input' value='<?php echo floatval( $item['price'] ); ?>'>
                                </div>

                            <?php } else {?>

                                <div class='col-4'>
                                    <div class='input-group'>

                                        <?php if ( 'left' == wpinv_currency_position() ) { ?>
                                            <div class='input-group-prepend'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                        <input type='text' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
                                        <?php if ( 'left' != wpinv_currency_position() ) { ?>
                                            <div class='input-group-append'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                        <?php if ( ! empty( $item['description'] )) { ?>
                            <small class='form-text text-muted pl-2 pr-2 m-0'><?php echo wp_kses_post( $item['description'] ); ?></small>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class='mt-4 border-top item_totals_total p-2'>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                    </div>

                    <div class='row' style='display: none;'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Discount', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-discount'></strong></div>
                    </div>

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Tax', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-tax' ><?php echo wpinv_price( wpinv_format_amount( $tax ) ) ?></strong></div>
                        </div>
                    <?php } ?>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-total' data-currency='<?php echo wpinv_currency_symbol(); ?>' data-currency-position='<?php echo wpinv_currency_position(); ?>'><?php echo wpinv_price( wpinv_format_amount( $total ) ) ?></strong></div>
                    </div>
                </div>

            </div>
            </div>
        <?php } ?>

        <?php if ( 'checkbox' == $field[ 'items_type' ] ) { ?>

            <div class="item_totals_type_checkbox">

                <?php
                    foreach ( $items as $index => $item ) {

                        if ( ! empty( $item['required'] ) ) {
                            continue;
                        }

                        $title = sanitize_text_field(  $item['title'] );
                        $price = wpinv_price( wpinv_format_amount( (float) sanitize_text_field(  $item['price'] ) ) );
                        $item_id    = esc_attr( $id . "_$index" );
                        $value = esc_attr( $item['id'] );
                        $checked = checked( ! isset( $selected_checkbox_item ), true, false );
                        $selected_checkbox_item = 1;

                        echo "
                            <div class='custom-control custom-checkbox'>
                                <input type='checkbox' name='payment-form-items[]' id='$item_id' value='$value' class='wpi-payment-form-items-select-checkbox form-control custom-control-input' $checked>
                                <label for='$item_id' class='custom-control-label'>$title &nbsp; ($price)</label>
                            </div>";

                        if ( ! empty( $item['description'] ) ) {
                            echo "<small class='form-text text-muted'>{$item['description']}</small>";
                        }
                    }
                ?>

                <div class="mt-3 border item_totals_type_checkbox_totals">

                    <?php

                        $total     = 0;
                        $tax       = 0;
                        $sub_total = 0;

                        foreach ( $items as $item ) {

                            $class  = 'col-8';
                            $class2 = '';

                            if ( ! empty( $item['allow_quantities'] ) ) {
                                $class = 'col-6 pt-2';
                                $class2 = 'pt-2';
                            }

                            if ( ! empty( $item['custom_price'] ) ) {
                                $class .= ' pt-2';
                            }

                            $class3 = 'd-none';
                            $name  = '';
                            if ( ! empty( $item['required'] ) || ! isset( $totals_selected_checkbox_item ) ) {

                                $amount = floatval( $item['price'] );
                                if ( wpinv_use_taxes() ) {

                                    $rate = wpinv_get_tax_rate( wpinv_get_default_country(), false, (int) $item['id'] );

                                    if ( wpinv_prices_include_tax() ) {
                                        $pre_tax  = ( $amount - $amount * $rate * 0.01 );
                                        $item_tax = $amount - $pre_tax;
                                    } else {
                                        $pre_tax  = $amount;
                                        $item_tax = $amount * $rate * 0.01;
                                    }

                                    $tax       = $tax + $item_tax;
                                    $sub_total = $sub_total + $pre_tax;
                                    $total     = $sub_total + $tax;

                                } else {
                                    $total  = $total + $amount;
                                }

                                $class3 = '';
                                $name  = "wpinv-items[{$item['id']}]";

                                if ( empty( $item['required'] ) ) {
                                    $totals_selected_checkbox_item = 1;
                                }

                            }

                            $class3 .= " wpinv_item_{$item['id']}";

                    ?>

                    <div  class="item_totals_item <?php echo $class3; ?>" data-id="<?php echo (int) $item['id']; ?>">
                        <div class='row pl-2 pr-2 pt-2'>
                            <div class='<?php echo $class; ?>'><?php echo sanitize_text_field( $item['title'] ) ?></div>

                            <?php  if ( ! empty( $item['allow_quantities'] ) ) { ?>

                                <div class='col-2'>
                                    <input name='wpinv-item-<?php echo (int) $item['id']; ?>-quantity' type='number' class='form-control wpinv-item-quantity-input pr-1' value='1' min='1' required>
                                </div>

                            <?php } else { ?>
                                <input type='hidden' class='wpinv-item-quantity-input' value='1'>
                            <?php } if ( empty( $item['custom_price'] ) ) { ?>

                                <div class='col-4 <?php echo $class2; ?>'>
                                    <?php echo wpinv_price( wpinv_format_amount( $item['price'] ) ) ?>
                                    <input name='<?php echo $name; ?>' type='hidden' class='wpinv-item-price-input' value='<?php echo floatval( $item['price'] ); ?>'>
                                </div>

                            <?php } else {?>

                                <div class='col-4'>
                                    <div class='input-group'>

                                        <?php if ( 'left' == wpinv_currency_position() ) { ?>
                                            <div class='input-group-prepend'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                        <input type='text' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
                                        <?php if ( 'left' != wpinv_currency_position() ) { ?>
                                            <div class='input-group-append'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                        <?php if ( ! empty( $item['description'] )) { ?>
                            <small class='form-text text-muted pl-2 pr-2 m-0'><?php echo wp_kses_post( $item['description'] ); ?></small>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class='mt-4 border-top item_totals_total p-2'>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                    </div>

                    <div class='row' style='display: none;'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Discount', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-discount'><?php echo wpinv_price( wpinv_format_amount( 0 ) ) ?></strong></div>
                    </div>

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Tax', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-tax' ><?php echo wpinv_price( wpinv_format_amount( $tax ) ) ?></strong></div>
                        </div>
                    <?php } ?>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-total' data-currency='<?php echo wpinv_currency_symbol(); ?>' data-currency-position='<?php echo wpinv_currency_position(); ?>'><?php echo wpinv_price( wpinv_format_amount( $total ) ) ?></strong></div>
                    </div>
                </div>
            </div>
            </div>
        <?php } ?>

        <?php if ( 'select' == $field[ 'items_type' ] ) { ?>

            <div class="item_totals_type_select">

                <?php

                    $options  = array();
                    $selected = '';
                    foreach ( $items as $index => $item ) {

                        if ( ! empty( $item['required'] ) ) {
                            continue;
                        }

                        $title = sanitize_text_field(  $item['title'] );
                        $price = wpinv_price( wpinv_format_amount( (float) sanitize_text_field(  $item['price'] ) ) );
                        $options[ $item['id'] ] = "$title &nbsp; ($price)";

                        if ( ! isset( $selected_item ) ) {
                            $selected = $item['id'];
                            $selected_item = 1;
                        }
                        
                    }

                    echo aui()->select(
                        array(
                                'name'        => 'payment-form-items',
                                'id'          => $id,
                                'placeholder' => __( 'Select an item', 'invoicing' ),
                                'no_wrap'     => true,
                                'options'     => $options,
                                'class'       => 'wpi_select2 wpinv-items-select-selector',
                                'value'       => $selected,
                        )
                    );
                ?>

                <div class="mt-3 border item_totals_type_select_totals">

                    <?php

                        $total     = 0;
                        $tax       = 0;
                        $sub_total = 0;

                        foreach ( $items as $item ) {

                            $class  = 'col-8';
                            $class2 = '';

                            if ( ! empty( $item['allow_quantities'] ) ) {
                                $class = 'col-6 pt-2';
                                $class2 = 'pt-2';
                            }

                            if ( ! empty( $item['custom_price'] ) ) {
                                $class .= ' pt-2';
                            }

                            $class3 = 'd-none';
                            $name  = '';
                            if ( ! empty( $item['required'] ) || ! isset( $totals_selected_select_item ) ) {

                                $amount = floatval( $item['price'] );
                                if ( wpinv_use_taxes() ) {

                                    $rate = wpinv_get_tax_rate( wpinv_get_default_country(), false, (int) $item['id'] );

                                    if ( wpinv_prices_include_tax() ) {
                                        $pre_tax  = ( $amount - $amount * $rate * 0.01 );
                                        $item_tax = $amount - $pre_tax;
                                    } else {
                                        $pre_tax  = $amount;
                                        $item_tax = $amount * $rate * 0.01;
                                    }

                                    $tax       = $tax + $item_tax;
                                    $sub_total = $sub_total + $pre_tax;
                                    $total     = $sub_total + $tax;

                                } else {
                                    $total  = $total + $amount;
                                }

                                $class3 = '';
                                $name  = "wpinv-items[{$item['id']}]";

                                if ( empty( $item['required'] ) ) {
                                    $totals_selected_select_item = 1;
                                }

                            }

                            $class3 .= " wpinv_item_{$item['id']}";

                    ?>

                    <div  class="item_totals_item <?php echo $class3; ?>" data-id="<?php echo (int) $item['id']; ?>">
                        <div class='row pl-2 pr-2 pt-2'>
                            <div class='<?php echo $class; ?>'><?php echo sanitize_text_field( $item['title'] ) ?></div>

                            <?php  if ( ! empty( $item['allow_quantities'] ) ) { ?>

                                <div class='col-2'>
                                    <input name='wpinv-item-<?php echo (int) $item['id']; ?>-quantity' type='number' class='form-control wpinv-item-quantity-input pr-1' value='1' min='1' required>
                                </div>

                            <?php } else { ?>
                                <input type='hidden' class='wpinv-item-quantity-input' value='1'>
                            <?php } if ( empty( $item['custom_price'] ) ) { ?>

                                <div class='col-4 <?php echo $class2; ?>'>
                                    <?php echo wpinv_price( wpinv_format_amount( $item['price'] ) ) ?>
                                    <input name='<?php echo $name; ?>' type='hidden' class='wpinv-item-price-input' value='<?php echo floatval( $item['price'] ); ?>'>
                                </div>

                            <?php } else {?>

                                <div class='col-4'>
                                    <div class='input-group'>

                                        <?php if ( 'left' == wpinv_currency_position() ) { ?>
                                            <div class='input-group-prepend'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                        <input type='text' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
                                        <?php if ( 'left' != wpinv_currency_position() ) { ?>
                                            <div class='input-group-append'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                        <?php if ( ! empty( $item['description'] )) { ?>
                            <small class='form-text text-muted pl-2 pr-2 m-0'><?php echo wp_kses_post( $item['description'] ); ?></small>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class='mt-4 border-top item_totals_total p-2'>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                    </div>

                    <div class='row' style='display: none;'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Discount', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-discount'><?php echo wpinv_price( wpinv_format_amount( 0 ) ) ?></strong></div>
                    </div>

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Tax', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-tax' ><?php echo wpinv_price( wpinv_format_amount( $tax ) ) ?></strong></div>
                        </div>
                    <?php } ?>
                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-total' data-currency='<?php echo wpinv_currency_symbol(); ?>' data-currency-position='<?php echo wpinv_currency_position(); ?>'><?php echo wpinv_price( wpinv_format_amount( $total ) ) ?></strong></div>
                    </div>
                </div>

            </div>
        <?php } ?>

        <?php if ( 'multi_select' == $field[ 'items_type' ] ) { ?>

            <div class="item_totals_type_multi_select">

                <?php

                    $options  = array();
                    $selected = array();

                    foreach ( $items as $index => $item ) {

                        if ( ! empty( $item['required'] ) ) {
                            continue;
                        }

                        $title = sanitize_text_field(  $item['title'] );
                        $price = wpinv_price( wpinv_format_amount( (float) sanitize_text_field(  $item['price'] ) ) );
                        $options[ $item['id'] ] = "$title &nbsp; ($price)";

                        if ( ! isset( $selected_item ) ) {
                            $selected = array( $item['id'] );
                            $selected_item = 1;
                        }

                    }

                    echo aui()->select(
                        array(
                                'name'        => 'payment-form-items',
                                'id'          => $id,
                                'no_wrap'     => true,
                                'options'     => $options,
                                'multiple'    => true,
                                'class'       => 'wpi_select2 wpinv-items-multiselect-selector',
                                'value'       => $selected,
                        )
                    );
                ?>

                <div class="mt-3 border item_totals_type_select_totals">

                    <?php

                        $total     = 0;
                        $tax       = 0;
                        $sub_total = 0;

                        foreach ( $items as $item ) {

                            $class  = 'col-8';
                            $class2 = '';

                            if ( ! empty( $item['allow_quantities'] ) ) {
                                $class = 'col-6 pt-2';
                                $class2 = 'pt-2';
                            }

                            if ( ! empty( $item['custom_price'] ) ) {
                                $class .= ' pt-2';
                            }

                            $class3 = 'd-none';
                            $name  = '';
                            if ( ! empty( $item['required'] ) || ! isset( $totals_selected_select_item ) ) {

                                $amount = floatval( $item['price'] );
                                if ( wpinv_use_taxes() ) {

                                    $rate = wpinv_get_tax_rate( wpinv_get_default_country(), false, (int) $item['id'] );

                                    if ( wpinv_prices_include_tax() ) {
                                        $pre_tax  = ( $amount - $amount * $rate * 0.01 );
                                        $item_tax = $amount - $pre_tax;
                                    } else {
                                        $pre_tax  = $amount;
                                        $item_tax = $amount * $rate * 0.01;
                                    }

                                    $tax       = $tax + $item_tax;
                                    $sub_total = $sub_total + $pre_tax;
                                    $total     = $sub_total + $tax;

                                } else {
                                    $total  = $total + $amount;
                                }

                                $class3 = '';
                                $name  = "wpinv-items[{$item['id']}]";

                                if ( empty( $item['required'] ) ) {
                                    $totals_selected_select_item = 1;
                                }

                            }

                            $class3 .= " wpinv_item_{$item['id']}";

                    ?>

                    <div  class="item_totals_item <?php echo $class3; ?>" data-id="<?php echo (int) $item['id']; ?>">
                        <div class='row pl-2 pr-2 pt-2'>
                            <div class='<?php echo $class; ?>'><?php echo sanitize_text_field( $item['title'] ) ?></div>

                            <?php  if ( ! empty( $item['allow_quantities'] ) ) { ?>

                                <div class='col-2'>
                                    <input name='wpinv-item-<?php echo (int) $item['id']; ?>-quantity' type='number' class='form-control wpinv-item-quantity-input pr-1' value='1' min='1' required>
                                </div>

                            <?php } else { ?>
                                <input type='hidden' class='wpinv-item-quantity-input' value='1'>
                            <?php } if ( empty( $item['custom_price'] ) ) { ?>

                                <div class='col-4 <?php echo $class2; ?>'>
                                    <?php echo wpinv_price( wpinv_format_amount( $item['price'] ) ) ?>
                                    <input name='<?php echo $name; ?>' type='hidden' class='wpinv-item-price-input' value='<?php echo floatval( $item['price'] ); ?>'>
                                </div>

                            <?php } else {?>

                                <div class='col-4'>
                                    <div class='input-group'>

                                        <?php if ( 'left' == wpinv_currency_position() ) { ?>
                                            <div class='input-group-prepend'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                        <input type='text' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
                                        <?php if ( 'left' != wpinv_currency_position() ) { ?>
                                            <div class='input-group-append'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                        <?php if ( ! empty( $item['description'] )) { ?>
                            <small class='form-text text-muted pl-2 pr-2 m-0'><?php echo wp_kses_post( $item['description'] ); ?></small>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class='mt-4 border-top item_totals_total p-2'>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                    </div>

                    <div class='row' style='display: none;'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Discount', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-discount'><?php echo wpinv_price( wpinv_format_amount( 0 ) ) ?></strong></div>
                    </div>

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Tax', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-tax' ><?php echo wpinv_price( wpinv_format_amount( $tax ) ) ?></strong></div>
                        </div>
                    <?php } ?>

                    <div class='row'>
                        <div class='col-8'><strong class='mr-5'><?php _e( 'Total', 'invoicing' ); ?></strong></div>
                        <div class='col-4'><strong class='wpinv-items-total' data-currency='<?php echo wpinv_currency_symbol(); ?>' data-currency-position='<?php echo wpinv_currency_position(); ?>'><?php echo wpinv_price( wpinv_format_amount( $total ) ) ?></strong></div>
                    </div>
                </div>

            </div>
        <?php } ?>
        <?php if ( ! empty( $field[ 'description' ] ) ) { ?>
            <small class='form-text text-muted'><?php echo wp_kses_post( $field[ 'description' ] ); ?></small>
        <?php } ?>
        </div>
        <?php
    }

    /**
     * Renders the items element template.
     */
    public function edit_items_template( $field ) {
        global $wpinv_euvat, $post;

        $restrict = $this->get_restrict_markup( $field, 'items' );
        $id2      = $field . '.id + "_edit2"';
        $id3      = $field . '.id + "_edit3"';

        // Item types.
        $item_types = apply_filters( 'wpinv_item_types_for_quick_add_item', wpinv_get_item_types(), $post );

        ?>
        <div <?php echo $restrict; ?>>
            <div v-if="!is_default">
                <label class='form-group'>
                    <input v-model='<?php echo $field; ?>.hide_cart' type='checkbox' />
                    <span class='form-check-label'><?php _e( 'Hide cart details', 'invoicing' ); ?></span>
                </label>

                <div class="mb-1"><?php _e( 'Form Items', 'invoicing' ); ?></div>
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

                                <div class="form-group" v-if="! item.new">
                                    <span class='form-text'>
                                        <a target="_blank" :href="'<?php echo esc_url( admin_url( '/post.php?action=edit&post' ) ) ?>=' + item.id">
                                            <?php _e( 'Edit the item name, price and other details', 'invoicing' ); ?>
                                        </a>
                                    </span>
                                </div>

                                <div class='form-group' v-if="item.new">
                                    <label class='mb-0 w-100'>
                                        <span><?php _e( 'Item Name', 'invoicing' ); ?></span>
                                        <input v-model='item.title' type='text' class='w-100'/>
                                    </label>
                                </div>

                                <div class='form-group'  v-if="item.new">
                                    <label class='mb-0 w-100'>
                                        <span v-if='!item.custom_price'><?php _e( 'Item Price', 'invoicing' ); ?></span>
                                        <span v-if='item.custom_price'><?php _e( 'Recommended Price', 'invoicing' ); ?></span>
                                        <input v-model='item.price' type='text' class='w-100'/>
                                    </label>
                                </div>

                                <div class='form-group' v-if='item.new'>
                                    <label :for="'edit_item_type' + item.id" class='mb-0 w-100'>
                                        <span><?php _e( 'Item Type', 'invoicing' ); ?></span>
                                        <select class='w-100' v-model='item.type'>
                                            <?php
                                                foreach ( $item_types as $type => $_label ) {
                                                    $type  = esc_attr( $type );
                                                    $_label = esc_html( $_label );
                                                    echo "<option value='$type'>$_label</type>";
                                                }
                                            ?>
                                        </select>
                                    </label>
                                </div>

                                <div v-if='item.new'>
                                    <?php if ( $wpinv_euvat->allow_vat_rules() ) : ?>
                                        <div class='form-group'>
                                            <label class='w-100 mb-0'><?php _e( 'VAT Rule', 'invoicing' ) ; ?>
                                                <select class='w-100' v-model='item.rule'>
                                                    <?php
                                                        foreach ( $wpinv_euvat->get_rules() as $type => $_label ) {
                                                            $type  = esc_attr( $type );
                                                            $_label = esc_html( $_label );
                                                            echo "<option value='$type'>$_label</type>";
                                                        }
                                                    ?>
                                                </select>
                                            </label>
                                        </div>
                                    <?php endif;?>

                                    <?php if ( $wpinv_euvat->allow_vat_classes() ) : ?>
                                        <div class='form-group'>
                                            <label class='w-100 mb-0'><?php _e( 'VAT class', 'invoicing' ) ; ?>
                                                <select class='w-100' v-model='item.class'>
                                                    <?php
                                                        foreach ( $wpinv_euvat->get_all_classes() as $type => $_label ) {
                                                            $type  = esc_attr( $type );
                                                            $_label = esc_html( $_label );
                                                            echo "<option value='$type'>$_label</type>"; 
                                                        }
                                                    ?>
                                                </select>
                                            </label>
                                        </div>
                                    <?php endif;?>
                                                        
                                </div>

                                <label v-if='item.new' class='form-group'>
                                    <input v-model='item.custom_price' type='checkbox' />
                                    <span class='form-check-label'><?php _e( 'Allow users to pay what they want', 'invoicing' ); ?></span>
                                </label>

                                <div class='form-group' v-if='item.new && item.custom_price'>
                                    <label class='mb-0 w-100'>
                                        <span><?php _e( 'Minimum Price', 'invoicing' ); ?></span>
                                        <input placeholder='0.00' v-model='item.minimum_price' class='w-100' />
                                        <small class='form-text text-muted'><?php _e( 'Enter the minimum price that a user can pay', 'invoicing' ); ?></small>
                                    </label>
                                </div>

                                <label class='form-group'>
                                    <input v-model='item.allow_quantities' type='checkbox' />
                                    <span><?php _e( 'Allow users to buy several quantities', 'invoicing' ); ?></span>
                                </label>

                                <label class='form-group'>
                                    <input v-model='item.required' type='checkbox' />
                                    <span><?php _e( 'This item is required', 'invoicing' ); ?></span>
                                </label>

                                <div class='form-group'>
                                    <label class="mb-0 w-100">
                                        <span><?php _e( 'Item Description', 'invoicing' ); ?></span>
                                        <textarea v-model='item.description' class='w-100'></textarea>
                                    </label>
                                </div>

                                    <button type='button' class='button button-link button-link-delete' @click.prevent='removeItem(item)'><?php _e( 'Delete Item', 'invoicing' ); ?></button>

                                </div>
                            </div>

                        </div>
                </draggable>

                <small v-if='! form_items.length' class='form-text text-danger'><?php _e( 'You have not set up any items. Please select an item below or create a new item.', 'invoicing' ); ?></small>

                <div class='form-group mt-2'>

                    <select class='w-100' style="padding: 6px 24px 6px 8px; border-color: #e0e0e0;" v-model='selected_item' @change='addSelectedItem'>
                        <option value=''><?php _e( 'Select an item to add...', 'invoicing' ) ?></option>
                        <option v-for='(item, index) in all_items' :value='index'>{{item.title}}</option>
                    </select>

                </div>

                <div class='form-group'>
                    <button @click.prevent='addNewItem' class="button button-link"><?php _e( 'Or create a new item.', 'invoicing' ) ?></button>
                </div>

                <div class='form-group mt-5'>
                    <label :for='<?php echo $id2; ?>'><?php _e( 'Let customers...', 'invoicing' ) ?></label>

                    <select class='w-100' style="padding: 6px 24px 6px 8px; border-color: #e0e0e0;" :id='<?php echo $id2; ?>' v-model='<?php echo $field; ?>.items_type'>
                        <option value='total' :disabled='canCheckoutSeveralSubscriptions(<?php echo $field; ?>)'><?php _e( 'Buy all items on the list', 'invoicing' ); ?></option>
                        <option value='radio'><?php _e( 'Select a single item from the list', 'invoicing' ); ?></option>
                        <option value='checkbox' :disabled='canCheckoutSeveralSubscriptions(<?php echo $field; ?>)'><?php _e( 'Select one or more items on the list', 'invoicing' ) ;?></option>
                        <option value='select'><?php _e( 'Select a single item from a dropdown', 'invoicing' ); ?></option>
                        <option value='multi_select' :disabled='canCheckoutSeveralSubscriptions(<?php echo $field; ?>)'><?php _e( 'Select a one or more items from a dropdown', 'invoicing' ) ;?></option>
                    </select>

                </div>
            </div>
            <div class='form-group'>
                <label :for='<?php echo $id3; ?>'><?php _e( 'Help Text', 'invoicing' ); ?></label>
                <textarea placeholder='<?php esc_attr_e( 'Add some help text for this element', 'invoicing' ); ?>' :id='<?php echo $id3; ?>' v-model='<?php echo $field; ?>.description' class='form-control' rows='3'></textarea>
            </div>

        </div>

        <?php

    }

    /**
     * Returns an array of all published items.
     */
    public function get_published_items() {

        $item_args = array(
            'post_type'      => 'wpi_item',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish' ),
            'meta_query'     => array(
                array(
                    'key'       => '_wpinv_type',
                    'compare'   => '!=',
                    'value'     => 'package'
                )
            )
        );

        $items = get_posts( apply_filters( 'getpaid_payment_form_item_dropdown_query_args', $item_args ) );

        if ( empty( $items ) ) {
            return array();
        }

        $options    = array();
        foreach ( $items as $item ) {
            $item      = new GetPaid_Form_Item( $item );
            $options[] = $item->prepare_data_for_use();
        }
        return $options;

    }

    /**
     * Returns an array of items for the currently being edited form.
     */
    public function get_form_items( $id = false ) {
        $form = new GetPaid_Payment_Form( $id );

        // Is this a default form?
        if ( $form->is_default() ) {
            return array();
        }

        return $form->get_items( 'view', 'arrays' );
    }

    /**
     * Converts form items for use.
     */
    public function convert_checkout_items( $items, $invoice ) {

        $converted = array();
        foreach ( $items as $item ) {

            $item_id = $item['id'];
            $_item   = new WPInv_Item( $item_id );

            if( ! $_item ) {
                continue;
            }

            $converted[] = array(
                'title'            => esc_html( wpinv_get_cart_item_name( $item ) ) . wpinv_get_item_suffix( $_item ),
                'id'               => $item['id'],
                'price'            => $item['subtotal'],
                'custom_price'     => $_item->get_is_dynamic_pricing(),
                'recurring'        => $_item->is_recurring(),
                'description'      => apply_filters( 'wpinv_checkout_cart_line_item_summary', '', $item, $_item, $invoice ),
                'minimum_price'    => $_item->get_minimum_price(),
                'allow_quantities' => false,
                'quantity'         => $item['quantity'],
                'required'         => true,
            );
        }
        return $converted;

    }

    /**
     * Converts an array of id => quantity for use.
     */
    public function convert_normal_items( $items ) {

        $converted = array();
        foreach ( $items as $item_id => $quantity ) {

            $item   = new WPInv_Item( $item_id );

            if( ! $item ) {
                continue;
            }

            $converted[] = array(
                'title'            => esc_html( $item->get_name() ) . wpinv_get_item_suffix( $item ),
                'id'               => $item_id,
                'price'            => $item->get_price(),
                'custom_price'     => $item->get_is_dynamic_pricing(),
                'recurring'        => $item->is_recurring(),
                'description'      => $item->get_summary(),
                'minimum_price'    => $item->get_minimum_price(),
                'allow_quantities' => ! empty( $quantity ),
                'quantity'         => empty( $quantity ) ? 1 : $quantity,
                'required'         => true,
            );

        }

        return $converted;

    }

    /**
     * Returns an array of elements for the currently being edited form.
     */
    public function get_form_elements( $id = false ) {

        if ( empty( $id ) ) {
            return wpinv_get_data( 'sample-payment-form' );
        }
        
        $form_elements = get_post_meta( $id, 'wpinv_form_elements', true );

        if ( is_array( $form_elements ) ) {
            return $form_elements;
        }

        return wpinv_get_data( 'sample-payment-form' );
    }

    /**
     * Sends a redrect response to payment details.
     *
     */
    public function send_redirect_response( $url ) {
        $url = urlencode( $url );
        wp_send_json_success( $url );
    }

    /**
     * Fired when a checkout error occurs
     *
     */
    public function checkout_error() {

        $errors = wpinv_get_errors();

        if ( ! empty( $errors ) ) {
            wpinv_print_errors();
            exit;
        }

        wp_send_json_error( __( 'An error occured while processing your payment. Please try again.', 'invoicing' ) );
        exit;

    }

}
