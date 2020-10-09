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
     * Renders the gateway select element template.
     */
    public function render_gateway_select_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'gateway_select' );
        $text     = __( 'The gateway select box will appear here', 'invoicing' );
        echo "
            <div $restrict class='alert alert-info' role='alert'>
                <span>$text</span>
            </div>
        ";
    }

    /**
     * Renders the ip address element template.
     */
    public function render_ip_address_template( $field ) {
        $restrict   = $this->get_restrict_markup( $field, 'ip_address' );
        $ip_address = sanitize_text_field( wpinv_get_ip() );
        $url        = esc_url( getpaid_ip_location_url( $ip_address ) );

        echo "
            <div $restrict class='getpaid-ip-info'>
                <span>{{{$field}.text}}</span>
                <a target='_blank' href='$url'>$ip_address&nbsp;&nbsp;<i class='fa fa-external-link-square' aria-hidden='true'></i></a>
            </div>
        ";
    }

    /**
     * Renders the total payable element template.
     */
    public function render_total_payable_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'total_payable' );
        $text     = __( 'The total payable amount will appear here', 'invoicing' );
        echo "
            <div $restrict class='alert alert-info' role='alert'>
                <span>$text</span>
            </div>
        ";
    }

    /**
     * Renders the title element template.
     */
    public function render_heading_template( $field ) {
        $restrict = $this->get_restrict_markup( $field, 'heading' );
        echo "<component :is='$field.level' $restrict v-html='$field.text'></component>";
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
     * Renders the items element template.
     */
    public function render_items_template( $field ) {
        $restrict  = $this->get_restrict_markup( $field, 'items' );
        ?>

        <div <?php echo $restrict; ?> class='item_totals'>
            <div v-if='!is_default'>
                <div v-if='! canCheckoutSeveralSubscriptions(<?php echo $field; ?>)' class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here. Click to set items.', 'invoicing' ) ?></div>
                <div v-if='canCheckoutSeveralSubscriptions(<?php echo $field; ?>)' class='alert alert-danger' role='alert'><?php _e( 'Your form allows customers to buy several recurring items. This is not supported and might lead to unexpected behaviour.', 'invoicing' ); ?></div>
            </div>
            <div v-if='is_default'>
                <div class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here.', 'invoicing' ) ?></div>
            </div>
        </div>

        <?php
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
