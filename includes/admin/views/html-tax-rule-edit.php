<?php
/**
 * Displays a single row when editing a tax rule.
 *
 * @var array $tax_rule
 */

defined( 'ABSPATH' ) || exit;

?>

<tr>

    <td class="wpinv-tax-rule-key">
        <input type="text" name="tax_rules[<?php echo esc_attr( $tax_rule['key'] ); ?>][key]" value="<?php echo esc_attr( $tax_rule['key'] ); ?>" required/>
    </td>

    <td class="wpinv-tax-rule-label">
        <input type="text" name="tax_rules[<?php echo esc_attr( $tax_rule['key'] ); ?>][label]" value="<?php echo esc_attr( $tax_rule['label'] ); ?>" required/>
    </td>

    <td class="wpinv-tax-rule-base-address">
        <select name="tax_rules[<?php echo esc_attr( $tax_rule['key'] ); ?>][tax_base]" class="getpaid-tax-rule-base-address" required>
            <option value="billing" <?php selected( $tax_rule['tax_base'], 'billing' ); ?>><?php esc_html_e( 'Customer billing address', 'invoicing' ); ?></option>
            <option value="base" <?php selected( $tax_rule['tax_base'], 'base' ); ?>><?php esc_html_e( 'Shop base address', 'invoicing' ); ?></option>
        </select>
    </td>

    <td class="wpinv_tax_remove">
        <button type="button" class="close btn-close wpinv_remove_tax_rule" aria-label="<?php esc_attr_e( 'Delete', 'invoicing' ); ?>" title="<?php esc_attr_e( 'Delete', 'invoicing' ); ?>">
            <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
                <span aria-hidden="true">×</span>
            <?php endif; ?>
        </button>
    </td>

</tr>
