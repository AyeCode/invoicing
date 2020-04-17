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

        $this->elements = array(

            array(
                'type'     => 'heading',
                'name'     => __( 'Heading', 'invoicing' ),
                'defaults' => array(
                    'level' => 'h2',
                    'text'  => __( 'Heading', 'invoicing' ),
                )
            ),

            array(
                'type' => 'paragraph',
                'name' => __( 'Paragraph', 'invoicing' ),
                'defaults'  => array(
                    'text'  => __( 'Paragraph text', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'alert',
                'name' => __( 'Alert', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'class'        => 'alert-warning',
                    'text'         => __( 'Alert', 'invoicing' ),
                    'dismissible'  => false,
                )
                ),

            array(
                'type' => 'text',
                'name' => __( 'Text Input', 'invoicing' ),
                'defaults'  => array(
                    'placeholder'  => __( 'Enter some text', 'invoicing' ),
                    'value'        => '',
                    'label'        => __( 'Field Label', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array(
                'type' => 'textarea',
                'name' => __( 'Textarea', 'invoicing' ),
                'defaults'         => array(
                    'placeholder'  => __( 'Enter your text hear', 'invoicing' ),
                    'value'        => '',
                    'label'        => __( 'Textarea Label', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array(
                'type' => 'select',
                'name' => __( 'Dropdown', 'invoicing' ),
                'defaults'         => array(
                    'placeholder'  => __( 'Select a value', 'invoicing' ),
                    'value'        => '',
                    'label'        => __( 'Dropdown Label', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'options'      => array(
                        esc_attr__( 'Option One', 'invoicing' ),
                        esc_attr__( 'Option Two', 'invoicing' ),
                        esc_attr__( 'Option Three', 'invoicing' )
                    ),
                )
            ),

            array(
                'type' => 'checkbox',
                'name' => __( 'Checkbox', 'invoicing' ),
                'defaults'         => array(
                    'value'        => '',
                    'label'        => __( 'Checkbox Label', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'radio',
                'name' => __( 'Multiple Choice', 'invoicing' ),
                'defaults'     => array(
                    'label'    => __( 'Select one choice', 'invoicing' ),
                    'options'  => array(
                        esc_attr__( 'Choice One', 'invoicing' ),
                        esc_attr__( 'Choice Two', 'invoicing' ),
                        esc_attr__( 'Choice Three', 'invoicing' )
                    ),
                )
            ),

            array( 
                'type' => 'date',
                'name' => __( 'Date', 'invoicing' ),
                'defaults' => array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Date', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'time',
                'name' => __( 'Time', 'invoicing' ),
                'defaults' => array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Time', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'number',
                'name' => __( 'Number', 'invoicing' ),
                'defaults' => array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Number', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'website',
                'name' => __( 'Website', 'invoicing' ),
                'defaults' => array(
                    'placeholder'  => 'http://example.com',
                    'value'        => '',
                    'label'        => __( 'Website', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'email',
                'name' => __( 'Email', 'invoicing' ),
                'defaults'  => array(
                    'placeholder'  => 'jon@snow.com',
                    'value'        => '',
                    'label'        => __( 'Email Address', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                )
            ),

            array( 
                'type' => 'discount',
                'name' => __( 'Discount Input', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'input_label'  => __( 'Coupon Code', 'invoicing' ),
                    'button_label' => __( 'Apply Coupon', 'invoicing' ),
                    'description'  => __( 'Have a discount code? Enter it above.', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'items',
                'name' => __( 'Items', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'items'        => array(),
                    'type'         => 'total',
                    'show_total'   => false,
                    'description'  => '',
                )
            ),

            array( 
                'type' => 'pay_button',
                'name' => __( 'Payment Button', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'class'        => 'btn-primary',
                    'label'        => __( 'Pay Now »', 'invoicing' ),
                    'description'  => __( 'By continuing with our payment, you are agreeing to our privacy policy and terms of service.', 'invoicing' ),
                )
            )
        );

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
            <div $restrict>
                <label :for='$field.id'>{{" . $label . "}}</label>
                <input  :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='text'>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
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
            <div $restrict>
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
            <div $restrict>
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
                        <button class='btn btn-outline-secondary' type='button' @click.prevent='$field.options.splice(index, 1)'><span class='dashicons dashicons-trash'></span></button>
                    </div>
                </div>
                <div class='form-group'>
                    <button class='btn btn-outline-secondary' type='button' @click.prevent='$field.options.push(\"\")'>Add Option</button>
                </div>
            </div>
        ";

    }

    /**
     * Renders the checkbox element template.
     */
    public function render_checkbox_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'checkbox' );
        $label    = "$field.label";
        echo "
            <div class='form-check' $restrict>
                <input  :id='$field.id' class='form-check-input' type='checkbox' />
                <label class='form-check-label' :for='$field.id'>{{" . $label . "}}</label>
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
            <div $restrict>
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
                        <button class='btn btn-outline-secondary' type='button' @click.prevent='$field.options.splice(index, 1)'><span class='dashicons dashicons-trash'></span></button>
                    </div>
                </div>
                <div class='form-group'>
                    <button class='btn btn-outline-secondary' type='button' @click.prevent='$field.options.push(\"\")'>Add Option</button>
                </div>
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
     * Renders the website element template.
     */
    public function render_website_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'website' );
        $label    = "$field.label";
        echo "
            <div $restrict>
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
            <div $restrict>
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
     * Renders the time element template.
     */
    public function render_time_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'time' );
        $label    = "$field.label";
        echo "
            <div $restrict>
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
     * Renders the number element template.
     */
    public function render_number_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'number' );
        $label    = "$field.label";
        echo "
            <div $restrict>
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
    public function render_discount_templates( $field ) {
        $restrict  = $this->get_restrict_markup( $field, 'discount' );
        echo "
            <div $restrict class='discount_field  border rounded p-3'>
                <div class='discount_field_inner d-flex flex-column flex-md-row'>
                    <input  :placeholder='$field.input_label' class='form-control mr-2 mb-2' style='flex: 1;' type='text'>
                    <button class='btn btn-secondary submit-button mb-2' type='submit' @click.prevent=''>{{" . "$field.button_label}}</button>
                </div>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>
        ";
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
        echo "
            <div $restrict class='item_totals'>

                <div v-if='$field.type == \"total\"'>

                </div>

                <div v-if='$field.type == \"checkboxes\"'>

                </div>

                <div v-if='$field.type == \"radio\"'>

                </div>

                <div v-if='$field.show_total'>

                </div>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>

                <pre>
// TODO: Ask admin to select which items he/she
// wants to sell via this form
            
// TODO:- Let admin set whether they want customers to select some items,
// a single item( use case variations), or be tied to all items ( i.e 
// Only display the totals)
                </pre>
            </div>
        ";
    }

    /**
     * Renders the items element template.
     */
    public function edit_items_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'items' );
        $label    = __( 'Items Type', 'invoicing' );
        $label2   = __( 'Help Text', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this element', 'invoicing' );
        $label4   = __( 'Button Text', 'invoicing' );
        $label5   = __( 'Items', 'invoicing' );
        $label6   = esc_attr__( 'Select the items that you want to sell via this form.', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $id2      = $field . '.id + "_edit2"';
        $id3      = $field . '.id + "_edit3"';
        $id4      = $field . '.id + "_edit4"';
        echo "<div $restrict>
                
                <pre>
                    {{items}}
                </pre>

            </div>
        ";

    }

    public function get_published_items() {
    
        $item_args = array(
            'post_type'      => 'wpi_item',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish' ),
        );
    
        $items      = get_posts( apply_filters( 'wpinv_item_dropdown_query_args', $item_args ) );

        if ( empty( $items ) ) {
            return array();
        }

        $options    = array();
        foreach ( $items as $item ) {
            $title     = esc_html( $item->post_title );
            $title    .= wpinv_get_item_suffix( $item->ID, false );
            $id        = absint( $item->ID );
            $price     = wpinv_sanitize_amount( get_post_meta( $id, '_wpinv_price', true ) );
            $recurring = get_post_meta( $id, '_wpinv_is_recurring', true );
            
            $options[] = compact( 'title', 'id', 'price', 'recurring');
        }
        return $options;

    }

}
