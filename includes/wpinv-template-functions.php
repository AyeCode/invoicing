<?php
/**
 * Template functions.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Displays an invoice.
 * 
 * @param WPInv_Invoice $invoice.
 */
function getpaid_invoice( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/invoice.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice', 'getpaid_invoice', 10 );

/**
 * Displays the invoice footer.
 */
function getpaid_invoice_footer( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/footer.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_footer', 'getpaid_invoice_footer', 10 );

/**
 * Displays the invoice top bar.
 */
function getpaid_invoice_header( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/header.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_header', 'getpaid_invoice_header', 10 );

/**
 * Displays actions on the left side of the header.
 */
function getpaid_invoice_header_left_actions( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/header-left-actions.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_header_left', 'getpaid_invoice_header_left_actions', 10 );

/**
 * Displays actions on the right side of the invoice top bar.
 */
function getpaid_invoice_header_right_actions( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/header-right-actions.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_header_right', 'getpaid_invoice_header_right_actions', 10 );

/**
 * Displays the invoice title, logo etc.
 */
function getpaid_invoice_details_top( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/details-top.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_details', 'getpaid_invoice_details_top', 10 );

/**
 * Displays the company logo.
 */
function getpaid_invoice_logo( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/invoice-logo.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_details_top_left', 'getpaid_invoice_logo' );

/**
 * Displays the type of invoice.
 */
function getpaid_invoice_type( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/invoice-type.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_details_top_right', 'getpaid_invoice_type' );

/**
 * Displays the invoice details.
 */
function getpaid_invoice_details_main( $invoice ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/details.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_details', 'getpaid_invoice_details_main', 50 );

/**
 * Returns a path to the templates directory.
 * 
 * @return string
 */
function wpinv_get_templates_dir() {
    return getpaid_template()->templates_dir;
}

/**
 * Returns a url to the templates directory.
 * 
 * @return string
 */
function wpinv_get_templates_url() {
    return getpaid_template()->templates_url;
}

/**
 * Displays a template.
 * 
 * First checks if there is a template overide, if not it loads the default template.
 * 
 * @param string $template_name e.g payment-forms/cart.php The template to locate.
 * @param string $template_path The templates directory relative to the theme's root dir. Defaults to 'invoicing'.
 * @param string $default_path The root path to the default template. Defaults to invoicing/templates
 */
function wpinv_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
    getpaid_template()->display_template( $template_name, $args, $template_path, $default_path );
}

/**
 * Retrieves a given template's html code.
 * 
 * First checks if there is a template overide, if not it loads the default template.
 * 
 * @param string $template_name e.g payment-forms/cart.php The template to locate.
 * @param array $args An array of args to pass to the template.
 * @param string $template_path The templates directory relative to the theme's root dir. Defaults to 'invoicing'.
 * @param string $default_path The root path to the default template. Defaults to invoicing/templates
 */
function wpinv_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	return getpaid_template()->get_template( $template_name, $args, $template_path, $default_path );
}

/**
 * Returns the default path from where to look for template overides.
 * 
 * @return string
 */
function wpinv_template_path() {
    return apply_filters( 'wpinv_template_path', wpinv_get_theme_template_dir_name() );
}

/**
 * Returns the directory containing the template overides.
 * 
 * @return string
 */
function wpinv_get_theme_template_dir_name() {
	return trailingslashit( apply_filters( 'wpinv_templates_dir', 'invoicing' ) );
}

/**
 * Locates a template path.
 * 
 * First checks if there is a template overide, if not it loads the default template.
 * 
 * @param string $template_name e.g payment-forms/cart.php The template to locate.
 * @param string $template_path The template path relative to the theme's root dir. Defaults to 'invoicing'.
 * @param string $default_path The root path to the default template. Defaults to invoicing/templates
 */
function wpinv_locate_template( $template_name, $template_path = '', $default_path = '' ) {
    return getpaid_template()->locate_template( $template_name, $template_path, $default_path );
}

function wpinv_get_template_part( $slug, $name = null, $load = true ) {
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'wpinv_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return wpinv_locate_tmpl( $templates, $load, false );
}

function wpinv_locate_tmpl( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array)$template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach( wpinv_get_theme_template_paths() as $template_path ) {

			if( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}

		if( !empty( $located ) ) {
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

function wpinv_get_theme_template_paths() {
	$template_dir = wpinv_get_theme_template_dir_name();

	$file_paths = array(
		1 => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10 => trailingslashit( get_template_directory() ) . $template_dir,
		100 => wpinv_get_templates_dir()
	);

	$file_paths = apply_filters( 'wpinv_template_paths', $file_paths );

	// sort the file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

function wpinv_checkout_meta_tags() {

	$pages   = array();
	$pages[] = wpinv_get_option( 'success_page' );
	$pages[] = wpinv_get_option( 'failure_page' );
	$pages[] = wpinv_get_option( 'invoice_history_page' );
	$pages[] = wpinv_get_option( 'invoice_subscription_page' );

	if( !wpinv_is_checkout() && !is_page( $pages ) ) {
		return;
	}

	echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
}
add_action( 'wp_head', 'wpinv_checkout_meta_tags' );

function wpinv_add_body_classes( $class ) {
	$classes = (array)$class;

	if( wpinv_is_checkout() ) {
		$classes[] = 'wpinv-checkout';
		$classes[] = 'wpinv-page';
	}

	if( wpinv_is_success_page() ) {
		$classes[] = 'wpinv-success';
		$classes[] = 'wpinv-page';
	}

	if( wpinv_is_failed_transaction_page() ) {
		$classes[] = 'wpinv-failed-transaction';
		$classes[] = 'wpinv-page';
	}

	if( wpinv_is_invoice_history_page() ) {
		$classes[] = 'wpinv-history';
		$classes[] = 'wpinv-page';
	}

	if( wpinv_is_subscriptions_history_page() ) {
		$classes[] = 'wpinv-subscription';
		$classes[] = 'wpinv-page';
	}

	if( wpinv_is_test_mode() ) {
		$classes[] = 'wpinv-test-mode';
		$classes[] = 'wpinv-page';
	}

	return array_unique( $classes );
}
add_filter( 'body_class', 'wpinv_add_body_classes' );

function wpinv_html_year_dropdown( $name = 'year', $selected = 0, $years_before = 5, $years_after = 0 ) {
    $current     = date( 'Y' );
    $start_year  = $current - absint( $years_before );
    $end_year    = $current + absint( $years_after );
    $selected    = empty( $selected ) ? date( 'Y' ) : $selected;
    $options     = array();

    while ( $start_year <= $end_year ) {
        $options[ absint( $start_year ) ] = $start_year;
        $start_year++;
    }

    $output = wpinv_html_select( array(
        'name'             => $name,
        'selected'         => $selected,
        'options'          => $options,
        'show_option_all'  => false,
        'show_option_none' => false
    ) );

    return $output;
}

function wpinv_html_month_dropdown( $name = 'month', $selected = 0 ) {

    $options = array(
        '1'  => __( 'January', 'invoicing' ),
        '2'  => __( 'February', 'invoicing' ),
        '3'  => __( 'March', 'invoicing' ),
        '4'  => __( 'April', 'invoicing' ),
        '5'  => __( 'May', 'invoicing' ),
        '6'  => __( 'June', 'invoicing' ),
        '7'  => __( 'July', 'invoicing' ),
        '8'  => __( 'August', 'invoicing' ),
        '9'  => __( 'September', 'invoicing' ),
        '10' => __( 'October', 'invoicing' ),
        '11' => __( 'November', 'invoicing' ),
        '12' => __( 'December', 'invoicing' ),
    );

    // If no month is selected, default to the current month
    $selected = empty( $selected ) ? date( 'n' ) : $selected;

    $output = wpinv_html_select( array(
        'name'             => $name,
        'selected'         => $selected,
        'options'          => $options,
        'show_option_all'  => false,
        'show_option_none' => false
    ) );

    return $output;
}

function wpinv_html_select( $args = array() ) {
    $defaults = array(
        'options'          => array(),
        'name'             => null,
        'class'            => '',
        'id'               => '',
        'selected'         => 0,
        'placeholder'      => null,
        'multiple'         => false,
        'show_option_all'  => _x( 'All', 'all dropdown items', 'invoicing' ),
        'show_option_none' => _x( 'None', 'no dropdown items', 'invoicing' ),
        'data'             => array(),
        'onchange'         => null,
        'required'         => false,
        'disabled'         => false,
        'readonly'         => false,
    );

    $args = wp_parse_args( $args, $defaults );

    $data_elements = '';
    foreach ( $args['data'] as $key => $value ) {
        $data_elements .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
    }

    if( $args['multiple'] ) {
        $multiple = ' MULTIPLE';
    } else {
        $multiple = '';
    }

    if( $args['placeholder'] ) {
        $placeholder = $args['placeholder'];
    } else {
        $placeholder = '';
    }
    
    $options = '';
    if( !empty( $args['onchange'] ) ) {
        $options .= ' onchange="' . esc_attr( $args['onchange'] ) . '"';
    }
    
    if( !empty( $args['required'] ) ) {
        $options .= ' required="required"';
    }
    
    if( !empty( $args['disabled'] ) ) {
        $options .= ' disabled';
    }
    
    if( !empty( $args['readonly'] ) ) {
        $options .= ' readonly';
    }

    $class  = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
    $output = '<select name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" class="wpinv-select ' . $class . '"' . $multiple . ' data-placeholder="' . $placeholder . '" ' . trim( $options ) . $data_elements . '>';

    if ( $args['show_option_all'] ) {
        if( $args['multiple'] ) {
            $selected = selected( true, in_array( 0, $args['selected'] ), false );
        } else {
            $selected = selected( $args['selected'], 0, false );
        }
        $output .= '<option value="all"' . $selected . '>' . esc_html( $args['show_option_all'] ) . '</option>';
    }

    if ( !empty( $args['options'] ) ) {

        if ( $args['show_option_none'] ) {
            if( $args['multiple'] ) {
                $selected = selected( true, in_array( "", $args['selected'] ), false );
            } else {
                $selected = selected( $args['selected'] === "", true, false );
            }
            $output .= '<option value=""' . $selected . '>' . esc_html( $args['show_option_none'] ) . '</option>';
        }

        foreach( $args['options'] as $key => $option ) {

            if( $args['multiple'] && is_array( $args['selected'] ) ) {
                $selected = selected( true, (bool)in_array( $key, $args['selected'] ), false );
            } else {
                $selected = selected( $args['selected'], $key, false );
            }

            $output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option ) . '</option>';
        }
    }

    $output .= '</select>';

    return $output;
}

function wpinv_item_dropdown( $args = array() ) {
    $defaults = array(
        'name'              => 'wpi_item',
        'id'                => 'wpi_item',
        'class'             => '',
        'multiple'          => false,
        'selected'          => 0,
        'number'            => -1,
        'placeholder'       => __( 'Choose a item', 'invoicing' ),
        'data'              => array( 'search-type' => 'item' ),
        'show_option_all'   => false,
        'show_option_none'  => false,
        'show_recurring'    => false,
    );

    $args = wp_parse_args( $args, $defaults );

    $item_args = array(
        'post_type'      => 'wpi_item',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'posts_per_page' => $args['number']
    );

    $item_args  = apply_filters( 'wpinv_item_dropdown_query_args', $item_args, $args, $defaults );

    $items      = get_posts( $item_args );
    $options    = array();
    if ( $items ) {
        foreach ( $items as $item ) {
            $title = esc_html( $item->post_title );
            
            if ( !empty( $args['show_recurring'] ) ) {
                $title .= wpinv_get_item_suffix( $item->ID, false );
            }
            
            $options[ absint( $item->ID ) ] = $title;
        }
    }

    // This ensures that any selected items are included in the drop down
    if( is_array( $args['selected'] ) ) {
        foreach( $args['selected'] as $item ) {
            if( ! in_array( $item, $options ) ) {
                $title = get_the_title( $item );
                if ( !empty( $args['show_recurring'] ) ) {
                    $title .= wpinv_get_item_suffix( $item, false );
                }
                $options[$item] = $title;
            }
        }
    } elseif ( is_numeric( $args['selected'] ) && $args['selected'] !== 0 ) {
        if ( ! in_array( $args['selected'], $options ) ) {
            $title = get_the_title( $args['selected'] );
            if ( !empty( $args['show_recurring'] ) ) {
                $title .= wpinv_get_item_suffix( $args['selected'], false );
            }
            $options[$args['selected']] = get_the_title( $args['selected'] );
        }
    }

    $output = wpinv_html_select( array(
        'name'             => $args['name'],
        'selected'         => $args['selected'],
        'id'               => $args['id'],
        'class'            => $args['class'],
        'options'          => $options,
        'multiple'         => $args['multiple'],
        'placeholder'      => $args['placeholder'],
        'show_option_all'  => $args['show_option_all'],
        'show_option_none' => $args['show_option_none'],
        'data'             => $args['data'],
    ) );

    return $output;
}

/**
 * Returns an array of published items.
 */
function wpinv_get_published_items_for_dropdown() {

    $items = get_posts(
        array(
            'post_type'      => 'wpi_item',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => '-1'
        )
    );

    $options = array();
    if ( $items ) {
        foreach ( $items as $item ) {
            $options[ $item->ID ] = esc_html( $item->post_title ) . wpinv_get_item_suffix( $item->ID, false );
        }
    }

    return $options;
}

function wpinv_html_checkbox( $args = array() ) {
    $defaults = array(
        'name'     => null,
        'current'  => null,
        'class'    => 'wpinv-checkbox',
        'options'  => array(
            'disabled' => false,
            'readonly' => false
        )
    );

    $args = wp_parse_args( $args, $defaults );

    $class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
    $options = '';
    if ( ! empty( $args['options']['disabled'] ) ) {
        $options .= ' disabled="disabled"';
    } elseif ( ! empty( $args['options']['readonly'] ) ) {
        $options .= ' readonly';
    }

    $output = '<input type="checkbox"' . $options . ' name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['name'] ) . '" class="' . $class . ' ' . esc_attr( $args['name'] ) . '" ' . checked( 1, $args['current'], false ) . ' />';

    return $output;
}

/**
 * Displays a hidden field.
 */
function getpaid_hidden_field( $name, $value ) {
    $name  = sanitize_text_field( $name );
    $value = esc_attr( $value );

    echo "<input type='hidden' name='$name' value='$value' />";
}

/**
 * Displays a submit field.
 */
function getpaid_submit_field( $value, $name = 'submit', $class = 'btn-primary' ) {
    $name  = sanitize_text_field( $name );
    $value = esc_attr( $value );
    $class = esc_attr( $class );

    echo "<input type='submit' name='$name' value='$value' class='btn $class' />";
}

function wpinv_html_text( $args = array() ) {
    // Backwards compatibility
    if ( func_num_args() > 1 ) {
        $args = func_get_args();

        $name  = $args[0];
        $value = isset( $args[1] ) ? $args[1] : '';
        $label = isset( $args[2] ) ? $args[2] : '';
        $desc  = isset( $args[3] ) ? $args[3] : '';
    }

    $defaults = array(
        'id'           => '',
        'name'         => isset( $name )  ? $name  : 'text',
        'value'        => isset( $value ) ? $value : null,
        'label'        => isset( $label ) ? $label : null,
        'desc'         => isset( $desc )  ? $desc  : null,
        'placeholder'  => '',
        'class'        => 'regular-text',
        'disabled'     => false,
        'readonly'     => false,
        'required'     => false,
        'autocomplete' => '',
        'data'         => false
    );

    $args = wp_parse_args( $args, $defaults );

    $class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
    $options = '';
    if( $args['required'] ) {
        $options .= ' required="required"';
    }
    if( $args['readonly'] ) {
        $options .= ' readonly';
    }
    if( $args['readonly'] ) {
        $options .= ' readonly';
    }

    $data = '';
    if ( !empty( $args['data'] ) ) {
        foreach ( $args['data'] as $key => $value ) {
            $data .= 'data-' . wpinv_sanitize_key( $key ) . '="' . esc_attr( $value ) . '" ';
        }
    }

    $output = '<span id="wpinv-' . wpinv_sanitize_key( $args['name'] ) . '-wrap">';
    $output .= '<label class="wpinv-label" for="' . wpinv_sanitize_key( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label>';
    if ( ! empty( $args['desc'] ) ) {
        $output .= '<span class="wpinv-description">' . esc_html( $args['desc'] ) . '</span>';
    }

    $output .= '<input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] )  . '" autocomplete="' . esc_attr( $args['autocomplete'] )  . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="' . $class . '" ' . $data . ' ' . trim( $options ) . '/>';

    $output .= '</span>';

    return $output;
}

function wpinv_html_textarea( $args = array() ) {
    $defaults = array(
        'name'        => 'textarea',
        'value'       => null,
        'label'       => null,
        'desc'        => null,
        'class'       => 'large-text',
        'disabled'    => false,
        'placeholder' => '',
    );

    $args = wp_parse_args( $args, $defaults );

    $class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
    $disabled = '';
    if( $args['disabled'] ) {
        $disabled = ' disabled="disabled"';
    }

    $output = '<span id="wpinv-' . wpinv_sanitize_key( $args['name'] ) . '-wrap">';
    $output .= '<label class="wpinv-label" for="' . wpinv_sanitize_key( $args['name'] ) . '">' . esc_html( $args['label'] ) . '</label>';
    $output .= '<textarea name="' . esc_attr( $args['name'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" id="' . wpinv_sanitize_key( $args['name'] ) . '" class="' . $class . '"' . $disabled . '>' . esc_attr( $args['value'] ) . '</textarea>';

    if ( ! empty( $args['desc'] ) ) {
        $output .= '<span class="wpinv-description">' . esc_html( $args['desc'] ) . '</span>';
    }
    $output .= '</span>';

    return $output;
}

function wpinv_html_ajax_user_search( $args = array() ) {
    $defaults = array(
        'name'        => 'user_id',
        'value'       => null,
        'placeholder' => __( 'Enter username', 'invoicing' ),
        'label'       => null,
        'desc'        => null,
        'class'       => '',
        'disabled'    => false,
        'autocomplete'=> 'off',
        'data'        => false
    );

    $args = wp_parse_args( $args, $defaults );

    $args['class'] = 'wpinv-ajax-user-search ' . $args['class'];

    $output  = '<span class="wpinv_user_search_wrap">';
        $output .= wpinv_html_text( $args );
        $output .= '<span class="wpinv_user_search_results hidden"><a class="wpinv-ajax-user-cancel" title="' . __( 'Cancel', 'invoicing' ) . '" aria-label="' . __( 'Cancel', 'invoicing' ) . '" href="#">x</a><span></span></span>';
    $output .= '</span>';

    return $output;
}

/**
 * Use our template to display invoices.
 * 
 * @param string $template the template that is currently being used.
 */
function wpinv_template( $template ) {
    global $post;

    if ( ! is_admin() && ( is_single() || is_404() ) && ! empty( $post->ID ) && getpaid_is_invoice_post_type( get_post_type( $post->ID ) ) ) {

        // If the user can view this invoice, display it.
        if ( wpinv_user_can_view_invoice( $post->ID ) ) {

            return wpinv_get_template_part( 'wpinv-invoice-print', false, false );

        // Else display an error message.
        } else {

            return wpinv_get_template_part( 'wpinv-invalid-access', false, false );

        }

    }

    return $template;
}
add_filter( 'template_include', 'wpinv_template', 10, 1 );

function wpinv_get_business_address() {
    $business_address   = wpinv_store_address();
    $business_address   = !empty( $business_address ) ? wpautop( wp_kses_post( $business_address ) ) : '';
    
    $business_address = $business_address ? '<div class="address">' . $business_address . '</div>' : '';
    
    return apply_filters( 'wpinv_get_business_address', $business_address );
}

/**
 * Displays the company address.
 */
function wpinv_display_from_address() {
    wpinv_get_template( 'invoice/company-address.php' );
}
add_action( 'getpaid_invoice_details_left', 'wpinv_display_from_address', 10 );

/**
 * Generates a watermark text for an invoice.
 * 
 * @param WPInv_Invoice $invoice
 * @return string
 */
function wpinv_watermark( $invoice ) {
    $watermark = wpinv_get_watermark( $invoice );
    return apply_filters( 'wpinv_get_watermark', $watermark, $invoice );
}

/**
 * Generates a watermark text for an invoice.
 * 
 * @param WPInv_Invoice $invoice
 * @return string
 */
function wpinv_get_watermark( $invoice ) {
    return $invoice->get_status_nicename();
}

/**
 * @deprecated
 */
function wpinv_display_invoice_details( $invoice ) {
    return getpaid_invoice_meta( $invoice );
}

/**
 * Displays invoice meta.
 */
function getpaid_invoice_meta( $invoice ) {

    $invoice = new WPInv_Invoice( $invoice );

    // Ensure that we have an invoice.
    if ( 0 == $invoice->get_id() ) {
        return;
    }

    // Get the invoice meta.
    $meta = getpaid_get_invoice_meta( $invoice );

    // Display the meta.
    wpinv_get_template( 'invoice/invoice-meta.php', compact( 'invoice', 'meta' ) );

}
add_action( 'getpaid_invoice_details_right', 'getpaid_invoice_meta', 10 );

/**
 * Retrieves the address markup to use on Invoices.
 * 
 * @since 1.0.13
 * @see `wpinv_get_full_address_format`
 * @see `wpinv_get_invoice_address_replacements`
 * @param array $billing_details customer's billing details
 * @param  string $separator How to separate address lines.
 * @return string
 */
function wpinv_get_invoice_address_markup( $billing_details, $separator = '<br/>' ) {

    // Retrieve the address markup...
    $country= empty( $billing_details['country'] ) ? '' : $billing_details['country'];
    $format = wpinv_get_full_address_format( $country );

    // ... and the replacements.
    $replacements = wpinv_get_invoice_address_replacements( $billing_details );

    $formatted_address = str_ireplace( array_keys( $replacements ), $replacements, $format );
    
	// Remove unavailable tags.
    $formatted_address = preg_replace( "/\{\{\w+\}\}/", '', $formatted_address );

    // Clean up white space.
	$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
    $formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );
    
    // Break newlines apart and remove empty lines/trim commas and white space.
	$formatted_address = array_filter( array_map( 'wpinv_trim_formatted_address_line', explode( "\n", $formatted_address ) ) );

    // Add html breaks.
	$formatted_address = implode( $separator, $formatted_address );

	// We're done!
	return $formatted_address;
    
}

/**
 * Displays the billing address.
 * 
 * @param WPInv_Invoice $invoice
 */
function wpinv_display_to_address( $invoice = 0 ) {
    if ( ! empty( $invoice ) ) {
        wpinv_get_template( 'invoice/billing-address.php', compact( 'invoice' ) );
    }
}
add_action( 'getpaid_invoice_details_left', 'wpinv_display_to_address', 40 );


/**
 * Displays invoice line items.
 */
function wpinv_display_line_items( $invoice_id = 0 ) {

    // Prepare the invoice.
    $invoice = new WPInv_Invoice( $invoice_id );

    // Abort if there is no invoice.
    if ( 0 == $invoice->get_id() ) {
        return;
    }

    // Line item columns.
    $columns = getpaid_invoice_item_columns( $invoice );
    $columns = apply_filters( 'getpaid_invoice_line_items_table_columns', $columns, $invoice );

    wpinv_get_template( 'invoice/line-items.php', compact( 'invoice', 'columns' ) );
}
add_action( 'getpaid_invoice_line_items', 'wpinv_display_line_items', 10 );

/**
 * Displays invoice subscriptions.
 * 
 * @param WPInv_Invoice $invoice
 */
function getpaid_display_invoice_subscriptions( $invoice ) {

    // Subscriptions.
	$subscriptions = getpaid_get_invoice_subscriptions( $invoice );

    if ( empty( $subscriptions ) || ! $invoice->is_recurring() ) {
        return;
    }

    $main_subscription = getpaid_get_invoice_subscription( $invoice );

    // Display related subscriptions.
    if ( is_array( $subscriptions ) ) {
        printf( '<h2 class="mt-5 mb-1 h4">%s</h2>', esc_html__( 'Related Subscriptions', 'invoicing' ) );
        getpaid_admin_subscription_related_subscriptions_metabox( $main_subscription, false );
    }

    if ( $main_subscription->get_total_payments() > 1 ) {
        printf( '<h2 class="mt-5 mb-1 h4">%s</h2>', esc_html__( 'Related Invoices', 'invoicing' ) );
        getpaid_admin_subscription_invoice_details_metabox( $main_subscription, false );
    }

}
add_action( 'getpaid_invoice_line_items', 'getpaid_display_invoice_subscriptions', 55 );
add_action( 'wpinv_receipt_end', 'getpaid_display_invoice_subscriptions', 11 );

/**
 * Displays invoice notices on invoices.
 */
function wpinv_display_invoice_notice() {

    $label  = wpinv_get_option( 'vat_invoice_notice_label' );
    $notice = wpinv_get_option( 'vat_invoice_notice' );

    if ( empty( $label ) && empty( $notice ) ) {
        return;
    }

    echo '<div class="mt-4 mb-4 wpinv-vat-notice">';

    if ( ! empty( $label ) ) {
        $label = sanitize_text_field( $label );
        echo "<h5>$label</h5>";
    }

    if ( ! empty( $notice ) ) {
        echo '<small class="form-text text-muted">' . wpautop( wptexturize( $notice ) ) . '</small>';
    }

    echo '</div>';
}
add_action( 'getpaid_invoice_line_items', 'wpinv_display_invoice_notice', 100 );

/**
 * @param WPInv_Invoice $invoice
 */
function wpinv_display_invoice_notes( $invoice ) {

    // Retrieve the notes.
    $notes = wpinv_get_invoice_notes( $invoice->get_id(), 'customer' );

    // Abort if we have non.
    if ( empty( $notes ) ) {
        return;
    }

    // Echo the note.
    echo '<div class="getpaid-invoice-notes-wrapper position-relative my-4">';
    echo '<h2 class="getpaid-invoice-notes-title mb-1 p-0 h4">' . __( 'Notes', 'invoicing' ) .'</h2>';
    echo '<ul class="getpaid-invoice-notes text-break overflow-auto list-unstyled p-0 m-0">';

    foreach( $notes as $note ) {
        wpinv_get_invoice_note_line_item( $note );
    }

    echo '</ul>';
    echo '</div>';
}
add_action( 'getpaid_invoice_line_items', 'wpinv_display_invoice_notes', 60 );

/**
 * Loads scripts on our invoice templates.
 */
function wpinv_display_style() {

    // Make sure that all scripts have been loaded.
    if ( ! did_action( 'wp_enqueue_scripts' ) ) {
        do_action( 'wp_enqueue_scripts' );
    }

    // Register the invoices style.
    wp_register_style( 'wpinv-single-style', WPINV_PLUGIN_URL . 'assets/css/invoice.css', array(), filemtime( WPINV_PLUGIN_DIR . 'assets/css/invoice.css' ) );

    // Load required styles
    wp_print_styles( 'wpinv-single-style' );
    wp_print_styles( 'ayecode-ui' );

    // Maybe load custom css.
    $custom_css = wpinv_get_option( 'template_custom_css' );

    if ( isset( $custom_css ) && ! empty( $custom_css ) ) {
        $custom_css     = wp_kses( $custom_css, array( '\'', '\"' ) );
        $custom_css     = str_replace( '&gt;', '>', $custom_css );
        echo '<style type="text/css">';
        echo $custom_css;
        echo '</style>';
    }

}
add_action( 'wpinv_invoice_print_head', 'wpinv_display_style' );
add_action( 'wpinv_invalid_invoice_head', 'wpinv_display_style' );


/**
 * Displays the checkout page.
 */
function wpinv_checkout_form() {
    global $wpi_checkout_id;

    // Retrieve the current invoice.
    $invoice_id = getpaid_get_current_invoice_id();

    if ( empty( $invoice_id ) ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'Invalid invoice', 'invoicing' ),
            )
        );

    }

    // Can the user view this invoice?
    if ( ! wpinv_user_can_view_invoice( $invoice_id ) ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'You are not allowed to view this invoice', 'invoicing' ),
            )
        );

    }

    // Ensure that it is not yet paid for.
    $invoice = new WPInv_Invoice( $invoice_id );

    // Maybe mark it as viewed.
    getpaid_maybe_mark_invoice_as_viewed( $invoice );

    if ( $invoice->is_paid() ) {

        return aui()->alert(
            array(
                'type'    => 'success',
                'content' => __( 'This invoice has already been paid.', 'invoicing' ),
            )
        );

    }

    // Set the global invoice id.
    $wpi_checkout_id = $invoice_id;

    // Retrieve appropriate payment form.
    $payment_form = new GetPaid_Payment_Form( $invoice->get_meta( 'force_payment_form' ) );
    $payment_form = $payment_form->exists() ? $payment_form : new GetPaid_Payment_Form( wpinv_get_default_payment_form() );

    if ( ! $payment_form->exists() ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'Error loading the payment form', 'invoicing' ),
            )
        );

    }

    // Set the invoice.
    $payment_form->invoice = $invoice;

    if ( ! $payment_form->is_default() ) {

        $items    = array();
        $item_ids = array();

        foreach ( $invoice->get_items() as $item ) {
            if ( ! in_array( $item->get_id(), $item_ids ) ) {
                $item_ids[] = $item->get_id();
                $items[]    = $item;
            }
        }

        foreach ( $payment_form->get_items() as $item ) {
            if ( ! in_array( $item->get_id(), $item_ids ) ) {
                $item_ids[] = $item->get_id();
                $items[]    = $item;
            }
        }

        $payment_form->set_items( $items );

    } else {
        $payment_form->set_items( $invoice->get_items() );
    }

    // Generate the html.
    return $payment_form->get_html();

}

function wpinv_empty_cart_message() {
	return apply_filters( 'wpinv_empty_cart_message', '<span class="wpinv_empty_cart">' . __( 'Your cart is empty.', 'invoicing' ) . '</span>' );
}

/**
 * Echoes the Empty Cart Message
 *
 * @since 1.0
 * @return void
 */
function wpinv_empty_checkout_cart() {
    echo aui()->alert(
        array(
            'type'    => 'warning',
            'content' => wpinv_empty_cart_message(),
        )
    );
}
add_action( 'wpinv_cart_empty', 'wpinv_empty_checkout_cart' );

/**
 * Filters the receipt page.
 */
function wpinv_filter_success_page_content( $content ) {

    // Maybe abort early.
    if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() || is_preview() ) {
        return $content;
    }

    // Ensure this is our page.
    if ( isset( $_GET['payment-confirm'] ) && wpinv_is_success_page() ) {

        $gateway = sanitize_text_field( $_GET['payment-confirm'] );
        return apply_filters( "wpinv_payment_confirm_$gateway", $content );

    }

    return $content;
}
add_filter( 'the_content', 'wpinv_filter_success_page_content', 99999 );

function wpinv_invoice_link( $invoice_id ) {
    $invoice = wpinv_get_invoice( $invoice_id );

    if ( empty( $invoice ) ) {
        return NULL;
    }

    $invoice_link = '<a href="' . esc_url( $invoice->get_view_url() ) . '">' . $invoice->get_number() . '</a>';

    return apply_filters( 'wpinv_get_invoice_link', $invoice_link, $invoice );
}

function wpinv_get_invoice_note_line_item( $note, $echo = true ) {
    if ( empty( $note ) ) {
        return NULL;
    }

    if ( is_int( $note ) ) {
        $note = get_comment( $note );
    }

    if ( !( is_object( $note ) && is_a( $note, 'WP_Comment' ) ) ) {
        return NULL;
    }

    $note_classes   = array( 'note' );
    $note_classes[] = get_comment_meta( $note->comment_ID, '_wpi_customer_note', true ) ? 'customer-note' : '';
    $note_classes[] = $note->comment_author === 'System' ? 'system-note' : '';
    $note_classes   = apply_filters( 'wpinv_invoice_note_class', array_filter( $note_classes ), $note );
    $note_classes   = !empty( $note_classes ) ? implode( ' ', $note_classes ) : '';

    ob_start();
    ?>
    <li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo esc_attr( $note_classes ); ?> mb-2">
        <div class="note_content">

            <?php echo wptexturize( wp_kses_post( $note->comment_content ) ); ?>

            <?php if ( ! is_admin() ) : ?>
                <em class="small form-text text-muted mt-0">
                    <?php
                        printf(
                            __( '%1$s - %2$s at %3$s', 'invoicing' ),
                            $note->comment_author,
                            getpaid_format_date_value( $note->comment_date ),
                            date_i18n( get_option( 'time_format' ), strtotime( $note->comment_date ) )
                        );
                    ?>
                </em>
            <?php endif; ?>

        </div>

        <?php if ( is_admin() ) : ?>

            <p class="meta px-4 py-2">
                <abbr class="exact-date" title="<?php echo esc_attr( $note->comment_date ); ?>">
                    <?php
                        printf(
                            __( '%1$s - %2$s at %3$s', 'invoicing' ),
                            $note->comment_author,
                            getpaid_format_date_value( $note->comment_date ),
                            date_i18n( get_option( 'time_format' ), strtotime( $note->comment_date ) )
                        );
                    ?>
                </abbr>&nbsp;&nbsp;
                <?php if ( $note->comment_author !== 'System' && wpinv_current_user_can_manage_invoicing() ) { ?>
                    <a href="#" class="delete_note"><?php _e( 'Delete note', 'invoicing' ); ?></a>
                <?php } ?>
            </p>

        <?php endif; ?>
        
    </li>
    <?php
    $note_content = ob_get_clean();
    $note_content = apply_filters( 'wpinv_get_invoice_note_line_item', $note_content, $note, $echo );

    if ( $echo ) {
        echo $note_content;
    } else {
        return $note_content;
    }
}

/**
 * Function to get privacy policy text.
 *
 * @since 1.0.13
 * @return string
 */
function wpinv_get_policy_text() {
    $privacy_page_id = get_option( 'wp_page_for_privacy_policy', 0 );

    $text = wpinv_get_option('invoicing_privacy_checkout_message', sprintf( __( 'Your personal data will be used to process your invoice, payment and for other purposes described in our %s.', 'invoicing' ), '[wpinv_privacy_policy]' ));

    if(!$privacy_page_id){
        $privacy_page_id = wpinv_get_option( 'privacy_page', 0 );
    }

    $privacy_link    = $privacy_page_id ? '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '" class="wpinv-privacy-policy-link" target="_blank">' . __( 'privacy policy', 'invoicing' ) . '</a>' : __( 'privacy policy', 'invoicing' );

    $find_replace = array(
        '[wpinv_privacy_policy]' => $privacy_link,
    );

    $privacy_text = str_replace( array_keys( $find_replace ), array_values( $find_replace ), $text );

    return wp_kses_post(wpautop($privacy_text));
}

function wpinv_oxygen_fix_conflict() {
    global $ct_ignore_post_types;

    if ( ! is_array( $ct_ignore_post_types ) ) {
        $ct_ignore_post_types = array();
    }

    $post_types = array( 'wpi_discount', 'wpi_invoice', 'wpi_item', 'wpi_payment_form' );

    foreach ( $post_types as $post_type ) {
        $ct_ignore_post_types[] = $post_type;

        // Ignore post type
        add_filter( 'pre_option_oxygen_vsb_ignore_post_type_' . $post_type, '__return_true', 999 );
    }

    remove_filter( 'template_include', 'wpinv_template', 10, 1 );
    add_filter( 'template_include', 'wpinv_template', 999, 1 );
}

/**
 * Helper function to display a payment form on the frontend.
 * 
 * @param GetPaid_Payment_Form $form
 */
function getpaid_display_payment_form( $form ) {

    if ( is_numeric( $form ) ) {
        $form = new GetPaid_Payment_Form( $form );
    }

    $form->display();

}

/**
 * Helper function to display a item payment form on the frontend.
 */
function getpaid_display_item_payment_form( $items ) {

    $form = new GetPaid_Payment_Form( wpinv_get_default_payment_form() );
    $form->set_items( $items );

    if ( 0 == count( $form->get_items() ) ) {
        echo aui()->alert(
			array(
				'type'    => 'warning',
				'content' => __( 'No published items found', 'invoicing' ),
			)
        );
        return;
    }

    $extra_items     = esc_attr( getpaid_convert_items_to_string( $items ) );
    $extra_items_key = md5( NONCE_KEY . AUTH_KEY . $extra_items );
    $extra_items     = "<input type='hidden' name='getpaid-form-items' value='$extra_items' />";
    $extra_items    .= "<input type='hidden' name='getpaid-form-items-key' value='$extra_items_key' />";

    $form->display( $extra_items );
}

/**
 * Helper function to display an invoice payment form on the frontend.
 */
function getpaid_display_invoice_payment_form( $invoice_id ) {

    $invoice = wpinv_get_invoice( $invoice_id );

    if ( empty( $invoice ) ) {
		echo aui()->alert(
			array(
				'type'    => 'warning',
				'content' => __( 'Invoice not found', 'invoicing' ),
			)
        );
        return;
    }

    if ( $invoice->is_paid() ) {
		echo aui()->alert(
			array(
				'type'    => 'warning',
				'content' => __( 'Invoice has already been paid', 'invoicing' ),
			)
        );
        return;
    }

    $form = new GetPaid_Payment_Form( wpinv_get_default_payment_form() );
    $form->set_items( $invoice->get_items() );

    $form->display();
}

/**
 * Helper function to convert item string to array.
 */
function getpaid_convert_items_to_array( $items ) {
    $items    = array_filter( array_map( 'trim', explode( ',', $items ) ) );
    $prepared = array();

    foreach ( $items as $item ) {
        $data = array_map( 'trim', explode( '|', $item ) );

        if ( empty( $data[0] ) || ! is_numeric( $data[0] ) ) {
            continue;
        }

        $quantity = 1;
        if ( isset( $data[1] ) && is_numeric( $data[1] ) ) {
            $quantity = (float) $data[1];
        }

        $prepared[ $data[0] ] = $quantity;

    }

    return $prepared;
}

/**
 * Helper function to convert item array to string.
 */
function getpaid_convert_items_to_string( $items ) {
    $prepared = array();

    foreach ( $items as $item => $quantity ) {
        $prepared[] = "$item|$quantity";
    }
    return implode( ',', $prepared );
}

/**
 * Helper function to display a payment item.
 * 
 * Provide a label and one of $form, $items or $invoice.
 */
function getpaid_get_payment_button( $label, $form = null, $items = null, $invoice = null ) {
    $label = sanitize_text_field( $label );

    if ( ! empty( $form ) ) {
        $form  = esc_attr( $form );
        return "<button class='btn btn-primary getpaid-payment-button' type='button' data-form='$form'>$label</button>"; 
    }
	
	if ( ! empty( $items ) ) {
        $items  = esc_attr( $items );
        return "<button class='btn btn-primary getpaid-payment-button' type='button' data-item='$items'>$label</button>"; 
    }
    
    if ( ! empty( $invoice ) ) {
        $invoice  = esc_attr( $invoice );
        return "<button class='btn btn-primary getpaid-payment-button' type='button' data-invoice='$invoice'>$label</button>"; 
    }

}

/**
 * Display invoice description before line items.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_the_invoice_description( $invoice ) {
    $description = $invoice->get_description();

    if ( empty( $description ) ) {
        return;
    }

    $description = wp_kses_post( wpautop( $description ) );
    echo "<small class='getpaid-invoice-description text-dark p-2 form-text' style='margin-bottom: 20px; border-left: 2px solid #2196F3;'><em>$description</em></small>";
}
add_action( 'getpaid_invoice_line_items', 'getpaid_the_invoice_description', 100 );
add_action( 'wpinv_email_billing_details', 'getpaid_the_invoice_description', 100 );

/**
 * Render element on a form.
 *
 * @param array $element
 * @param GetPaid_Payment_Form $form
 */
function getpaid_payment_form_element( $element, $form ) {

    // Set up the args.
    $element_type    = trim( $element['type'] );
    $element['form'] = $form;
    extract( $element );

    // Try to locate the appropriate template.
    $located = wpinv_locate_template( "payment-forms/elements/$element_type.php" );
    
    // Abort if this is not our element.
    if ( empty( $located ) || ! file_exists( $located ) ) {
        return;
    }

    // Generate the class and id of the element.
    $wrapper_class = 'getpaid-payment-form-element-' . trim( esc_attr( $element_type ) );
    $id            = isset( $id ) ? $id : uniqid( 'gp' );

    // Echo the opening wrapper.
    echo "<div class='getpaid-payment-form-element $wrapper_class'>";

    // Fires before displaying a given element type's content.
    do_action( "getpaid_before_payment_form_{$element_type}_element", $element, $form );

    // Include the template for the element.
    include $located;

    // Fires after displaying a given element type's content.
    do_action( "getpaid_payment_form_{$element_type}_element", $element, $form );

    // Echo the closing wrapper.
    echo '</div>';
}
add_action( 'getpaid_payment_form_element', 'getpaid_payment_form_element', 10, 2 );

/**
 * Render an element's edit page.
 *
 * @param WP_Post $post
 */
function getpaid_payment_form_edit_element_template( $post ) {

    // Retrieve all elements.
    $all_elements = wp_list_pluck( wpinv_get_data( 'payment-form-elements' ), 'type' );

    foreach ( $all_elements as $element ) {

        // Try to locate the appropriate template.
        $element = sanitize_key( $element );
        $located = wpinv_locate_template( "payment-forms-admin/edit/$element.php" );

        // Continue if this is not our element.
        if ( empty( $located ) || ! file_exists( $located ) ) {
            continue;
        }

        // Include the template for the element.
        echo "<div v-if=\"active_form_element.type=='$element'\">";
        include $located;
        echo '</div>';
    }

}
add_action( 'getpaid_payment_form_edit_element_template', 'getpaid_payment_form_edit_element_template' );

/**
 * Render an element's preview.
 *
 */
function getpaid_payment_form_render_element_preview_template() {

    // Retrieve all elements.
    $all_elements = wp_list_pluck( wpinv_get_data( 'payment-form-elements' ), 'type' );

    foreach ( $all_elements as $element ) {

        // Try to locate the appropriate template.
        $element = sanitize_key( $element );
        $located = wpinv_locate_template( "payment-forms-admin/previews/$element.php" );

        // Continue if this is not our element.
        if ( empty( $located ) || ! file_exists( $located ) ) {
            continue;
        }

        // Include the template for the element.
        echo "<div v-if=\"form_element.type=='$element'\">";
        include $located;
        echo '</div>';
    }

}
add_action( 'wpinv_payment_form_render_element_template', 'getpaid_payment_form_render_element_preview_template' );

/**
 * Shows a list of gateways that support recurring payments.
 */
function wpinv_get_recurring_gateways_text() {
    $gateways = array();

    foreach ( wpinv_get_payment_gateways() as $key => $gateway ) {
        if ( wpinv_gateway_support_subscription( $key ) ) {
            $gateways[] = sanitize_text_field( $gateway['admin_label'] );
        }
    }

    if ( empty( $gateways ) ) {
        return "<span class='form-text text-danger'>" . __( 'No active gateways support subscription payments.', 'invoicing' ) ."</span>";
    }

    return "<span class='form-text text-muted'>" . wp_sprintf( __( 'Subscription payments only supported by: %s', 'invoicing' ), implode( ', ', $gateways ) ) ."</span>";

}

/**
 * Returns the template.
 * 
 * @return GetPaid_Template
 */
function getpaid_template() {
    return getpaid()->get( 'template' );
}

/**
 * Displays pagination links.
 * 
 * @param array args
 * @return string
 */
function getpaid_paginate_links( $args ) {
    return str_replace( 'page-link dots', 'page-link text-dark', aui()->pagination( $args ) );
}

/**
 * Displays the states select markup.
 * 
 * @param string country
 * @param string state
 * @return string
 */
function getpaid_get_states_select_markup( $country, $state, $placeholder, $label, $help_text, $required = false, $wrapper_class = 'col-12', $field_name = 'wpinv_state' ) {

    $states = wpinv_get_country_states( $country );
    $uniqid = uniqid( '_' );

    if ( ! empty( $states ) ) {

        return aui()->select( array(
            'options'          => $states,
            'name'             => esc_attr( $field_name ),
            'id'               => sanitize_html_class( $field_name ) . $uniqid,
            'value'            => sanitize_text_field( $state ),
            'placeholder'      => $placeholder,
            'required'         => $required,
            'label'            => wp_kses_post( $label ),
            'label_type'       => 'vertical',
            'help_text'        => $help_text,
            'class'            => 'getpaid-address-field wpinv_state',
            'wrap_class'       => "$wrapper_class getpaid-address-field-wrapper__state",
            'label_class'      => 'getpaid-address-field-label getpaid-address-field-label__state',
            'extra_attributes' => array(
                'autocomplete' => "address-level1",
            ),
        ));

    }

    return aui()->input(
        array(
            'name'        => esc_attr( $field_name ),
            'id'          => sanitize_html_class( $field_name ) . $uniqid,
            'placeholder' => $placeholder,
            'required'    => $required,
            'label'       => wp_kses_post( $label ),
            'label_type'  => 'vertical',
            'help_text'   => $help_text,
            'value'       => sanitize_text_field( $state ),
            'class'       => 'getpaid-address-field wpinv_state',
            'wrap_class'  => "$wrapper_class getpaid-address-field-wrapper__state",
            'label_class' => 'getpaid-address-field-label getpaid-address-field-label__state',
            'extra_attributes' => array(
                'autocomplete' => "address-level1",
            ),
        )
    );

}

/**
 * Retrieves an element's grid width.
 * 
 * @param array $element
 * @return string
 */
function getpaid_get_form_element_grid_class( $element ) {

    $class = "col-12";
    $width = empty( $element['grid_width'] ) ? 'full' : $element['grid_width'];

    if ( $width == 'half' ) {
        $class .= " col-md-6";
    }

    if ( $width == 'third' ) {
        $class .= " col-md-4";
    }

    return $class;
}

/**
 * Retrieves the payment form embed URL.
 *
 * @param int $payment_form payment form.
 * @param string $items form items.
 *
 * @return string
 */
function getpaid_embed_url( $payment_form = false, $items = false ) {

    return add_query_arg(
        array(
            'getpaid_embed' => 1,
            'form'          => $payment_form ? absint( $payment_form ) : false,
            'item'          => $items ? urlencode( $items ) : false
        ),
        home_url( 'index.php' )
    );

}

/**
 * Embeds a payment form.
 *
 * @return string
 */
function getpaid_filter_embed_template( $template ) {

    if ( isset( $_GET['getpaid_embed'] ) ) {
        wpinv_get_template( 'payment-forms/embed.php' );
        exit;
    }

    return $template;
}
add_filter( 'template_include', 'getpaid_filter_embed_template' );
