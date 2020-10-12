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
            'payment_form'                => true,
            'get_payment_form'            => true,
            'get_payment_form_states_field' => true,
            'get_invoicing_items'         => false,
            'get_invoice_items'           => false,
            'add_invoice_items'           => false,
            'edit_invoice_item'           => false,
            'remove_invoice_item'         => false,
            'get_billing_details'         => false,
            'recalculate_invoice_totals'  => false,
            'check_new_user_email'        => false,
            'run_tool'                    => false,
            'payment_form_refresh_prices' => true,
            'ip_geolocation'              => true,
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
            getpaid_display_payment_form( $_GET['form'] );
		} else if( ! empty( $_GET['invoice'] ) ) {
		    getpaid_display_invoice_payment_form( $_GET['invoice'] );
        } else {
			$items = getpaid_convert_items_to_array( $_GET['item'] );
		    getpaid_display_item_payment_form( $items );
        }

        exit;

    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function payment_form() {

        // Check nonce.
        check_ajax_referer( 'getpaid_form_nonce' );

        // ... form fields...
        if ( empty( $_POST['getpaid_payment_form_submission'] ) ) {
            _e( 'Error: Reload the page and try again.', 'invoicing' );
            exit;
        }

        // Process the payment form.
        $checkout_class = apply_filters( 'getpaid_checkout_class', 'GetPaid_Checkout' );
        $checkout       = new $checkout_class( new GetPaid_Payment_Form_Submission() );
        $checkout->process_checkout();

        exit;
    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function get_payment_form_states_field() {

        if ( empty( $_GET['country'] ) || empty( $_GET['form'] ) ) {
            exit;
        }

        $elements = getpaid_get_payment_form_elements( $_GET['form'] );

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

        $total = wpinv_price( wpinv_format_amount( $invoice->get_total() ), $invoice->get_currency() );

        if ( $invoice->is_recurring() && $invoice->is_parent() && $invoice->get_total() != $invoice->get_recurring_total() ) {
            $recurring_total = wpinv_price( wpinv_format_amount( $invoice->get_recurring_total() ), $invoice->get_currency() );
            $total          .= '<small class="form-text text-muted">' . sprintf( __( 'Recurring Price: %s', 'invoicing' ), $recurring_total ) . '</small>';
        }

        $totals = array(
            'subtotal' => wpinv_price( wpinv_format_amount( $invoice->get_subtotal() ), $invoice->get_currency() ),
            'discount' => wpinv_price( wpinv_format_amount( $invoice->get_total_discount() ), $invoice->get_currency() ),
            'tax'      => wpinv_price( wpinv_format_amount( $invoice->get_total_tax() ), $invoice->get_currency() ),
            'total'    => $total,
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
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax(  $invoice->get_currency()  );
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
        $error = $invoice->add_item( $item );
        $alert = false;
        if ( is_wp_error( $error ) ) {
            $alert = $error->get_error_message();
        }

        // Update totals.
        $invoice->recalculate_total();

        // Save the invoice.
        $invoice->save();

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax(  $invoice->get_currency()  );
        }

        wp_send_json_success( compact( 'items', 'alert' ) );
    }

    /**
     * Deletes an invoice item.
     */
    public static function remove_invoice_item() {

        // Verify nonce.
        check_ajax_referer( 'wpinv-nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            exit;
        }

        // We need an invoice and an item.
        if ( empty( $_POST['post_id'] ) || empty( $_POST['item_id'] ) ) {
            exit;
        }

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( trim( $_POST['post_id'] ) );

        // Ensure it exists and its not been paid for.
        if ( ! $invoice->get_id() || $invoice->is_paid() || $invoice->is_refunded() ) {
            exit;
        }

        // Abort if the invoice does not have the specified item.
        $item = $invoice->get_item( (int) $_POST['item_id'] );

        if ( empty( $item ) ) {
            exit;
        }

        $invoice->remove_item( (int) $_POST['item_id'] );

        // Update totals.
        $invoice->recalculate_total();

        // Save the invoice.
        $invoice->save();

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax(  $invoice->get_currency()  );
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
                $error = $invoice->add_item( $item );

                if ( is_wp_error( $error ) ) {
                    $alert = $error->get_error_message();
                }

            }

        }

        // Save the invoice.
        $invoice->recalculate_total();
        $invoice->save();

        // Return an array of invoice items.
        $items = array();

        foreach ( $invoice->get_items() as $item_id => $item ) {
            $items[ $item_id ] = $item->prepare_data_for_invoice_edit_ajax( $invoice->get_currency() );
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


        $is_payment_form = ( ! empty( $_GET['post_id'] ) && 'wpi_payment_form' == get_post_type( $_GET['post_id'] ) );

        foreach ( $items as $item ) {
            $item      = new GetPaid_Form_Item( $item );
            $data[] = array(
                'id'        => $item->get_id(),
                'text'      => $item->get_name(),
                'form_data' => $is_payment_form ? $item->prepare_data_for_use() : '',
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
     * IP geolocation.
     *
     * @since 1.0.19
     */
    public static function ip_geolocation() {

        // Check nonce.
        check_ajax_referer( 'getpaid-ip-location' );

        // IP address.
        if ( empty( $_GET['ip'] ) || ! rest_is_ip_address( $_GET['ip'] ) ) {
            _e( 'Invalid IP Address.', 'invoicing' );
            exit;
        }

        // Retrieve location info.
        $location = getpaid_geolocate_ip_address( $_GET['ip'] );

        if ( empty( $location ) ) {
            _e( 'Unable to find geolocation for the IP Address.', 'invoicing' );
            exit;
        }

        // Sorry.
        extract( $location );

        // Prepare the address.
        $content = '';

        if ( ! empty( $location['city'] ) ) {
            $content .=  $location['city']  . ', ';
        }
        
        if ( ! empty( $location['region'] ) ) {
            $content .=  $location['region']  . ', ';
        }
        
        $content .=  $location['country'] . ' (' . $location['iso'] . ')';

        $location['address'] = $content;

        $content  = '<p>'. sprintf( __( '<b>Address:</b> %s', 'invoicing' ), $content ) . '</p>';
        $content .= '<p>'. $location['credit'] . '</p>';

        $location['content'] = $content;

        wpinv_get_template( 'geolocation.php', $location );

        exit;
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

        // Prepare the response.
        $response = new GetPaid_Payment_Form_Submission_Refresh_Prices( $submission );
        
        // Filter the response.
        $response = apply_filters( 'getpaid_payment_form_ajax_refresh_prices', $response->response, $submission );

        wp_send_json_success( $response );
    }

}

WPInv_Ajax::init();