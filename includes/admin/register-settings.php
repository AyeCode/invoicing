<?php
/**
 * Contains settings related functions
 *
 * @package Invoicing
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves all default settings.
 * 
 * @return array
 */
function wpinv_get_settings() {
    $defaults = array();

    foreach ( array_values( wpinv_get_registered_settings() ) as $tab_settings ) {

        foreach ( array_values( $tab_settings ) as $section_settings ) {

            foreach ( $section_settings as $key => $setting ) {
                if ( isset( $setting['std'] ) ) {
                    $defaults[ $key ] = $setting['std'];
                }
            }

        }

    }

    return $defaults;

}

/**
 * Retrieves all settings.
 * 
 * @return array
 */
function wpinv_get_options() {
    global $wpinv_options;

    // Try fetching the saved options.
    if ( empty( $wpinv_options ) ) {
        $wpinv_options = get_option( 'wpinv_settings' );
    }

    // If that fails, don't fetch the default settings to prevent a loop.
    if ( ! is_array( $wpinv_options ) ) {
        $wpinv_options = array();
    }

    return $wpinv_options;
}

/**
 * Retrieves a single setting.
 * 
 * @param string $key the setting key.
 * @param mixed $default The default value to use if the setting has not been set.
 * @return mixed
 */
function wpinv_get_option( $key = '', $default = false ) {

    $options = wpinv_get_options();
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : $default;
    $value   = apply_filters( 'wpinv_get_option', $value, $key, $default );

    return apply_filters( 'wpinv_get_option_' . $key, $value, $key, $default );
}

/**
 * Updates all settings.
 * 
 * @param array $options the new options.
 * @return bool
 */
function wpinv_update_options( $options ) {
    global $wpinv_options;

    // update the option.
    if ( is_array( $options ) && update_option( 'wpinv_settings', $options ) ) {
        $wpinv_options = $options;
        return true;
    }

    return false;
}

/**
 * Updates a single setting.
 * 
 * @param string $key the setting key.
 * @param mixed $value The setting value.
 * @return bool
 */
function wpinv_update_option( $key = '', $value = false ) {

    // If no key, exit.
    if ( empty( $key ) ) {
        return false;
    }

    // Maybe delete the option instead.
    if ( is_null( $value ) ) {
        return wpinv_delete_option( $key );
    }

    // Prepare the new options.
    $options         = wpinv_get_options();
    $options[ $key ] = apply_filters( 'wpinv_update_option', $value, $key );

    // Save the new options.
    return wpinv_update_options( $options );

}

/**
 * Deletes a single setting.
 * 
 * @param string $key the setting key.
 * @return bool
 */
function wpinv_delete_option( $key = '' ) {

    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    $options = wpinv_get_options();

    if ( isset( $options[ $key ] ) ) {
        unset( $options[ $key ] );
        return wpinv_update_options( $options );
    }

    return true;

}

/**
 * Register settings after admin inits.
 * 
 */
function wpinv_register_settings() {

    // Loop through all tabs.
    foreach ( wpinv_get_registered_settings() as $tab => $sections ) {

        // In each tab, loop through sections.
        foreach ( $sections as $section => $settings ) {

            // Check for backwards compatibility
            $section_tabs = wpinv_get_settings_tab_sections( $tab );
            if ( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
                $section = 'main';
                $settings = $sections;
            }

            // Register the setting section.
            add_settings_section(
                'wpinv_settings_' . $tab . '_' . $section,
                __return_null(),
                '__return_false',
                'wpinv_settings_' . $tab . '_' . $section
            );

            foreach ( $settings as $option ) {
                if ( ! empty( $option['id'] ) ) {
                    wpinv_register_settings_option( $tab, $section, $option );
                }
            }

        }
    }

    // Creates our settings in the options table.
    register_setting( 'wpinv_settings', 'wpinv_settings', 'wpinv_settings_sanitize' );
}
add_action( 'admin_init', 'wpinv_register_settings' );

/**
 * Register a single settings option.
 * 
 * @param string $tab
 * @param string $section
 * @param string $option
 * 
 */
function wpinv_register_settings_option( $tab, $section, $option ) {

    $name       = isset( $option['name'] ) ? $option['name'] : '';
    $cb         = "wpinv_{$option['type']}_callback";
    $section    = "wpinv_settings_{$tab}_$section";
	$is_wizzard = is_admin() && isset( $_GET['page'] ) && 'gp-setup' == $_GET['page'];

	if ( isset( $option['desc'] ) && ( ! $is_wizzard && ! empty( $option['help-tip'] ) ) ) {
		$tip   = wpinv_clean( $option['desc'] );
		$name .= "<span class='dashicons dashicons-editor-help wpi-help-tip' title='$tip'></span>";
		unset( $option['desc'] );
	}

    // Loop through all tabs.
    add_settings_field(
        'wpinv_settings[' . $option['id'] . ']',
        $name,
        function_exists( $cb ) ? $cb : 'wpinv_missing_callback',
        $section,
        $section,
        array(
            'section'     => $section,
            'id'          => isset( $option['id'] )          ? $option['id']          : uniqid( 'wpinv-' ),
            'desc'        => isset( $option['desc'] )        ? $option['desc']        : '',
            'name'        => $name,
            'size'        => isset( $option['size'] )        ? $option['size']        : null,
            'options'     => isset( $option['options'] )     ? $option['options']     : '',
            'selected'    => isset( $option['selected'] )    ? $option['selected']    : null,
            'std'         => isset( $option['std'] )         ? $option['std']         : '',
            'min'         => isset( $option['min'] )         ? $option['min']         : 0,
            'max'         => isset( $option['max'] )         ? $option['max']         : 999999,
            'step'        => isset( $option['step'] )        ? $option['step']        : 1,
            'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
            'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true,
            'readonly'    => isset( $option['readonly'] )    ? $option['readonly']    : false,
            'faux'        => isset( $option['faux'] )        ? $option['faux']        : false,
            'onchange'    => isset( $option['onchange'] )   ? $option['onchange']     : '',
            'custom'      => isset( $option['custom'] )     ? $option['custom']       : '',
			'default_content' => isset( $option['default_content'] )     ? $option['default_content']       : '',
			'class'       => isset( $option['class'] )     ? $option['class']         : '',
			'style'       => isset( $option['style'] )     ? $option['style']         : '',
            'cols'        => isset( $option['cols'] ) && (int) $option['cols'] > 0 ? (int) $option['cols'] : 50,
            'rows'        => isset( $option['rows'] ) && (int) $option['rows'] > 0 ? (int) $option['rows'] : 5,
        )
    );

}

/**
 * Returns an array of all registered settings.
 * 
 * @return array
 */
function wpinv_get_registered_settings() {
	return array_filter( apply_filters( 'wpinv_registered_settings', wpinv_get_data( 'admin-settings' ) ) );
}

/**
 * Returns an array of all integration settings.
 * 
 * @return array
 */
function getpaid_get_integration_settings() {
    return apply_filters( 'getpaid_integration_settings', array() );
}

/**
 * Sanitizes settings before they are saved.
 * 
 * @return array
 */
function wpinv_settings_sanitize( $input = array() ) {

	$wpinv_options = wpinv_get_options();
	$raw_referrer  = wp_get_raw_referer();

    if ( empty( $raw_referrer ) ) {
		return array_merge( $wpinv_options, $input );
    }

    wp_parse_str( $raw_referrer, $referrer );

	if ( in_array( 'gp-setup', $referrer ) ) {
		return array_merge( $wpinv_options, $input );
	}

    $settings = wpinv_get_registered_settings();
    $tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
    $section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

    $input = $input ? $input : array();
    $input = apply_filters( 'wpinv_settings_tab_' . $tab . '_sanitize', $input );
    $input = apply_filters( 'wpinv_settings_' . $tab . '-' . $section . '_sanitize', $input );

    // Loop through each setting being saved and pass it through a sanitization filter
    foreach ( $input as $key => $value ) {

        // Get the setting type (checkbox, select, etc)
        $type = isset( $settings[ $tab ][$section][ $key ]['type'] ) ? $settings[ $tab ][$section][ $key ]['type'] : false;

        if ( $type ) {
            // Field type specific filter
            $input[$key] = apply_filters( "wpinv_settings_sanitize_$type", $value, $key );
        }

        // General filter
		$input[ $key ] = apply_filters( 'wpinv_settings_sanitize', $input[ $key ], $key );

		// Key specific filter.
		$input[ $key ] = apply_filters( "wpinv_settings_sanitize_$key", $input[ $key ] );
    }

    // Loop through the whitelist and unset any that are empty for the tab being saved
    $main_settings    = isset( $settings[ $tab ] ) ? $settings[ $tab ] : array(); // Check for extensions that aren't using new sections
    $section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();

    $found_settings   = array_merge( $main_settings, $section_settings );

    if ( ! empty( $found_settings ) ) {
        foreach ( $found_settings as $key => $value ) {

            // settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
            if ( is_numeric( $key ) ) {
                $key = $value['id'];
            }

            if ( ! isset( $input[ $key ] ) && isset( $wpinv_options[ $key ] ) ) {
                unset( $wpinv_options[ $key ] );
            }
        }
    }

    // Merge our new settings with the existing
    $output = array_merge( $wpinv_options, $input );

    add_settings_error( 'wpinv-notices', '', __( 'Settings updated.', 'invoicing' ), 'updated' );

    return $output;
}
add_filter( 'wpinv_settings_sanitize_text', 'trim', 10, 1 );
add_filter( 'wpinv_settings_sanitize_tax_rate', 'wpinv_sanitize_amount' );

function wpinv_settings_sanitize_tax_rates( $input ) {
    if( ! wpinv_current_user_can_manage_invoicing() ) {
        return $input;
    }

    $new_rates = ! empty( $_POST['tax_rates'] ) ? wp_kses_post_deep( array_values( $_POST['tax_rates'] ) ) : array();
    $tax_rates = array();

    foreach ( $new_rates as $rate ) {

		$rate['rate']    = wpinv_sanitize_amount( $rate['rate'] );
		$rate['name']    = sanitize_text_field( $rate['name'] );
		$rate['state']   = sanitize_text_field( $rate['state'] );
		$rate['country'] = sanitize_text_field( $rate['country'] );
		$rate['global']  = empty( $rate['state'] );
		$tax_rates[]     = $rate;

	}

    update_option( 'wpinv_tax_rates', $tax_rates );

    return $input;
}
add_filter( 'wpinv_settings_taxes-rates_sanitize', 'wpinv_settings_sanitize_tax_rates' );

function wpinv_get_settings_tabs() {
    $tabs             = array();
    $tabs['general']  = __( 'General', 'invoicing' );
    $tabs['gateways'] = __( 'Payment Gateways', 'invoicing' );
    $tabs['taxes']    = __( 'Taxes', 'invoicing' );
	$tabs['emails']   = __( 'Emails', 'invoicing' );

	if ( count( getpaid_get_integration_settings() ) > 0 ) {
		$tabs['integrations'] = __( 'Integrations', 'invoicing' );
	}

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
            'page_section' => __( 'Page Settings', 'invoicing' ),
            'currency_section' => __( 'Currency Settings', 'invoicing' ),
            'labels' => __( 'Label Texts', 'invoicing' ),
        ) ),
        'gateways' => apply_filters( 'wpinv_settings_sections_gateways', array(
            'main' => __( 'Gateway Settings', 'invoicing' ),
        ) ),
        'taxes' => apply_filters( 'wpinv_settings_sections_taxes', array(
            'main'  => __( 'Tax Settings', 'invoicing' ),
			'rates' => __( 'Tax Rates', 'invoicing' ),
			'vat'   => __( 'EU VAT Settings', 'invoicing' )
        ) ),
        'emails' => apply_filters( 'wpinv_settings_sections_emails', array(
            'main' => __( 'Email Settings', 'invoicing' ),
		) ),

		'integrations' => wp_list_pluck( getpaid_get_integration_settings(), 'label', 'id' ),

        'privacy' => apply_filters( 'wpinv_settings_sections_privacy', array(
            'main' => __( 'Privacy policy', 'invoicing' ),
        ) ),
        'misc' => apply_filters( 'wpinv_settings_sections_misc', array(
            'main' => __( 'Miscellaneous', 'invoicing' ),
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

	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

	if ( isset( $args['set_value'] ) ) {
		$value = $args['set_value'];
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$html = '<input type="hidden" id="wpinv_settings[' . wpinv_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" />';
    
	echo $html;
}

/**
 * Displays a checkbox settings callback.
 */
function wpinv_checkbox_callback( $args ) {

	$std = isset( $args['std'] ) ? $args['std'] : '';
	$std = wpinv_get_option( $args['id'], $std );
	$id  = esc_attr( $args['id'] );

	getpaid_hidden_field( "wpinv_settings[$id]", '0' );
	?>
		<fieldset>
			<label>
				<input id="wpinv-settings-<?php echo $id; ?>" name="wpinv_settings[<?php echo $id; ?>]" <?php checked( empty( $std ), false ); ?> value="1" type="checkbox">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</fieldset>
	<?php
}

function wpinv_multicheck_callback( $args ) {

	$sanitize_id = wpinv_sanitize_key( $args['id'] );
	$class = !empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

	if ( ! empty( $args['options'] ) ) {

		$std     = isset( $args['std'] ) ? $args['std'] : array();
		$value   = wpinv_get_option( $args['id'], $std );

		echo '<div class="wpi-mcheck-rows wpi-mcheck-' . $sanitize_id . $class . '">';
        foreach( $args['options'] as $key => $option ):
			$sanitize_key = wpinv_sanitize_key( $key );
			if ( in_array( $sanitize_key, $value ) ) { 
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
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
	$value   = wpinv_get_option( $args['id'], false);

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ) {
            $sanitize_key = wpinv_sanitize_key( $key );
            
			if( empty( $value ) ) {
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

/**
 * Displays a radio settings field.
 */
function wpinv_radio_callback( $args ) {

	$std = isset( $args['std'] ) ? $args['std'] : '';
	$std = wpinv_get_option( $args['id'], $std );
	?>
		<fieldset>
			<ul id="wpinv-settings-<?php echo esc_attr( $args['id'] ); ?>" style="margin-top: 0;">
				<?php foreach( $args['options'] as $key => $option ) : ?>
					<li>
						<label>
							<input name="wpinv_settings[<?php echo esc_attr( $args['id'] ); ?>]" <?php checked( $std, $key ); ?> value="<?php echo esc_attr( $key ); ?>" type="radio">
							<?php echo wp_kses_post( $option ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php
	getpaid_settings_description_callback( $args );
}

/**
 * Displays a description if available.
 */
function getpaid_settings_description_callback( $args ) {

	if ( ! empty( $args['desc'] ) ) {
		$description = wp_kses_post( $args['desc'] );
		echo "<p class='description'>$description</p>";
	}

}

/**
 * Displays a list of available gateways.
 */
function wpinv_gateways_callback() {

	?>
		</td>
	</tr>
	<tr class="bsui">
    	<td colspan="2" class="p-0">
			<?php include plugin_dir_path( __FILE__ ) . 'views/html-gateways-edit.php'; ?>

	<?php
}

function wpinv_gateway_select_callback($args) {

    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    $class = !empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';
	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

	echo '<select name="wpinv_settings[' . $sanitize_id . ']"" id="wpinv_settings[' . $sanitize_id . ']" class="'.$class.'" >';

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $args['selected'] ) && $args['selected'] !== null && $args['selected'] !== false ) {
            $selected = selected( $key, $args['selected'], false );
        } else {
            $selected = selected( $key, $value, false );
        }
		echo '<option value="' . wpinv_sanitize_key( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';
}

/**
 * Generates attributes.
 * 
 * @param array $args
 * @return string
 */
function wpinv_settings_attrs_helper( $args ) {

	$value        = isset( $args['std'] ) ? $args['std'] : '';
	$id           = esc_attr( $args['id'] );
	$placeholder  = esc_attr( $args['placeholder'] );

	if ( ! empty( $args['faux'] ) ) {
		$args['readonly'] = true;
		$name             = '';
	} else {
		$value  = wpinv_get_option( $args['id'], $value );
		$name   = "wpinv_settings[$id]";
	}

	$value    = is_scalar( $value ) ? esc_attr( $value ) : '';
	$class    = esc_attr( $args['class'] );
	$style    = esc_attr( $args['style'] );
	$readonly = empty( $args['readonly'] ) ? '' : 'readonly onclick="this.select()"';

	$onchange = '';
    if ( ! empty( $args['onchange'] ) ) {
        $onchange = ' onchange="' . esc_attr( $args['onchange'] ) . '"';
	}

	return "name='$name' id='wpinv-settings-$id' style='$style' value='$value' class='$class' placeholder='$placeholder' data-placeholder='$placeholder' $onchange $readonly";
}

/**
 * Displays a text input settings callback.
 */
function wpinv_text_callback( $args ) {

	$desc = wp_kses_post( $args['desc'] );
	$desc = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr = wpinv_settings_attrs_helper( $args );

	?>
		<label style="width: 100%;">
			<input type="text" <?php echo $attr; ?>>
			<?php echo $desc; ?>
		</label>
	<?php

}

/**
 * Displays a number input settings callback.
 */
function wpinv_number_callback( $args ) {

	$desc = wp_kses_post( $args['desc'] );
	$desc = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr = wpinv_settings_attrs_helper( $args );
	$max  = intval( $args['max'] );
	$min  = intval( $args['min'] );
	$step = floatval( $args['step'] );

	?>
		<label style="width: 100%;">
			<input type="number" step="<?php echo $step; ?>" max="<?php echo $max; ?>" min="<?php echo $min; ?>" <?php echo $attr; ?>>
			<?php echo $desc; ?>
		</label>
	<?php

}

function wpinv_textarea_callback( $args ) {
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

    $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $class = ( isset( $args['class'] ) && ! is_null( $args['class'] ) ) ? $args['class'] : 'large-text';

	$html = '<textarea class="' . sanitize_html_class( $class ) . ' txtarea-' . sanitize_html_class( $size ) . ' wpi-' . esc_attr( sanitize_html_class( $sanitize_id ) ) . ' " cols="' . $args['cols'] . '" rows="' . $args['rows'] . '" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<br /><label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_password_callback( $args ) {
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

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

/**
 * Displays a number input settings callback.
 */
function wpinv_select_callback( $args ) {

	$desc   = wp_kses_post( $args['desc'] );
	$desc   = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr   = wpinv_settings_attrs_helper( $args );
	$value  = isset( $args['std'] ) ? $args['std'] : '';
	$value  = wpinv_get_option( $args['id'], $value );
	$rand   = uniqid( 'random_id' );

	?>
		<label style="width: 100%;">
			<select <?php echo $attr; ?> data-allow-clear="true">
				<?php foreach ( $args['options'] as $option => $name ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php echo selected( $option, $value ); ?>><?php echo wpinv_clean( $name ); ?></option>
				<?php endforeach;?>
			</select>

			<?php if ( substr( $args['id'], -5 ) === '_page' && is_numeric( $value ) ) : ?>
				<a href="<?php echo get_edit_post_link( $value ); ?>" target="_blank" class="button getpaid-page-setting-edit"><?php _e( 'Edit Page', 'invoicing' ) ?></a>
			<?php endif; ?>

			<?php if ( substr( $args['id'], -5 ) === '_page' && ! empty( $args['default_content'] ) ) : ?>
				&nbsp;<a href="#TB_inline?&width=400&height=550&inlineId=<?php echo $rand; ?>" class="button thickbox getpaid-page-setting-view-default"><?php _e( 'View Default Content', 'invoicing' ) ?></a>
				<div id='<?php echo $rand; ?>' style='display:none;'>
					<div>
						<h3><?php _e( 'Original Content', 'invoicing' ) ?></h3>
						<textarea readonly onclick="this.select()" rows="8" style="width: 100%;"><?php echo gepaid_trim_lines( wp_kses_post( $args['default_content'] ) ); ?></textarea>
						<h3><?php _e( 'Current Content', 'invoicing' ) ?></h3>
						<textarea readonly onclick="this.select()" rows="8" style="width: 100%;"><?php $_post = get_post( $value ); echo empty( $_post ) ? '' : gepaid_trim_lines( wp_kses_post( $_post->post_content ) ); ?></textarea>
					</div>
				</div>
			<?php endif; ?>

			<?php echo $desc; ?>
		</label>
	<?php

}

function wpinv_color_select_callback( $args ) {
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

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
	global $wp_version;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );
	
	if ( ! empty( $args['allow_blank'] ) && empty( $value ) ) {
		$value = $std;
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	$html = '<div class="getpaid-settings-editor-input">';
	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'wpinv_settings_' . esc_attr( $args['id'] ), array( 'textarea_name' => 'wpinv_settings[' . esc_attr( $args['id'] ) . ']', 'textarea_rows' => absint( $rows ), 'media_buttons' => false ) );
		$html .= ob_get_clean();
	} else {
		$html .= '<textarea class="large-text" rows="10" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" class="wpi-' . esc_attr( sanitize_html_class( $args['id'] ) ) . '">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '</div><br/><label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_upload_callback( $args ) {
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpinv_settings_upload_button button-secondary" value="' . __( 'Upload File', 'invoicing' ) . '"/></span>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_color_callback( $args ) {

	$std         = isset( $args['std'] ) ? $args['std'] : '';
	$value       = wpinv_get_option( $args['id'], $std );
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	$html = '<input type="text" class="wpinv-color-picker" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $std ) . '" />';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_country_states_callback($args) {

	$std     = isset( $args['std'] ) ? $args['std'] : '';
	$value   = wpinv_get_option( $args['id'], $std );

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
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

/**
 * Displays the tax rates edit table.
 */
function wpinv_tax_rates_callback() {
	
	?>
		</td>
	</tr>
	<tr class="bsui">
    	<td colspan="2" class="p-0">
			<?php include plugin_dir_path( __FILE__ ) . 'views/html-tax-rates-edit.php'; ?>

	<?php

}

/**
 * Displays a tax rate' edit row.
 */
function wpinv_tax_rate_callback( $tax_rate, $key, $echo = true ) {
	ob_start();

	$key                      = sanitize_key( $key );
	$tax_rate['reduced_rate'] = empty( $tax_rate['reduced_rate'] ) ? 0 : $tax_rate['reduced_rate'];
	include plugin_dir_path( __FILE__ ) . 'views/html-tax-rate-edit.php';

	if ( $echo ) {
		echo ob_get_clean();
	} else {
		return ob_get_clean(); 
	}

}


function wpinv_tools_callback($args) {
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

        <tbody>
			<tr>
                <td><?php _e( 'Check Pages', 'invoicing' );?></td>
                <td>
                    <small><?php _e( 'Creates any missing GetPaid pages.', 'invoicing' ); ?></small>
                </td>
                <td>
					<a href="<?php
						echo esc_url(
							wp_nonce_url(
								add_query_arg( 'getpaid-admin-action', 'create_missing_pages' ),
								'getpaid-nonce',
								'getpaid-nonce'
							)
						);
					?>" class="button button-primary"><?php _e('Run', 'invoicing');?></a>
                </td>
            </tr>
			<tr>
                <td><?php _e( 'Create Database Tables', 'invoicing' );?></td>
                <td>
                    <small><?php _e( 'Run this tool to create any missing database tables.', 'invoicing' ); ?></small>
                </td>
                <td>
					<a href="<?php
						echo esc_url(
							wp_nonce_url(
								add_query_arg( 'getpaid-admin-action', 'create_missing_tables' ),
								'getpaid-nonce',
								'getpaid-nonce'
							)
						);
					?>" class="button button-primary"><?php _e('Run', 'invoicing');?></a>
                </td>
            </tr>
			<tr>
                <td><?php _e( 'Migrate old invoices', 'invoicing' );?></td>
                <td>
                    <small><?php _e( 'If your old invoices were not migrated after updating from Invoicing to GetPaid, you can use this tool to migrate them.', 'invoicing' ); ?></small>
                </td>
                <td>
					<a href="<?php
						echo esc_url(
							wp_nonce_url(
								add_query_arg( 'getpaid-admin-action', 'migrate_old_invoices' ),
								'getpaid-nonce',
								'getpaid-nonce'
							)
						);
					?>" class="button button-primary"><?php _e('Run', 'invoicing');?></a>
                </td>
            </tr>

			<tr>
                <td><?php _e( 'Recalculate Discounts', 'invoicing' );?></td>
                <td>
                    <small><?php _e( 'Recalculate discounts for existing invoices that have discount codes but are not discounted.', 'invoicing' ); ?></small>
                </td>
                <td>
					<a href="<?php
						echo esc_url(
							wp_nonce_url(
								add_query_arg( 'getpaid-admin-action', 'recalculate_discounts' ),
								'getpaid-nonce',
								'getpaid-nonce'
							)
						);
					?>" class="button button-primary"><?php _e( 'Run', 'invoicing' );?></a>
                </td>
            </tr>

			<tr>
                <td><?php _e( 'Set-up Wizard', 'invoicing' );?></td>
                <td>
                    <small><?php _e( 'Launch the quick set-up wizard.', 'invoicing' ); ?></small>
                </td>
                <td>
					<a href="<?php
						echo esc_url( admin_url( 'index.php?page=gp-setup' ) );
					?>" class="button button-primary"><?php _e( 'Launch', 'invoicing' );?></a>
                </td>
            </tr>

			<?php do_action( 'wpinv_tools_row' ); ?>
        </tbody>
    </table>
    <?php do_action( 'wpinv_tools_after' ); ?>
    <?php
    echo ob_get_clean();
}


function wpinv_descriptive_text_callback( $args ) {
	echo wp_kses_post( $args['desc'] );
}

function wpinv_raw_html_callback( $args ) {
	echo $args['desc'];
}

function wpinv_hook_callback( $args ) {
	do_action( 'wpinv_' . $args['id'], $args );
}

function wpinv_set_settings_cap() {
	return wpinv_get_capability();
}
add_filter( 'option_page_capability_wpinv_settings', 'wpinv_set_settings_cap' );


function wpinv_on_update_settings( $old_value, $value, $option ) {
    $old = !empty( $old_value['remove_data_on_unistall'] ) ? 1 : '';
    $new = !empty( $value['remove_data_on_unistall'] ) ? 1 : '';
    
    if ( $old != $new ) {
        update_option( 'wpinv_remove_data_on_invoice_unistall', $new );
    }
}
add_action( 'update_option_wpinv_settings', 'wpinv_on_update_settings', 10, 3 );

/**
 * Returns the merge tags help text.
 *
 * @since    2.1.8
 * 
 * @return string
 */
function wpinv_get_merge_tags_help_text( $subscription = false ) {

	$url  = $subscription ? 'https://gist.github.com/picocodes/3d213982d57c34edf7a46fd3f0e8583e' : 'https://gist.github.com/picocodes/43bdc4d4bbba844534b2722e2af0b58f';
	$link = sprintf(
		'<strong><a href="%s" target="_blank">%s</a></strong>',
		$url,
		esc_html__( 'View available merge tags.', 'wpinv-quotes' )
	);

	$description = esc_html__( 'The content of the email (Merge Tags and HTML are allowed).', 'invoicing' );

	return "$description $link";

}
