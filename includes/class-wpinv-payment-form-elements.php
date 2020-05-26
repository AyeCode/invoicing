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

            if ( method_exists( $this, "frontend_render_{$element}_template" ) ) {
                add_action( "wpinv_frontend_render_payment_form_$element", array( $this, "frontend_render_{$element}_template" ), 10, 3 );
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

            /*array( 
                'type' => 'separator',
                'name' => __( 'Separator', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'dismissible'  => false,
                )
            ),*/

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
                'type' => 'address',
                'name' => __( 'Address', 'invoicing' ),
                'defaults'  => array(

                    'fields' => array(
                        array(
                            'placeholder'  => 'Jon',
                            'value'        => '',
                            'label'        => __( 'First Name', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_first_name',
                        ),

                        array(
                            'placeholder'  => 'Snow',
                            'value'        => '',
                            'label'        => __( 'Last Name', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_last_name',
                        ),
                    
                        array(
                            'placeholder'  => '',
                            'value'        => '',
                            'label'        => __( 'Address', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_address',
                        ),

                        array(
                            'placeholder'  => '',
                            'value'        => '',
                            'label'        => __( 'City', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_city',
                        ),

                        array(
                            'placeholder'  => __( 'Select your country' ),
                            'value'        => '',
                            'label'        => __( 'Country', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_country',
                        ),

                        array(
                            'placeholder'  => __( 'Choose a state', 'invoicing' ),
                            'value'        => '',
                            'label'        => __( 'State / Province', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_state',
                        ),

                        array(
                            'placeholder'  => '',
                            'value'        => '',
                            'label'        => __( 'ZIP / Postcode', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_zip',
                        ),

                        array(
                            'placeholder'  => '',
                            'value'        => '',
                            'label'        => __( 'Phone', 'invoicing' ),
                            'description'  => '',
                            'required'     => false,
                            'visible'      => true,
                            'name'         => 'wpinv_phone',
                        )
                    )
                )
            ),

            array( 
                'type' => 'billing_email',
                'name' => __( 'Billing Email', 'invoicing' ),
                'defaults'  => array(
                    'placeholder'  => 'jon@snow.com',
                    'value'        => '',
                    'label'        => __( 'Billing Email', 'invoicing' ),
                    'description'  => '',
                    'premade'      => true,
                )
            ),
/*
            array( 
                'type' => 'discount',
                'name' => __( 'Discount Input', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'input_label'  => __( 'Coupon Code', 'invoicing' ),
                    'button_label' => __( 'Apply Coupon', 'invoicing' ),
                    'description'  => __( 'Have a discount code? Enter it above.', 'invoicing' ),
                )
            ),*/

            array( 
                'type' => 'items',
                'name' => __( 'Items', 'invoicing' ),
                'defaults'  => array(
                    'value'        => '',
                    'items_type'   => 'total',
                    'description'  => '',
                    'premade'      => true,
                )
            ),

            array( 
                'type'       => 'pay_button',
                'name'       => __( 'Payment Button', 'invoicing' ),
                'defaults'   => array(
                    'value'        => '',
                    'class'        => 'btn-primary',
                    'label'        => __( 'Pay Now »', 'invoicing' ),
                    'description'  => __( 'By continuing with our payment, you are agreeing to our privacy policy and terms of service.', 'invoicing' ),
                    'premade'      => true,
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
     * Renders the title element on the frontend.
     */
    public function frontend_render_heading_template( $field ) {
        $tag = $field['level'];
        echo "<$tag>{$field['text']}</$tag>";
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
     * Renders the paragraph element on the frontend.
     */
    public function frontend_render_paragraph_template( $field ) {
        echo "<p>{$field['text']}</p>";
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
     * Renders the text element on the frontend.
     */
    public function frontend_render_text_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'placeholder'=> esc_attr( $field['placeholder'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the textarea element on the frontend.
     */
    public function frontend_render_textarea_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->textarea(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'placeholder'=> esc_attr( $field['placeholder'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'rows'       => 3,
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the select element on the frontend.
     */
    public function frontend_render_select_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->select(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'placeholder'=> esc_attr( $field['placeholder'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'options'    => array_combine( $field['options'], $field['options'] ),
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
        $label    = "$field.label";
        echo "
            <div class='form-check' $restrict>
                <div class='wpinv-payment-form-field-preview-overlay'></div>
                <input  :id='$field.id' class='form-check-input' type='checkbox' />
                <label class='form-check-label' :for='$field.id'>{{" . $label . "}}</label>
                <small v-if='$field.description' class='form-text text-muted' v-html='$field.description'></small>
            </div>    
        ";
    }

    /**
     * Renders the checkbox element on the frontend.
     */
    public function frontend_render_checkbox_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'value'      => esc_attr__( 'Yes', 'invoicing' ),
                'type'       => 'checkbox',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the radio element on the frontend.
     */
    public function frontend_render_radio_template( $field ) {
        
        echo "<div class='form-group'>";

        if ( ! empty( $field['label'] ) ) {
            $label = wp_kses_post( $field['label'] );
            echo "<legend class='col-form-label'>$label</legend>";
        }

        foreach( $field['options'] as $index => $option ) {
            $id    = $field['id'] . $index;
            $name  = $field['id'];
            $value = esc_attr( $option );
            $label = wp_kses_post( $option );

            echo "
                <div class='form-check'>
                    <input class='form-check-input' type='radio' name='$name' id='$id' value='$value'>
                    <label class='form-check-label' for='$id'>$label</label>
                </div>
            ";
        }

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the address element on the frontend.
     */
    public function frontend_render_address_template( $field ) {
        
        echo "<div class='wpinv-address-fields'>";

        foreach( $field['fields'] as $address_field ) {

            if ( empty( $address_field['visible'] ) ) {
                continue;
            }

            $class = esc_attr( $address_field['name'] );
            echo "<div class='form-group $class'>";

            $label = $address_field['label'];

            if ( ! empty( $address_field['required'] ) ) {
                $label .= "<span class='text-danger'> *</span>";
            }

            if ( 'wpinv_country' == $address_field['name'] ) {

                echo aui()->select( array(
                    'options'          => wpinv_get_country_list(),
                    'name'             => esc_attr( $address_field['name'] ),
                    'id'               => esc_attr( $address_field['name'] ),
                    'value'            => wpinv_get_default_country(),
                    'placeholder'      => esc_attr( $address_field['placeholder'] ),
                    'required'         => (bool) $address_field['required'],
                    'no_wrap'          => true,
                    'label'            => wp_kses_post( $label ),
                    'select2'          => false,
                ));
    
            } else if ( 'wpinv_state' == $address_field['name'] ) {

                $states = wpinv_get_country_states( wpinv_get_default_country() );
                $state  = wpinv_get_default_state();

                if ( ! empty( $states ) ) {

                    echo aui()->select( array(
                        'options'          => $states,
                        'name'             => esc_attr( $address_field['name'] ),
                        'id'               => esc_attr( $address_field['name'] ),
                        'value'            => $state,
                        'placeholder'      => esc_attr( $address_field['placeholder'] ),
                        'required'         => (bool) $address_field['required'],
                        'no_wrap'          => true,
                        'label'            => wp_kses_post( $label ),
                        'select2'          => false,
                    ));

                } else {

                    echo aui()->input(
                        array(
                            'name'       => esc_attr( $address_field['name'] ),
                            'id'         => esc_attr( $address_field['name'] ),
                            'required'   => (bool) $address_field['required'],
                            'label'      => wp_kses_post( $label ),
                            'no_wrap'    => true,
                            'type'       => 'text',
                        )
                    );

                }

            } else {

                echo aui()->input(
                    array(
                        'name'       => esc_attr( $address_field['name'] ),
                        'id'         => esc_attr( $address_field['name'] ),
                        'required'   => (bool) $address_field['required'],
                        'label'      => wp_kses_post( $label ),
                        'no_wrap'    => true,
                        'placeholder' => esc_attr( $address_field['placeholder'] ),
                        'type'       => 'text',
                    )
                );

            }
            

            if ( ! empty( $address_field['description'] ) ) {
                $description = wp_kses_post( $address_field['description'] );
                echo "<small class='form-text text-muted'>$description</small>";
            }
    
            echo '</div>';

        }

        echo '</div>';

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
     * Renders the email element on the frontend.
     */
    public function frontend_render_email_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'placeholder' => esc_attr( $field['placeholder'] ),
                'type'       => 'email',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

    }

    /**
     * Renders the billing email element on the frontend.
     */
    public function frontend_render_billing_email_template( $field ) {
        
        echo "<div class='form-group'>";
        $value = '';

        if ( is_user_logged_in() ) {
            $user  = wp_get_current_user();
            $value = sanitize_email( $user->user_email );
        }
        echo aui()->input(
            array(
                'name'       => 'billing_email',
                'value'      => $value,
                'id'         => esc_attr( $field['id'] ),
                'required'   => true,
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'placeholder' => esc_attr( $field['placeholder'] ),
                'type'       => 'email',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the website element on the frontend.
     */
    public function frontend_render_website_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'placeholder' => esc_attr( $field['placeholder'] ),
                'type'       => 'url',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the date element on the frontend.
     */
    public function frontend_render_date_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'type'       => 'date',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the time element on the frontend.
     */
    public function frontend_render_time_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'no_wrap'    => true,
                'type'       => 'time',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the number element on the frontend.
     */
    public function frontend_render_number_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'required'   => (bool) $field['required'],
                'label'      => wp_kses_post( $field['label'] ),
                'placeholder' => esc_attr( $field['placeholder'] ),
                'no_wrap'    => true,
                'type'       => 'number',
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
        echo "<hr class='featurette-divider mt-0 mb-2' $restrict>";
    }

    /**
     * Renders the separator element on the frontend.
     */
    public function frontend_render_separator_template( $field ) {
        echo '<hr class="featurette-divider mt-0 mb-2" />';
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
     * Renders the pay_button element on the frontend.
     */
    public function frontend_render_pay_button_template( $field ) {

        echo "<div class='mt-4 mb-4'>";
            do_action( 'wpinv_payment_mode_select' );
        echo "</div>";

        echo "<div class='form-group'>";

        $class = 'wpinv-payment-form-submit btn btn-block submit-button ' . sanitize_html_class( $field['class'] );
        echo aui()->input(
            array(
                'name'       => esc_attr( $field['id'] ),
                'id'         => esc_attr( $field['id'] ),
                'value'      => esc_attr( $field['label'] ),
                'no_wrap'    => true,
                'type'       => 'submit',
                'class'      => $class,
            )
        );

        if ( ! empty( $field['description'] ) ) {
            $description = wp_kses_post( $field['description'] );
            echo "<small class='form-text text-muted'>$description</small>";
        }

        echo '</div>';

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
     * Renders the alert element on the frontend.
     */
    public function frontend_render_alert_template( $field ) {
        
        echo "<div class='form-group'>";

        echo aui()->alert(
            array(
                'content'     => wp_kses_post( $field['text'] ),
                'dismissible' => $field['dismissible'],
                'type'        => str_replace( 'alert-', '', $field['class'] ),
            )
        );

        echo '</div>';

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

            <div <?php echo $restrict; ?> class="discount_field  border rounded p-3">
                <div class="discount_field_inner d-flex flex-column flex-md-row">
                    <input  :placeholder="<?php echo $field ?>.input_label" class="form-control mr-2 mb-2" style="flex: 1;" type="text">
                    <button class="btn btn-secondary submit-button mb-2" type="submit" @click.prevent="">{{<?php echo $field; ?>.button_label}}</button>
                </div>
                <small v-if='<?php echo $field ?>.description' class='form-text text-muted' v-html='<?php echo $field ?>.description'></small>
            </div>

        <?php
    }

    /**
     * Renders the discount element on the frontend.
     */
    public function frontend_render_discount_template( $field ) {
        
        $placeholder = esc_attr( $field['input_label'] );
        $label       = sanitize_text_field( $field['button_label'] );
        $description = '';

        if ( ! empty( $field['description'] ) ) {
            $description = "<small class='form-text text-muted'>{$field['description']}</small>";
        }
?>

    <div class="form-group">
        <div class="discount_field  border rounded p-3">
            <div class="discount_field_inner d-flex flex-column flex-md-row">
                <input  placeholder="<?php echo $placeholder; ?>" class="form-control mr-2 mb-2" style="flex: 1;" type="text">
                <a href="#" class="btn btn-secondary submit-button mb-2 wpinv-payment-form-coupon-button"><?php echo $label; ?></a>
            </div>
            <?php echo $description ?>
        </div>
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
        $label     = __( 'Item totals will appear here. Click to set items.', 'invoicing' );
        $label2    = __( 'Your form allows customers to buy several recurring items. This is not supported and will lead to unexpected behaviour.', 'invoicing' );
        $label2   .= ' ' . __( 'To prevent this, limit customers to selecting a single item.', 'invoicing' );
        $label3    = __( 'Item totals will appear here.', 'invoicing' );
        echo "
            <div $restrict class='item_totals text-center'>
                <div v-if='!is_default'>
                    <div v-if='canCheckoutSeveralSubscriptions($field)' class='p-4 bg-danger text-light'>$label2</div>
                    <div v-if='! canCheckoutSeveralSubscriptions($field)' class='p-4 bg-warning'>$label</div>
                </div>
                <div v-if='is_default'>
                    <div class='p-4 bg-warning'>$label3</div>
                </div>
            </div>
        ";
    }

    /**
     * Renders the items element on the frontend.
     */
    public function frontend_render_items_template( $field, $items ) {

        echo "<div class='form-group item_totals'>";
        
        $id = esc_attr( $field['id'] );
        if ( 'total' == $field[ 'items_type' ] ) {
            $total     = 0;
            $tax       = 0;
            $sub_total = 0;

            ?>
            <div class="border item_totals_type_total">

                <?php
                    foreach( $items as $item ) {

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

                        $class  = 'col-8';
                        $class2 = '';

                        if ( ! empty( $item['allow_quantities'] ) ) {
                            $class = 'col-6 pt-2';
                            $class2 = 'pt-2';
                        }

                        if ( ! empty( $item['custom_price'] ) ) {
                            $class .= ' pt-2';
                        }
            
                ?>
                    <div  class="item_totals_item">
                        <div class='row pl-2 pr-2 pt-2'>
                            <div class='<?php echo $class; ?>'><?php echo esc_html( $item['title'] ) ?></div>

                            <?php  if ( ! empty( $item['allow_quantities'] ) ) { ?>

                                <div class='col-2'>
                                    <input name='wpinv-item-<?php echo (int) $item['id']; ?>-quantity' type='number' class='form-control wpinv-item-quantity-input pr-1' value='1' min='1' required>
                                </div>

                            <?php } else { ?>
                                <input type='hidden' class='wpinv-item-quantity-input' value='1'>
                            <?php } if ( empty( $item['custom_price'] ) ) { ?>

                                <div class='col-4 <?php echo $class2; ?>'>
                                    <?php echo wpinv_price( wpinv_format_amount( $item['price'] ) ) ?>
                                    <input name='wpinv-items[<?php echo (int) $item['id']; ?>]' type='hidden' class='wpinv-item-price-input' value='<?php echo floatval( $item['price'] ); ?>'>
                                </div>

                            <?php } else {?>

                                <div class='col-4'>
                                    <div class='input-group'>

                                        <?php if ( 'left' == wpinv_currency_position() ) { ?>
                                            <div class='input-group-prepend'>
                                                <span class='input-group-text'><?php echo wpinv_currency_symbol(); ?></span>
                                            </div>
                                        <?php } ?>

                                        <input type='number' name='wpinv-items[<?php echo (int) $item['id']; ?>]' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
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

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                        </div>
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

        <?php if ( 'radio' == $field[ 'items_type' ] ) { ?>
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
                            <div class='<?php echo $class; ?>'><?php echo esc_html( $item['title'] ) ?></div>

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

                                        <input type='number' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
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
                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                        </div>
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
                            <div class='<?php echo $class; ?>'><?php echo esc_html( $item['title'] ) ?></div>

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

                                        <input type='number' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
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

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                        </div>
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
                            <div class='<?php echo $class; ?>'><?php echo esc_html( $item['title'] ) ?></div>

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

                                        <input type='number' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
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

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                        </div>
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
                            <div class='<?php echo $class; ?>'><?php echo esc_html( $item['title'] ) ?></div>

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

                                        <input type='number' name='<?php echo $name; ?>' class='form-control wpinv-item-price-input' placeholder='<?php echo floatval( $item['price'] ); ?>' value='<?php echo floatval( $item['price'] ); ?>' min='<?php echo intval( $item['minimum_price'] ); ?>'>
                                    
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

                    <?php if ( wpinv_use_taxes() ) { ?>
                        <div class='row'>
                            <div class='col-8'><strong class='mr-5'><?php _e( 'Sub Total', 'invoicing' ); ?></strong></div>
                            <div class='col-4'><strong class='wpinv-items-sub-total'><?php echo wpinv_price( wpinv_format_amount( $sub_total ) ) ?></strong></div>
                        </div>
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
        $label    = __( 'Let customers...', 'invoicing' );
        $label2   = __( 'Available Items', 'invoicing' );
        $label3   = esc_attr__( 'Add some help text for this element', 'invoicing' );
        $id       = $field . '.id + "_edit"';
        $id2      = $field . '.id + "_edit2"';
        $id3      = $field . '.id + "_edit3"';
        $id4      = $field . '.id + "_edit4"';
        $label4   = esc_attr__( 'This will be shown to the customer as the recommended price', 'invoicing' );
        $label5   = esc_attr__( 'Allow users to pay what they want', 'invoicing' );
        $label6   = esc_attr__( 'Enter the minimum price that a user can pay', 'invoicing' );
        $label7   = esc_attr__( 'Allow users to buy several quantities', 'invoicing' );
        $label8   = esc_attr__( 'This item is required', 'invoicing' );

        // Item types.
        $item_types      = apply_filters( 'wpinv_item_types_for_quick_add_item', wpinv_get_item_types(), $post );
        $item_types_html = '';

        foreach ( $item_types as $type => $_label ) {
            $type  = esc_attr( $type );
            $_label = esc_html( $_label );
            $item_types_html .= "<option value='$type'>$_label</type>";
        }

        // Taxes.
        $taxes = '';
        if ( $wpinv_euvat->allow_vat_rules() ) {
            $taxes .= "<div class='form-group'> <label :for='$id + item.id + \"rule\"'>";
            $taxes .= __( 'VAT rule type', 'invoicing' );
            $taxes .= "</label><select :id='$id + item.id + \"rule\"' class='form-control custom-select' v-model='item.rule'>";

            foreach ( $wpinv_euvat->get_rules() as $type => $_label ) {
                $type    = esc_attr( $type );
                $_label  = esc_html( $_label );
                $taxes  .= "<option value='$type'>$_label</type>";
            }

            $taxes .= '</select></div>';
        }

        if ( $wpinv_euvat->allow_vat_classes() ) {
            $taxes .= "<div class='form-group'> <label :for='$id + item.id + \"class\"'>";
            $taxes .= __( 'VAT class', 'invoicing' );
            $taxes .= "</label><select :id='$id + item.id + \"class\"' class='form-control custom-select' v-model='item.class'>";

            foreach ( $wpinv_euvat->get_all_classes() as $type => $_label ) {
                $type    = esc_attr( $type );
                $_label  = esc_html( $_label );
                $taxes  .= "<option value='$type'>$_label</type>";
            }

            $taxes .= '</select></div>';
        }

        echo "<div $restrict>

                <label v-if='!is_default'>$label2</label>

                <draggable v-model='form_items' group='selectable_form_items'>
                    <div class='wpinv-available-items-editor' v-for='(item, index) in form_items' :class='\"item_\" + item.id' :key='item.id'>

                        <div class='wpinv-available-items-editor-header' @click.prevent='togglePanel(item.id)'>
                            <span class='label'>{{item.title}}</span>
                            <span class='price'>({{formatPrice(item.price)}})</span>
                            <span class='toggle-icon'>
                                <span class='dashicons dashicons-arrow-down'></span>
                                <span class='dashicons dashicons-arrow-up' style='display:none'></span>
                            </span>
                        </div>

                        <div class='wpinv-available-items-editor-body'>
                            <div class='p-2'>

                                <div class='form-group'>
                                    <label :for='$id + item.id'>Item Name</label>
                                    <input :id='$id + item.id' v-model='item.title' class='form-control' />
                                </div>

                                <div class='form-group'>
                                    <label :for='$id + item.id + \"price\"'>Item Price</label>
                                    <input :id='$id + item.id + \"price\"' v-model='item.price' class='form-control' />
                                    <small class='form-text text-muted' v-if='item.custom_price'>$label4</small>
                                </div>

                                <div class='form-group' v-if='item.new'>
                                    <label :for='$id + item.id + \"type\"'>Item Type</label>
                                    <select class='form-control custom-select' v-model='item.type'>
                                        $item_types_html
                                    </select>
                                </div>

                                <div v-if='item.new'>$taxes</div>

                                <div class='form-group form-check'>
                                    <input :id='$id4 + item.id + \"custom_price\"' v-model='item.custom_price' type='checkbox' class='form-check-input' />
                                    <label class='form-check-label' :for='$id4 + item.id + \"custom_price\"'>$label5</label>
                                </div>

                                <div class='form-group' v-if='item.custom_price'>
                                    <label :for='$id + item.id + \"minimum_price\"'>Minimum Price</label>
                                    <input :id='$id + item.id + \"minimum_price\"' placeholder='0.00' v-model='item.minimum_price' class='form-control' />
                                    <small class='form-text text-muted'>$label6</small>
                                </div>

                                <div class='form-group form-check'>
                                    <input :id='$id + item.id + \"quantities\"' v-model='item.allow_quantities' type='checkbox' class='form-check-input' />
                                    <label class='form-check-label' :for='$id + item.id + \"quantities\"'>$label7</label>
                                </div>

                                <div class='form-group form-check'>
                                    <input :id='$id + item.id + \"required\"' v-model='item.required' type='checkbox' class='form-check-input' />
                                    <label class='form-check-label' :for='$id + item.id + \"required\"'>$label8</label>
                                </div>

                                <div class='form-group'>
                                    <label :for='$id + item.id + \"description\"'>Item Description</label>
                                    <textarea :id='$id + item.id + \"description\"' v-model='item.description' class='form-control'></textarea>
                                </div>

                                <button type='button' class='button button-link button-link-delete' @click.prevent='removeItem(item)'>Delete Item</button>

                            </div>
                        </div>

                    </div>
                </draggable>

                <small v-if='! form_items.length && !is_default' class='form-text text-danger'> You have not set up any items. Please select an item below or create a new item.</small>

                <div class='form-group mt-2' v-if='!is_default'>

                    <select class='form-control custom-select' v-model='selected_item' @change='addSelectedItem'>
                        <option value=''>"        . __( 'Add an existing item to the form', 'invoicing' ) ."</option>
                        <option v-for='(item, index) in all_items' :value='index'>{{item.title}}</option>
                    </select>

                </div>

                <div class='form-group' v-if='!is_default'>
                    <input type='button' value='Add item' class='button button-primary'  @click.prevent='addSelectedItem' :disabled='selected_item == \"\"'>
                    <small>Or <a href='' @click.prevent='addNewItem'>create a new item</a>.</small>
                </div>

                <div class='form-group mt-5' v-if='!is_default'>
                    <label :for='$id2'>$label</label>

                    <select class='form-control custom-select' :id='$id2' v-model='$field.items_type'>
                        <option value='total' :disabled='canCheckoutSeveralSubscriptions($field)'>"        . __( 'Buy all items on the list', 'invoicing' ) ."</option>
                        <option value='radio'>"        . __( 'Select a single item from the list', 'invoicing' ) ."</option>
                        <option value='checkbox' :disabled='canCheckoutSeveralSubscriptions($field)'>"     . __( 'Select one or more items on the list', 'invoicing' ) ."</option>
                        <option value='select'>"       . __( 'Select a single item from a dropdown', 'invoicing' ) ."</option>
                        <option value='multi_select' :disabled='canCheckoutSeveralSubscriptions($field)'>" . __( 'Select a one or more items from a dropdown', 'invoicing' ) ."</option>
                    </select>

                </div>

                <div class='form-group'>
                    <label :for='$id3'>Help Text</label>
                    <textarea placeholder='$label3' :id='$id3' v-model='$field.description' class='form-control' rows='3'></textarea>
                </div>

            </div>
        ";

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
    
        $items = get_posts( apply_filters( 'wpinv_payment_form_item_dropdown_query_args', $item_args ) );

        if ( empty( $items ) ) {
            return array();
        }

        $options    = array();
        foreach ( $items as $item ) {
            $title            = esc_html( $item->post_title );
            $title           .= wpinv_get_item_suffix( $item->ID, false );
            $id               = absint( $item->ID );
            $price            = wpinv_sanitize_amount( get_post_meta( $id, '_wpinv_price', true ) );
            $recurring        = (bool) get_post_meta( $id, '_wpinv_is_recurring', true );
            $description      = $item->post_excerpt;
            $custom_price     = (bool) get_post_meta( $id, '_wpinv_dynamic_pricing', true );
            $minimum_price    = (float) get_post_meta( $id, '_minimum_price', true );
            $allow_quantities = false;
            $options[]        = compact( 'title', 'id', 'price', 'recurring', 'description', 'custom_price', 'minimum_price', 'allow_quantities' );

        }
        return $options;

    }

    /**
     * Returns an array of items for the currently being edited form.
     */
    public function get_form_items( $id = false ) {
        
        if ( empty( $id ) ) {
            return wpinv_get_data( 'sample-payment-form-items' );
        }
        
        $form_elements = get_post_meta( $id, 'wpinv_form_items', true );

        if ( is_array( $form_elements ) ) {
            return $form_elements;
        }

        return wpinv_get_data( 'sample-payment-form-items' );

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
                'title'        => esc_html( wpinv_get_cart_item_name( $item ) ) . wpinv_get_item_suffix( $_item ),
                'id'           => $item['id'],
                'price'        => $item['subtotal'],
                'custom_price' => $_item->get_is_dynamic_pricing(),
                'recurring'    => $_item->is_recurring(),
                'description'  => apply_filters( 'wpinv_checkout_cart_line_item_summary', '', $item, $_item, $invoice ),
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
