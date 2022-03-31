<?php
/**
 * Displays the tax rates table.
 *
 */

defined( 'ABSPATH' ) || exit;

$tax_rates  = GetPaid_Tax::get_all_tax_rates();
$dummy_rate = array(
    'country'      => '',
    'state'        => '',
    'global'       => true,
    'rate'         => wpinv_get_default_tax_rate(),
    'reduced_rate' => 5,
    'name'         => __( 'VAT', 'invoicing' ),
);

$reset_url = wp_nonce_url(
    add_query_arg( 'getpaid-admin-action', 'reset_tax_rates' ),
    'getpaid-nonce',
    'getpaid-nonce'
);

?>
<div class="table-responsive">
    <table id="wpinv_tax_rates" class="widefat fixed table">
        <caption><?php echo esc_html_e( 'Enter tax rates for specific regions.', 'invoicing' ); ?></caption>

        <thead>
            <tr class="table-light">

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Country', 'invoicing' ); ?>
                    <?php getpaid_get_help_tip( __( 'Optionally limit this tax rate to a specific country.', 'invoicing' ), 'position-static', true ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'State', 'invoicing' ); ?>
                    <?php getpaid_get_help_tip( __( 'Separate state codes using a comma or leave blank to apply country wide.', 'invoicing' ), 'position-static', true ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Standard Rate %', 'invoicing' ); ?>
                    <?php getpaid_get_help_tip( __( 'The tax rate (percentage) to charge on items that use the "Standard rate" tax class.', 'invoicing' ), 'position-static', true ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Reduced Rate %', 'invoicing' ); ?>
                    <?php getpaid_get_help_tip( __( 'The tax rate (percentage) to charge on items that use the "Reduced rate" tax class.', 'invoicing' ), 'position-static', true ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Tax Name', 'invoicing' ); ?>
                    <?php getpaid_get_help_tip( __( 'The name of this tax, e.g VAT.', 'invoicing' ), 'position-static', true ); ?>
                </th>

                <th scope="col" class="border-bottom border-top" style="width:32px">&nbsp;</th>

            </tr>
        </thead>

        <tbody>
            <?php array_walk( $tax_rates, 'wpinv_tax_rate_callback' ); ?>
        </tbody>

        <tfoot>
            <tr class="table-light">
                <td colspan="6" class="border-top">

                    <button type="button" class="button button-secondary wpinv_add_tax_rate" aria-label="<?php esc_attr_e( 'Add Tax Rate', 'invoicing' ); ?>">
                        <span><?php esc_html_e( 'Add Tax Rate', 'invoicing' ); ?></span>
                    </button>

                    <a href="<?php echo esc_url( $reset_url ); ?>" class="button button-secondary wpinv_reset_tax_rates" aria-label="<?php esc_attr_e( 'Reset Tax Rates', 'invoicing' ); ?>">
                        <span><?php esc_html_e( 'Reset Tax Rates', 'invoicing' ); ?></span>
                    </a>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/html" id="tmpl-wpinv-tax-rate-row">
    <?php wpinv_tax_rate_callback( $dummy_rate, 0, true ); ?>
</script>

