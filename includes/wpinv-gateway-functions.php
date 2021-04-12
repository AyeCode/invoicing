<?php
/**
 * Contains gateway functions.
 *
 */
defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of payment gateways.
 * 
 * @return array
 */
function wpinv_get_payment_gateways() {
    return apply_filters( 'wpinv_payment_gateways', array() );
}

function wpinv_payment_gateway_titles( $all_gateways ) {
    global $wpinv_options;

    $gateways = array();
    foreach ( $all_gateways as $key => $gateway ) {
        if ( !empty( $wpinv_options[$key . '_title'] ) ) {
            $all_gateways[$key]['checkout_label'] = __( $wpinv_options[$key . '_title'], 'invoicing' );
        }

        $gateways[$key] = isset( $wpinv_options[$key . '_ordering'] ) ? $wpinv_options[$key . '_ordering'] : ( isset( $gateway['ordering'] ) ? $gateway['ordering'] : '' );
    }

    asort( $gateways );

    foreach ( $gateways as $gateway => $key ) {
        $gateways[$gateway] = $all_gateways[$gateway];
    }

    return $gateways;
}
add_filter( 'wpinv_payment_gateways', 'wpinv_payment_gateway_titles', 1000, 1 );

function wpinv_get_enabled_payment_gateways( $sort = false ) {
    $gateways = wpinv_get_payment_gateways();
    $enabled  = wpinv_get_option( 'gateways', array( 'manual' => 1 ) );

    $gateway_list = array();

    foreach ( $gateways as $key => $gateway ) {
        if ( isset( $enabled[ $key ] ) && $enabled[ $key ] == 1 ) {
            $gateway_list[ $key ] = $gateway;
        }
    }

    if ( true === $sort ) {
        uasort( $gateway_list, 'wpinv_sort_gateway_order' );
        
        // Reorder our gateways so the default is first
        $default_gateway_id = wpinv_get_default_gateway();

        if ( wpinv_is_gateway_active( $default_gateway_id ) ) {
            $default_gateway    = array( $default_gateway_id => $gateway_list[ $default_gateway_id ] );
            unset( $gateway_list[ $default_gateway_id ] );

            $gateway_list = array_merge( $default_gateway, $gateway_list );
        }
    }

    return apply_filters( 'wpinv_enabled_payment_gateways', $gateway_list );
}

function wpinv_sort_gateway_order( $a, $b ) {
    return $a['ordering'] - $b['ordering'];
}

function wpinv_is_gateway_active( $gateway ) {
    $gateways = wpinv_get_enabled_payment_gateways();

    $ret = is_array($gateways) && $gateway ?  array_key_exists( $gateway, $gateways ) : false;

    return apply_filters( 'wpinv_is_gateway_active', $ret, $gateway, $gateways );
}

function wpinv_get_default_gateway() {
    $default = wpinv_get_option( 'default_gateway', 'paypal' );

    if ( !wpinv_is_gateway_active( $default ) ) {
        $gateways = wpinv_get_enabled_payment_gateways();
        $gateways = array_keys( $gateways );
        $default  = reset( $gateways );
    }

    return apply_filters( 'wpinv_default_gateway', $default );
}

/**
 * Returns a gateway's name.
 *
 * @param string $gateway The gateway to key.
 * @return string
 */
function wpinv_get_gateway_admin_label( $gateway ) {

    if ( empty( $gateway ) || 'none' == $gateway ) {
        return esc_html__( 'No Gateway', 'invoicing' );
    }

    $gateways = wpinv_get_payment_gateways();
    $label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['admin_label'] : $gateway;
    $gateway  = apply_filters( 'wpinv_gateway_admin_label', $label, $gateway );

    return wpinv_clean( $gateway );
}

/**
 * Retrieves the gateway description.
 * 
 * @param string $gateway
 */
function wpinv_get_gateway_description( $gateway ) {
    global $wpinv_options;

    $description = ! empty( $wpinv_options[$gateway . '_desc'] ) ? $wpinv_options[$gateway . '_desc'] : '';

    return apply_filters( 'wpinv_gateway_description', $description, $gateway );
}

function wpinv_get_gateway_button_label( $gateway ) {
    return apply_filters( 'wpinv_gateway_' . $gateway . '_button_label', '' );
}

function wpinv_get_gateway_checkout_label( $gateway ) {
    $gateways = wpinv_get_payment_gateways();
    $label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['checkout_label'] : $gateway;

    if ( $gateway == 'none' ) {
        $label = __( 'None', 'invoicing' );
    }

    return apply_filters( 'wpinv_gateway_checkout_label', ucfirst( $label ), $gateway );
}

function wpinv_settings_sections_gateways( $settings ) {
    $gateways = wpinv_get_payment_gateways();
    
    if (!empty($gateways)) {
        foreach  ($gateways as $key => $gateway) {
            $settings[$key] = $gateway['admin_label'];
        }
    }
    
    return $settings;    
}
add_filter( 'wpinv_settings_sections_gateways', 'wpinv_settings_sections_gateways', 10, 1 );

/**
 * Adds GateWay settings.
 */
function wpinv_settings_gateways( $settings ) {

    // Loop through each gateway.
    foreach  ( wpinv_get_payment_gateways() as $key => $gateway ) {

        $gateway_settings = array(

            // Header.
            "{$key}_header" => array(

                'id'     => "{$key}_gateway_header",
                'name'   => '<h3>' . wp_sprintf( __( '%s Settings', 'invoicing' ), $gateway['admin_label'] ) . '</h3>',
                'custom' => $key,
                'type'   => 'gateway_header',

            ),

            // Activate/Deactivate a gateway.
            "{$key}_active" => array(
                'id'   => $key . '_active',
                'name' => __( 'Activate', 'invoicing' ),
                'desc' => wp_sprintf( __( 'Enable %s', 'invoicing' ), $gateway['admin_label'] ),
                'type' => 'checkbox',
            ),

            // Activate/Deactivate sandbox.
            "{$key}_sandbox" => array(
                'id'   => $key . '_sandbox',
                'name' => __( 'Sandbox', 'invoicing' ),
                'desc' => __( 'Enable sandbox to test payments', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => '1',
            ),

            // Checkout title.
            "{$key}_title" => array(
                'id'   => $key . '_title',
                'name' => __( 'Checkout Title', 'invoicing' ),
                'std'  => isset( $gateway['checkout_label'] ) ? $gateway['checkout_label'] : '',
                'type' => 'text',
            ),

            // Checkout description.
            "{$key}_desc" => array(
                'id'   => $key . '_desc',
                'name' => __( 'Checkout Description', 'invoicing' ),
                'std'  => apply_filters( "getpaid_default_{$key}_checkout_description", '' ),
                'type' => 'text',
            ),

            // Checkout order.
            "{$key}_ordering" => array(
                'id'   => $key . '_ordering',
                'name' => __( 'Priority', 'invoicing' ),
                'std'  => apply_filters( "getpaid_default_{$key}_checkout_description", '' ),
                'type' => 'number',
                'step' => '1',
                'min'  => '0',
                'max'  => '100000',
                'std'  => isset( $gateway['ordering'] ) ? $gateway['ordering'] : '10',
            ),

        );

        // Maybe remove the sandbox.
        if ( ! apply_filters( "wpinv_{$key}_supports_sandbox", false ) ) {
            unset( $gateway_settings["{$key}_sandbox"] );
        }
  
        $gateway_settings = apply_filters( 'wpinv_gateway_settings', $gateway_settings, $key, $gateway );
        $gateway_settings = apply_filters( 'wpinv_gateway_settings_' . $key, $gateway_settings, $gateway );
        
        $settings[$key] = $gateway_settings;
    }

    return $settings;

}
add_filter( 'wpinv_settings_gateways', 'wpinv_settings_gateways', 10, 1 );

function wpinv_gateway_header_callback( $args ) {
    echo '<input type="hidden" id="wpinv_settings[save_gateway]" name="wpinv_settings[save_gateway]" value="' . esc_attr( $args['custom'] ) . '" />';
}

function wpinv_get_gateway_supports( $gateway ) {
    $gateways = wpinv_get_enabled_payment_gateways();
    $supports = isset( $gateways[ $gateway ]['supports'] ) ? $gateways[ $gateway ]['supports'] : array();
    return apply_filters( 'wpinv_gateway_supports', $supports, $gateway );
}

function wpinv_get_chosen_gateway( $invoice_id = 0 ) {
	$gateways = array_keys( wpinv_get_enabled_payment_gateways() );

    $chosen = false;
    if ( $invoice_id > 0 && $invoice = wpinv_get_invoice( $invoice_id ) ) {
        $chosen = $invoice->get_gateway();
    }

	$chosen   = isset( $_REQUEST['payment-mode'] ) ? sanitize_text_field( $_REQUEST['payment-mode'] ) : $chosen;

	if ( false !== $chosen ) {
		$chosen = preg_replace('/[^a-zA-Z0-9-_]+/', '', $chosen );
	}

	if ( ! empty ( $chosen ) ) {
		$enabled_gateway = urldecode( $chosen );
	} else if (  !empty( $invoice ) && (float)$invoice->get_subtotal() <= 0 ) {
		$enabled_gateway = 'manual';
	} else {
		$enabled_gateway = wpinv_get_default_gateway();
	}
    
    if ( !wpinv_is_gateway_active( $enabled_gateway ) && !empty( $gateways ) ) {
        if(wpinv_is_gateway_active( wpinv_get_default_gateway()) ){
            $enabled_gateway = wpinv_get_default_gateway();
        }else{
            $enabled_gateway = $gateways[0];
        }

    }

	return apply_filters( 'wpinv_chosen_gateway', $enabled_gateway );
}

function wpinv_record_gateway_error( $title = '', $message = '' ) {
    return wpinv_error_log( $message, $title );
}

function wpinv_count_sales_by_gateway( $gateway_id = 'paypal', $status = 'publish' ) {
	$ret  = 0;
	$args = array(
		'meta_key'    => '_wpinv_gateway',
		'meta_value'  => $gateway_id,
		'nopaging'    => true,
		'post_type'   => 'wpi_invoice',
		'post_status' => $status,
		'fields'      => 'ids'
	);

	$payments = new WP_Query( $args );

	if( $payments )
		$ret = $payments->post_count;
	return $ret;
}

function wpinv_settings_update_gateways( $input ) {
    global $wpinv_options;
    
    if ( !empty( $input['save_gateway'] ) ) {
        $gateways = wpinv_get_option( 'gateways', array( 'manual' => 1 ) );
        $gateways = !empty($gateways) ? $gateways : array();
        $gateway = $input['save_gateway'];
        
        if ( !empty( $input[$gateway . '_active'] ) ) {
            $gateways[$gateway] = 1;
        } else {
            if ( isset( $gateways[$gateway] ) ) {
                unset( $gateways[$gateway] );
            }
        }
        
        $input['gateways'] = $gateways;
    }
    
    if ( !empty( $input['default_gateway'] ) ) {
        $gateways = wpinv_get_payment_gateways();
        
        foreach ( $gateways as $key => $gateway ) {
            $active   = 0;
            if ( !empty( $input['gateways'] ) && !empty( $input['gateways'][$key] ) ) {
                $active = 1;
            }
            
            $input[$key . '_active'] = $active;
            
            if ( empty( $wpinv_options[$key . '_title'] ) ) {
                $input[$key . '_title'] = $gateway['checkout_label'];
            }
            
            if ( !isset( $wpinv_options[$key . '_ordering'] ) && isset( $gateway['ordering'] ) ) {
                $input[$key . '_ordering'] = $gateway['ordering'];
            }
        }
    }
    
    return $input;
}
add_filter( 'wpinv_settings_tab_gateways_sanitize', 'wpinv_settings_update_gateways', 10, 1 );

// PayPal Standard settings
function wpinv_gateway_settings_paypal( $setting ) {    
    $setting['paypal_active']['desc'] = $setting['paypal_active']['desc'] . ' ' . __( '( Supported Currencies: AUD, BRL, CAD, CZK, DKK, EUR, HKD, HUF, ILS, JPY, MYR, MXN, NOK, NZD, PHP, PLN, GBP, SGD, SEK, CHF, TWD, THB, USD )', 'invoicing' );
    $setting['paypal_desc']['std'] = __( 'Pay via PayPal: you can pay with your credit card if you don\'t have a PayPal account.', 'invoicing' );
    
    $setting['paypal_sandbox'] = array(
            'type' => 'checkbox',
            'id'   => 'paypal_sandbox',
            'name' => __( 'PayPal Sandbox', 'invoicing' ),
            'desc' => __( 'PayPal sandbox can be used to test payments.', 'invoicing' ),
            'std'  => 1
        );
        
    $setting['paypal_email'] = array(
            'type' => 'text',
            'id'   => 'paypal_email',
            'name' => __( 'PayPal Email', 'invoicing' ),
            'desc' => __( 'Please enter your PayPal account\'s email address. Ex: myaccount@paypal.com', 'invoicing' ),
            'std' => __( 'myaccount@paypal.com', 'invoicing' ),
        );
    /*
    $setting['paypal_ipn_url'] = array(
            'type' => 'text',
            'id'   => 'paypal_ipn_url',
            'name' => __( 'PayPal IPN Url', 'invoicing' ),
            'desc' => __( 'Configure Instant Payment Notifications(IPN) url at PayPal. Ex: http://yoursite.com/?wpi-ipn=paypal', 'invoicing' ),
            'size' => 'large'
        );
    */
        
    return $setting;
}
add_filter( 'wpinv_gateway_settings_paypal', 'wpinv_gateway_settings_paypal', 10, 1 );

/**
 * Displays the ipn url field.
 */
function wpinv_ipn_url_callback( $args ) {
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    
    $attrs = $args['readonly'] ? ' readonly' : '';

    $html = '<input class="regular-text" type="text" ' . $attrs . ' value="' . esc_attr( $args['std'] ) . '" name="wpinv_settings[' . $sanitize_id . ']" id="wpinv_settings[' . $sanitize_id . ']" onClick="this.select()">';
    $html .= '<label for="wpinv_settings[' . $sanitize_id . ']">'  . $args['desc'] . '</label>';

    echo $html;
}

/**
 * Checks if a gateway is in test mode.
 *
 * @param string $gateway The gateway to check for.
 *
 * @return bool
 */
function wpinv_is_test_mode( $gateway = '' ) {
    $sandbox  = empty( $gateway ) ? false : wpinv_get_option( "{$gateway}_sandbox", true );
    $supports = apply_filters( "wpinv_{$gateway}_supports_sandbox", false );
    return apply_filters( 'wpinv_is_test_mode', $sandbox && $supports, $gateway );
}

/**
 * Retrieves the ipn url.
 * 
 * @param string $gateway The gateway whose IPN url we should retrieve.
 * @param array $args extra args to add to the url.
 * 
 * @return string
 */
function wpinv_get_ipn_url( $gateway = false, $args = array() ) {
    $args = wp_parse_args(
        array(
            'wpi-listener' => 'IPN',
            'wpi-gateway'  => $gateway
        ),
        $args
    );

    return apply_filters( 'wpinv_ipn_url', add_query_arg( $args,  home_url( 'index.php' ) ), $gateway, $args );

}

/**
 * Retrieves the non-query string ipn url.
 * 
 * @param string $gateway The gateway whose IPN url we should retrieve.
 * 
 * @return string
 */
function getpaid_get_non_query_string_ipn_url( $gateway ) {
    $gateway = wpinv_sanitize_key( $gateway );
    return home_url( "getpaid-ipn/$gateway" );
}


/**
 * Retrieves request data with slashes removed slashes.
 */
function wpinv_get_post_data( $method = 'request' ) {

    if ( $method == 'post' ) {
        return wp_unslash( $_POST );
    }

    if ( $method == 'get' ) {
        return wp_unslash( $_GET );
    }

    return wp_unslash( $_REQUEST );
  
}

/**
 * Checks if a given gateway supports subscription payments.
 */
function wpinv_gateway_support_subscription( $gateway ) {
    $supports = false;

    if ( wpinv_is_gateway_active( $gateway ) ) {
        $supports = apply_filters( 'wpinv_' . $gateway . '_support_subscription', $supports );

        $supports = apply_filters( 'getapid_gateway_supports_subscription', $supports, $gateway );
    }

    return $supports;
}

/**
 * Filters payment form gateways.
 * 
 * @param array $gateways an array of gateways.
 * @param GetPaid_Payment_Form $form payment form.
 */
function wpinv_payment_gateways_on_cart( $gateways, $form ) {

    if ( $form->is_recurring() ) {

        foreach ( array_keys( $gateways ) as $gateway ) {

            if ( ! wpinv_gateway_support_subscription( $gateway ) ) {
                unset( $gateways[$gateway] );
            }

        }

    }

    return $gateways;
}
add_filter( 'getpaid_payment_form_gateways', 'wpinv_payment_gateways_on_cart', 10, 2 );
