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

}
