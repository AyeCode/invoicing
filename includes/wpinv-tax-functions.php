<?php
/**
 * Contains the tax functions.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the tax class objet.
 * 
 * @return WPInv_EUVat
 */
function getpaid_tax() {
    return getpaid()->tax;
}

/**
 * Checks if a given country is an EU state.
 * 
 * @return bool
 */
function getpaid_is_eu_state( $country ) {
    return WPInv_EUVat::is_eu_state( $country );
}

/**
 * Checks if a given country is GST country.
 * 
 * @return bool
 */
function getpaid_is_gst_country( $country ) {
    return WPInv_EUVat::is_gst_country( $country );
}

/**
 * Returns the vat name.
 * 
 * @return string
 */
function getpaid_vat_name() {
    return getpaid_tax()->get_vat_name();
}

/**
 * Checks whether or not taxes are enabled.
 *
 * @return bool
 */
function wpinv_use_taxes() {

    $ret = wpinv_get_option( 'enable_taxes', false );
    return (bool) apply_filters( 'wpinv_use_taxes', ! empty( $ret ) );

}

/**
 * Checks whether or not an invoice is taxable.
 *
 * @param WPInv_Invoice $invoice
 * @return bool
 */
function wpinv_is_invoice_taxable( $invoice ) {
    return $invoice->is_taxable();
}

/**
 * Checks whether or not an item is taxable.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @return bool
 */
function wpinv_is_item_taxable( $item ) {
    return '_exempt' != $item->get_vat_rule();
}

/**
 * Checks whether or not taxes are calculated based on the store address.
 *
 * @return bool
 */
function wpinv_use_store_address_as_tax_base() {
    $use_base = wpinv_get_option( 'tax_base', 'billing' ) == 'base';
    return (bool) apply_filters( 'wpinv_use_store_address_as_tax_base', $use_base );
}

/**
 * Checks whether or not prices include tax.
 *
 * @return bool
 */
function wpinv_prices_include_tax() {
    $is_inclusive = wpinv_get_option( 'prices_include_tax', 'no' ) == 'yes';
    return (bool) apply_filters( 'wpinv_prices_include_tax', $is_inclusive );
}

/**
 * Checks whether we should round per rate or per subtotal
 *
 * @return bool
 */
function wpinv_round_tax_per_tax_rate() {
    $subtotal_rounding = wpinv_get_option( 'tax_subtotal_rounding', 1 );
    return (bool) apply_filters( 'wpinv_round_tax_per_tax_rate', empty( $subtotal_rounding ) );
}

/**
 * Checks whether we should display individual tax rates.
 *
 * @return bool
 */
function wpinv_display_individual_tax_rates() {
    $individual = wpinv_get_option( 'tax_display_totals', 'single' ) == 'individual';
    return (bool) apply_filters( 'wpinv_display_individual_tax_rates', $individual );
}

/**
 * Retrieves the default tax rate.
 *
 * @return float
 */
function wpinv_get_default_tax_rate() {
    $rate = wpinv_get_option( 'tax_rate', false );
    return (float) apply_filters( 'wpinv_get_default_tax_rate', floatval( $rate ) );
}

/**
 * Retrieves an array of all tax rates.
 *
 * @return array
 */
function wpinv_get_tax_rates() {
    return GetPaid_Tax::get_all_tax_rates();
}

/**
 * Retrieves an item's tax rates.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @param string $country
 * @param string $state
 * @return array
 */
function getpaid_get_item_tax_rates( $item, $country = '', $state = '' ) {

    // Abort if the item is not taxable.
    if ( ! wpinv_is_item_taxable( $item ) ) {
        return array();
    }

    // Maybe use the store address.
    if ( wpinv_use_store_address_as_tax_base() ) {
        $country = wpinv_get_default_country();
        $state   = wpinv_get_default_state();
    }

    // Retrieve tax rates.
    $tax_rates = GetPaid_Tax::get_address_tax_rates( $country, $state );

    // Fallback to the default tax rates if non were found.
    if ( empty( $tax_rates ) ) {
        $tax_rates = GetPaid_Tax::get_default_tax_rates();
    }

    return apply_filters( 'getpaid_get_item_tax_rates', $tax_rates, $item, $country, $state );
}

/**
 * Retrieves an item's taxes.
 *
 * @param float $amount
 * @param array $rates
 * @return array
 */
function getpaid_calculate_item_taxes( $amount, $rates ) {

    $is_inclusive = wpinv_prices_include_tax();
    $taxes        = GetPaid_Tax::calc_tax( $amount, $rates, $is_inclusive );

    return apply_filters( 'getpaid_calculate_taxes', $taxes, $amount, $rates );
}

/**
 * Prepares an item's tax.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @param string $tax_name
 * @param float $tax_amount
 * @param float $recurring_tax_amount
 * @return array
 */
function getpaid_prepare_item_tax( $item, $tax_name, $tax_amount, $recurring_tax_amount ) {

    $initial_tax   = $tax_amount;
	$recurring_tax = 0;

    if ( $item->is_recurring() ) {
		$recurring_tax = $recurring_tax_amount;
	}

	return array(
		'name'          => sanitize_text_field( $tax_name ),
		'initial_tax'   => $initial_tax,
		'recurring_tax' => $recurring_tax,
    );

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
                                        'class'            => 'wpi_select2',
                                        'options'          => $vat_groups,
                                        'multiple'         => false,
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

/**
 * Filters the VAT rules to ensure that each item has a VAT rule.
 * 
 * @param string|bool|null $vat_rule
 */
function getpaid_filter_vat_rule( $vat_rule ) {

    if ( empty( $vat_rule ) ) {        
        return getpaid_tax()->allow_vat_rules() ? 'digital' : 'physical';
    }

    return $vat_rule;
}
add_filter( 'wpinv_get_item_vat_rule', 'getpaid_filter_vat_rule' );

/**
 * Filters the VAT class to ensure that each item has a VAT class.
 * 
 * @param string|bool|null $vat_rule
 */
function getpaid_filter_vat_class( $vat_class ) {
    return empty( $vat_class ) ? '_standard' : $vat_class;
}
add_filter( 'wpinv_get_item_vat_class', 'getpaid_filter_vat_class' );
