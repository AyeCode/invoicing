<?php
/**
 * Displays the tax rules table.
 *
 */

defined( 'ABSPATH' ) || exit;

$dummy_rule = array(
    'key'      => 'TAX_RULE_KEY',
	'label'    => __( 'New Tax Rule', 'invoicing' ),
	'tax_base' => wpinv_get_option( 'tax_base', 'billing' ),
);

wp_nonce_field( 'wpinv_tax_rules', 'wpinv_tax_rules_nonce' );

?>
<div class="table-responsive">
    <table id="wpinv-tax-rules" class="widefat fixed table">
        <caption><?php echo esc_html_e( 'You can use this section to create or edit your tax rules', 'invoicing' ); ?></caption>

        <thead>
            <tr class="table-light">

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Unique Key', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Label', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top">
                    <?php esc_html_e( 'Calculate tax based on', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top" style="width:32px">&nbsp;</th>

            </tr>
        </thead>

        <tbody>
            <?php foreach ( GetPaid_Tax::get_all_tax_rules() as $tax_rule ) : ?>
                <?php include plugin_dir_path( __FILE__ ) . 'html-tax-rule-edit.php'; ?>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr class="table-light">
                <td colspan="4" class="border-top">

                    <button type="button" class="button button-secondary wpinv_add_tax_rule" aria-label="<?php esc_attr_e( 'Add Tax Rule', 'invoicing' ); ?>">
                        <span><?php esc_html_e( 'Add Tax Rule', 'invoicing' ); ?></span>
                    </button>

                </td>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/html" id="tmpl-wpinv-tax-rule-row">
    <?php $tax_rule = $dummy_rule; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'html-tax-rule-edit.php'; ?>
</script>

