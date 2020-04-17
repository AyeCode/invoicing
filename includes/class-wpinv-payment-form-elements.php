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
                    'level' => 'h1',
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
                )
            ),

            array(
                'type' => 'checkbox',
                'name' => __( 'Checkboxes', 'invoicing' ),
                'defaults'         => array(
                    'value'        => '1',
                    'label'        => __( 'Checkbox Label', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'radio',
                'name' => __( 'Multiple Choice', 'invoicing' ),
                'defaults'     => array(
                    'label'    => __( 'Select one choice', 'invoicing' ),
                    'choices'  => array(
                        __( 'Choice One', 'invoicing' ),
                        __( 'Choice Two', 'invoicing' ),
                        __( 'Choice Three', 'invoicing' )
                    ),
                )
            ),

            array( 
                'type' => 'date',
                'name' => __( 'Date', 'invoicing' ),
                'defaults' => array(
                    'label'    => __( 'Date', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'time',
                'name' => __( 'Time', 'invoicing' ),
                'defaults' => array(
                    'label'    => __( 'Time', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'number',
                'name' => __( 'Number', 'invoicing' ),
                'defaults' => array(
                    'label'    => __( 'Number', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'website',
                'name' => __( 'Website', 'invoicing' ),
                'defaults' => array(
                    'label'    => __( 'Website', 'invoicing' ),
                )
            ),

            array( 
                'type' => 'email',
                'name' => __( 'Email', 'invoicing' ),
                'defaults'  => array(
                    'label' => __( 'Email Address', 'invoicing' ),
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
                <input :required='$field.required' :placeholder='$field.placeholder' :id='$field.id' class='form-control' type='text'>
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
                <textarea :required='$field.required' :placeholder='$field.placeholder' :id='$field.id' class='form-control' rows='3'></textarea>
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
        $restrict = $this->get_restrict_markup( $field, 'select' );
        $label    = "$field.name";
        echo "<div $restrict><label>{{" . $label . "}}</label>";
        echo "<select class='form-control custom-select'></select></div>";
    }

    /**
     * Renders the checkbox element template.
     */
    public function render_checkbox_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'checkbox' );
        $label    = "$field.name";
        echo "<div class='form-check' $restrict>";
        echo "<input class='form-check-input' type='checkbox' />";
        echo "<label class='form-check-label'>{{" . $label . "}}</label>";
        echo '</div>';
    }

    /**
     * Renders radio select fields.
     */
    public function render_radio_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'radio' );
        $label    = "$field.name";
        echo "<div class='form-check' $restrict>";
        echo "<input class='form-check-input' type='radio' />";
        echo "<label class='form-check-label'>{{" . $label . "}}</label>";
        echo '</div>';
    }

    /**
     * Renders the email element template.
     */
    public function render_email_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'email' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='email'></div>";
    }

    /**
     * Renders the website element template.
     */
    public function render_website_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'website' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='url'></div>";
    }

    /**
     * Renders the date element template.
     */
    public function render_date_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'date' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='date'></div>";
    }

    /**
     * Renders the time element template.
     */
    public function render_time_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'time' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='time'></div>";
    }

    /**
     * Renders the number element template.
     */
    public function render_number_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'number' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='number'></div>";
    }
  
}
