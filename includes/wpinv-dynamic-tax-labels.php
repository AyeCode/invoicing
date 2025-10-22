<?php
/**
 * Dynamic Tax Labels
 *
 * Handles dynamic tax field labels based on country-specific tax configuration.
 *
 * @package Invoicing
 * @since 2.8.35
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the tax name for a specific country code.
 *
 * Retrieves the tax name (e.g., "VAT", "GST", "Sales Tax") configured
 * for a specific country in the GetPaid tax rates.
 *
 * @since 2.8.35
 * @param string $country_code Two-letter ISO country code (e.g., "NZ", "AU", "DE").
 * @return string Tax name for the country or "Tax ID" as fallback.
 */
function getpaid_get_tax_name_for_country( $country_code ) {
	
	// Validate country code
	if ( empty( $country_code ) || ! is_string( $country_code ) ) {
		return apply_filters( 'getpaid_default_tax_name', __( 'Tax ID', 'invoicing' ) );
	}

	// Get all configured tax rates
	$tax_rates = wpinv_get_tax_rates();
	
	if ( empty( $tax_rates ) || ! is_array( $tax_rates ) ) {
		return apply_filters( 'getpaid_default_tax_name', __( 'Tax ID', 'invoicing' ) );
	}

	// Search for matching country in tax rates
	foreach ( $tax_rates as $rate ) {
		if ( isset( $rate['country'], $rate['name'] ) && $rate['country'] === $country_code && ! empty( $rate['name'] ) ) {
			return apply_filters( 'getpaid_tax_name_for_country', $rate['name'], $country_code );
		}
	}

	// Return generic fallback if no match found.
	return apply_filters( 'getpaid_default_tax_name', __( 'Tax ID', 'invoicing' ) );
}

/**
 * Get the current context tax name.
 *
 * Attempts to determine the appropriate tax name based on current context
 * (invoice, customer, or store default).
 *
 * @since 2.8.35
 * @param WPInv_Invoice|null $invoice Optional invoice object for context.
 * @return string Tax name for display.
 */
function getpaid_get_current_tax_name( $invoice = null ) {
	
	$country_code = '';

	// Try to get from invoice.
	if ( $invoice && $invoice instanceof WPInv_Invoice ) {
		$country_code = $invoice->get_country( 'edit' );
	}

	// Try to get from current customer (only if not already set from invoice).
	if ( empty( $country_code ) && is_user_logged_in() ) {
		$customer = getpaid_get_customer_by_user_id( get_current_user_id() );
		if ( ! empty( $customer ) ) {
			$country_code = $customer->get( 'country' );
		}
	}

	// Always fallback to store's base country if still empty.
	if ( empty( $country_code ) ) {
		$country_code = wpinv_get_default_country();
	}

	// Ensure we have a country code before getting tax name.
	if ( empty( $country_code ) ) {
		return apply_filters( 'getpaid_default_tax_name', __( 'Tax ID', 'invoicing' ) );
	}

	return getpaid_get_tax_name_for_country( $country_code );
}

/**
 * Filter admin invoice address fields to use dynamic tax labels.
 *
 * @since 2.8.35
 * @param array $fields Address fields configuration.
 * @param WPInv_Invoice $invoice Invoice object.
 * @return array Modified fields with dynamic tax label.
 */
function getpaid_filter_admin_invoice_tax_label( $fields, $invoice ) {
	
	if ( ! isset( $fields['vat_number'] ) ) {
		return $fields;
	}

	$tax_name = getpaid_get_current_tax_name( $invoice );
	$fields['vat_number']['label'] = sprintf( __( '%s Number', 'invoicing' ), $tax_name );

	return $fields;
}
add_filter( 'getpaid_admin_edit_invoice_address_fields', 'getpaid_filter_admin_invoice_tax_label', 10, 2 );

/**
 * Filter user address fields to use dynamic tax labels.
 *
 * @since 2.8.35
 * @param array $fields Address fields configuration.
 * @return array Modified fields with dynamic tax label.
 */
function getpaid_filter_user_address_tax_label( $fields ) {
	
	// Prevent infinite recursion.
	static $running = false;
	
	if ( $running || ! isset( $fields['vat_number'] ) ) {
		return $fields;
	}
	
	$running = true;
	
	// Get tax name based on store's default country to avoid customer lookups
	$country = wpinv_get_default_country();
	$tax_name = getpaid_get_tax_name_for_country( $country );
	$fields['vat_number'] = sprintf( __( '%s Number', 'invoicing' ), $tax_name );
	
	$running = false;
	
	return $fields;
}
add_filter( 'getpaid_user_address_fields', 'getpaid_filter_user_address_tax_label', 10 );

/**
 * Enqueue admin scripts for dynamic tax label updates.
 *
 * Adds JavaScript to update tax field labels in real-time when
 * country selection changes on invoice edit screens.
 *
 * @since 2.8.35
 */
function getpaid_enqueue_admin_dynamic_tax_script() {
	
	$screen = get_current_screen();
	
	if ( ! $screen || 'wpi_invoice' !== $screen->post_type ) {
		return;
	}

	// Build tax rate map for JavaScript
	$tax_rates = wpinv_get_tax_rates();
	$tax_map   = array();

	foreach ( $tax_rates as $rate ) {
		if ( ! empty( $rate['country'] ) && ! empty( $rate['name'] ) ) {
			$tax_map[ $rate['country'] ] = $rate['name'];
		}
	}

	// Enqueue inline script
	$script = sprintf(
		'var getpaidTaxMap = %s;
		var numberText = "%s";
		var defaultTax = "%s";
		(function($) {
			"use strict";
			
			function updateTaxLabel() {
				var country = $("#wpinv_country").val();
				var taxName = getpaidTaxMap[country] || defaultTax;
				var label = $("label[for=\'wpinv_vat_number\']");
				if (label.length) {
					label.text(`${taxName} ${numberText}`);
				}
			}
			
			// Wait for page to be fully loaded
			$(document).ready(function() {
				// Initial update
				setTimeout(updateTaxLabel, 500);
				
				// Listen for country changes with a delay to avoid conflicts
				$(document).on("change", "#wpinv_country", function() {
					setTimeout(updateTaxLabel, 200);
				});
			});
		})(jQuery);',
		wp_json_encode( $tax_map ),
		__( 'Number', 'invoicing' ),
		__( 'Tax ID', 'invoicing' )
	);

	wp_add_inline_script( 'wpinv-admin-script', $script );
}
add_action( 'admin_enqueue_scripts', 'getpaid_enqueue_admin_dynamic_tax_script' );

/**
 * Output frontend tax label updater script in footer.
 *
 * @since 2.8.35
 * @return void
 */
function getpaid_output_frontend_tax_script() {
	
	$tax_rates = wpinv_get_tax_rates();
	$tax_map   = array();

	foreach ( $tax_rates as $rate ) {
		if ( ! empty( $rate['country'] ) && ! empty( $rate['name'] ) ) {
			$tax_map[ $rate['country'] ] = $rate['name'];
		}
	}

	$tax_map_json = wp_json_encode( $tax_map );
	$default_tax  = wp_json_encode( __( 'Tax ID', 'invoicing' ) );

	?>
	<script type="text/javascript">
	const numberText = "<?php echo __( 'Number', 'invoicing' ); ?>";
	window.getpaidTaxMap = window.getpaidTaxMap || <?php echo $tax_map_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	(function($) {
		"use strict";
		
		function updateTaxLabel() {
			if (typeof window.getpaidTaxMap === "undefined") {
				return;
			}
			
			var countrySelectors = [
				"#wpinv-country",
				"#wpinv_country",
				"[name='wpinv_country']",
				"[name='getpaid_address[country]']"
			];
			
			var selectedCountry = null;
			for (var i = 0; i < countrySelectors.length; i++) {
				var $field = $(countrySelectors[i]);
				if ($field.length && $field.val()) {
					selectedCountry = $field.val();
					break;
				}
			}
			
			var taxName = window.getpaidTaxMap[selectedCountry] || <?php echo $default_tax; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			
			$("label[for*='vat_number']").text(`${taxName} ${numberText}`);
			$("input[name*='vat_number']").attr("placeholder", `${taxName} ${numberText}`);
		}
		
		$(document).on("change", "#wpinv-country, #wpinv_country, [name='wpinv_country'], [name='getpaid_address[country]']", updateTaxLabel);
		$(document).ready(function() {
			setTimeout(updateTaxLabel, 500);
			setTimeout(updateTaxLabel, 1000);
		});
		
	})(jQuery);
	</script>
	<?php
}

add_action( 'wp_footer', 'getpaid_output_frontend_tax_script', 999 );