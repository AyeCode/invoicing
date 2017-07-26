<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_use_taxes() {
    $ret = wpinv_get_option( 'enable_taxes', false );
    
    return (bool) apply_filters( 'wpinv_use_taxes', $ret );
}

function wpinv_get_tax_rates() {
    $rates = get_option( 'wpinv_tax_rates', array() );
    
    return apply_filters( 'wpinv_get_tax_rates', $rates );
}

function wpinv_get_tax_rate( $country = false, $state = false, $item_id = 0 ) {
    global $wpinv_euvat, $wpi_tax_rates, $wpi_userID;
    $wpi_tax_rates = !empty( $wpi_tax_rates ) ? $wpi_tax_rates : array();
    
    if ( !empty( $wpi_tax_rates ) && !empty( $item_id ) && isset( $wpi_tax_rates[$item_id] ) ) {
        return $wpi_tax_rates[$item_id];
    }
    
    if ( !$wpinv_euvat->item_is_taxable( $item_id, $country, $state ) ) {
        $wpi_tax_rates[$item_id] = 0;
        return 0;
    }
    
    $is_global = false;
    if ( $item_id == 'global' ) {
        $is_global = true;
        $item_id = 0;
    }
    
    $rate           = (float)wpinv_get_option( 'tax_rate', 0 );
    $user_address   = wpinv_get_user_address( $wpi_userID );
    
    if( empty( $country ) ) {
        if( !empty( $_POST['wpinv_country'] ) ) {
            $country = $_POST['wpinv_country'];
        } elseif( !empty( $_POST['wpinv_country'] ) ) {
            $country = $_POST['wpinv_country'];
        } elseif( !empty( $_POST['country'] ) ) {
            $country = $_POST['country'];
        } elseif( is_user_logged_in() && !empty( $user_address ) ) {
            $country = $user_address['country'];
        }
        $country = !empty( $country ) ? $country : wpinv_get_default_country();
    }

    if( empty( $state ) ) {
        if( !empty( $_POST['wpinv_state'] ) ) {
            $state = $_POST['wpinv_state'];
        } elseif( !empty( $_POST['wpinv_state'] ) ) {
            $state = $_POST['wpinv_state'];
        } elseif( !empty( $_POST['state'] ) ) {
            $state = $_POST['state'];
        } elseif( is_user_logged_in() && !empty( $user_address ) ) {
            $state = $user_address['state'];
        }
        $state = !empty( $state ) ? $state : wpinv_get_default_state();
    }
    
    if( !empty( $country ) ) {
        $tax_rates   = wpinv_get_tax_rates();

        if( !empty( $tax_rates ) ) {
            // Locate the tax rate for this country / state, if it exists
            foreach( $tax_rates as $key => $tax_rate ) {
                if( $country != $tax_rate['country'] )
                    continue;

                if( !empty( $tax_rate['global'] ) ) {
                    if( !empty( $tax_rate['rate'] ) ) {
                        $rate = number_format( $tax_rate['rate'], 4 );
                    }
                } else {

                    if( empty( $tax_rate['state'] ) || strtolower( $state ) != strtolower( $tax_rate['state'] ) )
                        continue;

                    $state_rate = $tax_rate['rate'];
                    if( 0 !== $state_rate || !empty( $state_rate ) ) {
                        $rate = number_format( $state_rate, 4 );
                    }
                }
            }
        }
    }
    
    $rate = apply_filters( 'wpinv_tax_rate', $rate, $country, $state, $item_id );
    
    if ( !empty( $item_id ) ) {
        $wpi_tax_rates[$item_id] = $rate;
    } else if ( $is_global ) {
        $wpi_tax_rates['global'] = $rate;
    }
    
    return $rate;
}

function wpinv_get_formatted_tax_rate( $country = false, $state = false, $item_id ) {
    $rate = wpinv_get_tax_rate( $country, $state, $item_id );
    $rate = round( $rate, 4 );
    $formatted = $rate .= '%';
    return apply_filters( 'wpinv_formatted_tax_rate', $formatted, $rate, $country, $state, $item_id );
}

function wpinv_calculate_tax( $amount = 0, $country = false, $state = false, $item_id = 0 ) {
    $rate = wpinv_get_tax_rate( $country, $state, $item_id );
    $tax  = 0.00;

    if ( wpinv_use_taxes() ) {        
        if ( wpinv_prices_include_tax() ) {
            $pre_tax = ( $amount / ( ( 1 + $rate ) * 0.01 ) );
            $tax     = $amount - $pre_tax;
        } else {
            $tax = $amount * $rate * 0.01;
        }

    }

    return apply_filters( 'wpinv_taxed_amount', $tax, $rate, $country, $state, $item_id );
}

function wpinv_prices_include_tax() {
    return false; // TODO
    $ret = ( wpinv_get_option( 'prices_include_tax', false ) == 'yes' && wpinv_use_taxes() );

    return apply_filters( 'wpinv_prices_include_tax', $ret );
}

function wpinv_sales_tax_for_year( $year = null ) {
    return wpinv_price( wpinv_format_amount( wpinv_get_sales_tax_for_year( $year ) ) );
}

function wpinv_get_sales_tax_for_year( $year = null ) {
    global $wpdb;

    // Start at zero
    $tax = 0;

    if ( ! empty( $year ) ) {
        $args = array(
            'post_type'      => 'wpi_invoice',
            'post_status'    => array( 'publish' ),
            'posts_per_page' => -1,
            'year'           => $year,
            'fields'         => 'ids'
        );

        $payments    = get_posts( $args );
        $payment_ids = implode( ',', $payments );

        if ( count( $payments ) > 0 ) {
            $sql = "SELECT SUM( meta_value ) FROM $wpdb->postmeta WHERE meta_key = '_wpinv_tax' AND post_id IN( $payment_ids )";
            $tax = $wpdb->get_var( $sql );
        }

    }

    return apply_filters( 'wpinv_get_sales_tax_for_year', $tax, $year );
}

function wpinv_is_cart_taxed() {
    return wpinv_use_taxes();
}

function wpinv_prices_show_tax_on_checkout() {
    return false; // TODO
    $ret = ( wpinv_get_option( 'checkout_include_tax', false ) == 'yes' && wpinv_use_taxes() );

    return apply_filters( 'wpinv_taxes_on_prices_on_checkout', $ret );
}

function wpinv_display_tax_rate() {
    $ret = wpinv_use_taxes() && wpinv_get_option( 'display_tax_rate', false );

    return apply_filters( 'wpinv_display_tax_rate', $ret );
}

function wpinv_cart_needs_tax_address_fields() {
    if( !wpinv_is_cart_taxed() )
        return false;

    return ! did_action( 'wpinv_after_cc_fields', 'wpinv_default_cc_address_fields' );
}

function wpinv_item_is_tax_exclusive( $item_id = 0 ) {
    $ret = (bool)get_post_meta( $item_id, '_wpinv_tax_exclusive', false );
    return apply_filters( 'wpinv_is_tax_exclusive', $ret, $item_id );
}

function wpinv_currency_decimal_filter( $decimals = 2 ) {
    $currency = wpinv_get_currency();

    switch ( $currency ) {
        case 'RIAL' :
        case 'JPY' :
        case 'TWD' :
        case 'HUF' :
            $decimals = 0;
            break;
    }

    return apply_filters( 'wpinv_currency_decimal_count', $decimals, $currency );
}

function wpinv_tax_amount() {
    $output = 0.00;
    
    return apply_filters( 'wpinv_tax_amount', $output );
}

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
    
    if ( $invoice->is_free_trial() ) {
        $total = 0;
    }
    
    $response = array(
        'total'        => html_entity_decode( wpinv_price( wpinv_format_amount( $total ) ), ENT_COMPAT, 'UTF-8' ),
        'total_raw'    => $total,
        'free'         => !( (float)$total > 0 ) && $invoice->is_free() ? true : false,
        'html'         => wpinv_checkout_cart( $invoice->cart_details, false ),
    );
    
    if ( $return ) {
        return $response;
    }

    wp_send_json( $response );
}
add_action( 'wp_ajax_wpinv_recalculate_tax', 'wpinv_recalculate_tax' );
add_action( 'wp_ajax_nopriv_wpinv_recalculate_tax', 'wpinv_recalculate_tax' );

// VAT Settings
function wpinv_vat_rate_add_callback( $args ) {
    ?>
    <p class="wpi-vat-rate-actions"><input id="wpi_vat_rate_add" type="button" value="<?php esc_attr_e( 'Add', 'invoicing' );?>" class="button button-primary" />&nbsp;&nbsp;<i style="display:none;" class="fa fa-refresh fa-spin"></i></p>
    <?php
}

function wpinv_vat_rate_delete_callback( $args ) {
    global $wpinv_euvat;
    
    $vat_classes = $wpinv_euvat->get_rate_classes();
    $vat_class = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '';
    if ( isset( $vat_classes[$vat_class] ) ) {
    ?>
    <p class="wpi-vat-rate-actions"><input id="wpi_vat_rate_delete" type="button" value="<?php echo wp_sprintf( esc_attr__( 'Delete class "%s"', 'invoicing' ), $vat_classes[$vat_class] );?>" class="button button-primary" />&nbsp;&nbsp;<i style="display:none;" class="fa fa-refresh fa-spin"></i></p>
    <?php
    }
}

function wpinv_vat_rates_callback( $args ) {
    global $wpinv_euvat;
    
    $vat_classes    = $wpinv_euvat->get_rate_classes();
    $vat_class      = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '_standard';
    
    $eu_states      = $wpinv_euvat->get_eu_states();
    $countries      = wpinv_get_country_list();
    $vat_groups     = $wpinv_euvat->get_vat_groups();
    $rates          = $wpinv_euvat->get_vat_rates( $vat_class );
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
            <td class="wpinv_vat_rate"><input type="number" class="small-text" step="any" min="0" max="99" name="vat_rates[<?php echo $state;?>][rate]" value="<?php echo $vat_rate; ?>" /></td>
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

function wpinv_vat_number_callback( $args ) {
    global $wpinv_euvat;
    
    $vat_number     = $wpinv_euvat->get_vat_number();
    $vat_valid      = $wpinv_euvat->is_vat_validated();

    $size           = ( isset( $args['size'] ) && !is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $validated_text = $vat_valid ? __( 'VAT number validated', 'invoicing' ) : __( 'VAT number not validated', 'invoicing' );
    $disabled       = $vat_valid ? 'disabled="disabled"' : " ";
    
    $html = '<input type="text" class="' . $size . '-text" id="wpinv_settings[' . $args['id'] . ']" name="wpinv_settings[' . $args['id'] . ']" placeholder="GB123456789" value="' . esc_attr( stripslashes( $vat_number ) ) . '"/>';
    $html .= '<span>&nbsp;<input type="button" id="wpinv_vat_validate" class="wpinv_validate_vat_button button-secondary" ' . $disabled . ' value="' . esc_attr__( 'Validate VAT Number', 'invoicing' ) . '" /></span>';
    $html .= '<span class="wpinv-vat-stat wpinv-vat-stat-' . (int)$vat_valid . '"><i class="fa"></i> <font>' . $validated_text . '</font></span>';
    $html .= '<label for="wpinv_settings[' . $args['id'] . ']">' . '<p>' . __( 'Enter your VAT number including country identifier, eg: GB123456789 (Settings must be saved after validation)', 'invoicing' ).'</p>' . '</label>';
    $html .= '<input type="hidden" name="_wpi_nonce" value="' . wp_create_nonce( 'vat_validation' ) . '">';

    echo $html;
}

function wpinv_eu_fallback_rate_callback( $args ) {
    global $wpinv_options;

    $value = isset( $wpinv_options[$args['id']] ) ? $wpinv_options[ $args['id'] ] : ( isset( $args['std'] ) ? $args['std'] : '' );
    $size = ( isset( $args['size'] ) && !is_null( $args['size'] ) ) ? $args['size'] : 'small';
    
    $html = '<input type="number" min="0" max="99" step="any" class="' . $size . '-text" id="wpinv_settings_' . $args['section'] . '_' . $args['id'] . '" name="wpinv_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" />';
    $html .= '<span>&nbsp;<input id="wpi_add_eu_states" type="button" class="button-secondary" value="' . esc_attr__( 'Add EU Member States', 'invoicing' ) . '" /></span>';
    $html .= '<span>&nbsp;<input id="wpi_remove_eu_states" type="button" class="button-secondary" value="' . esc_attr__( 'Remove EU Member States', 'invoicing' ) . '" /></span>';
    $html .= '<span>&nbsp;<input id="wpi_vat_get_rates" type="button" class="button-secondary" value="' . esc_attr__( 'Update EU VAT Rates', 'invoicing' ) . '" />&nbsp;&nbsp;<i style="display:none" class="fa fa-refresh fa-spin"></i></span>';
    $html .= '<p><label for="wpinv_settings_' . $args['section'] . '_' . $args['id'] . '">' . $args['desc'] . '</label></p>';
    echo $html;
    ?>
    <span id="wpinv-rates-error-wrap" class="wpinv_errors" style="display:none;"></span>
    <?php
}

function wpinv_vat_ip_lookup_callback( $args ) {
    global $wpinv_options, $wpinv_euvat;

    $value =  isset( $wpinv_options[ $args['id'] ] ) ? $wpinv_options[ $args['id'] ]  : ( isset( $args['std'] ) ? $args['std'] : 'default' );
    
    $options = array();
    if ( function_exists( 'geoip_country_code_by_name' ) ) {
        $options['geoip'] = __( 'PHP GeoIP extension', 'invoicing' );
    }
    
    $geoip2_database = $wpinv_euvat->geoip2_country_dbfile();
    
    if ( !function_exists( 'bcadd' ) ) {
        $geoip2_message = __( 'GeoIP2 service requires the BC Math PHP extension, it is not loaded in your version of PHP!', 'invoicing' );
    } else {
        $geoip2_message = ini_get('safe_mode') ? __( 'GeoIP2 is not supported with PHP safe mode enabled!', 'invoicing' ) : '';
    }
    
    if ( $geoip2_database !== false && empty( $geoip2_message ) ) {
        $options['geoip2'] = __( 'GeoIP2 Database', 'invoicing' );
    }
    
    if ( function_exists( 'simplexml_load_file' ) ) {
        $options['geoplugin'] = __( 'geoPlugin Web Service', 'invoicing' );
    }
    
    $options['site']    = __( 'Use default country', 'invoicing' );
    $options['default'] = __( 'Auto', 'invoicing' );

    $html = wpinv_html_select( array(
        'name'             => "wpinv_settings[{$args['id']}]",
        'selected'         => $value,
        'id'               => "wpinv_settings[{$args['id']}]",
        'class'            => isset($args['class']) ? $args['class'] : "",
        'options'          => $options,
        'multiple'         => false,
        'chosen'           => false,
        'show_option_all'  => false,
        'show_option_none' => false
    ));
    
    $desc = '<label for="wpinv_settings[' . $args['id'] . ']">';
    $desc .= __( 'Select the option Invoicing should use to determine the country from the IP address of the user.', 'invoicing' );
    $desc .= '<p>';
    if ( empty( $geoip2_message ) ) {
        if ( $geoip2_database ) {
            $last_updated = '';
            if ( $time_updated = wpinv_get_option( 'wpinv_geoip2_date_updated' ) ) {
                $date_updated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time_updated );
                $last_updated = '<br>' . sprintf( __( 'The GeoIP2 database was last updated on: <b>%s</b>', 'invoicing' ), $date_updated );
            }
            $desc .= __(  'GeoIP2 database exists:', 'invoicing' ) . $last_updated . '&nbsp;<input type="button" id="wpi_geoip2" action="update" class="wpinv-refresh-geoip2-btn button-secondary" value="' . __( 'Update GeoIP2 database now (~55MB)', 'invoicing' ) . '"></input>';
        } else {
            $desc .= __( 'GeoIP2 database does not exist:', 'invoicing' ) . '&nbsp;<input type="button" id="wpi_geoip2" action="download" class="wpinv-download-geoip2-btn button-secondary" value="' . __( 'Download GeoIP2 database now', 'invoicing' ) . ' (~53MB)"></input><br>' . __(  'After downloading the GeoIP2 database the GeoIP2 lookup option will show.', 'invoicing' );
        }
    } else {
        $desc .= $geoip2_message;
    }
    $desc .= '</p><p>'. __( 'GeoPlugin is a great free service please consider supporting them: ', 'invoicing' ) . ' <a href="http://www.geoplugin.com/" target="_blank">GeoPlugin.com</a></p>';
    $desc .= '</label>';
    
    $html .= $desc;

    echo $html;
    ?>
    <span id="wpinv-geoip2-errors" class="wpinv_errors" style="display:none;padding:4px;"></span>
    <?php
}
