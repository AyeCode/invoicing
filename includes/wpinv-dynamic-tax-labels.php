<?php
/**
 * Dynamic Tax Labels.
 *
 * Handles dynamic tax field labels based on country-specific tax configuration.
 *
 * @package Invoicing
 * @since   2.8.35
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns translatable tax name mappings.
 *
 * Maps database-stored tax names to their translatable equivalents.
 *
 * @since 2.8.40
 *
 * @return array<string, string> Tax name => translated tax name.
 */
function getpaid_get_translatable_tax_names() {
	static $names = null;

	if ( null === $names ) {
		$names = array(
			'VAT'       => __( 'VAT', 'invoicing' ),
			'GST'       => __( 'GST', 'invoicing' ),
			'Tax'       => __( 'Tax', 'invoicing' ),
			'Tax ID'    => __( 'Tax ID', 'invoicing' ),
			'Sales Tax' => __( 'Sales Tax', 'invoicing' ),
		);
	}

	return $names;
}

/**
 * Translates a tax name if it matches a known translatable term.
 *
 * @since 2.8.40
 *
 * @param string $tax_name Raw tax name from database.
 * @return string Translated tax name, or original if no match found.
 */
function getpaid_translate_tax_name( $tax_name ) {
	$translatable = getpaid_get_translatable_tax_names();

	// Exact match.
	if ( isset( $translatable[ $tax_name ] ) ) {
		return $translatable[ $tax_name ];
	}

	// Case-insensitive fallback.
	$lower_name = strtolower( $tax_name );
	foreach ( $translatable as $key => $value ) {
		if ( strtolower( $key ) === $lower_name ) {
			return $value;
		}
	}

	return $tax_name;
}

/**
 * Retrieves the tax name for a specific country.
 *
 * @since 2.8.35
 *
 * @param string $country_code Two-letter ISO country code.
 * @return string Tax name for the country.
 */
function getpaid_get_tax_name_for_country( $country_code ) {
	$default = __( 'Tax ID', 'invoicing' );

	if ( empty( $country_code ) || ! is_string( $country_code ) ) {
		return apply_filters( 'getpaid_default_tax_name', $default );
	}

	$tax_rates = wpinv_get_tax_rates();

	if ( empty( $tax_rates ) || ! is_array( $tax_rates ) ) {
		return apply_filters( 'getpaid_default_tax_name', $default );
	}

	foreach ( $tax_rates as $rate ) {
		if ( ! empty( $rate['country'] ) && $rate['country'] === $country_code && ! empty( $rate['name'] ) ) {
			$translated = getpaid_translate_tax_name( $rate['name'] );
			return apply_filters( 'getpaid_tax_name_for_country', $translated, $country_code );
		}
	}

	return apply_filters( 'getpaid_default_tax_name', $default );
}

/**
 * Determines the tax name based on current context.
 *
 * Priority: Invoice country > Customer country > Store default country.
 *
 * @since 2.8.35
 *
 * @param WPInv_Invoice|null $invoice Optional invoice for context.
 * @return string Tax name for display.
 */
function getpaid_get_current_tax_name( $invoice = null ) {
	$country_code = '';

	// 1. Try invoice.
	if ( $invoice instanceof WPInv_Invoice ) {
		$country_code = $invoice->get_country( 'edit' );
	}

	// 2. Try logged-in customer.
	if ( empty( $country_code ) && is_user_logged_in() ) {
		$customer = getpaid_get_customer_by_user_id( get_current_user_id() );
		if ( $customer ) {
			$country_code = $customer->get( 'country' );
		}
	}

	// 3. Fallback to store default.
	if ( empty( $country_code ) ) {
		$country_code = wpinv_get_default_country();
	}

	if ( empty( $country_code ) ) {
		return apply_filters( 'getpaid_default_tax_name', __( 'Tax ID', 'invoicing' ) );
	}

	return getpaid_get_tax_name_for_country( $country_code );
}

/**
 * Filters admin invoice address fields to use dynamic tax labels.
 *
 * @since 2.8.35
 *
 * @param array         $fields  Address fields configuration.
 * @param WPInv_Invoice $invoice Invoice object.
 * @return array Modified fields.
 */
function getpaid_filter_admin_invoice_tax_label( $fields, $invoice ) {
	if ( ! isset( $fields['vat_number'] ) ) {
		return $fields;
	}

	$tax_name                      = getpaid_get_current_tax_name( $invoice );
	$fields['vat_number']['label'] = sprintf(
		/* translators: %s: Tax name (e.g., VAT, GST) */
		__( '%s Number', 'invoicing' ),
		$tax_name
	);

	return $fields;
}
add_filter( 'getpaid_admin_edit_invoice_address_fields', 'getpaid_filter_admin_invoice_tax_label', 10, 2 );

/**
 * Filters user address fields to use dynamic tax labels.
 *
 * @since 2.8.35
 *
 * @param array $fields Address fields configuration.
 * @return array Modified fields.
 */
function getpaid_filter_user_address_tax_label( $fields ) {
	static $is_running = false;

	if ( $is_running || ! isset( $fields['vat_number'] ) ) {
		return $fields;
	}

	$is_running = true;

	$country              = wpinv_get_default_country();
	$tax_name             = getpaid_get_tax_name_for_country( $country );
	$fields['vat_number'] = sprintf(
		/* translators: %s: Tax name (e.g., VAT, GST) */
		__( '%s Number', 'invoicing' ),
		$tax_name
	);

	$is_running = false;

	return $fields;
}
add_filter( 'getpaid_user_address_fields', 'getpaid_filter_user_address_tax_label', 10 );

/**
 * Builds the country-to-tax-name map for JavaScript.
 *
 * @since 2.8.40
 *
 * @return array<string, string> Country code => translated tax name.
 */
function getpaid_build_tax_map_for_js() {
	$tax_rates = wpinv_get_tax_rates();
	$tax_map   = array();

	if ( ! empty( $tax_rates ) && is_array( $tax_rates ) ) {
		foreach ( $tax_rates as $rate ) {
			if ( ! empty( $rate['country'] ) && ! empty( $rate['name'] ) ) {
				$tax_map[ $rate['country'] ] = getpaid_translate_tax_name( $rate['name'] );
			}
		}
	}

	return $tax_map;
}

/**
 * Enqueues admin script for dynamic tax label updates.
 *
 * @since 2.8.35
 *
 * @return void
 */
function getpaid_enqueue_admin_dynamic_tax_script() {
	$screen = get_current_screen();

	if ( ! $screen || 'wpi_invoice' !== $screen->post_type ) {
		return;
	}

	$tax_map     = getpaid_build_tax_map_for_js();
	$number_text = esc_js( __( 'Number', 'invoicing' ) );
	$default_tax = esc_js( __( 'Tax ID', 'invoicing' ) );

	ob_start();
	?>
	var getpaidTaxMap = <?php echo wp_json_encode( $tax_map ); ?>;
	var getpaidNumberText = "<?php echo $number_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
	var getpaidDefaultTax = "<?php echo $default_tax; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";

	( function( $ ) {
		"use strict";

		function updateTaxLabel() {
			var country = $( "#wpinv_country" ).val();
			var taxName = getpaidTaxMap[ country ] || getpaidDefaultTax;
			var $label  = $( "label[for='wpinv_vat_number']" );

			if ( $label.length ) {
				$label.text( taxName + " " + getpaidNumberText );
			}
		}

		$( function() {
			setTimeout( updateTaxLabel, 500 );
			$( document ).on( "change", "#wpinv_country", function() {
				setTimeout( updateTaxLabel, 200 );
			} );
		} );
	} )( jQuery );
	<?php
	$script = ob_get_clean();

	wp_add_inline_script( 'wpinv-admin-script', $script );
}
add_action( 'admin_enqueue_scripts', 'getpaid_enqueue_admin_dynamic_tax_script' );

/**
 * Outputs frontend script for dynamic tax label updates.
 *
 * @since 2.8.35
 *
 * @return void
 */
function getpaid_output_frontend_tax_script() {
	$tax_map     = getpaid_build_tax_map_for_js();
	$number_text = esc_js( __( 'Number', 'invoicing' ) );
	$default_tax = esc_js( __( 'Tax ID', 'invoicing' ) );

	?>
	<script>
	( function( $ ) {
		"use strict";

		var taxMap = <?php echo wp_json_encode( $tax_map ); ?>;
		var numberText = "<?php echo $number_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
		var defaultTax = "<?php echo $default_tax; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
		var selectors = [
			"#wpinv-country",
			"#wpinv_country",
			"[name='wpinv_country']",
			"[name='billing[wpinv_country]']",
			"[name='getpaid_address[country]']"
		];

		function updateLabel() {
			var country = null;

			for ( var i = 0; i < selectors.length; i++ ) {
				var $field = $( selectors[ i ] );
				if ( $field.length && $field.val() ) {
					country = $field.val();
					break;
				}
			}

			console.log( "Detected country:", country );

			var taxName = taxMap[ country ] || defaultTax;
			var label   = taxName + " " + numberText;

			$( "label[for*='vat_number']" ).text( label );
			$( "input[name*='vat_number']" ).attr( "placeholder", label );
		}

		$( document ).on( "change", selectors.join( "," ), updateLabel );

		$( function() {
			setTimeout( updateLabel, 500 );
			setTimeout( updateLabel, 1000 );
		} );
	} )( jQuery );
	</script>
	<?php
}
add_action( 'wp_footer', 'getpaid_output_frontend_tax_script', 999 );
