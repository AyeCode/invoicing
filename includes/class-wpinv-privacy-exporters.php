<?php
/**
 * Personal data exporters.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Privacy_Exporters Class.
 */
class WPInv_Privacy_Exporters {
    /**
     * Finds and exports customer data by email address.
     *
     * @since 1.0.13
     * @param string $email_address The user email address.
     * @param int    $page  Page.
     * @return array An array of invoice data in name value pairs
     */
    public static function customer_invoice_data_exporter( $email_address, $page ) {
        $done           = false;
        $page           = (int) $page;
        $data_to_export = array();

        $user           = get_user_by( 'email', $email_address );
        if ( ! $user instanceof WP_User ) {
            return array(
                'data' => $data_to_export,
                'done' => true,
            );
        }

        $args    = array(
            'limit'    => 30,
            'page'     => $page,
            'user'     => $user->ID,
        );

        $invoices = wpinv_get_invoices( $args );

        if ( 0 < count( $invoices ) ) {
            foreach ( $invoices as $invoice ) {
                $data_to_export[] = array(
                    'group_id'          => 'customer_invoices',
                    'group_label'       => __( 'Invoicing Data', 'invoicing' ),
                    'group_description' => __( 'Customer invoicing data.', 'invoicing' ),
                    'item_id'           => "wpinv-{$invoice->ID}",
                    'data'              => self::get_customer_invoice_data( $invoice ),
                );
            }
            $done = 30 > count( $invoices );
        } else {
            $done = true;
        }

        return array(
            'data' => $data_to_export,
            'done' => $done,
        );
    }

    /**
     * Get invoice data (key/value pairs) for a user.
     *
     * @since 1.0.13
     * @param WPInv_Invoice $invoice invoice object.
     * @return array
     */
    public static function get_customer_invoice_data( $invoice ) {
        $personal_data = array();

        $props_to_export = array(
            'number'               => __( 'Invoice Number', 'invoicing' ),
            'created_date'         => __( 'Invoice Date', 'invoicing' ),
            'status'               => __( 'Invoice Status', 'invoicing' ),
            'total'                => __( 'Invoice Total', 'invoicing' ),
            'items'                => __( 'Invoice Items', 'invoicing' ),
            'first_name'           => __( 'First Name', 'invoicing' ),
            'last_name'            => __( 'Last Name', 'invoicing' ),
            'email'                => __( 'Email Address', 'invoicing' ),
            '_wpinv_company'       => __( 'Company', 'invoicing' ),
            'phone'                => __( 'Phone Number', 'invoicing' ),
            'address'              => __( 'Address', 'invoicing' ),
            '_wpinv_city'          => __( 'City', 'invoicing' ),
            '_wpinv_country'       => __( 'Country', 'invoicing' ),
            '_wpinv_state'         => __( 'State', 'invoicing' ),
            '_wpinv_zip'           => __( 'Zip Code', 'invoicing' ),
        );

        $subscription = wpinv_get_subscription( $invoice );
        $period = $initial_amt = $bill_times = $billed = $renewal_date = '';

        if ( $invoice->is_recurring() && !empty( $subscription ) ) {
            $frequency = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->period,$subscription->frequency );
            $period = wpinv_price( wpinv_format_amount( $subscription->recurring_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) ) . ' / ' . $frequency;
            $initial_amt = wpinv_price( wpinv_format_amount( $subscription->initial_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) );
            $bill_times = $subscription->get_times_billed() . ' / ' . ( ( $subscription->bill_times == 0 ) ? 'Until Cancelled' : $subscription->bill_times );
            $renewal_date = ! empty( $subscription->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration ) ) : __( 'N/A', 'invoicing' );

            $props_to_export['period'] = __( 'Billing Cycle', 'invoicing' );
            $props_to_export['initial_amount'] = __( 'Initial Amount', 'invoicing' );
            $props_to_export['bill_times'] = __( 'Times Billed', 'invoicing' );
            $props_to_export['renewal_date'] = __( 'Renewal Date', 'invoicing' );
        }

        $props_to_export['ip'] = __( 'IP Address', 'invoicing' );
        $props_to_export['view_url'] = __( 'Invoice Link', 'invoicing' );

        $props_to_export = apply_filters( 'wpinv_privacy_export_invoice_personal_data_props', $props_to_export, $invoice, $subscription);

        foreach ( $props_to_export as $prop => $name ) {
            $value = '';

            switch ( $prop ) {
                case 'items':
                    $item_names = array();
                    foreach ( $invoice->get_cart_details() as $key => $cart_item ) {
                        $item_quantity  = $cart_item['quantity'] > 0 ? absint( $cart_item['quantity'] ) : 1;
                        $item_names[] = $cart_item['name'] . ' x ' . $item_quantity;
                    }
                    $value = implode( ', ', $item_names );
                    break;
                case 'status':
                    $value = $invoice->get_status(true);
                    break;
                case 'total':
                    $value = $invoice->get_total(true);
                    break;
                case 'period':
                    $value = $period;
                    break;
                case 'initial_amount':
                    $value = $initial_amt;
                    break;
                case 'bill_times':
                    $value = $bill_times;
                    break;
                case 'renewal_date':
                    $value = $renewal_date;
                    break;
                default:
                    if ( is_callable( array( $invoice, 'get_' . $prop ) ) ) {
                        $value = $invoice->{"get_$prop"}();
                    } else {
                        $value = $invoice->get_meta($prop);
                    }
                    break;
            }

            $value = apply_filters( 'wpi_privacy_export_invoice_personal_data_prop', $value, $prop, $invoice );

            if ( $value ) {
                $personal_data[] = array(
                    'name'  => $name,
                    'value' => $value,
                );
            }

        }

        $personal_data = apply_filters( 'wpinv_privacy_export_invoice_personal_data', $personal_data, $invoice );

        return $personal_data;

    }

}
