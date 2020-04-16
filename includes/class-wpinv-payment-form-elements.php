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
                'type' => 'heading',
                'name' => __( 'Heading', 'invoicing' ),
            ),

            array(
                'type' => 'paragraph',
                'name' => __( 'Paragraph', 'invoicing' ),
            ),

            array(
                'type' => 'text',
                'name' => __( 'Text Input', 'invoicing' ),
            ),

            array(
                'type' => 'textarea',
                'name' => __( 'Textarea', 'invoicing' ),
            ),

            array(
                'type' => 'select',
                'name' => __( 'Dropdown', 'invoicing' ),
            ),

            array(
                'type' => 'checkbox',
                'name' => __( 'Checkboxes', 'invoicing' ),
            ),

            array( 
                'type' => 'radio',
                'name' => __( 'Multiple Choice', 'invoicing' ),
            ),

            array( 
                'type' => 'date',
                'name' => __( 'Date', 'invoicing' ),
            ),

            array( 
                'type' => 'time',
                'name' => __( 'Time', 'invoicing' ),
            ),

            array( 
                'type' => 'number',
                'name' => __( 'Number', 'invoicing' ),
            ),

            array( 
                'type' => 'website',
                'name' => __( 'Website', 'invoicing' ),
            ),

            array( 
                'type' => 'email',
                'name' => __( 'Email', 'invoicing' ),
            ),
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
        $label    = "$field.name";
        echo "<h1 $restrict v-html='$label'></h1>";
    }

    /**
     * Renders a paragraph element template.
     */
    public function render_paragraph_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'paragraph' );
        $label    = "$field.name";
        echo "<p $restrict v-html='$label'></p>";
    }

    /**
     * Renders the text element template.
     */
    public function render_text_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'text' );
        $label    = "$field.name";
        echo "<div $restrict><label $restrict>{{" . $label . "}}</label>";
        echo "<input class='form-control' type='text'></div>";
    }

    /**
     * Renders the textarea element template.
     */
    public function render_textarea_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'textarea' );
        $label    = "$field.name";
        echo "<div $restrict><label>{{" . $label . "}}</label>";
        echo "<textarea class='form-control' rows='3'></textarea></div>";
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
