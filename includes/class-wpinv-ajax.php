<?php
/**
 * Contains the ajax handlers.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Ajax class.
 */
class WPInv_Ajax {

    /**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_wpinv_ajax' ), 0 );
		self::add_ajax_events();
    }

    /**
	 * Set GetPaid AJAX constant and headers.
	 */
	public static function define_ajax() {

		if ( ! empty( $_GET['wpinv-ajax'] ) ) {
			getpaid_maybe_define_constant( 'DOING_AJAX', true );
			getpaid_maybe_define_constant( 'WPInv_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				/** @scrutinizer ignore-unhandled */ @ini_set( 'display_errors', 0 );
			}
			$GLOBALS['wpdb']->hide_errors();
		}

    }
    
    /**
	 * Send headers for GetPaid Ajax Requests.
	 *
	 * @since 1.0.18
	 */
	private static function wpinv_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		}
    }
    
    /**
	 * Check for GetPaid Ajax request and fire action.
	 */
	public static function do_wpinv_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['wpinv-ajax'] ) ) {
			$wp_query->set( 'wpinv-ajax', sanitize_text_field( wp_unslash( $_GET['wpinv-ajax'] ) ) );
		}

		$action = $wp_query->get( 'wpinv-ajax' );

		if ( $action ) {
			self::wpinv_ajax_headers();
			$action = sanitize_text_field( $action );
			do_action( 'wpinv_ajax_' . $action );
			wp_die();
		}

    }

    /**
	 * Hook in ajax methods.
	 */
    public static function add_ajax_events() {

        // array( 'event' => is_frontend )
        $ajax_events = array(
            'add_note'                    => false,
            'delete_note'                 => false,
            'get_states_field'            => true,
            'get_aui_states_field'        => true,
            'checkout'                    => false,
            'payment_form'                => true,
            'get_payment_form'            => true,
            'get_payment_form_states_field' => true,
            'get_invoicing_items'         => false,
            'get_invoice_items'           => false,
            'add_invoice_items'           => false,
            'edit_invoice_item'           => false,
            'get_billing_details'         => false,
            'recalculate_invoice_totals'  => false,
            'check_new_user_email'        => false,
            'run_tool'                    => false,
            'buy_items'                   => true,
            'payment_form_refresh_prices' => true,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            add_action( 'wp_ajax_getpaid_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
                add_action( 'wp_ajax_nopriv_getpaid_' . $ajax_event, array( __CLASS__, $ajax_event ) );
                add_action( 'wpinv_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }
    
    public static function add_note() {
        check_ajax_referer( 'add-invoice-note', '_nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        $post_id   = absint( $_POST['post_id'] );
        $note      = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );
        $note_type = sanitize_text_field( $_POST['note_type'] );

        $is_customer_note = $note_type == 'customer' ? 1 : 0;

        if ( $post_id > 0 ) {
            $note_id = wpinv_insert_payment_note( $post_id, $note, $is_customer_note );

            if ( $note_id > 0 && !is_wp_error( $note_id ) ) {
                wpinv_get_invoice_note_line_item( $note_id );
            }
        }

        die();
    }

    public static function delete_note() {
        check_ajax_referer( 'delete-invoice-note', '_nonce' );

        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        $note_id = (int)$_POST['note_id'];

        if ( $note_id > 0 ) {
            wp_delete_comment( $note_id, true );
        }

        die();
    }
    
    public static function get_states_field() {
        echo wpinv_get_states_field();
        
        die();
    }
    
    public static function checkout() {
        if ( ! defined( 'WPINV_CHECKOUT' ) ) {
            define( 'WPINV_CHECKOUT', true );
        }

        wpinv_process_checkout();
        die(0);
    }

    /**
     * Retrieves a given user's billing address.
     */
    public static function get_billing_details() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        // Can the user manage the plugin?
        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        // Do we have a user id?
        $user_id = $_GET['user_id'];

        if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
            die(-1);
        }

        // Fetch the billing details.
        $billing_details    = wpinv_get_user_address( $user_id );
        $billing_details    = apply_filters( 'wpinv_ajax_billing_details', $billing_details, $user_id );

        // unset the user id and email.
        $to_ignore = array( 'user_id', 'email' );

        foreach ( $to_ignore as $key ) {
            if ( isset( $billing_details[ $key ] ) ) {
                unset( $billing_details[ $key ] );
            }
        }

        wp_send_json_success( $billing_details );

    }

    /**
     * Checks if a new users email is valid.
     */
    public static function check_new_user_email() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        // Can the user manage the plugin?
        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        // We need an email address.
        if ( empty( $_GET['email'] ) ) {
            _e( "Provide the new user's email address", 'invoicing' );
            exit;
        }

        // Ensure the email is valid.
        $email = sanitize_text_field( $_GET['email'] );
        if ( ! is_email( $email ) ) {
            _e( 'Invalid email address', 'invoicing' );
            exit;
        }

        // And it does not exist.
        if ( email_exists( $email ) ) {
            _e( 'A user with this email address already exists', 'invoicing' );
            exit;
        }

        wp_send_json_success( true );
    }
    
    public static function run_tool() {
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $tool = sanitize_text_field( $_POST['tool'] );
        
        do_action( 'wpinv_run_tool' );
        
        if ( !empty( $tool ) ) {
            do_action( 'wpinv_tool_' . $tool );
        }
    }

    /**
     * Retrieves the markup for a payment form.
     */
    public static function get_payment_form() {

        // Check nonce.
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'getpaid_ajax_form' ) ) {
            _e( 'Error: Reload the page and try again.', 'invoicing' );
            exit;
        }

        // Is the request set up correctly?
		if ( empty( $_GET['form'] ) && empty( $_GET['item'] ) ) {
			echo aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'No payment form or item provided', 'invoicing' ),
				)
            );
            exit;
        }

        // Payment form or button?
		if ( ! empty( $_GET['form'] ) ) {
            echo getpaid_display_payment_form( $_GET['form'] );
		} else if( $_GET['invoice'] ) {
		    echo getpaid_display_invoice_payment_form( $_GET['invoice'] );
        } else {
			$items = getpaid_convert_items_to_array( $_GET['item'] );
		    echo getpaid_display_item_payment_form( $items );
        }
        
        exit;

    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function payment_form() {
        global $invoicing, $wpi_checkout_id, $cart_total;

        // Check nonce.
        check_ajax_referer( 'getpaid_form_nonce' );

        // ... form fields...
        if ( empty( $_POST['getpaid_payment_form_submission'] ) ) {
            _e( 'Error: Reload the page and try again.', 'invoicing' );
            exit;
        }

        // Load the submission.
        $submission = new GetPaid_Payment_Form_Submission();

        // Do we have an error?
        if ( ! empty( $submission->last_error ) ) {
            echo $submission->last_error;
            exit;
        }

        // We need a billing email.
        if ( ! $submission->has_billing_email() || ! is_email( $submission->get_billing_email() ) ) {
            wp_send_json_error( __( 'Provide a valid billing email.', 'invoicing' ) );
        }

        // Prepare items.
        $items            = $submission->get_items();
        $prepared_items   = array();

        if ( ! empty( $items ) ) {

            foreach( $items as $item_id => $item ) {

                if ( $item->can_purchase() ) {
                    $prepared_items[] = array(
                        'id'           => $item_id,
                        'item_price'   => $item->get_price(),
                        'custom_price' => $item->get_price(),
                        'name'         => $item->get_name(),
                        'quantity'     => $item->get_quantity(),
                    );
                }

            }

        }

        if ( empty( $prepared_items ) ) {
            wp_send_json_error( __( 'You have not selected any items.', 'invoicing' ) );
        }

        if ( $submission->has_recurring && 1 != count( $prepared_items ) ) {
            wp_send_json_error( __( 'Recurring items should be bought individually.', 'invoicing' ) );
        }

        // Prepare the submission details.
        $prepared = array(
            'billing_email'                    => sanitize_email( $submission->get_billing_email() ),
            __( 'Billing Email', 'invoicing' ) => sanitize_email( $submission->get_billing_email() ),
            __( 'Form Id', 'invoicing' )       => absint( $submission->payment_form->get_id() ),
        );

        // Address fields.
        $address_fields = array();

        // Add discount code.
        if ( $submission->has_discount_code() ) {
            $address_fields['discount'] = array( $submission->get_discount_code() );
        }

        // Are all required fields provided?
        $data = $submission->get_data();

        foreach ( $submission->payment_form->get_elements() as $field ) {

            if ( ! empty( $field['premade'] ) ) {
                continue;
            }

            if ( ! $submission->is_required_field_set( $field ) ) {
                wp_send_json_error( __( 'Fill all required fields.', 'invoicing' ) );
            }

            if ( $field['type'] == 'address' ) {

                foreach ( $field['fields'] as $address_field ) {

                    if ( empty( $address_field['visible'] ) ) {
                        continue;
                    }

                    if ( ! empty( $address_field['required'] ) && empty( $data[ $address_field['name'] ] ) ) {
                        wp_send_json_error( __( 'Some required fields have not been filled.', 'invoicing' ) );
                    }

                    if ( isset( $data[ $address_field['name'] ] ) ) {
                        $label = str_replace( 'wpinv_', '', $address_field['name'] );
                        $address_fields[ $label ] = wpinv_clean( $data[ $address_field['name'] ] );
                    }

                }

            } else if ( isset( $data[ $field['id'] ] ) ) {
                $label = $field['id'];

                if ( isset( $field['label'] ) ) {
                    $label = $field['label'];
                }

                $prepared[ wpinv_clean( $label ) ] = wpinv_clean( $data[ $field['id'] ] );
            }

        }

        // (Maybe) create the user.
        $user = get_user_by( 'email', $prepared['billing_email'] );

        if ( empty( $user ) ) {
            $user = wpinv_create_user( $prepared['billing_email'] );
        }

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( $user->get_error_message() );
        }

        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', $user );
        }

        // Create the invoice.
        if ( ! $submission->has_invoice() ) {

            $invoice = wpinv_insert_invoice(
                array(
                    'status'        => 'wpi-pending',
                    'created_via'   => 'payment_form',
                    'user_id'       => $user->ID,
                    'cart_details'  => $prepared_items,
                    'user_info'     => $address_fields,
                ),
                true
            );

        } else {

            $invoice = $submission->get_invoice();

            if ( $invoice->is_paid() ) {
                wp_send_json_error( __( 'This invoice has already been paid for.', 'invoicing' ) );
            }

            $invoice = wpinv_update_invoice(
                array(
                    'ID'            => $submission->get_invoice()->ID,
                    'status'        => 'wpi-pending',
                    'cart_details'  => $prepared_items,
                    'user_info'     => $address_fields,
                ),
                true
            );

        }

        if ( is_wp_error( $invoice ) ) {
            wp_send_json_error( $invoice->get_error_message() );
        }

        if ( empty( $invoice ) ) {
            wp_send_json_error( __( 'Could not create your invoice.', 'invoicing' ) );
        }

        unset( $prepared['billing_email'] );
        update_post_meta( $invoice->ID, 'payment_form_data', $prepared );

        $wpi_checkout_id = $invoice->ID;
        $cart_total = wpinv_price(
            wpinv_format_amount(
                wpinv_get_cart_total( $invoice->get_cart_details(), NULL, $invoice ) ),
                $invoice->get_currency()
        );

        $data                   = array();
        $data['invoice_id']     = $invoice->ID;
        $data['cart_discounts'] = $invoice->get_discounts( true );

        wpinv_set_checkout_session( $data );
        add_filter( 'wp_redirect', array( $invoicing->form_elements, 'send_redirect_response' ) );
        add_action( 'wpinv_pre_send_back_to_checkout', array( $invoicing->form_elements, 'checkout_error' ) );
        
        if ( ! defined( 'WPINV_CHECKOUT' ) ) {
            define( 'WPINV_CHECKOUT', true );
        }

        wpinv_process_checkout();

        $invoicing->form_elements->checkout_error();

        exit;
    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function get_payment_form_states_field() {
        global $invoicing;

        if ( empty( $_GET['country'] ) || empty( $_GET['form'] ) ) {
            exit;
        }

        $elements = $invoicing->form_elements->get_form_elements( $_GET['form'] );

        if ( empty( $elements ) ) {
            exit;
        }

        $address_fields = array();
        foreach ( $elements as $element ) {
            if ( 'address' === $element['type'] ) {
                $address_fields = $element;
                break;
            }
        }

        if ( empty( $address_fields ) ) {
            exit;
        }

        foreach( $address_fields['fields'] as $address_field ) {

            if ( 'wpinv_state' == $address_field['name'] ) {

                $label = $address_field['label'];

                if ( ! empty( $address_field['required'] ) ) {
                    $label .= "<span class='text-danger'> *</span>";
                }

                $states = wpinv_get_country_states( $_GET['country'] );

                if ( ! empty( $states ) ) {

                    $html = aui()->select(
                            array(
                                'options'          => $states,
                                'name'             => esc_attr( $address_field['name'] ),
                                'id'               => esc_attr( $address_field['name'] ),
                                'placeholder'      => esc_attr( $address_field['placeholder'] ),
                                'required'         => (bool) $address_field['required'],
                                'no_wrap'          => true,
                                'label'            => wp_kses_post( $label ),
                                'select2'          => false,
                            )
                        );

                } else {

                    $html = aui()->input(
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

                wp_send_json_success( str_replace( 'sr-only', '', $html ) );
                exit;

            }

        }
    
        exit;
    }

    /**
     * Recalculates invoice totals.
     */
    public static function recalculate_invoice_totals() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need an invoice.
        if ( empty( $_POST['post_id'] ) ) {
            exit;
        }

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( trim( $_POST['post_id'] ) );

        // Ensure it exists.
        if ( ! $invoice->get_id() ) {
            exit;
        }

        // Maybe set the country, state, currency.
        foreach ( array( 'country', 'state', 'currency' ) as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $method = "set_$key";
                $invoice->$method( $_POST[ $key ] );
            }
        }

        // Maybe disable taxes.
        $invoice->set_disable_taxes( ! empty( $_POST['taxes'] ) );

        // Recalculate totals.
        $invoice->recalculate_total();

        $totals = array(
            'subtotal' => wpinv_price( wpinv_format_amount( $invoice->get_subtotal() ) ),
            'discount' => wpinv_price( wpinv_format_amount( $invoice->get_total_discount() ) ),
            'tax'      => wpinv_price( wpinv_format_amount( $invoice->get_total_tax() ) ),
            'total'    => wpinv_price( wpinv_format_amount( $invoice->get_total() ) ),
        );

        $totals = apply_filters( 'getpaid_invoice_totals', $totals, $invoice );

        wp_send_json_success( compact( 'totals' ) );
    }

    /**
     * Get items belonging to a given invoice.
     */
    public static function get_invoice_items() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need an invoice and items.
        if ( empty( $_POST['post_id'] ) ) {
            exit;
        }

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( trim( $_POST['post_id'] ) );

        // Ensure it exists.
        if ( ! $invoice->get_id() ) {
            exit;
        }

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax();
        }

        wp_send_json_success( compact( 'items' ) );
    }

    /**
     * Edits an invoice item.
     */
    public static function edit_invoice_item() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need an invoice and item details.
        if ( empty( $_POST['post_id'] ) || empty( $_POST['data'] ) ) {
            exit;
        }

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( trim( $_POST['post_id'] ) );

        // Ensure it exists and its not been paid for.
        if ( ! $invoice->get_id() || $invoice->is_paid() || $invoice->is_refunded() ) {
            exit;
        }

        // Format the data.
        $data = wp_list_pluck( $_POST['data'], 'value', 'field' );

        // Ensure that we have an item id.
        if ( empty( $data['id'] ) ) {
            exit;
        }

        // Abort if the invoice does not have the specified item.
        $item = $invoice->get_item( (int) $data['id'] );

        if ( empty( $item ) ) {
            exit;
        }

        // Update the item.
        $item->set_price( $data['price'] );
        $item->set_name( $data['name'] );
        $item->set_description( $data['description'] );
        $item->set_quantity( $data['quantity'] );

        // Add it to the invoice.
        $invoice->add_item( $item );

        // Update totals.
        $invoice->recalculate_total();

        // Save the invoice.
        $invoice->save();

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax();
        }

        wp_send_json_success( compact( 'items' ) );
    }
    /**
     * Adds a items to an invoice.
     */
    public static function add_invoice_items() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need an invoice and items.
        if ( empty( $_POST['post_id'] ) || empty( $_POST['items'] ) ) {
            exit;
        }

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( trim( $_POST['post_id'] ) );
        $alert   = false;

        // Ensure it exists and its not been paid for.
        if ( ! $invoice->get_id() || $invoice->is_paid() || $invoice->is_refunded() ) {
            exit;
        }

        // Add the items.
        foreach ( $_POST['items'] as $data ) {

            $item = new GetPaid_Form_Item( $data[ 'id' ] );

            if ( is_numeric( $data[ 'qty' ] ) && (int) $data[ 'qty' ] > 0 ) {
                $item->set_quantity( $data[ 'qty' ] );
            }

            if ( $item->get_id() > 0 ) {
                if ( ! $invoice->add_item( $item ) ) {
                    $alert = __( 'An invoice can only contain one recurring item', 'invoicing' );
                }
            }

        }

        // Save the invoice.
        $invoice->recalculate_total();
        $invoice->save();

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax();
        }

        wp_send_json_success( compact( 'items', 'alert' ) );
    }

    /**
     * Retrieves items that should be added to an invoice.
     */
    public static function get_invoicing_items() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need a search term.
        if ( empty( $_GET['search'] ) ) {
            wp_send_json_success( array() );
        }

        // Retrieve items.
        $item_args = array(
            'post_type'      => 'wpi_item',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish' ),
            's'              => trim( $_GET['search'] ),
            'meta_query'     => array(
                array(
                    'key'       => '_wpinv_type',
                    'compare'   => '!=',
                    'value'     => 'package'
                )
            )
        );

        $items = get_posts( apply_filters( 'getpaid_ajax_invoice_items_query_args', $item_args ) );
        $data  = array();

        foreach ( $items as $item ) {
            $item      = new GetPaid_Form_Item( $item );
            $data[] = array(
                'id'   => $item->get_id(),
                'text' => $item->get_name()
            );
        }

        wp_send_json_success( $data );

    }

    /**
     * Retrieves the states field for AUI forms.
     */
    public static function get_aui_states_field() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        // We need a country.
        if ( empty( $_GET['country'] ) ) {
            exit;
        }

        $states = wpinv_get_country_states( trim( $_GET['country'] ) );
        $state  = isset( $_GET['state'] ) ? trim( $_GET['state'] ) : wpinv_get_default_state();

        if ( empty( $states ) ) {

            $html = aui()->input(
                array(
                    'type'        => 'text',
                    'id'          => 'wpinv_state',
                    'name'        => 'wpinv_state',
                    'label'       => __( 'State', 'invoicing' ),
                    'label_type'  => 'vertical',
                    'placeholder' => 'Liège',
                    'class'       => 'form-control-sm',
                    'value'       => $state,
                )
            );

        } else {

            $html = aui()->select(
                array(
                    'id'          => 'wpinv_state',
                    'name'        => 'wpinv_state',
                    'label'       => __( 'State', 'invoicing' ),
                    'label_type'  => 'vertical',
                    'placeholder' => __( 'Select a state', 'invoicing' ),
                    'class'       => 'form-control-sm',
                    'value'       => $state,
                    'options'     => $states,
                    'data-allow-clear' => 'false',
                    'select2'          => true,
                )
            );

        }

        wp_send_json_success(
            array(
                'html'   => $html,
                'select' => ! empty ( $states )
            )
        );

    }

    /**
     * Refresh prices.
     *
     * @since 1.0.19
     */
    public static function payment_form_refresh_prices() {

        // Check nonce.
        check_ajax_referer( 'getpaid_form_nonce' );

        // ... form fields...
        if ( empty( $_POST['getpaid_payment_form_submission'] ) ) {
            _e( 'Error: Reload the page and try again.', 'invoicing' );
            exit;
        }

        // Load the submission.
        $submission = new GetPaid_Payment_Form_Submission();

        // Do we have an error?
        if ( ! empty( $submission->last_error ) ) {
            echo $submission->last_error;
            exit;
        }

        // Prepare the result.
        $result = array(

            'submission_id' => $submission->id,
            'has_recurring' => $submission->has_recurring,
            'is_free'       => $submission->get_payment_details(),

            'totals'        => array(
                'subtotal'  => wpinv_price( wpinv_format_amount( $submission->subtotal_amount ), $submission->get_currency() ),
                'discount'  => wpinv_price( wpinv_format_amount( $submission->get_total_discount() ), $submission->get_currency() ),
                'fees'      => wpinv_price( wpinv_format_amount( $submission->get_total_fees() ), $submission->get_currency() ),
                'tax'       => wpinv_price( wpinv_format_amount( $submission->get_total_tax() ), $submission->get_currency() ),
                'total'     => wpinv_price( wpinv_format_amount( $submission->get_total() ), $submission->get_currency() ),
            ),

            'texts'         => array(
                '.getpaid-checkout-total-payable' => wpinv_price( wpinv_format_amount( $submission->get_total() ), $submission->get_currency() ),
            )

        );

        // Add items.
        $items = $submission->get_items();
        if ( ! empty( $items ) ) {
            $result['items'] = array();

            foreach( $items as $item_id => $item ) {
                $result['items']["$item_id"] = wpinv_price( wpinv_format_amount( $item->get_price() * $item->get_quantity() ) );
            }
        }

        // Add invoice.
        if ( $submission->has_invoice() ) {
            $result['invoice'] = $submission->get_invoice()->ID;
        }

        // Add discount code.
        if ( $submission->has_discount_code() ) {
            $result['discount_code'] = $submission->get_discount_code();
        }

        // Filter the result.
        $result = apply_filters( 'getpaid_payment_form_ajax_refresh_prices', $result, $submission );

        wp_send_json_success( $result );
    }

    /**
     * Lets users buy items via ajax.
     *
     * @since 1.0.0
     */
    public static function buy_items() {
        $user_id = get_current_user_id();

        if ( empty( $user_id ) ) { // If not logged in then lets redirect to the login page
            wp_send_json( array(
                'success' => wp_login_url( wp_get_referer() )
            ) );
        } else {
            // Only check nonce if logged in as it could be cached when logged out.
            if ( ! isset( $_POST['wpinv_buy_nonce'] ) || ! wp_verify_nonce( $_POST['wpinv_buy_nonce'], 'wpinv_buy_items' ) ) {
                wp_send_json( array(
                    'error' => __( 'Security checks failed.', 'invoicing' )
                ) );
                wp_die();
            }

            // allow to set a custom price through post_id
            $items = $_POST['items'];
            $related_post_id = isset( $_POST['post_id'] ) ? (int)$_POST['post_id'] : 0;
            $custom_item_price = $related_post_id ? abs( get_post_meta( $related_post_id, '_wpi_custom_price', true ) ) : 0;

            $cart_items = array();
            if ( $items ) {
                $items = explode( ',', $items );

                foreach( $items as $item ) {
                    $item_id = $item;
                    $quantity = 1;

                    if ( strpos( $item, '|' ) !== false ) {
                        $item_parts = explode( '|', $item );
                        $item_id = $item_parts[0];
                        $quantity = $item_parts[1];
                    }

                    if ( $item_id && $quantity ) {
                        $cart_items_arr = array(
                            'id'            => (int)$item_id,
                            'quantity'      => (int)$quantity
                        );

                        // If there is a related post id then add it to meta
                        if ( $related_post_id ) {
                            $cart_items_arr['meta'] = array(
                                'post_id'   => $related_post_id
                            );
                        }

                        // If there is a custom price then set it.
                        if ( $custom_item_price ) {
                            $cart_items_arr['custom_price'] = $custom_item_price;
                        }

                        $cart_items[] = $cart_items_arr;
                    }
                }
            }

            /**
             * Filter the wpinv_buy shortcode cart items on the fly.
             *
             * @param array $cart_items The cart items array.
             * @param int $related_post_id The related post id if any.
             * @since 1.0.0
             */
            $cart_items = apply_filters( 'wpinv_buy_cart_items', $cart_items, $related_post_id );

            // Make sure its not in the cart already, if it is then redirect to checkout.
            $cart_invoice = wpinv_get_invoice_cart();

            if ( isset( $cart_invoice->items ) && !empty( $cart_invoice->items ) && !empty( $cart_items ) && serialize( $cart_invoice->items ) == serialize( $cart_items ) ) {
                wp_send_json( array(
                    'success' =>  $cart_invoice->get_checkout_payment_url()
                ) );
                wp_die();
            }

            // Check if user has invoice with same items waiting to be paid.
            $user_invoices = wpinv_get_users_invoices( $user_id , 10 , false , 'wpi-pending' );
            if ( !empty( $user_invoices ) ) {
                foreach( $user_invoices as $user_invoice ) {
                    $user_cart_details = array();
                    $invoice  = wpinv_get_invoice( $user_invoice->ID );
                    $cart_details = $invoice->get_cart_details();

                    if ( !empty( $cart_details ) ) {
                        foreach ( $cart_details as $invoice_item ) {
                            $ii_arr = array();
                            $ii_arr['id'] = (int)$invoice_item['id'];
                            $ii_arr['quantity'] = (int)$invoice_item['quantity'];

                            if (isset( $invoice_item['meta'] ) && !empty( $invoice_item['meta'] ) ) {
                                $ii_arr['meta'] = $invoice_item['meta'];
                            }

                            if ( isset( $invoice_item['custom_price'] ) && !empty( $invoice_item['custom_price'] ) ) {
                                $ii_arr['custom_price'] = $invoice_item['custom_price'];
                            }

                            $user_cart_details[] = $ii_arr;
                        }
                    }

                    if ( !empty( $user_cart_details ) && serialize( $cart_items ) == serialize( $user_cart_details ) ) {
                        wp_send_json( array(
                            'success' =>  $invoice->get_checkout_payment_url()
                        ) );
                        wp_die();
                    }
                }
            }

            // Create invoice and send user to checkout
            if ( !empty( $cart_items ) ) {
                $invoice_data = array(
                    'status'        =>  'wpi-pending',
                    'created_via'   =>  'wpi',
                    'user_id'       =>  $user_id,
                    'cart_details'  =>  $cart_items,
                );

                $invoice = wpinv_insert_invoice( $invoice_data, true );

                if ( !empty( $invoice ) && isset( $invoice->ID ) ) {
                    wp_send_json( array(
                        'success' =>  $invoice->get_checkout_payment_url()
                    ) );
                } else {
                    wp_send_json( array(
                        'error' => __( 'Invoice failed to create', 'invoicing' )
                    ) );
                }
            } else {
                wp_send_json( array(
                    'error' => __( 'Items not valid.', 'invoicing' )
                ) );
            }
        }

        wp_die();
    }
}

WPInv_Ajax::init();