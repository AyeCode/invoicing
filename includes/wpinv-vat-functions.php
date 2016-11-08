<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}


function wpinv_vat_standard_rate_label() {
	return __( 'Standard Rates', 'invoicing' );
}

function wpinv_vat_get_all_rate_classes() {
    $rate_classes = wpinv_vat_get_rate_classes();
    $rate_classes['_exempt'] = __( 'Exempt class (0%)', 'invoicing' );
    return apply_filters( 'wpinv_vat_get_all_rate_classes', $rate_classes );

}

function wpinv_vat_get_rate_classes( $with_desc = false ) {
    $rate_classes_option = get_option( '_wpinv_vat_rate_classes', true );
    $classes = maybe_unserialize( $rate_classes_option );
    
    if ( empty( $classes ) || !is_array( $classes ) ) {
        $classes = array();
    }

    $rate_classes = array();
    if ( !array_key_exists( '_standard', $classes ) ) {
        if ( $with_desc ) {
            $rate_classes['_standard'] = array( 'name' => wpinv_vat_standard_rate_label(), 'desc' => __( 'EU member states standard VAT rates', 'invoicing' ) );
        } else {
            $rate_classes['_standard'] = wpinv_vat_standard_rate_label();
        }
    }
    
    foreach ( $classes as $key => $class ) {
        $name = !empty( $class['name'] ) ? __( $class['name'], 'invoicing' ) : $key;
        $desc = !empty( $class['desc'] ) ? __( $class['desc'], 'invoicing' ) : '';
        
        if ( $with_desc ) {
            $rate_classes[$key] = array( 'name' => $name, 'desc' => $desc );
        } else {
            $rate_classes[$key] = $name;
        }
    }

    return $rate_classes;
}

function wpinv_vat_get_class_desc( $rate_class ) {
    $rate_classes = wpinv_vat_get_rate_classes( true );

    if ( !empty( $rate_classes ) && isset( $rate_classes[$rate_class] ) && isset( $rate_classes[$rate_class]['desc'] ) ) {
        return $rate_classes[$rate_class]['desc'];
    }
    
    return '';
}

function wpinv_vat_rate_all_classes() {
    $classes = wpinv_vat_get_rate_classes();
    $classes['_exempt'] = __( 'Exempt (0%)', 'invoicing' );
    
    return apply_filters( 'wpinv_vat_rate_all_classes', $classes );
}

function wpinv_vat_rate_types() {
    return apply_filters( 'wpinv_vat_rate_types', array(
        'standard' => 'Standard',
        'reduced' => 'Reduced',
        'superreduced' => 'Super Reduced',
        'parking' => 'Parking',
        'increased' => 'Increased'
    ));
}

function wpinv_vat_rule_types() {
    return apply_filters( 'wpinv_vat_rule_types', array(
                                'digital' => __( 'Digital Product', 'invoicing' ),
                                'physical' => __( 'Physical Product', 'invoicing' )
                            ));
}

function wpinv_vat_rates( $class ) {
    if ( $class === '_standard' ) {
        return wpinv_get_tax_rates();
    }

    $rates = wpinv_non_standard_rates();

    return array_key_exists( $class, $rates ) ? $rates[$class] : array();
}

function wpinv_non_standard_rates() {
    $option = get_option( 'wpinv_vat_rates', array());
    return is_array( $option ) ? $option : array();
}

function wpinv_get_vat_rates_settings() {
    $vat_classes = wpinv_vat_get_rate_classes();
    $vat_rates = array();
    $vat_class = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '_new';
    $current_url = remove_query_arg( 'wpi_sub' );
    
    $vat_rates['vat_rates_header'] = array(
        'id' => 'vat_rates_header',
        'name' => '<h3>' . __( 'Manage VAT Rates', 'invoicing' ) . '</h3>',
        'desc' => '',
        'type' => 'header',
        'size' => 'regular'
    );
    $vat_rates['vat_rates_class'] = array(
        'id'          => 'vat_rates_class',
        'name'        => __( 'Edit VAT Rates', 'invoicing' ),
        'desc'        => __( 'The standard rate will apply where no explicit rate is provided.', 'invoicing' ),
        'type'        => 'select',
        'options'     => array_merge( $vat_classes, array( '_new' => __( 'Add New Rate Class', 'invoicing' ) ) ),
        'chosen'      => true,
        'placeholder' => __( 'Select a VAT Rate', 'invoicing' ),
        'selected'    => $vat_class,
        'onchange'    => 'document.location.href="' . $current_url . '&wpi_sub=" + this.value;',
    );
    
    if ( $vat_class != '_standard' && $vat_class != '_new' ) {
        $vat_rates['vat_rate_delete'] = array(
            'id'   => 'vat_rate_delete',
            'type' => 'vat_rate_delete',
        );
    }
                
    if ( $vat_class == '_new' ) {
        $vat_rates['vat_rates_settings'] = array(
            'id' => 'vat_rates_settings',
            'name' => '<h3>' . __( 'Add New Rate Class', 'invoicing' ) . '</h3>',
            'type' => 'header',
        );
        $vat_rates['vat_rate_name'] = array(
            'id'   => 'vat_rate_name',
            'name' => __( 'Name', 'invoicing' ),
            'desc' => __( 'A short name for the new VAT Rate class', 'invoicing' ),
            'type' => 'text',
            'size' => 'regular',
        );
        $vat_rates['vat_rate_desc'] = array(
            'id'   => 'vat_rate_desc',
            'name' => __( 'Description', 'invoicing' ),
            'desc' => __( 'Manage VAT Rate class', 'invoicing' ),
            'type' => 'text',
            'size' => 'regular',
        );
        $vat_rates['vat_rate_add'] = array(
            'id'   => 'vat_rate_add',
            'type' => 'vat_rate_add',
        );
    } else {
        $vat_rates['vat_rates'] = array(
            'id'   => 'vat_rates',
            'name' => '<h3>' . $vat_classes[$vat_class] . '</h3>',
            'desc' => wpinv_vat_get_class_desc( $vat_class ),
            'type' => 'vat_rates',
        );
    }
    
    return $vat_rates;
}

function wpinv_vat_rate_add_callback( $args ) {
    ?>
    <p class="wpi-vat-rate-actions"><input id="wpi_vat_rate_add" type="button" value="<?php esc_attr_e( 'Add', 'invoicing' );?>" class="button button-primary" />&nbsp;&nbsp;<i style="display:none;" class="fa fa-refresh fa-spin"></i></p>
    <?php
}

function wpinv_vat_rate_delete_callback( $args ) {
    $vat_classes = wpinv_vat_get_rate_classes();
    $vat_class = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '';
    if ( isset( $vat_classes[$vat_class] ) ) {
    ?>
    <p class="wpi-vat-rate-actions"><input id="wpi_vat_rate_delete" type="button" value="<?php echo wp_sprintf( esc_attr__( 'Delete class "%s"', 'invoicing' ), $vat_classes[$vat_class] );?>" class="button button-primary" />&nbsp;&nbsp;<i style="display:none;" class="fa fa-refresh fa-spin"></i></p>
    <?php
    }
}

function wpinv_vat_rates_callback( $args ) {
    $vat_classes = wpinv_vat_get_rate_classes();
    $vat_class = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '_standard';
    
    $eu_states = wpinv_get_eu_states();
    $countries = wpinv_get_country_list();
    $vat_groups = wpinv_vat_rate_types();
    $rates = wpinv_vat_rates( $vat_class );
    ob_start();
?>
</td><tr>
    <td colspan="2" class="wpinv_vat_tdbox">
    <input type="hidden" name="wpi_vat_class" value="<?php echo $vat_class;?>" />
    <p><?php echo ( isset( $args['desc'] ) ? $args['desc'] : '' ); ?></p>
    <table id="wpinv_vat_rates" class="wp-list-table widefat fixed posts">
        <colgroup>
            <col width="50px" />
            <col width="auto" />
            <col width="auto" />
            <col width="auto" />
            <col width="auto" />
            <col width="auto" />
        </colgroup>
        <thead>
            <tr>
                <th scope="col" colspan="2" class="wpinv_vat_country_name"><?php _e( 'Country', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_vat_global" title="<?php esc_attr_e( 'Apply rate to whole country', 'invoicing' ); ?>"><?php _e( 'Country Wide', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_vat_rate"><?php _e( 'Rate %', 'invoicing' ); ?></th> 
                <th scope="col" class="wpinv_vat_name"><?php _e( 'VAT Name', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_vat_group"><?php _e( 'Tax Group', 'invoicing' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if( !empty( $eu_states ) ) { ?>
        <?php 
        foreach ( $eu_states as $state ) { 
            $country_name = isset( $countries[$state] ) ? $countries[$state] : '';
            
            // Filter the rate for each country
            $country_rate = array_filter( $rates, function( $rate ) use( $state ) { return $rate['country'] === $state; } );
            
            // If one does not exist create a default
            $country_rate = is_array( $country_rate ) && count( $country_rate ) > 0 ? reset( $country_rate ) : array();
            
            $vat_global = isset( $country_rate['global'] ) ? !empty( $country_rate['global'] ) : true;
            $vat_rate = isset( $country_rate['rate'] ) ? $country_rate['rate'] : '';
            $vat_name = !empty( $country_rate['name'] ) ? esc_attr( stripslashes( $country_rate['name'] ) ) : '';
            $vat_group = !empty( $country_rate['group'] ) ? $country_rate['group'] : ( $vat_class === '_standard' ? 'standard' : 'reduced' );
        ?>
        <tr>
            <td class="wpinv_vat_country"><?php echo $state; ?><input type="hidden" name="vat_rates[<?php echo $state; ?>][country]" value="<?php echo $state; ?>" /><input type="hidden" name="vat_rates[<?php echo $state; ?>][state]" value="" /></td>
            <td class="wpinv_vat_country_name"><?php echo $country_name; ?></td>
            <td class="wpinv_vat_global">
                <input type="checkbox" name="vat_rates[<?php echo $state;?>][global]" id="vat_rates[<?php echo $state;?>][global]" value="1" <?php checked( true, $vat_global );?> disabled="disabled" />
                <label for="tax_rates[<?php echo $state;?>][global]"><?php _e( 'Apply to whole country', 'invoicing' ); ?></label>
                <input type="hidden" name="vat_rates[<?php echo $state;?>][global]" value="1" checked="checked" />
            </td>
            <td class="wpinv_vat_rate"><input type="number" class="small-text" step="0.10" min="0.00" name="vat_rates[<?php echo $state;?>][rate]" value="<?php echo $vat_rate; ?>" /></td>
            <td class="wpinv_vat_name"><input type="text" class="regular-text" name="vat_rates[<?php echo $state;?>][name]" value="<?php echo $vat_name; ?>" /></td>
            <td class="wpinv_vat_group">
            <?php
            echo wpinv_html_select( array(
                                        'name'             => 'vat_rates[' . $state . '][group]',
                                        'selected'         => $vat_group,
                                        'id'               => 'vat_rates[' . $state . '][group]',
                                        'class'            => '',
                                        'options'          => $vat_groups,
                                        'multiple'         => false,
                                        'chosen'           => false,
                                        'show_option_all'  => false,
                                        'show_option_none' => false
                                    ) );
            ?>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td colspan="6" style="background-color:#fafafa;">
                <span><input id="wpi_vat_get_rates_group" type="button" class="button-secondary" value="<?php esc_attr_e( 'Update EU VAT Rates', 'invoicing' ); ?>" />&nbsp;&nbsp;<i style="display:none" class="fa fa-refresh fa-spin"></i></span><span id="wpinv-rates-error-wrap" class="wpinv_errors" style="display:none;"></span>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php
    $content = ob_get_clean();
    
    echo $content;
}

function wpinv_add_vat_class() {
    $response = array();
    $response['success'] = false;
    
    if ( !current_user_can( 'manage_options' ) ) {
        $response['error'] = __( 'Invalid access!', 'invoicing' );
        wp_send_json( $response );
    }
    
    $vat_class_name = !empty( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : false;
    $vat_class_desc = !empty( $_POST['desc'] ) ? sanitize_text_field( $_POST['desc'] ) : false;
    
    if ( empty( $vat_class_name ) ) {
        $response['error'] = __( 'Select the VAT rate name', 'invoicing' );
        wp_send_json( $response );
    }
    
    $vat_classes = (array)wpinv_vat_get_rate_classes();

    if ( !empty( $vat_classes ) && in_array( strtolower( $vat_class_name ), array_map( 'strtolower', array_values( $vat_classes ) ) ) ) {
        $response['error'] = wp_sprintf( __( 'A VAT Rate name "%s" already exists', 'invoicing' ), $vat_class_name );
        wp_send_json( $response );
    }
    
    $rate_class_key = normalize_whitespace( 'wpi-' . $vat_class_name );
    $rate_class_key = sanitize_key( str_replace( " ", "-", $rate_class_key ) );
    
    $vat_classes = (array)wpinv_vat_get_rate_classes( true );
    $vat_classes[$rate_class_key] = array( 'name' => $vat_class_name, 'desc' => $vat_class_desc );
    
    update_option( '_wpinv_vat_rate_classes', $vat_classes );
    
    $response['success'] = true;
    $response['redirect'] = admin_url( 'admin.php?page=wpinv-settings&tab=taxes&section=vat_rates&wpi_sub=' . $rate_class_key );
    
    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_add_vat_class', 'wpinv_add_vat_class' );
add_action( 'wp_ajax_nopriv_wpinv_add_vat_class', 'wpinv_add_vat_class' );

function wpinv_delete_vat_class() {
    $response = array();
    $response['success'] = false;
    
    if ( !current_user_can( 'manage_options' ) || !isset( $_POST['class'] ) ) {
        $response['error'] = __( 'Invalid access!', 'invoicing' );
        wp_send_json( $response );
    }
    
    $vat_class = isset( $_POST['class'] ) && $_POST['class'] !== '' ? sanitize_text_field( $_POST['class'] ) : false;
    $vat_classes = (array)wpinv_vat_get_rate_classes();

    if ( !isset( $vat_classes[$vat_class] ) ) {
        $response['error'] = __( 'Requested class does not exists', 'invoicing' );
        wp_send_json( $response );
    }
    
    if ( $vat_class == '_new' || $vat_class == '_standard' ) {
        $response['error'] = __( 'You can not delete standard rates class', 'invoicing' );
        wp_send_json( $response );
    }
        
    $vat_classes = (array)wpinv_vat_get_rate_classes( true );
    unset( $vat_classes[$vat_class] );
    
    update_option( '_wpinv_vat_rate_classes', $vat_classes );
    
    $response['success'] = true;
    $response['redirect'] = admin_url( 'admin.php?page=wpinv-settings&tab=taxes&section=vat_rates&wpi_sub=_new' );
    
    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_delete_vat_class', 'wpinv_delete_vat_class' );
add_action( 'wp_ajax_nopriv_wpinv_delete_vat_class', 'wpinv_delete_vat_class' );

function wpinv_allow_vat_rules() {
    global $wpinv_options;
    
    return ( !empty( $wpinv_options['apply_vat_rules'] ) ? true : false );
}

function wpinv_allow_vat_classes() {
    return false; // TODO
    global $wpinv_options;
    return ( !empty( $wpinv_options['vat_allow_classes'] ) ? true : false );
}

function wpinv_settings_sanitize_vat_rates( $input ) {
    if( !current_user_can( 'manage_options' ) ) {
        add_settings_error( 'wpinv-notices', '', __( 'Your account does not have permission to add rate classes.', 'invoicing' ), 'error' );
        return $input;
    }
    
    $vat_classes = wpinv_vat_get_rate_classes();
    $vat_class = !empty( $_REQUEST['wpi_vat_class'] ) && isset( $vat_classes[$_REQUEST['wpi_vat_class']] )? sanitize_text_field( $_REQUEST['wpi_vat_class'] ) : '';
    
    if ( empty( $vat_class ) ) {
        add_settings_error( 'wpinv-notices', '', __( 'No valid VAT rates class contained in the request to save rates.', 'invoicing' ), 'error' );
        
        return $input;
    }

    $new_rates = ! empty( $_POST['vat_rates'] ) ? array_values( $_POST['vat_rates'] ) : array();

    if ( $vat_class === '_standard' ) {
        // Save the active rates in the invoice settings
        update_option( 'wpinv_tax_rates', $new_rates );
    } else {
        // Get the existing set of rates
        $rates = wpinv_non_standard_rates();
        $rates[$vat_class] = $new_rates;

        update_option( 'wpinv_vat_rates', $rates );
    }
    
    return $input;
}
add_filter( 'wpinv_settings_taxes-vat_rates_sanitize', 'wpinv_settings_sanitize_vat_rates' );

function wpinv_item_is_taxable( $item_id = 0, $country = false, $state = false ) {
    if ( !wpinv_use_taxes() ) {
        return false;
    }
    
    $is_taxable = true;
    
    if ( !empty( $item_id ) && wpinv_get_item_vat_class( $item_id ) == '_exempt' ) {
        $is_taxable = false;
    }
    
    return apply_filters( 'wpinv_item_is_taxable', $is_taxable, $item_id, $country , $state );
}

function wpinv_get_vat_rate( $rate = 1, $country = '', $state = '', $item_id = 0 ) {
    global $wpinv_options, $wpi_session, $wpinv_euvat, $wpi_item_id;
    
    $item_id = $item_id > 0 ? $item_id : $wpi_item_id;
    $allow_vat_classes = wpinv_allow_vat_classes();
    $class = $item_id ? wpinv_get_item_vat_class( $item_id ) : '_standard';

    if ( $class === '_exempt' ) {
        return 0;
    } else if ( !$allow_vat_classes ) {
        $class = '_standard';
    }

    if( !empty( $_POST['wpinv_country'] ) ) {
        $post_country = $_POST['wpinv_country'];
    } elseif( !empty( $_POST['wpinv_country'] ) ) {
        $post_country = $_POST['wpinv_country'];
    } elseif( !empty( $_POST['country'] ) ) {
        $post_country = $_POST['country'];
    } else {
        $post_country = '';
    }

    // If there is a VAT number, the rate is zero
    // Grab the country either from the POST array or
    // use the original country
    $country = !empty( $post_country ) ? $post_country : apply_filters( 'wpinv-get-country', !empty( $wpinv_options['vat_ip_country_default'] ) ? '' : $country );
    
    $requires_vat   = $wpinv_euvat->requires_vat( 0, false );
    $is_digital     = wpinv_item_get_vat_rule( $item_id ) == 'digital' ;
    
    $rate = isset( $wpinv_options['tax_rate'] ) ? (float)$wpinv_options['tax_rate'] : ( $requires_vat && isset( $wpinv_options['vat_eu_states'] ) ? $wpinv_options['vat_eu_states'] : 0 );
      
    if ( wpinv_vat_same_country_rule() == 'no' && wpinv_is_base_country( $country ) ) { // Disable VAT to same country
        $rate = 0;
    } else if ( $requires_vat ) { // If VAT is not required return the default tax rate
        // OK, VAT is required so see if there is a VAT number
        // in the user's account
        $vat_number = wpinv_get_vat_number( '', 0, true );
        // A supplied VAT number may be in the session variable
        $vat_info = $wpi_session->get( 'user_vat_data' );
        
        if ( is_array( $vat_info ) ) {
            $vat_number = isset( $vat_info['number'] ) && !empty( $vat_info['valid'] ) ? $vat_info['number'] : "";
        }
        
        // If there is a VAT number, the rate is zero
        // Grab the country either from the POST array or
        // use the original country
        if ( $country == 'UK' ) {
            $country = 'GB';
        }

        if ( !empty( $vat_number ) ) {
            $rate = 0;
        } else {
            $rate = wpinv_lookup_rate( $country, $state, $rate, $class ); // Fix if there are no tax rated and you try to pay an invoice it does not add the fallback tax rate
        }

        // Otherwise work out what rate to use
        // Case 1: It's a physical item sold to a consumer
        if ( empty( $vat_number ) && !$is_digital ) {
            // If the consumer is in the same country as the shop, charge VAT at the rate in the shop's country
            // Otherwise zero
            if ( wpinv_is_base_country( $country ) ) {
                $rate = wpinv_lookup_rate( $country, null, $rate, $class );
            } else {
                // Default to the VAT default value.
                // Case 1a: There's no billing country so use the the default VAT rate if it is available
                if ( empty( $country ) && isset( $wpinv_options['vat_eu_states'] ) ) {
                    $rate = $wpinv_options['vat_eu_states'];
                } else if( !empty( $country ) ) { // Case 2b: Lookup the rate for the country
                    $rate = wpinv_lookup_rate( $country, $state, $rate, $class );
                }
            }
        }
        // Case 2: There is no VAT number or its a company sale in the base country
        else if ( empty( $vat_number ) || ( wpinv_vat_same_country_rule() == 'always' && wpinv_is_base_country( $country ) ) ) {
            // Get here if this is is a digital item and there is no VAT number or there is a
            // VAT number but VAT has to be charged because the customer is in the same country
            // Default to the VAT default value.
            // Case 2a: There's no billing country so use the the default VAT rate if it is available
            if ( empty( $country ) && isset( $wpinv_options['vat_eu_states'] ) ) {
                $rate = $wpinv_options['vat_eu_states'];
            } else if( !empty( $country ) ) { // Case 2b: Lookup the rate for the country
                $rate = wpinv_lookup_rate( $country, $state, $rate, $class );
            }
        }
    } else {
        // Getting here means the billing address is not EU
        // Tax may still be due if there is a rate for the country
        // However if the IP address of the user is an EU state then
        // it is the rate to use
        if ( $is_digital ) {
            $ip_country_code = wpinv_get_ip_country();
            
            if ( $ip_country_code && in_array( $ip_country_code, wpinv_get_eu_states() ) ) {
                $rate = wpinv_lookup_rate( $ip_country_code, '', 0, $class );
            } else {
                $rate = wpinv_lookup_rate( $country, $state, $rate, $class );
            }
        } else {
            $rate = wpinv_lookup_rate( $country, $state, $rate, $class );
        }
    }

    return $rate;
}
add_filter( 'wpinv_tax_rate', 'wpinv_get_vat_rate', 10, 4 );

function wpinv_get_item_vat_class( $postID ) {
    $class = get_post_meta( $postID, '_wpinv_vat_class', true );

    if ( empty( $class ) ) {
        $class = '_standard';
    }
    
    $class = apply_filters( 'wpinv_get_item_vat_class', $class, $postID );

    return $class;
}

function wpinv_item_vat_class( $postID ) {
    $vat_classes = wpinv_vat_rate_all_classes();
    
    $class = wpinv_get_item_vat_class( $postID );
    $class = isset( $vat_classes[$class] ) ? $vat_classes[$class] : __( $class, 'invoicing' );
    
    $class = apply_filters( 'wpinv_item_vat_class', $class, $postID );

    return $class;
}

function wpinv_item_get_vat_rule( $postID ) {
    $rule_type = get_post_meta( $postID, '_wpinv_vat_rule', true );
    
    if ( empty( $rule_type ) ) {        
        $rule_type = wpinv_allow_vat_rules() ? 'digital' : 'physical';
    }
    
    $rule_type = apply_filters( 'wpinv_item_get_vat_rule', $rule_type, $postID );
    
    return $rule_type;
}

function wpinv_item_vat_rule( $postID ) {
    $vat_rules = wpinv_vat_rule_types();
    
    $vat_rule = wpinv_item_get_vat_rule( $postID );
    $vat_rule = isset( $vat_rules[$vat_rule] ) ? $vat_rules[$vat_rule] : $vat_rule;
    
    $vat_rule = apply_filters( 'wpinv_item_vat_rule', $vat_rule, $postID );

    return $vat_rule;
}

function wpinv_vat_rule_is_digital( $item_id = 0 ) {
    return wpinv_item_get_vat_rule( $item_id ) == 'digital' ? true : false;
}

function wpinv_invoice_has_digital_item( $invoice = 0 ) {
    if ( !wpinv_allow_vat_rules() ) {
        return false;
    }
    
    if ( empty( $invoice ) ) {
        return true;
    }
    
    if ( is_int( $invoice ) ) {
        $invoice = new WPInv_Invoice( $invoice );
    }
    
    if ( !( is_object( $invoice ) && is_a( $invoice, 'WPInv_Invoice' ) ) ) {
        return true;
    }
    
    $cart_items  = $invoice->get_cart_details();
    
    if ( !empty( $cart_items ) ) {
        // Are any of the products digital?
        $item_is_digital = false;
        foreach ( $cart_items as $key => $item ) {
            $item_is_digital = wpinv_vat_rule_is_digital( $item['id'] );
            
            if ( $item_is_digital ) {
                break;
            }
        }
    } else {
        $item_is_digital = true;
    }
    
    return $item_is_digital;
}

function wpinv_get_vat_number( $vat_number = '', $user_id = 0, $is_valid = false ) {
    global $wpi_current_id;
    
    if ( empty( $user_id ) ) {
        $user_id = $wpi_current_id ? wpinv_get_user_id( $wpi_current_id ) : get_current_user_id();
    }

    // Look to see if a VAT number has been recorded
    $vat_number = empty( $user_id ) ? "" : get_user_meta( $user_id, '_wpinv_vat_number', true );
    
    if ( $is_valid && $vat_number ) {
        $self_certified = empty( $user_id ) ? false : get_user_meta( $user_id, '_wpinv_self_certified', true );
        if ( !$self_certified ) {
            $vat_number = "";
        }
    }

    // Allow the company to be retrieved from elsewhere
    $result = apply_filters('wpinv_get_vat_number_custom', $vat_number, $user_id, $is_valid );

    return $result;
}

function wpinv_lookup_rate( $country, $state, $rate, $class ) {
    if ( $class === '_exempt' ) {
        return 0;
    }

    // Always need the default tax rates
    $tax_rates   = wpinv_get_tax_rates();
    // But use rates from another class unless the rate does not exist in which case use the standard rate
    if ( $class !== '_standard' ) {
        $class_rates = wpinv_vat_rates( $class );
        
        if ( is_array( $class_rates ) ) {
            $indexed_class_rates = array();
            
            foreach ( $class_rates as $key => $cr ) {
                $indexed_class_rates[$cr['country']] = $cr;
            }

            // Join the two arrays on their country
            $tax_rates = array_map( function( $tr ) use( $indexed_class_rates ) {
                // If the rate for country in $tr does not exist in $class_rates or
                // if the rate for the country in $class_rates exists but has no value use the $tr
                $tr_country = $tr['country'];
                if ( !isset( $indexed_class_rates[$tr_country] ) ) {
                    return $tr;
                }
                $icr = $indexed_class_rates[$tr_country];
                return ( empty( $icr['rate'] ) && $icr['rate'] !== "0" ) ? $tr : $icr;

            }, $tax_rates, $class_rates );
        }
    }

    if ( !empty( $tax_rates ) ) {

        // Locate the tax rate for this country / state, if it exists
        foreach ( $tax_rates as $key => $tax_rate ) {

            if ( $country != $tax_rate['country'] )
                continue;

            if ( !empty( $tax_rate['global'] ) ) {
                if ( 0 !== $tax_rate['rate'] || !empty( $tax_rate['rate'] ) ) {
                    $rate = number_format( $tax_rate['rate'], 4 );
                }
            } else {
                if ( empty( $tax_rate['state'] ) || strtolower( $state ) != strtolower( $tax_rate['state'] ) )
                    continue;

                $state_rate = $tax_rate['rate'];
                if ( 0 !== $state_rate || !empty( $state_rate ) ) {
                    $rate = number_format( $state_rate, 4 );
                }
            }
        }
    }
    
    return $rate;
}

function wpinv_vat_same_country_rule() {
    global $wpinv_options;

    return isset( $wpinv_options['vat_same_country_rule'] ) ? $wpinv_options['vat_same_country_rule'] : '';
}

function wpinv_is_base_country( $country ) {
    $base_country = wpinv_get_default_country();
    
    if ( $base_country === 'UK' ) {
        $base_country = 'GB';
    }
    if ( $country == 'UK' ) {
        $country = 'GB';
    }

    return ( $country && $country === $base_country ) ? true : false;
}

function wpinv_update_eu_vat_rates() {
    $response = array();
    $response['success'] = false;
    
    if ( !current_user_can( 'manage_options' ) ) {
        $response['error'] = __( 'Invalid access!', 'invoicing' );
        wp_send_json( $response );
    }
    
    $euvatrates_url = 'https://euvatrates.com/rates.json';
    $euvatrates_url = apply_filters( 'wpinv_euvatrates_url', $euvatrates_url );
    
    $success = false;
    $api_response = wp_remote_get( $euvatrates_url );

    try {
        if ( is_wp_error( $api_response ) ) {
            $json = null;
            $response['error'] = __( $api_response->get_error_message(), 'invoicing' );
        } else {
            $success = true;
            $json = json_decode( $api_response['body'] );
            
            $vat_group = !empty( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : '';
            
            $json = wpinv_process_euvatrates_data( $json, $api_response, $vat_group );
        }
    } catch ( Exception $e ) {
        $json = null;
        $response['error'] = __( $e->getMessage(), 'invoicing' );
    }
         
    $response['success'] = $success;
    $response['data'] = $json;
    
    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_update_vat_rates', 'wpinv_update_eu_vat_rates' );
add_action( 'wp_ajax_nopriv_wpinv_update_vat_rates', 'wpinv_update_eu_vat_rates' );

function wpinv_process_euvatrates_data( $data, $response, $group = '' ) {
    if ( isset( $data->disclaimer ) ) {
        unset( $data->disclaimer );
    }
    
    if ( isset( $data->rates ) ) {
        $rates = array();
        
        foreach ( $data->rates as $country_code => $rate ) {
            $vat_rate = array();
            $vat_rate['country']        = $rate->country;
            $vat_rate['standard']       = (float)$rate->standard_rate;
            $vat_rate['reduced']        = (float)$rate->reduced_rate;
            $vat_rate['superreduced']   = (float)$rate->super_reduced_rate;
            $vat_rate['parking']        = (float)$rate->parking_rate;
            
            if ( $group !== '' && in_array( $group, array( 'standard', 'reduced', 'superreduced', 'parking' ) ) ) {
                $vat_rate_group = array();
                $vat_rate_group['country'] = $rate->country;
                $vat_rate_group[$group]    = $vat_rate[$group];
                
                $vat_rate = $vat_rate_group;
            }
            
            $rates[$country_code] = (object)$vat_rate;
        }
        
        $data->rates = (object)$rates;
    }
    
    
    $data = apply_filters( 'wpinv_process_euvatrates_data', $data, $response );
    
    return $data;
}

function wpinv_user_vat_info() {
    global $wpi_session;
    
    return $wpi_session->get( 'user_vat_data' );
}

function wpinv_owner_get_vat_name() {
    $vat_name   = wpinv_get_option( 'vat_name' );
    $vat_name   = !empty( $vat_name ) ? $vat_name : 'VAT';
    return apply_filters( 'wpinv_owner_get_vat_name', $vat_name );
}

function wpinv_owner_vat_number() {
    $vat_number = wpinv_get_option( 'vat_number' );
    $vat_number = $vat_number ? maybe_unserialize($vat_number) : NULL;
    $vat_number = !empty($vat_number) && isset($vat_number['number']) ? $vat_number['number'] : '';
    return $vat_number;
}

function wpinv_owner_vat_company_name() {
    $company_name = wpinv_get_option( 'vat_company_name' );
    return apply_filters('wpinv_owner_vat_company_name', $company_name);
}

function wpinv_vat_enqueue_vat_scripts() {
    global $wpinv_options;
    
    $suffix     = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    
    wp_register_script( 'wpinv-vat-validation-script', WPINV_PLUGIN_URL . 'assets/js/vat_validation' . $suffix . '.js', array( 'jquery' ),  WPINV_VERSION );
    wp_register_script( 'wpinv-vat-script', WPINV_PLUGIN_URL . 'assets/js/vat' . $suffix . '.js', array( 'jquery' ),  WPINV_VERSION );
    
    $vat_name   = wpinv_owner_get_vat_name();
    
    $vars = array();
    $vars['NoRateSet'] = __( 'You have not set a rate. Do you want to continue?', 'invoicing' );
    $vars['EmptyCompany'] = __( 'Please enter your registered company name!', 'invoicing' );
    $vars['EmptyVAT'] = wp_sprintf( __( 'Please enter your %s number!', 'invoicing' ), $vat_name );
    $vars['TotalsRefreshed'] = wp_sprintf( __( 'The invoice totals will be refreshed to update the %s.', 'invoicing' ), $vat_name );
    $vars['ErrorValidatingVATID'] = wp_sprintf( __( 'Fail to validate the %s number!', 'invoicing' ), $vat_name );
    $vars['ErrorResettingVATID'] = wp_sprintf( __( 'Fail to reset the %s number!', 'invoicing' ), $vat_name );
    $vars['ReasonInvalidFormat'] = wp_sprintf( __( 'The %s number supplied does not have a valid format!', 'invoicing' ), $vat_name );
    $vars['ReasonSimpleCheckFails'] = __( 'Simple check failed!', 'invoicing' );
    $vars['ErrorInvalidResponse'] = __( 'An invalid response has been received from the server!', 'invoicing' );
    $vars['ApplyVATRules'] = wpinv_allow_vat_rules();
    $vars['RateRequestResponseInvalid'] = __( 'The get rate request response is invalid', 'invoicing' );
    $vars['PageRefresh'] = __( 'The page will be refreshed in 10 seconds to show the new options.', 'invoicing' );
    $vars['RequestResponseNotValidJSON'] = __( 'The get rate request response is not valid JSON', 'invoicing' );
    $vars['GetRateRequestFailed'] = __( 'The get rate request failed: ', 'invoicing' );
    $vars['NoRateInformationInResponse'] = __( 'The get rate request response does not contain any rate information', 'invoicing' );
    $vars['RatesUpdated'] = __( 'The rates have been updated. Press the save button to record these new rates.', 'invoicing' );
    $vars['IPAddressInformation'] = __( 'IP Address Information', 'invoicing' );
    $vars['VatValidating'] = wp_sprintf( __( 'Validating %s number...', 'invoicing' ), $vat_name );
    $vars['VatReseting'] = __( 'Reseting...', 'invoicing' );
    $vars['VatValidated'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
    $vars['VatNotValidated'] = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
    $vars['ConfirmDeleteClass'] = __( 'Are you sure you wish to delete this rates class?', 'invoicing' );
    $vars['isFront'] = is_admin() ? false : true;
    $vars['checkoutNonce'] = wp_create_nonce( 'wpinv_checkout_nonce' );
    $vars['baseCountry'] = wpinv_get_default_country();
    $vars['disableVATSameCountry'] = ( wpinv_vat_same_country_rule() == 'no' ? true : false );
    $vars['disableVATSimpleCheck'] = wpinv_get_option( 'vat_offline_check' ) ? true : false;
    
    wp_enqueue_script( 'wpinv-vat-validation-script' );
    wp_enqueue_script( 'wpinv-vat-script' );
    wp_localize_script( 'wpinv-vat-script', 'WPInv_VAT_Vars', $vars );
}

function wpinv_vat_admin_enqueue_scripts() {
    if( isset( $_GET['page'] ) && 'wpinv-settings' == $_GET['page'] ) {
        wpinv_vat_enqueue_vat_scripts();
    }
}
add_action( 'admin_enqueue_scripts', 'wpinv_vat_admin_enqueue_scripts' );

function wpinv_settings_section_vat_settings( $sections ) {
    if ( !empty( $sections ) ) {
        $sections['vat'] = __( 'VAT Settings', 'invoicing' );
        
        if ( wpinv_allow_vat_classes() ) {
            $sections['vat_rates'] = __( 'EU VAT Rates', 'invoicing' );
        }
    }
    return $sections;
}
add_filter( 'wpinv_settings_sections_taxes', 'wpinv_settings_section_vat_settings' );

function wpinv_settings_vat_settings( $settings ) {
    if ( !empty( $settings ) ) {    
        $vat_settings = array();
        $vat_settings['vat_settings'] = array(
            'id' => 'vat_settings',
            'name' => '<h3>' . __( 'VAT Settings', 'invoicing' ) . '</h3>',
            'desc' => '',
            'type' => 'header',
            'size' => 'regular'
        );
        
        $vat_settings['vat_company_name'] = array(
            'id' => 'vat_company_name',
            'name' => __( 'Company Name', 'invoicing' ),
            'desc' => __( 'Enter your company name as it appears on your VAT return (not case sensitive)', 'invoicing' ),
            'type' => 'text',
            'size' => 'regular',
        );
        
        $vat_settings['vat_number'] = array(
            'id'   => 'vat_number',
            'name' => __( 'VAT Number', 'invoicing' ),
            'type' => 'vat_number',
            'size' => 'regular',
        );
        
        $vat_settings['vat_name'] = array(
            'id' => 'vat_name',
            'name' => __( 'VAT Name', 'invoicing' ),
            'desc' => __( 'Enter the VAT name', 'invoicing' ),
            'type' => 'text',
            'size' => 'regular',
            'std' => 'VAT'
        );
        
        $vat_settings['vat_invoice_notice_label'] = array(
            'id' => 'vat_invoice_notice_label',
            'name' => __( 'Invoice notice label', 'invoicing' ),
            'desc' => __( 'A label that should appear for the invoice notice if used', 'invoicing' ),
            'type' => 'text',
            'size' => 'regular',
        );
        
        $vat_settings['vat_invoice_notice'] = array(
            'id' => 'vat_invoice_notice',
            'name' => __( 'Invoice notice', 'invoicing' ),
            'desc' => '<p>' . __( 'Enter the text that should appear in the invoice', 'invoicing' ) . '</p>' ,
            'type' => 'text',
            'size' => 'regular',
        );
        
        $vat_settings['vat_vies_check'] = array(
            'id' => 'vat_vies_check',
            'name' => __( 'Disable VIES check', 'invoicing' ),
            'desc' => wp_sprintf( __( 'Check this option if you do not want VAT numbers to be checked with the %sEU VIES System.%s', 'invoicing' ), '<a href="http://ec.europa.eu/taxation_customs/vies/" target="_blank">', '</a>' ),
            'type' => 'checkbox'
        );

        $vat_settings['vat_disable_company_name_check'] = array(
            'id' => 'vat_disable_company_name_check',
            'name' => __( 'Disable the company name check', 'invoicing' ),
            'desc' => __( 'Check this option if you do not want the company name entered to be validated against the name registered with the VAT tax authority.', 'invoicing' ),
            'type' => 'checkbox'
        );

        $vat_settings['vat_offline_check'] = array(
            'id' => 'vat_offline_check',
            'name' => __( 'Disable simple check', 'invoicing' ),
            'desc' => __( 'Check this option if you do not want VAT numbers to be checked to have a correct format (not recommended). Each EU member state has its own format for a VAT number.<br>While we try to make sure the format rules are respected it is possible that a specific rule is not respected so a correct VAT number is not validated. If you encounter this situation, use this option to prevent simple checks.', 'invoicing' ),
            'type' => 'checkbox'
        );
        
        $vat_settings['vat_same_country_rule'] = array(
            'id'          => 'vat_same_country_rule',
            'name'        => __( 'Same country rule', 'invoicing' ),
            'desc'        => __( 'Select how you want handle VAT charge if sales are in the same country as the base country.', 'invoicing' ),
            'type'        => 'select',
            'options'     => array(
                ''          => __( 'Normal', 'invoicing' ),
                'no'        => __( 'No VAT', 'invoicing' ),
                'always'    => __( 'Always apply VAT', 'invoicing' ),
            ),
            'placeholder' => __( 'Select a option', 'invoicing' ),
            'std'         => ''
        );
        
        $vat_settings['vat_disable_fields'] = array(
            'id' => 'vat_disable_fields',
            'name' => __( 'Disable VAT fields', 'invoicing' ),
            'desc' => __( 'Check if the fields to collect VAT should NOT be shown, for example, if the plug-in is being used for GST.', 'invoicing' ),
            'type' => 'checkbox'
        );
        
        $vat_settings['vat_ip_lookup'] = array(
            'id'   => 'vat_ip_lookup',
            'name' => __( 'IP Country lookup', 'invoicing' ),
            'type' => 'vat_ip_lookup',
            'size' => 'regular',
            'std' => 'default'
        );
    
        $vat_settings['hide_ip_address'] = array(
            'id' => 'hide_ip_address',
            'name' => __( 'Hide IP address at checkout', 'invoicing' ),
            'desc' => __( 'Check if the user IP address should not be shown on the checkout page.', 'invoicing' ),
            'type' => 'checkbox'
        );
    
        $vat_settings['vat_ip_country_default'] = array(
            'id' => 'vat_ip_country_default',
            'name' => __( 'Enable IP Country as Default', 'invoicing' ),
            'desc' => __( 'By default it shows visitors the country of the site as the default country during checkout.<br>Enable this option to show the country of the visitor\'s IP address as the default (the address from their profile will be used if the user is signed in).', 'invoicing' ),
            'type' => 'checkbox'
        );
    
        $vat_settings['apply_vat_rules'] = array(
            'id' => 'apply_vat_rules',
            'name' => __( 'Enable VAT rules', 'invoicing' ),
            'desc' => __( 'Check if VAT should always be applied to consumer sales from IP addresses within the EU even if the billing address is outside the EU.<br>When checked, an option will be available to update current VAT rates if more recent rates are available.', 'invoicing' ) . '<br><font style="color:red">' . __( 'Do not disable unless you know what you are doing.', 'invoicing' ) . '</font>',
            'type' => 'checkbox',
            'std' => '1'
        );
    
        /*
        $vat_settings['vat_allow_classes'] = array(
            'id' => 'vat_allow_classes',
            'name' => __( 'Allow the use of VAT rate classes', 'invoicing' ),
            'desc' =>  __( 'When enabled this option makes it possible to define alternative rate classes so rates for items that do not use the standard VAT rate in all member states can be defined.<br>A menu option will appear under the "Invoicing -> Settings -> Taxes -> EU VAT Rates" menu heading that will take you to a page on which new classes can be defined and rates entered. A meta-box will appear in the invoice page in which you are able to select one of the alternative classes you create so the rates associated with the class will be applied to invoice.<br>By default the standard rates class will be used just as they are when this option is not enabled.', 'invoicing' ),
            'type' => 'checkbox'
        );
        */
    
        $vat_settings['vat_prevent_b2c_purchase'] = array(
            'id' => 'vat_prevent_b2c_purchase',
            'name' => __( 'Prevent sales to EU consumers', 'invoicing' ),
            'desc' => __( 'Enable this option to prevent B2C sales to EU consumers reduce the circumstances in which VAT registration is required.', 'invoicing' ),
            'type' => 'checkbox'
        );
                
        $settings['vat'] = $vat_settings;
        
        if ( wpinv_allow_vat_classes() ) {
            $settings['vat_rates'] = wpinv_get_vat_rates_settings();
        }
        
        $vat_eu_states = array(
            'id'   => 'vat_eu_states',
            'name' => '<h3>' . __( 'VAT rate for EU member states', 'invoicing' ) . '</h3>',
            'type' => 'vat_eu_states',
            'desc' => __( 'Enter the VAT rate you charge purchasers from other EU member states. You can edit the rates for each member state when a country rate has been set up by pressing this button.', 'invoicing' ),
            'std'  => '20',
            'size' => 'small'
        );
        $settings['rates']['vat_eu_states'] = $vat_eu_states;
    }

    return $settings;
}
add_filter( 'wpinv_settings_taxes', 'wpinv_settings_vat_settings' );

function wpinv_get_gst_countries( $sort = true ) {
    $gst_countries  = array( 'AU', 'NZ', 'CA', 'CN' );
    
    if ( $sort ) {
        $sort = sort( $gst_countries );
    }
    
    return $gst_countries;
}

function wpinv_get_eu_states( $sort = true ) {
    $eu_states = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GB', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );
    if ( $sort ) {
        $sort = sort( $eu_states );
    }
    
    return $eu_states;
}

function wpinv_vat_number_callback( $args ) {
    global $wpinv_options;

    $value = '';
    if ( isset( $wpinv_options[$args['id']] ) ) {
        $value = $wpinv_options[$args['id']];
        $value = maybe_unserialize($value);
    }
    
    if ( !is_array( $value ) || empty( $value ) ) {
        $value = array( 'valid' => false, 'number' => '' );
    }

    $size = ( isset( $args['size'] ) && !is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $validated_text = $value['valid'] ? __( 'VAT number validated', 'invoicing' ) : __( 'VAT number not validated', 'invoicing' );
    $disabled = $value['valid'] ? 'disabled="disabled"' : " ";
    
    $html = '<input type="text" class="' . $size . '-text" id="wpinv_settings[' . $args['id'] . ']" name="wpinv_settings[' . $args['id'] . ']" placeholder="GB123456789" value="' . esc_attr( stripslashes( $value['number'] ) ) . '"/>';
    $html .= '<span>&nbsp;<input type="button" id="wpinv_vat_validate" class="wpinv_validate_vat_button button-secondary" ' . $disabled . ' value="' . esc_attr__( 'Validate VAT Number', 'invoicing' ) . '" /></span>';
    $html .= '<span class="wpinv-vat-stat wpinv-vat-stat-' . (int)$value['valid'] . '"><i class="fa"></i> <font>' . $validated_text . '</font></span>';
    $html .= '<label for="wpinv_settings[' . $args['id'] . ']">' . '<p>' . __( 'Enter your VAT number including country identifier, eg: GB123456789', 'invoicing' ) . '<br/><b>' . __( 'If you are having difficulty validating the VAT number, check the "Disable VIES check" option below and save these settings.', 'invoicing' ) . '</b><br/><b>' . __( 'After saving, try again to validate the VAT number.', 'invoicing' ) . '</b></p>' . '</label>';
    $html .= '<input type="hidden" name="_wpi_nonce" value="' . wp_create_nonce( 'vat_validation' ) . '">';
    $html .= '<input type="hidden" id="wpi_vat_number_original" value="'. esc_attr( stripslashes( $value['number'] ) ) . '">';

    echo $html;
}

function wpinv_vat_eu_states_callback( $args ) {
    global $wpinv_options;

    $value = isset( $wpinv_options[$args['id']] ) ? $wpinv_options[ $args['id'] ] : ( isset( $args['std'] ) ? $args['std'] : '' );
    $size = ( isset( $args['size'] ) && !is_null( $args['size'] ) ) ? $args['size'] : 'small';
    
    $html = '<input type="number" min="0", max="99.99" step="0.10" class="' . $size . '-text" id="wpinv_settings_' . $args['section'] . '_' . $args['id'] . '" name="wpinv_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" />';
    $html .= '<span>&nbsp;<input id="wpi_add_eu_states" type="button" class="button-secondary" value="' . esc_attr__( 'Add EU Member States', 'invoicing' ) . '" /></span>';
    $html .= '<span>&nbsp;<input id="wpi_remove_eu_states" type="button" class="button-secondary" value="' . esc_attr__( 'Remove EU Member States', 'invoicing' ) . '" /></span>';
    $html .= '<span>&nbsp;<input id="wpi_vat_get_rates" type="button" class="button-secondary" value="' . esc_attr__( 'Update EU VAT Rates', 'invoicing' ) . '" />&nbsp;&nbsp;<i style="display:none" class="fa fa-refresh fa-spin"></i></span>';
    $html .= '<p><label for="wpinv_settings_' . $args['section'] . '_' . $args['id'] . '">' . $args['desc'] . '</label></p>';
    $html .= '<input type="hidden" id="wpi_vat_company_original" value="' . esc_attr( stripslashes( $value ) ) . '">';
    echo $html;
    ?>
    <span id="wpinv-rates-error-wrap" class="wpinv_errors" style="display:none;"></span>
    <?php
}

function wpinv_vat_ip_lookup_callback( $args ) {
    global $wpinv_options;

    $value =  isset( $wpinv_options[ $args['id'] ] ) ? $wpinv_options[ $args['id'] ]  : ( isset( $args['std'] ) ? $args['std'] : 'default' );
    
    $ip_lookup_options = array(
        'site' => __( 'Use default country', 'invoicing' ),
        'default' => __( 'Let the plug-in choose the best available option', 'invoicing' )
    );

    if ( function_exists( 'simplexml_load_file' ) ) {
        $ip_lookup_options = array( 'geoplugin' => __( 'GeoPlugin.net (if you choose this option consider donating)', 'invoicing' ) ) + $ip_lookup_options;
    }

    // If it does exist this variable will contain the path and filename
    // It will not be available until the plugin is licensed
    $geoip2_file_exists = wpinv_getGeoLiteCountryFilename();

    // If geoip2 can be used the variable will be empty or it will contain the reason why not
    if ( !function_exists( 'bcadd' ) ) {
        $permit_geoip2 = __( 'GeoIP collection requires the BC Math PHP extension and it is not loaded on your version of PHP!', 'invoicing' );
    } else {
        $permit_geoip2 = ini_get('safe_mode') ? __( 'PHP safe mode detected!  GeoIP collection is not supported with PHP\'s safe mode enabled!', 'invoicing' ) : '';
    }
    
    if ( $geoip2_file_exists !== false && empty( $permit_geoip2 ) ) {
        $ip_lookup_options = array( 'geoip2' => __( 'GeoIP2 functions', 'invoicing' ) ) + $ip_lookup_options;
    }

    if ( function_exists( 'geoip_country_code_by_name' ) ) {
        $ip_lookup_options = array('geoip' => __( 'PHP GeoIP extension functions', 'invoicing' ) ) + $ip_lookup_options;
    }

    $html = wpinv_html_select( array(
        'name'             => "wpinv_settings[{$args['id']}]",
        'selected'         => $value,
        'id'               => "wpinv_settings[{$args['id']}]",
        'class'            => isset($args['class']) ? $args['class'] : "",
        'options'          => $ip_lookup_options,
        'multiple'         => false,
        'chosen'           => false,
        'show_option_all'  => false,
        'show_option_none' => false
    ));
    
    $desc = '<label for="wpinv_settings[' . $args['id'] . ']">';
    $desc .= __( 'Choose the mechanism the plug-in should use to determine the country from the IP address of the visitor. The country is used as evidence to support the selected country of supply.', 'invoicing' );
    $desc .= '<p>' . __( 'This is important because from Jan 2015 if you sell digital services you are required to collect and retain for 10 years evidence to justify why the consumer has been charged the VAT rate of one member state not the rate of another member state.<br>One of the few pieces of evidence available to an on-line store is the IP address of the visitor.', 'invoicing' ) . '</p><p>';
    if ( empty( $permit_geoip2 ) ) {
        if ( $geoip2_file_exists !== FALSE ) {
            $desc .= __(  'The GeoIP2 database already exists:', 'invoicing' ) . '&nbsp;<input type="button" id="wpi_download_geoip2" action="refresh"" class="wpinv-refresh-geoip2-btn button-secondary" value="' . __( 'Click to refresh the GeoIP2 database', 'invoicing' ) . ' (~18MB)"></input>';
        } else {
            $desc .= __( 'The GeoIP2 database does not exist:', 'invoicing' ) . '&nbsp;<input type="button" id="wpi_download_geoip2" action="download" class="wpinv-download-geoip2-btn button-secondary" value="' . __( 'Click to download the GeoIP2 database', 'invoicing' ) . ' (~18MB)"></input><br>' . __(  'If you download the database another IP lookup mechanism will be available.', 'invoicing' );
        }
    } else {
        $desc .= $permit_geoip2;
    }
    $desc .= '</p><p>'. __( 'If you choose the GeoPlugin option please consider supporting the site: ', 'invoicing' ) . ' <a href="http://www.geoplugin.net/" target="_blank">GeoPlugin.net</a></p>';
    $desc .= '</label>';
    
    $html .= $desc;

    echo $html;
    ?>
    <span id="wpinv-geoip2-errors" class="wpinv_errors" style="display:none;padding:4px;"></span>
    <?php
}

function wpinv_settings_sanitize_vat_settings( $input ) {
    global $wpinv_options;
    
    $valid      = false;
    $changed    = false;

    if ( ( empty( $input['vat_company_name'] ) && !empty( $wpinv_options['vat_company_name'] ) ) || ( !empty( $input['vat_company_name'] ) && empty( $wpinv_options['vat_company_name'] ) ) || ( strcasecmp( $wpinv_options['vat_company_name'], $input['vat_company_name'] ) !== 0 ) ) {
        $changed = true;
        add_settings_error( 'wpinv-notices', '', __( 'The company name has been changed', 'invoicing' ), 'updated' );
    }
    
    if ( ( empty( $input['vat_number'] ) && !empty( $wpinv_options['vat_number'] ) ) || ( !empty( $input['vat_number'] ) && empty( $wpinv_options['vat_number'] ) ) ) {
        $changed = true;
        add_settings_error( 'wpinv-notices', '', __( 'The VAT number has changed', 'invoicing' ), 'updated' );
    } else if ( !empty( $input['vat_number'] ) && !empty( $wpinv_options['vat_number'] ) ) {
        $value = maybe_unserialize( $wpinv_options['vat_number'] );
        $number = is_array( $value ) ? $value['number'] : $value;

        if ( strcasecmp( $number, $input['vat_number'] ) !== 0 ) {
            $changed = true;
            add_settings_error( 'wpinv-notices', '', __( 'The VAT number has been changed', 'invoicing' ), 'updated' );
        }
    }
    
    if ( $changed ) {
        $message = '';
        
        if ( !empty( $wpinv_options['vat_vies_check'] ) ) {
            if ( empty( $wpinv_options['vat_offline_check'] ) ) {
                $valid = $wpinv_euvat->offline_check( $input['vat_number'] );
            } else {
                $valid = true;
            }
            
            $message = $valid ? __( 'VAT number validated', 'invoicing' ) : __( 'VAT number not validated', 'invoicing' );
        } else {
            $result = wpinv_check_vat( $input['vat_number'] );
            
            if ( empty( $result->valid ) ) {
                $valid = false;
                $message = $result->message;
            } else {
                $valid = ( isset( $result['company'] ) && ( $result['company'] == '---' || ( strcasecmp( trim( $result['company'] ), trim( $input['vat_company_name'] ) ) == 0 ) ) ) || !empty( $wpinv_options['vat_disable_company_name_check'] );
                $message = $vat['valid'] ? __( 'VAT number validated', 'invoicing' ) : __( 'The company name associated with the VAT number provided is not the same as the company name provided.', 'invoicing' );
            }
        }

        add_settings_error( 'wpinv-notices', '', $message, ( $valid ? 'updated' : 'error' ) );
    }

    $input['vat_number'] = maybe_serialize($a);

    return $input;
}

add_filter( 'wpinv_settings_taxes-vat_sanitize', 'wpinv_settings_sanitize_vat_settings' );

function wpinv_ajax_vat_reset() {
    global $wpi_session;
    
    $wpi_session->set( 'user_vat_data', null );
    
    $company    = is_user_logged_in() ? wpinv_user_company() : '';
    $vat_number = wpinv_get_vat_number();
    
    $response                       = array();
    $response['success']            = true;
    $response['data']['company']    = $company;
    $response['data']['number']     = $vat_number;
    
    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_vat_reset', 'wpinv_ajax_vat_reset' );
add_action( 'wp_ajax_nopriv_wpinv_vat_reset', 'wpinv_ajax_vat_reset' );

function wpinv_ajax_vat_validate() {
    global $wpinv_options, $wpi_session, $wpinv_euvat;
    
    $is_checkout            = ( !empty( $_POST['source'] ) && $_POST['source'] == 'checkout' ) ? true : false;
    $response               = array();
    $response['success']    = false;
    
    if ( empty( $_REQUEST['_wpi_nonce'] ) || ( !empty( $_REQUEST['_wpi_nonce'] ) && !wp_verify_nonce( $_REQUEST['_wpi_nonce'], 'vat_validation' ) ) ) {
        $response['error'] = __( 'Invalid security nonce', 'invoicing' );
        wp_send_json( $response );
    }
    
    $vat_name   = wpinv_owner_get_vat_name();
    
    if ( $is_checkout ) {
        $invoice = wpinv_get_invoice_cart();
        
        if ( !$wpinv_euvat->requires_vat( false, 0, wpinv_invoice_has_digital_item( $invoice ) ) ) {
            $vat_info = array();
            $wpi_session->set( 'user_vat_data', $vat_info );

            wpinv_save_user_vat_details();
            
            $response['success'] = true;
            $response['message'] = wp_sprintf( __( 'Ignore %s', 'invoicing' ), $vat_name );
            wp_send_json( $response );
        }
    }
    
    $company    = !empty( $_POST['company'] ) ? sanitize_text_field( $_POST['company'] ) : '';
    $vat_number = !empty( $_POST['number'] ) ? sanitize_text_field( $_POST['number'] ) : '';
    
    $vat_info = $wpi_session->get( 'user_vat_data' );
    if ( !is_array( $vat_info ) || empty( $vat_info ) ) {
        $vat_info = array( 'company'=> $company, 'number' => '', 'valid' => true );
    }
    
    if ( empty( $vat_number ) ) {
        if ( $is_checkout ) {
            $response['success'] = true;
            $response['message'] = wp_sprintf( __( 'No %s number has been applied. %s will be added to invoice totals', 'invoicing' ), $vat_name, $vat_name );
            
            $vat_info = $wpi_session->get( 'user_vat_data' );
            $vat_info['number'] = "";
            $vat_info['valid'] = true;
            
            wpinv_save_user_vat_details( $company );
        } else {
            $response['error'] = wp_sprintf( __( 'Please enter your %s number!', 'invoicing' ), $vat_name );
            
            $vat_info['valid'] = false;
        }

        $wpi_session->set( 'user_vat_data', $vat_info );
        wp_send_json( $response );
    }
    
    if ( empty( $company ) ) {
        $vat_info['valid'] = false;
        $wpi_session->set( 'user_vat_data', $vat_info );
        
        $response['error'] = __( 'Please enter your registered company name!', 'invoicing' );
        wp_send_json( $response );
    }
    
    if ( !empty( $wpinv_options['vat_vies_check'] ) ) {
        if ( empty( $wpinv_options['vat_offline_check'] ) && !$wpinv_euvat->offline_check( $vat_number ) ) {
            $vat_info['valid'] = false;
            $wpi_session->set( 'user_vat_data', $vat_info );
            
            $response['error'] = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
            wp_send_json( $response );
        }
        
        $response['success'] = true;
        $response['message'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
    } else {
        $result = wpinv_check_vat( $vat_number );
        
        if ( empty( $result['valid'] ) ) {
            $response['error'] = $result['message'];
            wp_send_json( $response );
        }
        
        $vies_company = !empty( $result['company'] ) ? $result['company'] : '';
        $vies_company = apply_filters( 'wpinv_vies_company_name', $vies_company );
        
        $valid_company = $vies_company && $company && ( $vies_company == '---' || strcasecmp( trim( $vies_company ), trim( $company ) ) == 0 ) ? true : false;

        if ( !empty( $wpinv_options['vat_disable_company_name_check'] ) || $valid_company ) {
            $response['success'] = true;
            $response['message'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
        } else {           
            $vat_info['valid'] = false;
            $wpi_session->set( 'user_vat_data', $vat_info );
            
            $response['success'] = false;
            $response['message'] = wp_sprintf( __( 'The company name associated with the %s number provided is not the same as the company name provided.', 'invoicing' ), $vat_name );
            wp_send_json( $response );
        }
    }
    
    if ( $is_checkout ) {
        wpinv_save_user_vat_details( $company, $vat_number );

        $vat_info = array('company' => $company, 'number' => $vat_number, 'valid' => true );
        $wpi_session->set( 'user_vat_data', $vat_info );
    }

    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_vat_validate', 'wpinv_ajax_vat_validate' );
add_action( 'wp_ajax_nopriv_wpinv_vat_validate', 'wpinv_ajax_vat_validate' );

function wpinv_check_vat( $vat_number, $country_code = '' ) {
    global $wpinv_options, $wpinv_euvat;
    
    $vat_name  = wpinv_owner_get_vat_name();
    
    $return             = array();
    $return['valid']    = false;
    $return['message']  = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
            
    if ( empty( $wpinv_options['vat_offline_check'] ) && !$wpinv_euvat->offline_check( $vat_number, $country_code ) ) {
        return $return;
    }
        
    $response = $wpinv_euvat->vies_check( $vat_number, $country_code );
    
    if ( $response ) {
        $return['valid']    = true;
        
        if ( is_array( $response ) ) {
            $return['company'] = isset( $response['company'] ) ? $response['company'] : '';
            $return['address'] = isset( $response['address'] ) ? $response['address'] : '';
            $return['message'] = $return['company'] . '<br/>' . $return['address'];
        }
    } else {
        $return['valid']    = false;
        $return['message']  = wp_sprintf( __( 'Fail to validate the %s number: EU Commission VAT server (VIES) check fails.', 'invoicing' ), $vat_name );
    }
    
    return $return;
}

function wpinv_vat_details( $invoice_id ) {
    $cart_details       = wpinv_get_cart_details( $invoice_id );
    $user_info          = wpinv_get_invoice_user_info( $invoice_id );
    $ip_address         = wpinv_get_invoice_ip( $invoice_id );
    $self_certified     = !empty( $user_info['self_certified'] ) ? true : false;
    $default_rate       = isset( $user_info['vat_rate'] ) ? $user_info['vat_rate'] : 0;
    $vat_rates_classes  = wpinv_vat_rate_all_classes();
    $vat_rate_types     = wpinv_vat_rate_types();
    $country_code       = $user_info['country'];
    
    $vat_rates = array();
    if ( !empty( $cart_details ) ) {
        foreach( $cart_details as $key => $item ) {
            // Is there a rate for this item?
            $rate   = isset( $item['vat_rate'] ) ? (float)$item['vat_rate'] : (float)$default_rate;
            $class = wpinv_get_item_vat_class( $item['id'] );
            
            // Look up the correct set of class rates for this item
            $class_rates = ('_standard' === $class) ? wpinv_get_tax_rates() : wpinv_vat_rates($class);
            
            // Filter the rate for each country
            $country_rate = array_filter( $class_rates, function( $class_rate ) use( $country_code ) {
                return $class_rate['country'] === $country_code;
            });
            
            // If one exists, take the first or create a default
            $country_rate = !is_array( $country_rate ) || count( $country_rate ) == 0 ? array( 'country' => $country_code, 'rate' => null, 'global' => true, 'state' => null, 'group' => 'reduced' ) : reset( $country_rate );

            $country_rate['group'] = isset( $country_rate['group'] ) ? $country_rate['group'] : 'reduced';
            $group = $country_rate['group'];
            
            if ( isset( $vat_rate_types[$group] ) ) {
                $group_name = __( $vat_rate_types[$group], 'invoicing' );
                if ( $class === '_exempt' ) {
                    $class_name = __( 'Exempt', 'invoicing' );
                } else {
                    $class_name = isset( $vat_rates_classes[$class] ) ? $vat_rates_classes[$class] : get_vat_standard_rate_label();
                    //$class_name = strtok( $class_name, ' ' );
                }
               
                $rate               = $rate > 0 ? (float)wpinv_format_amount( $rate, 2 ) : $rate;
                $name               = wp_sprintf( '%1$s (%2$s: %3$s)', $group_name, $class_name, $rate . '%' );
                $item_amount        = apply_filters( 'wpinv_vat_net_item_price', $item['price'], $item ) * 0.01 * $rate;
                
                $vat_rates[$name]  = (isset($vat_rates[$name]) ? $vat_rates[$name] : 0 ) + apply_filters( 'wpinv-vat-net-amount', $item['price'] - $item['tax'], $item );
            }
        }
    }
    ?>
    <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-vat table-layout">
        <div class="gdmbx-th"><?php _e( 'Vat Details', 'invoicing' ); ?></label></div>
        <div class="gdmbx-td">
            <p>
                <strong><?php _e( 'Location self-certified:', 'invoicing' ); ?></strong>&nbsp;
                <span><?php echo ( $self_certified ? __( 'Yes', 'invoicing' ) : __( 'No', 'invoicing' ) ); ?></span>
            </p>
            <table id="wpinv-vat-rates-list" class="transaction-display wp-list-table widefat" cellspacing="0">
                <tbody>
                <?php if ( !empty( $vat_rates ) ) { ?>
                <?php foreach ( $vat_rates as $name => $amount ) { ?>
                    <tr>
                        <td class="wpinv-vat-name"><?php echo $name; ?>:</td>
                        <td class="wpinv-vat-val"><?php echo apply_filters( 'wpinv_vat_net_item_amount_display', wpinv_price( wpinv_format_amount( $amount ) ), $amount, $item, $invoice_id ); ?></td>
                    </tr>
                <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
add_action( 'wpinv_meta_box_details_inner', 'wpinv_vat_details' );

function wpinv_disable_vat_fields() {
    global $wpinv_options;

    return isset( $wpinv_options['vat_disable_fields'] ) && $wpinv_options['vat_disable_fields'];
}

function wpinv_user_country( $country = '', $user_id = 0 ) {
    $user_address = wpinv_get_user_address( $user_id, false );
    
    $country = empty( $user_address ) || !isset( $user_address['country'] ) || empty( $user_address['country'] ) ? $country : $user_address['country'];

    $result = apply_filters( 'wpinv-user-country', $country, $user_id );

    if ( empty( $result ) ) {
        $result = wpinv_get_ip_country();
    }

    return $result;
}
add_filter( 'wpinv-get-country', 'wpinv_user_country', 10 );

function wpinv_set_user_country( $country = '', $user_id = 0 ) {
    global $wpi_userID;
    
    if ( empty($country) && !empty($wpi_userID) && get_current_user_id() != $wpi_userID ) {
        $country = wpinv_get_default_country();
    }
    
    return $country;
}
add_filter( 'wpinv-user-country', 'wpinv_set_user_country', 10 );

function wpinv_user_company( $company = '', $user_id = 0 ) {
    if ( empty( $user_id ) ) {
        $user_id = get_current_user_id();
    }

    $company = empty( $user_id ) ? "" : get_user_meta( $user_id, '_wpinv_company', true );
    $result = apply_filters( 'wpinv_user_company', $company, $user_id );

    return $result;
}

function wpinv_save_user_vat_details( $company = '', $vat_number = '' ) {
    $save = apply_filters( 'wpinv_allow_save_user_vat_details', true );

    if ( is_user_logged_in() && $save ) {
        $user_id = get_current_user_id();

        if ( !empty( $vat_number ) ) {
            update_user_meta( $user_id, '_wpinv_vat_number', $vat_number );
        } else {
            delete_user_meta( $user_id, '_wpinv_vat_number');
        }

        if ( !empty( $company ) ) {
            update_user_meta( $user_id, '_wpinv_company', $company );
        } else {
            delete_user_meta( $user_id, '_wpinv_company');
            delete_user_meta( $user_id, '_wpinv_vat_number');
        }
    }

    do_action('wpinv_save_user_vat_details', $company, $vat_number);
}

function wpinv_checkout_vat_validate( $valid_data, $post ) {
    global $wpinv_options, $wpi_session, $wpinv_euvat;
    
    $vat_name  = __( wpinv_owner_get_vat_name(), 'invoicing' );
    
    if ( !isset( $_POST['_wpi_nonce'] ) || !wp_verify_nonce( $_POST['_wpi_nonce'], 'vat_validation' ) ) {
        wpinv_set_error( 'vat_validation', wp_sprintf( __( "Invalid %s validation request. You are cheating.", 'invoicing' ), $vat_name ) );
        return;
    }
    
    $wpi_session->set( 'user_vat_data', null );
    
    $invoice        = wpinv_get_invoice_cart();
    $amount         = $invoice->get_total();
    $is_digital     = wpinv_invoice_has_digital_item( $invoice );
    $no_vat         = !$wpinv_euvat->requires_vat( 0, false, $is_digital );
    
    $company        = !empty( $_POST['wpinv_company'] ) ? $_POST['wpinv_company'] : null;
    $vat_number     = !empty( $_POST['wpinv_vat_number'] ) ? $_POST['wpinv_vat_number'] : null;
    $country        = !empty( $_POST['wpinv_country'] ) ? $_POST['wpinv_country'] : $invoice->country;
    if ( empty( $country ) ) {
        $country = apply_filters( 'wpinv-get-country', !empty( $wpinv_options['vat_ip_country_default'] ) ? '' : wpinv_get_default_country() );
    }
    
    if ( !$is_digital && $no_vat ) {
        return;
    }
        
    $vat_data           = array( 'company' => '', 'number' => '', 'valid' => false );
    
    $ip_country_code    = wpinv_get_ip_country();
    $is_eu_state        = $wpinv_euvat->is_eu_state( $country );
    $is_eu_state_ip     = $wpinv_euvat->is_eu_state( $ip_country_code );
    $is_non_eu_user     = !$is_eu_state && !$is_eu_state_ip;
    
    if ( $is_digital && !$is_non_eu_user && empty( $vat_number ) && apply_filters( 'wpinv_checkout_requires_country', true, $amount ) ) {
        $vat_data['adddress_confirmed'] = false;
        
        if ( !isset( $_POST['wpinv_adddress_confirmed'] ) ) {
            if ( $ip_country_code != $country ) {
                wpinv_set_error( 'vat_validation', sprintf( __( 'The country of your current location must be the same as the country of your billing location or you must %s confirm %s the billing address is your home country.', 'invoicing' ), '<a href="#wpi-ip-country">', '</a>' ) );
            }
        } else {
            $vat_data['adddress_confirmed'] = true;
        }
    }
    
    if ( !empty( $wpinv_options['vat_prevent_b2c_purchase'] ) && !$is_non_eu_user && ( empty( $vat_number ) || $no_vat ) ) {
        if ( $is_eu_state ) {
            wpinv_set_error( 'vat_validation', wp_sprintf( __( 'Please enter and validate your %s number to verify your purchase is by an EU business.', 'invoicing' ), $vat_name ) );
        } else if ( $is_digital && $is_eu_state_ip ) {
            wpinv_set_error( 'vat_validation', wp_sprintf( __( 'Sales to non-EU countries cannot be completed because %s must be applied.', 'invoicing' ), $vat_name ) );
        }
    }
    
    if ( !$is_eu_state || $no_vat || empty( $vat_number ) ) {
        return;
    }

    if ( $company !== null ) {
        $vat_data['company'] = $company;
    }

    $message = '';
    if ( $vat_number !== null ) {
        $vat_data['number'] = $vat_number;
        $valid              = false;
    
        if ( !empty( $wpinv_options['vat_vies_check'] ) ) {
            $valid = true;
            
            if ( empty( $wpinv_options['vat_offline_check'] ) && !$wpinv_euvat->offline_check( $vat_number ) ) {
                $valid = false;
            }
        } else {
            $result = wpinv_check_vat( $vat_number );
            
            if ( !empty( $result['valid'] ) ) {                
                $vies_company = !empty( $result['company'] ) ? $result['company'] : '';
                $vies_company = apply_filters( 'wpinv_vies_company_name', $vies_company );
            
                $valid_company = $vies_company && $company && ( $vies_company == '---' || strcasecmp( trim( $vies_company ), trim( $company ) ) == 0 ) ? true : false;

                if ( !empty( $wpinv_options['vat_disable_company_name_check'] ) || $valid_company ) {
                    $valid = true;
                } else {           
                    $valid = false;
                    
                    $message = wp_sprintf( __( 'The company name associated with the %s number provided is not the same as the company name provided.', 'invoicing' ), $vat_name );
                }
            } else {
                $message = wp_sprintf( __( 'Fail to validate the %s number: EU Commission VAT server (VIES) check fails.', 'invoicing' ), $vat_name );
            }
        }
        $vat_data['valid']  = $valid;
        
        if ( !$vat_data['valid'] ) {
            $error = wp_sprintf( __( 'The %s %s number %s you have entered has not been validated', 'invoicing' ), '<a href="#wpi-vat-details">', $vat_name, '</a>' ) . ( $message ? ' ( ' . $message . ' )' : '' );
            wpinv_set_error( 'vat_validation', $error );
        }
    }

    $wpi_session->set( 'user_vat_data', $vat_data );
}
add_action( 'wpinv_checkout_error_checks', 'wpinv_checkout_vat_validate', 10, 2 );

function wpinv_recalculated_tax() {
    define( 'WPINV_RECALCTAX', true );
}
add_action( 'wp_ajax_wpinv_recalculate_tax', 'wpinv_recalculated_tax', 1 );

function wpinv_recalculate_tax( $return = false ) {
    $invoice_id = (int)wpinv_get_invoice_cart_id();
    if ( empty( $invoice_id ) ) {
        return false;
    }
    
    $invoice = wpinv_get_invoice_cart( $invoice_id );

    if ( empty( $invoice ) ) {
        return false;
    }

    if ( empty( $_POST['country'] ) ) {
        $_POST['country'] = !empty($invoice->country) ? $invoice->country : wpinv_get_default_country();
    }
        
    $invoice->country = sanitize_text_field($_POST['country']);
    $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
    if (isset($_POST['state'])) {
        $invoice->state = sanitize_text_field($_POST['state']);
        $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );
    }

    $invoice->cart_details  = wpinv_get_cart_content_details();
    
    $subtotal               = wpinv_get_cart_subtotal( $invoice->cart_details );
    $tax                    = wpinv_get_cart_tax( $invoice->cart_details );
    $total                  = wpinv_get_cart_total( $invoice->cart_details );

    $invoice->tax           = $tax;
    $invoice->subtotal      = $subtotal;
    $invoice->total         = $total;

    $invoice->save();
    
    $response = array(
        'total'        => html_entity_decode( wpinv_price( wpinv_format_amount( $total ) ), ENT_COMPAT, 'UTF-8' ),
        'total_raw'    => $total,
        'html'         => wpinv_checkout_cart( $invoice->cart_details, false ),
    );
    
    if ( $return ) {
        return $response;
    }

    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_recalculate_tax', 'wpinv_recalculate_tax' );
add_action( 'wp_ajax_nopriv_wpinv_recalculate_tax', 'wpinv_recalculate_tax' );

function wpinv_invoice_show_vat_info( $invoice ) {
    if ( empty( $invoice ) ) {
        return NULL;
    }
    
    $vat_name   = wpinv_owner_get_vat_name();
    $vat_number = wpinv_owner_vat_number();
    $company    = wpinv_owner_vat_company_name();
    if ( $vat_number || $company ) {
    ?>
    <div class="row wpinv-vat-info">
        <div class="col-sm-12">
            <strong><?php echo wp_sprintf( __( '%s Info', 'invoicing' ), $vat_name ); ?></strong>
            <?php if ( $vat_number ) { ?>
            <div class="vat-number"><span><?php echo wp_sprintf( __( '%s Number:', 'invoicing' ), $vat_name ); ?></span> <?php echo wpinv_owner_vat_number(); ?></div>
            <?php } if ( $company ) { ?>
            <div class="company"><span><?php echo __( 'Company:', 'invoicing' ); ?></span> <?php echo wpinv_owner_vat_company_name(); ?></div>
            <?php } ?>
        </div>
    </div>
    <?php
    }
}
//add_action( 'wpinv_invoice_print_after_line_items', 'wpinv_invoice_show_vat_info', 11, 1 );

function wpinv_invoice_show_vat_notice( $invoice ) {
    if ( empty( $invoice ) ) {
        return NULL;
    }
    
    $label      = wpinv_get_option( 'vat_invoice_notice_label' );
    $notice     = wpinv_get_option( 'vat_invoice_notice' );
    if ( $label || $notice ) {
    ?>
    <div class="row wpinv-vat-notice">
        <div class="col-sm-12">
            <?php if ( $label ) { ?>
            <strong><?php _e( $label, 'invoicing' ); ?></strong>
            <?php } if ( $notice ) { ?>
            <?php echo wpautop( wptexturize( __( $notice, 'invoicing' ) ) ) ?>
            <?php } ?>
        </div>
    </div>
    <?php
    }
}
add_action( 'wpinv_invoice_print_after_line_items', 'wpinv_invoice_show_vat_notice', 999, 1 );

function wpinv_tax_label( $label = '' ) {
    global $wpinv_euvat, $wpi_requires_vat;
    
    if ( !( $wpi_requires_vat !== 0 && $wpi_requires_vat ) ) {
        $wpi_requires_vat = $wpinv_euvat->requires_vat( 0, false );
    }
    
    return $wpi_requires_vat ? __( wpinv_owner_get_vat_name(), 'invoicing' ) : ( $label ? $label : __( 'Tax', 'invoicing' ) );
}