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
            'limit'    => get_option( 'posts_per_page' ),
            'page'     => $page,
            'user'     => $user->ID,
            'paginate' => false,
        );

        $invoices = wpinv_get_invoices( $args );

        if ( 0 < count( $invoices ) ) {
            foreach ( $invoices as $invoice ) {
                $data_to_export[] = array(
                    'group_id'          => 'customer_invoices',
                    'group_label'       => __( 'GetPaid: Invoices', 'invoicing' ),
                    'group_description' => __( 'Customer invoices.', 'invoicing' ),
                    'item_id'           => "wpinv-{$invoice->get_id()}",
                    'data'              => self::get_customer_invoice_data( $invoice ),
                );
            }
            $done = get_option( 'posts_per_page' ) > count( $invoices );
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

        // Prepare basic properties.
        $props_to_export = array(
            'number'               => array(
                'name' => __( 'Invoice Number', 'invoicing' ),
                'value' => $invoice->get_number(),
            ),
            'created_date'         => array(
                'name' => __( 'Created Date', 'invoicing' ),
                'value' => $invoice->get_date_created(),
            ),
            'due_date'         => array(
                'name' => __( 'Due Date', 'invoicing' ),
                'value' => $invoice->get_due_date(),
            ),
            'items'                => array(
                'name' => __( 'Invoice Items', 'invoicing' ),
                'value' => self::process_invoice_items( $invoice ),
            ),
            'discount'                => array(
                'name' => __( 'Invoice Discount', 'invoicing' ),
                'value' => wpinv_price( $invoice->get_total_discount(), $invoice->get_currency() ),
            ),
            'total'                => array(
                'name' => __( 'Invoice Total', 'invoicing' ),
                'value' => wpinv_price( $invoice->get_total(), $invoice->get_currency() ),
            ),
            'status'               => array(
                'name' => __( 'Invoice Status', 'invoicing' ),
                'value' => $invoice->get_status_nicename(),
            ),
            'first_name'           => array(
                'name' => __( 'First Name', 'invoicing' ),
                'value' => $invoice->get_first_name(),
            ),
            'last_name'           => array(
                'name' => __( 'Last Name', 'invoicing' ),
                'value' => $invoice->get_last_name(),
            ),
            'email'           => array(
                'name' => __( 'Email Address', 'invoicing' ),
                'value' => $invoice->get_email(),
            ),
            'company'           => array(
                'name' => __( 'Company', 'invoicing' ),
                'value' => $invoice->get_company(),
            ),
            'phone'           => array(
                'name' => __( 'Phone Number', 'invoicing' ),
                'value' => $invoice->get_phone(),
            ),
            'address'           => array(
                'name' => __( 'Address', 'invoicing' ),
                'value' => $invoice->get_address(),
            ),
            'city'           => array(
                'name' => __( 'City', 'invoicing' ),
                'value' => $invoice->get_city(),
            ),
            'state'           => array(
                'name' => __( 'State', 'invoicing' ),
                'value' => $invoice->get_state(),
            ),
            'zip'           => array(
                'name' => __( 'Zip', 'invoicing' ),
                'value' => $invoice->get_zip(),
            ),
            'vat_number'    => array(
                'name' => __( 'VAT Number', 'invoicing' ),
                'value' => $invoice->get_vat_number(),
            ),
            'description'   => array(
                'name' => __( 'Description', 'invoicing' ),
                'value' => $invoice->get_description(),
            ), 
        );

        // In case the invoice is paid, add the payment date and gateway.
        if ( $invoice->is_paid() ) {

            $props_to_export['completed_date'] = array(
                'name' => __( 'Completed Date', 'invoicing' ),
                'value' => $invoice->get_completed_date(),
            );

            $props_to_export['gateway'] = array(
                'name' => __( 'Paid Via', 'invoicing' ),
                'value' => $invoice->get_gateway(),
            );

        }

        // Maybe add subscription details.
        $props_to_export = self::process_subscription( $invoice, $props_to_export );

        // Add the ip address.
        $props_to_export['ip'] = array(
            'name' => __( 'IP Address', 'invoicing' ),
            'value' => $invoice->get_ip(),
        );

        // Add the invoice url.
        $props_to_export['view_url'] = array(
            'name' => __( 'Invoice URL', 'invoicing' ),
            'value' => $invoice->get_view_url(),
        );

        // Return the values.
        return apply_filters( 'getpaid_privacy_export_invoice_personal_data', array_values( $props_to_export ), $invoice );

    }

    /**
     * Processes invoice subscriptions.
     *
     * @since 2.0.7
     * @param WPInv_Invoice $invoice invoice object.
     * @param array $props invoice props.
     * @return array
     */
    public static function process_subscription( $invoice, $props ) {

        $subscription = wpinv_get_subscription( $invoice );
        if ( ! empty( $subscription ) ) {

            $frequency    = getpaid_get_subscription_period_label( $subscription->get_period(),$subscription->get_frequency() );
            $period       = wpinv_price( $subscription->get_recurring_amount(), $subscription->get_parent_payment()->get_currency() ) . ' / ' . $frequency;
            $initial_amt  = wpinv_price( $subscription->get_initial_amount(), $subscription->get_parent_payment()->get_currency() );
            $bill_times   = $subscription->get_times_billed() . ' / ' . ( ( $subscription->get_bill_times() == 0 ) ? __( 'Until Cancelled', 'invoicing' ) : $subscription->get_bill_times() );
            $renewal_date = getpaid_format_date_value( $subscription->get_expiration() );

            // Billing cycle.
            $props['period'] = array(
                'name' => __( 'Billing Cycle', 'invoicing' ),
                'value' => $period,
            );

            // Initial amount.
            $props['initial_amount'] = array(
                'name' => __( 'Initial Amount', 'invoicing' ),
                'value' => $initial_amt,
            );

            // Bill times.
            $props['bill_times'] = array(
                'name' => __( 'Times Billed', 'invoicing' ),
                'value' => $bill_times,
            );

            // Add expiry date.
            if ( $subscription->is_active() ) {

                $props['renewal_date'] = array(
                    'name' => __( 'Expires', 'invoicing' ),
                    'value' => $renewal_date,
                );

            }

        }

        return $props;

    }

    /**
     * Processes invoice items.
     *
     * @since 2.0.7
     * @param WPInv_Invoice $invoice invoice object.
     * @return array
     */
    public static function process_invoice_items( $invoice ) {

        $item_names = array();
        foreach ( $invoice->get_items() as $cart_item ) {
            $item_names[] = sprintf(
                '%s x %s - %s',
                $cart_item->get_name(),
                $cart_item->get_quantity(),
                wpinv_price( $invoice->is_renewal() ? $cart_item->get_recurring_sub_total() : $cart_item->get_sub_total(), $invoice->get_currency() )
            );
        }

        return implode( ', ', $item_names );

    }

}
