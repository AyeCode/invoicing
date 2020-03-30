<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_get_option( $key = '', $default = false ) {
    global $wpinv_options;

    $value = isset( $wpinv_options[ $key ] ) ? $wpinv_options[ $key ] : $default;
    $value = apply_filters( 'wpinv_get_option', $value, $key, $default );

    return apply_filters( 'wpinv_get_option_' . $key, $value, $key, $default );
}

function wpinv_update_option( $key = '', $value = false ) {
    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    if ( empty( $value ) ) {
        $remove_option = wpinv_delete_option( $key );
        return $remove_option;
    }

    // First let's grab the current settings
    $options = get_option( 'wpinv_settings' );

    // Let other plugin alter the value
    $value = apply_filters( 'wpinv_update_option', $value, $key );

    // Next let's try to update the value
    $options[ $key ] = $value;
    $did_update = update_option( 'wpinv_settings', $options );

    // If it's updated, let's update the global variable
    if ( $did_update ) {
        global $wpinv_options;
        $wpinv_options[ $key ] = $value;
    }

    return $did_update;
}

function wpinv_delete_option( $key = '' ) {
    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    // First let's grab the current settings
    $options = get_option( 'wpinv_settings' );

    // Next let's try to update the value
    if( isset( $options[ $key ] ) ) {
        unset( $options[ $key ] );
    }

    $did_update = update_option( 'wpinv_settings', $options );

    // If it updated, let's update the global variable
    if ( $did_update ){
        global $wpinv_options;
        $wpinv_options = $options;
    }

    return $did_update;
}

function wpinv_get_settings() {
    $settings = get_option( 'wpinv_settings' );

    if ( empty( $settings ) ) {
        // Update old settings with new single option
        $general_settings   = is_array( get_option( 'wpinv_settings_general' ) )    ? get_option( 'wpinv_settings_general' )    : array();
        $gateways_settings  = is_array( get_option( 'wpinv_settings_gateways' ) )   ? get_option( 'wpinv_settings_gateways' )   : array();
        $checkout_settings  = is_array( get_option( 'wpinv_settings_checkout' ) )   ? get_option( 'wpinv_settings_checkout' )   : array();
        $email_settings     = is_array( get_option( 'wpinv_settings_emails' ) )     ? get_option( 'wpinv_settings_emails' )     : array();
        $tax_settings       = is_array( get_option( 'wpinv_settings_taxes' ) )      ? get_option( 'wpinv_settings_taxes' )      : array();
        $misc_settings      = is_array( get_option( 'wpinv_settings_misc' ) )       ? get_option( 'wpinv_settings_misc' )       : array();
        $tool_settings      = is_array( get_option( 'wpinv_settings_tools' ) )      ? get_option( 'wpinv_settings_tools' )      : array();

        $settings = array_merge( $general_settings, $gateways_settings, $checkout_settings, $email_settings, $tax_settings, $misc_settings, $tool_settings );

        update_option( 'wpinv_settings', $settings );

    }
    return apply_filters( 'wpinv_get_settings', $settings );
}

function wpinv_register_settings() {
    if ( false == get_option( 'wpinv_settings' ) ) {
        add_option( 'wpinv_settings' );
    }
    
    $register_settings = wpinv_get_registered_settings();
    
    foreach ( $register_settings as $tab => $sections ) {
        foreach ( $sections as $section => $settings) {
            // Check for backwards compatibility
            $section_tabs = wpinv_get_settings_tab_sections( $tab );
            if ( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
                $section = 'main';
                $settings = $sections;
            }

            add_settings_section(
                'wpinv_settings_' . $tab . '_' . $section,
                __return_null(),
                '__return_false',
                'wpinv_settings_' . $tab . '_' . $section
            );

            foreach ( $settings as $option ) {
                // For backwards compatibility
                if ( empty( $option['id'] ) ) {
                    continue;
                }

                $name = isset( $option['name'] ) ? $option['name'] : '';

                add_settings_field(
                    'wpinv_settings[' . $option['id'] . ']',
                    $name,
                    function_exists( 'wpinv_' . $option['type'] . '_callback' ) ? 'wpinv_' . $option['type'] . '_callback' : 'wpinv_missing_callback',
                    'wpinv_settings_' . $tab . '_' . $section,
                    'wpinv_settings_' . $tab . '_' . $section,
                    array(
                        'section'     => $section,
                        'id'          => isset( $option['id'] )          ? $option['id']          : null,
                        'desc'        => ! empty( $option['desc'] )      ? $option['desc']        : '',
                        'name'        => isset( $option['name'] )        ? $option['name']        : null,
                        'size'        => isset( $option['size'] )        ? $option['size']        : null,
                        'options'     => isset( $option['options'] )     ? $option['options']     : '',
                        'selected'    => isset( $option['selected'] )    ? $option['selected']    : null,
                        'std'         => isset( $option['std'] )         ? $option['std']         : '',
                        'min'         => isset( $option['min'] )         ? $option['min']         : null,
                        'max'         => isset( $option['max'] )         ? $option['max']         : null,
                        'step'        => isset( $option['step'] )        ? $option['step']        : null,
                        'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
                        'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true,
                        'readonly'    => isset( $option['readonly'] )    ? $option['readonly']    : false,
                        'faux'        => isset( $option['faux'] )        ? $option['faux']        : false,
                        'onchange'    => !empty( $option['onchange'] )   ? $option['onchange']    : '',
                        'custom'      => !empty( $option['custom'] )     ? $option['custom']      : '',
                        'class'       =>  !empty( $option['class'] )     ? $option['class']      : '',
                        'cols'        => !empty( $option['cols'] ) && (int)$option['cols'] > 0 ? (int)$option['cols'] : 50,
                        'rows'        => !empty( $option['rows'] ) && (int)$option['rows'] > 0 ? (int)$option['rows'] : 5,
                    )
                );
            }
        }
    }

    // Creates our settings in the options table
    register_setting( 'wpinv_settings', 'wpinv_settings', 'wpinv_settings_sanitize' );
}
add_action( 'admin_init', 'wpinv_register_settings' );

/**
 * Returns an array of registered settings.
 */
function wpinv_get_registered_settings() {
    return apply_filters( 'wpinv_registered_settings', wpinv_get_data( 'registered-settings' ) );
}

function wpinv_settings_sanitize( $input = array() ) {
    global $wpinv_options;

    if ( empty( wp_get_raw_referer() ) ) {
        return $input;
    }

    wp_parse_str( wp_get_raw_referer(), $referrer );

    $settings = wpinv_get_registered_settings();
    $tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
    $section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

    $input = $input ? $input : array();
    $input = apply_filters( 'wpinv_settings_tab_' . $tab . '_sanitize', $input );
    $input = apply_filters( 'wpinv_settings_' . $tab . '-' . $section . '_sanitize', $input );

    // Loop through each setting being saved and pass it through a sanitization filter
    foreach ( $input as $key => $value ) {

        // Get the setting type (checkbox, select, etc)
        $type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;

        if ( ! $type ) {
            $type = isset( $settings[ $tab ][ $section ][ $key ]['type'] ) ? $settings[ $tab ][ $section ][ $key ]['type'] : false;
        }

        if ( $type ) {
            // Field type specific filter
            $input[$key] = apply_filters( 'wpinv_settings_sanitize_' . $type, $value, $key );
        }

        // General filter
        $input[ $key ] = apply_filters( 'wpinv_settings_sanitize', $input[ $key ], $key );
    }

    // Loop through the whitelist and unset any that are empty for the tab being saved
    $main_settings    = $section == 'main' ? $settings[ $tab ] : array(); // Check for extensions that aren't using new sections
    $section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();

    $found_settings = array_merge( $main_settings, $section_settings );

    if ( ! empty( $found_settings ) ) {
        foreach ( $found_settings as $key => $value ) {

            // settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
            if ( is_numeric( $key ) ) {
                $key = $value['id'];
            }

            if ( empty( $input[ $key ] ) ) {
                unset( $wpinv_options[ $key ] );
            }
        }
    }

    // Merge our new settings with the existing
    $output = array_merge( $wpinv_options, $input );

    add_settings_error( 'wpinv-notices', '', __( 'Settings updated.', 'invoicing' ), 'updated' );

    return $output;
}

function wpinv_settings_sanitize_misc_accounting( $input ) {
    global $wpi_session;

    if ( ! wpinv_current_user_can_manage_invoicing() ) {
        return $input;
    }

    if( ! empty( $input['enable_sequential'] ) && !wpinv_get_option( 'enable_sequential' ) ) {
        // Shows an admin notice about upgrading previous order numbers
        $wpi_session->set( 'upgrade_sequential', '1' );
    }

    return $input;
}
add_filter( 'wpinv_settings_misc-accounting_sanitize', 'wpinv_settings_sanitize_misc_accounting' );

function wpinv_settings_sanitize_tax_rates( $input ) {
    if( ! wpinv_current_user_can_manage_invoicing() ) {
        return $input;
    }

    $new_rates = !empty( $_POST['tax_rates'] ) ? array_values( $_POST['tax_rates'] ) : array();

    $tax_rates = array();

    if ( !empty( $new_rates ) ) {
        foreach ( $new_rates as $rate ) {
            if ( isset( $rate['country'] ) && empty( $rate['country'] ) && empty( $rate['state'] ) ) {
                continue;
            }
            
            $rate['rate'] = wpinv_sanitize_amount( $rate['rate'], 4 );
            
            $tax_rates[] = $rate;
        }
    }

    update_option( 'wpinv_tax_rates', $tax_rates );

    return $input;
}
add_filter( 'wpinv_settings_taxes-rates_sanitize', 'wpinv_settings_sanitize_tax_rates' );

function wpinv_sanitize_text_field( $input ) {
    return trim( $input );
}
add_filter( 'wpinv_settings_sanitize_text', 'wpinv_sanitize_text_field' );

/**
 * Sanitizes checkout fields
 */
function wpinv_sanitize_checkout_fields_field( $input ) {

    // Checkout fields are json encoded.
    if ( is_string( $input ) ) {
        $input = json_decode( $input, true );
    }

    // Ensure that we have an array.
    if ( ! is_array( $input ) ) {
        $input = wpinv_get_default_checkout_fields();
    }

    return $input;
}
add_filter( 'wpinv_settings_sanitize_checkout_fields', 'wpinv_sanitize_checkout_fields_field' );

function wpinv_get_settings_tabs() {
    $tabs             = array();
    $tabs['general']  = __( 'General', 'invoicing' );
    $tabs['gateways'] = __( 'Payment Gateways', 'invoicing' );
    $tabs['checkout'] = __( 'Checkout', 'invoicing' );
    $tabs['taxes']    = __( 'Taxes', 'invoicing' );
    $tabs['emails']   = __( 'Emails', 'invoicing' );
    $tabs['privacy']  = __( 'Privacy', 'invoicing' );
    $tabs['misc']     = __( 'Misc', 'invoicing' );
    $tabs['tools']    = __( 'Tools', 'invoicing' );

    return apply_filters( 'wpinv_settings_tabs', $tabs );
}

function wpinv_get_settings_tab_sections( $tab = false ) {
    $tabs     = false;
    $sections = wpinv_get_registered_settings_sections();

    if( $tab && ! empty( $sections[ $tab ] ) ) {
        $tabs = $sections[ $tab ];
    } else if ( $tab ) {
        $tabs = false;
    }

    return $tabs;
}

function wpinv_get_registered_settings_sections() {
    static $sections = false;

    if ( false !== $sections ) {
        return $sections;
    }

    $sections = array(
        'general' => apply_filters( 'wpinv_settings_sections_general', array(
            'main' => __( 'General Settings', 'invoicing' ),
            'currency_section' => __( 'Currency Settings', 'invoicing' ),
            'labels' => __( 'Label Texts', 'invoicing' ),
        ) ),
        'gateways' => apply_filters( 'wpinv_settings_sections_gateways', array(
            'main' => __( 'Gateway Settings', 'invoicing' ),
        ) ),
        'checkout' => apply_filters( 'wpinv_settings_sections_checkout', array(
            'main' => __( 'Checkout Settings', 'invoicing' ),
        ) ),
        'taxes' => apply_filters( 'wpinv_settings_sections_taxes', array(
            'main' => __( 'Tax Settings', 'invoicing' ),
            'rates' => __( 'Tax Rates', 'invoicing' ),
        ) ),
        'emails' => apply_filters( 'wpinv_settings_sections_emails', array(
            'main' => __( 'Email Settings', 'invoicing' ),
        ) ),
        'privacy' => apply_filters( 'wpinv_settings_sections_privacy', array(
            'main' => __( 'Privacy policy', 'invoicing' ),
        ) ),
        'misc' => apply_filters( 'wpinv_settings_sections_misc', array(
            'main' => __( 'Miscellaneous', 'invoicing' ),
            'fields' => __( 'Fields Settings', 'invoicing' ),
            'custom-css' => __( 'Custom CSS', 'invoicing' ),
        ) ),
        'tools' => apply_filters( 'wpinv_settings_sections_tools', array(
            'main' => __( 'Diagnostic Tools', 'invoicing' ),
        ) ),
    );

    $sections = apply_filters( 'wpinv_settings_sections', $sections );

    return $sections;
}

function wpinv_get_pages( $with_slug = false, $default_label = NULL ) {
	$pages_options = array();

	if( $default_label !== NULL && $default_label !== false ) {
		$pages_options = array( '' => $default_label ); // Blank option
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$title = $with_slug ? $page->post_title . ' (' . $page->post_name . ')' : $page->post_title;
            $pages_options[ $page->ID ] = $title;
		}
	}

	return $pages_options;
}

function wpinv_header_callback( $args ) {
	if ( !empty( $args['desc'] ) ) {
        echo $args['desc'];
    }
}

function wpinv_hidden_callback( $args ) {
	global $wpinv_options;

	if ( isset( $args['set_value'] ) ) {
		$value = $args['set_value'];
	} elseif ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$html = '<input type="hidden" id="wpinv_settings[' . wpinv_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" />';
    
	echo $html;
}

function wpinv_checkbox_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="wpinv_settings[' . $sanitize_id . ']"';
	}

	$checked = isset( $wpinv_options[ $args['id'] ] ) ? checked( 1, $wpinv_options[ $args['id'] ], false ) : '';
	$html = '<input type="checkbox" id="wpinv_settings[' . $sanitize_id . ']"' . $name . ' value="1" ' . $checked . '/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_checkout_fields_callback( $args ) {
    include plugin_dir_path( __FILE__ ) . 'checkout-fields-template.php';
}

function wpinv_multicheck_callback( $args ) {
	global $wpinv_options;

	$sanitize_id = wpinv_sanitize_key( $args['id'] );
	$class = !empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

	if ( ! empty( $args['options'] ) ) {
		echo '<div class="wpi-mcheck-rows wpi-mcheck-' . $sanitize_id . $class . '">';
        foreach( $args['options'] as $key => $option ):
			$sanitize_key = wpinv_sanitize_key( $key );
			if ( isset( $wpinv_options[$args['id']][$sanitize_key] ) ) { 
				$enabled = $sanitize_key;
			} else { 
				$enabled = NULL; 
			}
			echo '<div class="wpi-mcheck-row"><input name="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="' . esc_attr( $sanitize_key ) . '" ' . checked( $sanitize_key, $enabled, false ) . '/>&nbsp;';
			echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . wp_kses_post( $option ) . '</label></div>';
		endforeach;
		echo '</div>';
		echo '<p class="description">' . $args['desc'] . '</p>';
	}
}

function wpinv_payment_icons_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ) {
            $sanitize_key = wpinv_sanitize_key( $key );
            
			if( isset( $wpinv_options[$args['id']][$key] ) ) {
				$enabled = $option;
			} else {
				$enabled = NULL;
			}

			echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" style="margin-right:10px;line-height:16px;height:16px;display:inline-block;">';

				echo '<input name="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="' . esc_attr( $option ) . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';

				if ( wpinv_string_is_image_url( $key ) ) {
					echo '<img class="payment-icon" src="' . esc_url( $key ) . '" style="width:32px;height:24px;position:relative;top:6px;margin-right:5px;"/>';
				} else {
					$card = strtolower( str_replace( ' ', '', $option ) );

					if ( has_filter( 'wpinv_accepted_payment_' . $card . '_image' ) ) {
						$image = apply_filters( 'wpinv_accepted_payment_' . $card . '_image', '' );
					} else {
						$image       = wpinv_locate_template( 'images' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . $card . '.gif', false );
						$content_dir = WP_CONTENT_DIR;

						if ( function_exists( 'wp_normalize_path' ) ) {
							// Replaces backslashes with forward slashes for Windows systems
							$image = wp_normalize_path( $image );
							$content_dir = wp_normalize_path( $content_dir );
						}

						$image = str_replace( $content_dir, content_url(), $image );
					}

					echo '<img class="payment-icon" src="' . esc_url( $image ) . '" style="width:32px;height:24px;position:relative;top:6px;margin-right:5px;"/>';
				}
			echo $option . '</label>';
		}
		echo '<p class="description" style="margin-top:16px;">' . wp_kses_post( $args['desc'] ) . '</p>';
	}
}

function wpinv_radio_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    
    foreach ( $args['options'] as $key => $option ) :
		$sanitize_key = wpinv_sanitize_key( $key );
        
        $checked = false;

		if ( isset( $wpinv_options[ $args['id'] ] ) && $wpinv_options[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $wpinv_options[ $args['id'] ] ) )
			$checked = true;

		echo '<input name="wpinv_settings[' . $sanitize_id . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="radio" value="' . $sanitize_key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . esc_html( $option ) . '</label><br/>';
	endforeach;

	echo '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';
}

function wpinv_gateways_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	foreach ( $args['options'] as $key => $option ) :
		$sanitize_key = wpinv_sanitize_key( $key );
        
        if ( isset( $wpinv_options['gateways'][ $key ] ) )
			$enabled = '1';
		else
			$enabled = null;

		echo '<input name="wpinv_settings[' . esc_attr( $args['id'] ) . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
		echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . esc_html( $option['admin_label'] ) . '</label><br/>';
	endforeach;
}

function wpinv_gateway_select_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    $class = !empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

	echo '<select name="wpinv_settings[' . $sanitize_id . ']"" id="wpinv_settings[' . $sanitize_id . ']" class="'.$class.'" >';

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $args['selected'] ) && $args['selected'] !== null && $args['selected'] !== false ) {
            $selected = selected( $key, $args['selected'], false );
        } else {
            $selected = isset( $wpinv_options[ $args['id'] ] ) ? selected( $key, $wpinv_options[$args['id']], false ) : '';
        }
		echo '<option value="' . wpinv_sanitize_key( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';
}

function wpinv_text_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}
	$class = !empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';

	$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html     = '<input type="text" class="' . sanitize_html_class( $size ) . '-text ' . $class . '" id="wpinv_settings[' . $sanitize_id . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>';
	$html    .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_number_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;
	$class = !empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . sanitize_html_class( $size ) . '-text ' . $class . '" id="wpinv_settings[' . $sanitize_id . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_textarea_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
    
    $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $class = ( isset( $args['class'] ) && ! is_null( $args['class'] ) ) ? $args['class'] : 'large-text';

	$html = '<textarea class="' . sanitize_html_class( $class ) . ' txtarea-' . sanitize_html_class( $size ) . ' wpi-' . esc_attr( sanitize_html_class( $sanitize_id ) ) . ' " cols="' . $args['cols'] . '" rows="' . $args['rows'] . '" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_password_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . sanitize_html_class( $size ) . '-text" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_missing_callback($args) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'invoicing' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

function wpinv_select_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
    
    if ( isset( $args['selected'] ) && $args['selected'] !== null && $args['selected'] !== false ) {
        $value = $args['selected'];
    }

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}
    
    if( !empty( $args['onchange'] ) ) {
        $onchange = ' onchange="' . esc_attr( $args['onchange'] ) . '"';
    } else {
        $onchange = '';
    }

    $class = !empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" class="'.$class.'"  name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" data-placeholder="' . esc_html( $placeholder ) . '"' . $onchange . ' />';

	foreach ( $args['options'] as $option => $name ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_color_select_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"/>';

	foreach ( $args['options'] as $option => $color ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $color['label'] ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_rich_editor_callback( $args ) {
	global $wpinv_options, $wp_version;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];

		if( empty( $args['allow_blank'] ) && empty( $value ) ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'wpinv_settings_' . esc_attr( $args['id'] ), array( 'textarea_name' => 'wpinv_settings[' . esc_attr( $args['id'] ) . ']', 'textarea_rows' => absint( $rows ), 'media_buttons' => false ) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text" rows="10" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" class="wpi-' . esc_attr( sanitize_html_class( $args['id'] ) ) . '">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_upload_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[$args['id']];
	} else {
		$value = isset($args['std']) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpinv_settings_upload_button button-secondary" value="' . __( 'Upload File', 'invoicing' ) . '"/></span>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_color_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<input type="text" class="wpinv-color-picker" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_country_states_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	$states = wpinv_get_country_states();

	$class = empty( $states ) ? ' class="wpinv-no-states"' : ' class="wpi_select2"';
	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"' . $class . 'data-placeholder="' . esc_html( $placeholder ) . '"/>';

	foreach ( $states as $option => $name ) {
		$selected = isset( $wpinv_options[ $args['id'] ] ) ? selected( $option, $wpinv_options[$args['id']], false ) : '';
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_tax_rates_callback($args) {
	global $wpinv_options;
	$rates = wpinv_get_tax_rates();
	ob_start(); ?>
    </td><tr>
    <td colspan="2" class="wpinv_tax_tdbox">
	<p><?php echo $args['desc']; ?></p>
	<table id="wpinv_tax_rates" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th scope="col" class="wpinv_tax_country"><?php _e( 'Country', 'invoicing' ); ?></th>
				<th scope="col" class="wpinv_tax_state"><?php _e( 'State / Province', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_tax_global" title="<?php esc_attr_e( 'Apply rate to whole country, regardless of state / province', 'invoicing' ); ?>"><?php _e( 'Country Wide', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_tax_rate"><?php _e( 'Rate %', 'invoicing' ); ?></th> 
                <th scope="col" class="wpinv_tax_name"><?php _e( 'Tax Name', 'invoicing' ); ?></th>
				<th scope="col" class="wpinv_tax_action"><?php _e( 'Remove', 'invoicing' ); ?></th>
			</tr>
		</thead>
        <tbody>
		<?php if( !empty( $rates ) ) : ?>
			<?php foreach( $rates as $key => $rate ) : ?>
            <?php 
            $sanitized_key = wpinv_sanitize_key( $key );
            ?>
			<tr>
				<td class="wpinv_tax_country">
					<?php
					echo wpinv_html_select( array(
						'options'          => wpinv_get_country_list( true ),
						'name'             => 'tax_rates[' . $sanitized_key . '][country]',
                        'id'               => 'tax_rates[' . $sanitized_key . '][country]',
						'selected'         => $rate['country'],
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'wpinv-tax-country wpi_select2',
						'placeholder'      => __( 'Choose a country', 'invoicing' )
					) );
					?>
				</td>
				<td class="wpinv_tax_state">
					<?php
					$states = wpinv_get_country_states( $rate['country'] );
					if( !empty( $states ) ) {
						echo wpinv_html_select( array(
							'options'          => array_merge( array( '' => '' ), $states ),
							'name'             => 'tax_rates[' . $sanitized_key . '][state]',
                            'id'               => 'tax_rates[' . $sanitized_key . '][state]',
							'selected'         => $rate['state'],
							'show_option_all'  => false,
							'show_option_none' => false,
                            'class'            => 'wpi_select2',
							'placeholder'      => __( 'Choose a state', 'invoicing' )
						) );
					} else {
						echo wpinv_html_text( array(
							'name'  => 'tax_rates[' . $sanitized_key . '][state]', $rate['state'],
							'value' => ! empty( $rate['state'] ) ? $rate['state'] : '',
                            'id'    => 'tax_rates[' . $sanitized_key . '][state]',
						) );
					}
					?>
				</td>
				<td class="wpinv_tax_global">
					<input type="checkbox" name="tax_rates[<?php echo $sanitized_key; ?>][global]" id="tax_rates[<?php echo $sanitized_key; ?>][global]" value="1"<?php checked( true, ! empty( $rate['global'] ) ); ?>/>
					<label for="tax_rates[<?php echo $sanitized_key; ?>][global]"><?php _e( 'Apply to whole country', 'invoicing' ); ?></label>
				</td>
				<td class="wpinv_tax_rate"><input type="number" class="small-text" step="any" min="0" max="99" name="tax_rates[<?php echo $sanitized_key; ?>][rate]" value="<?php echo esc_html( $rate['rate'] ); ?>"/></td>
                <td class="wpinv_tax_name"><input type="text" class="regular-text" name="tax_rates[<?php echo $sanitized_key; ?>][name]" value="<?php echo esc_html( $rate['name'] ); ?>"/></td>
				<td class="wpinv_tax_action"><span class="wpinv_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'invoicing' ); ?></span></td>
			</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td class="wpinv_tax_country">
					<?php
					echo wpinv_html_select( array(
						'options'          => wpinv_get_country_list( true ),
						'name'             => 'tax_rates[0][country]',
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'wpinv-tax-country wpi_select2',
						'placeholder'      => __( 'Choose a country', 'invoicing' )
					) ); ?>
				</td>
				<td class="wpinv_tax_state">
					<?php echo wpinv_html_text( array(
						'name' => 'tax_rates[0][state]'
					) ); ?>
				</td>
				<td class="wpinv_tax_global">
					<input type="checkbox" name="tax_rates[0][global]" id="tax_rates[0][global]" value="1"/>
					<label for="tax_rates[0][global]"><?php _e( 'Apply to whole country', 'invoicing' ); ?></label>
				</td>
				<td class="wpinv_tax_rate"><input type="number" class="small-text" step="any" min="0" max="99" name="tax_rates[0][rate]" placeholder="<?php echo (float)wpinv_get_option( 'tax_rate', 0 ) ;?>" value="<?php echo (float)wpinv_get_option( 'tax_rate', 0 ) ;?>"/></td>
                <td class="wpinv_tax_name"><input type="text" class="regular-text" name="tax_rates[0][name]" /></td>
				<td><span class="wpinv_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'invoicing' ); ?></span></td>
			</tr>
		<?php endif; ?>
        </tbody>
        <tfoot><tr><td colspan="5"></td><td class="wpinv_tax_action"><span class="button-secondary" id="wpinv_add_tax_rate"><?php _e( 'Add Tax Rate', 'invoicing' ); ?></span></td></tr></tfoot>
	</table>
	<?php
	echo ob_get_clean();
}

function wpinv_tools_callback($args) {
    global $wpinv_options;
    ob_start(); ?>
    </td><tr>
    <td colspan="2" class="wpinv_tools_tdbox">
    <?php if ( $args['desc'] ) { ?><p><?php echo $args['desc']; ?></p><?php } ?>
    <?php do_action( 'wpinv_tools_before' ); ?>
    <table id="wpinv_tools_table" class="wp-list-table widefat fixed posts">
        <thead>
            <tr>
                <th scope="col" class="wpinv-th-tool"><?php _e( 'Tool', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv-th-desc"><?php _e( 'Description', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv-th-action"><?php _e( 'Action', 'invoicing' ); ?></th>
            </tr>
        </thead>
            <?php do_action( 'wpinv_tools_row' ); ?>
        <tbody>
        </tbody>
    </table>
    <?php do_action( 'wpinv_tools_after' ); ?>
    <?php
    echo ob_get_clean();
}

function wpinv_descriptive_text_callback( $args ) {
	echo wp_kses_post( $args['desc'] );
}

function wpinv_hook_callback( $args ) {
	do_action( 'wpinv_' . $args['id'], $args );
}

function wpinv_set_settings_cap() {
	return wpinv_get_capability();
}
add_filter( 'option_page_capability_wpinv_settings', 'wpinv_set_settings_cap' );

function wpinv_settings_sanitize_input( $value, $key ) {
    if ( $key == 'tax_rate' || $key == 'eu_fallback_rate' ) {
        $value = wpinv_sanitize_amount( $value, 4 );
        $value = $value >= 100 ? 99 : $value;
    }
        
    return $value;
}
add_filter( 'wpinv_settings_sanitize', 'wpinv_settings_sanitize_input', 10, 2 );

function wpinv_on_update_settings( $old_value, $value, $option ) {
    $old = !empty( $old_value['remove_data_on_unistall'] ) ? 1 : '';
    $new = !empty( $value['remove_data_on_unistall'] ) ? 1 : '';
    
    if ( $old != $new ) {
        update_option( 'wpinv_remove_data_on_invoice_unistall', $new );
    }
}
add_action( 'update_option_wpinv_settings', 'wpinv_on_update_settings', 10, 3 );
add_action( 'wpinv_settings_tab_bottom_emails_new_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_cancelled_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_failed_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_onhold_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_processing_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_completed_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_refunded_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_user_invoice', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_user_note', 'wpinv_settings_tab_bottom_emails', 10, 2 );
add_action( 'wpinv_settings_tab_bottom_emails_overdue', 'wpinv_settings_tab_bottom_emails', 10, 2 );

function wpinv_settings_tab_bottom_emails( $active_tab, $section ) {
    ?>
    <div class="wpinv-email-wc-row ">
        <div class="wpinv-email-wc-td">
            <h3 class="wpinv-email-wc-title"><?php echo apply_filters( 'wpinv_settings_email_wildcards_title', __( 'Wildcards For Emails', 'invoicing' ) ); ?></h3>
            <p class="wpinv-email-wc-description">
                <?php
                $description = __( 'The following wildcards can be used in email subjects, heading and content:<br>
                    <strong>{site_title} :</strong> Site Title<br>
                    <strong>{name} :</strong> Customer\'s full name<br>
                    <strong>{first_name} :</strong> Customer\'s first name<br>
                    <strong>{last_name} :</strong> Customer\'s last name<br>
                    <strong>{email} :</strong> Customer\'s email address<br>
                    <strong>{invoice_number} :</strong> The invoice number<br>
                    <strong>{invoice_total} :</strong> The invoice total<br>
                    <strong>{invoice_link} :</strong> The invoice link<br>
                    <strong>{invoice_pay_link} :</strong> The payment link<br>
                    <strong>{invoice_date} :</strong> The date the invoice was created<br>
                    <strong>{invoice_due_date} :</strong> The date the invoice is due<br>
                    <strong>{date} :</strong> Today\'s date.<br>
                    <strong>{is_was} :</strong> If due date of invoice is past, displays "was" otherwise displays "is"<br>
                    <strong>{invoice_label} :</strong> Invoices/quotes singular name. Ex: Invoice/Quote<br>', 'invoicing' );
                echo apply_filters('wpinv_settings_email_wildcards_description', $description, $active_tab, $section);
                ?>
            </p>
        </div>
    </div>
    <?php
}