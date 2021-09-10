<?php
/**
 * Displays a single row when editing a tax rate.
 * 
 * @var string $key
 * @var array $tax_rate
 */

defined( 'ABSPATH' ) || exit;

?>

<tr>

    <td class="wpinv_tax_country">
        <?php

            $country = aui()->select(
                array(
                    'options'     => array_merge(
                        array( '' => __( 'All Countries', 'invoicing' ) ),
                        wpinv_get_country_list()
                    ),
                    'name'        => "tax_rates[$key][country]",
                    'id'          => uniqid( 'tax_rate_country' ),
                    'value'       => esc_html( $tax_rate['country'] ),
                    'label'       => __( 'Country', 'invoicing' ),
                    'class'       => 'wpinv_country',
                    'no_wrap'     => true,
                )
            );

            echo str_replace( 'custom-select', '', $country );
        ?>
    </td>

    <td class="wpinv_tax_state">

        <label class="w-100">
            <span class="screen-reader-text"><?php _e( 'States', 'invoicing' ); ?></span>
            <input type="text" placeholder="<?php esc_attr_e( 'Apply to whole country', 'invoicing' ); ?>" name="tax_rates[<?php echo esc_attr( $key ) ?>][state]" value="<?php echo empty( $tax_rate['global'] ) ? esc_attr( $tax_rate['state'] ) : ''; ?>"/>
        </label>

    </td>

    <td class="wpinv_standard_rate">
        <label class="w-100">
            <span class="screen-reader-text"><?php _e( 'Standard Rate', 'invoicing' ); ?></span>
            <input type="number" step="any" min="0" max="99" name="tax_rates[<?php echo esc_attr( $key ) ?>][rate]" value="<?php echo wpinv_sanitize_amount( $tax_rate['rate'] ); ?>"/>
        </label>
    </td>

    <td class="wpinv_reduced_rate">
        <label class="w-100">
            <span class="screen-reader-text"><?php _e( 'Reduced Rate', 'invoicing' ); ?></span>
            <input type="number" step="any" min="0" max="99" name="tax_rates[<?php echo esc_attr( $key ) ?>][reduced_rate]" value="<?php echo wpinv_sanitize_amount( $tax_rate['reduced_rate'] ); ?>"/>
        </label>
    </td>

    <td class="wpinv_tax_name">
        <label class="w-100">
            <span class="screen-reader-text"><?php _e( 'Tax Name', 'invoicing' ); ?></span>
            <input type="text" name="tax_rates[<?php echo esc_attr( $key ) ?>][name]" value="<?php echo esc_attr( $tax_rate['name'] ); ?>"/>
        </label>
    </td>

    <td class="wpinv_tax_remove">
        <button type="button" class="close wpinv_remove_tax_rate" aria-label="<?php esc_attr_e( 'Delete', 'invoicing' ); ?>" title="<?php esc_attr_e( 'Delete', 'invoicing' ); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
    </td>

</tr>
