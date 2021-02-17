<?php
/**
 * Displays the gateways table.
 *
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="table-responsive">
    <table id="wpinv_gateways_select" class="table border bg-white form-table">
        <caption><?php echo esc_html_e( 'This table displays installed payment methods.', 'invoicing' ); ?></caption>

        <thead>
            <tr class="table-light">

                <th scope="col" class="border-bottom border-top text-left">
                    <?php _e( 'Payment Method', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top text-center">
                    <?php _e( 'Enabled', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top text-center">
                    <?php _e( 'Supports Subscriptions', 'invoicing' ); ?>
                </th>

                <th scope="col" class="border-bottom border-top text-right" style="width:32px">&nbsp;</th>

            </tr>
        </thead>

        <tbody>
            <?php foreach ( wpinv_get_payment_gateways() as $id => $gateway ) : ?>
                <tr>
                    <td class="getpaid-payment-method text-left">
                        <a style="color: #0073aa;" href="<?php echo esc_url( add_query_arg( 'section', $id ) ); ?>" class="font-weight-bold"><?php echo sanitize_text_field( $gateway['admin_label'] ); ?></a>
                    </td>
                    <td class="getpaid-payment-method-enabled text-center">
                        <?php

                            $id = esc_attr( $id );
                            echo aui()->input(
                                array(
                                    'type'    => 'checkbox',
                                    'name'    => "wpinv_settings[gateways][$id]",
                                    'id'      => "wpinv-settings-gateways-$id",
                                    'value'   => 1,
                                    'switch'  => true,
                                    'label'   => '&nbsp;',
                                    'checked' => wpinv_is_gateway_active( $id ),
                                    'no_wrap' => true,
                                )
                            );

                        ?>
                    </td>
                    <td class="getpaid-payment-method-subscription text-center">
                        <?php

                            $supports = apply_filters( "wpinv_{$id}_support_subscription", false );
                            $supports = apply_filters( 'getapid_gateway_supports_subscription', $supports, $id );

                            if ( $supports ) {
                                echo "<i class='text-success fa fa-check'></i>";
                            } else {
                                echo "<i class='text-dark fa fa-times'></i>";
                            }

                        ?>
                    </td>

                    <td class="getpaid-payment-method-action text-right">
                        <a class="button button-secondary" href="<?php echo esc_url( add_query_arg( 'section', $id ) ); ?>"><?php _e( 'Manage', 'invoicing' ); ?></a>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr class="table-light">
                <td colspan="4" class="border-top">
                    <a class="button button-secondary getpaid-install-gateways" href="https://wpgetpaid.com/downloads/category/gateways/">
                        <span><?php _e( 'Add Payment Methods', 'invoicing' ); ?></span>
                    </a>
                </td>
            </tr>
        </tfoot>

    </table>
</div>
