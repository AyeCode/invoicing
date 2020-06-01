<?php
/**
 * Upgrade related functions.
 *
 * @since 1.0.0
 */

/**
 * Perform automatic upgrades when necessary.
 *
 * @since 1.0.0
*/
function wpinv_automatic_upgrade() {
    $wpi_version = get_option( 'wpinv_version' );

    // Update tables.
    if ( ! get_option( 'getpaid_created_invoice_tables' ) ) {
        wpinv_v119_upgrades();
        update_option( 'getpaid_created_invoice_tables', true );
    }

    if ( $wpi_version == WPINV_VERSION ) {
        return;
    }

    if ( version_compare( $wpi_version, '0.0.5', '<' ) ) {
        wpinv_v005_upgrades();
    }

    if ( version_compare( $wpi_version, '1.0.3', '<' ) ) {
        wpinv_v110_upgrades();
    }

    update_option( 'wpinv_version', WPINV_VERSION );
}
add_action( 'admin_init', 'wpinv_automatic_upgrade' );

function wpinv_v005_upgrades() {
    global $wpdb;

    // Invoices status
    $results = $wpdb->get_results( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );
    if ( !empty( $results ) ) {
        $wpdb->query( "UPDATE " . $wpdb->posts . " SET post_status = CONCAT( 'wpi-', post_status ) WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );

        // Clean post cache
        foreach ( $results as $row ) {
            clean_post_cache( $row->ID );
        }
    }

    // Item meta key changes
    $query = "SELECT DISTINCT post_id FROM " . $wpdb->postmeta . " WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id', '_wpinv_cpt_name', '_wpinv_cpt_singular_name' )";
    $results = $wpdb->get_results( $query );

    if ( !empty( $results ) ) {
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_id' WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id' )" );
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_name' WHERE meta_key = '_wpinv_cpt_name'" );
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_singular_name' WHERE meta_key = '_wpinv_cpt_singular_name'" );
        
        foreach ( $results as $row ) {
            clean_post_cache( $row->post_id );
        }
    }

    wpinv_add_admin_caps();
}

function wpinv_v110_upgrades() {
    // Upgrade email settings
    wpinv_update_new_email_settings();

    // Add Subscription tables
    $db = new WPInv_Subscriptions_DB;
    /** @scrutinizer ignore-unhandled */ @$db->create_table();

    wpinv_convert_old_subscriptions();
}

function wpinv_convert_old_subscriptions() {
    global $wpdb;

    $query = "SELECT ". $wpdb->posts .".ID FROM ". $wpdb->posts ." INNER JOIN ". $wpdb->postmeta ." ON ( ". $wpdb->posts .".ID = ". $wpdb->postmeta .".post_id ) WHERE 1=1  AND ". $wpdb->postmeta .".meta_key = '_wpinv_subscr_status' AND (". $wpdb->postmeta .".meta_value = 'pending' OR ". $wpdb->postmeta .".meta_value = 'active' OR ". $wpdb->postmeta .".meta_value = 'cancelled' OR ". $wpdb->postmeta .".meta_value = 'completed' OR ". $wpdb->postmeta .".meta_value = 'expired' OR ". $wpdb->postmeta .".meta_value = 'trialling' OR ". $wpdb->postmeta .".meta_value = 'failing') AND ". $wpdb->posts .".post_type = 'wpi_invoice' GROUP BY ". $wpdb->posts .".ID ORDER BY ". $wpdb->posts .".ID ASC";

    $results = $wpdb->get_results( $query );

    if ( empty( $results ) ) {
        return;
    }

    foreach ( $results as $row ) {
        $invoice = new WPInv_Invoice( $row->ID );

        if ( empty( $invoice->ID ) ) {
            continue;
        }

        if ( $invoice->has_status( 'wpi-renewal' ) ) {
            continue;
        }
        
        $item = $invoice->get_recurring( true );

        if ( empty( $item ) ) {
            continue;
        }

        $is_free_trial          = $invoice->is_free_trial();
        $profile_id             = get_post_meta( $invoice->ID, '_wpinv_subscr_profile_id', true );
        $subscription_status    = get_post_meta( $invoice->ID, '_wpinv_subscr_status', true );
        $transaction_id         = $invoice->get_transaction_id();

        // Last invoice
        $query          = "SELECT ID, post_date FROM ". $wpdb->posts ." WHERE post_type = 'wpi_invoice' AND post_parent = '" . $invoice->ID . "' ORDER BY ID DESC LIMIT 1";
        $last_payment   = $wpdb->get_row( $query );

        if ( !empty( $last_payment ) ) {
            $invoice_date       = $last_payment->post_date;
            
            $meta_profile_id     = get_post_meta( $last_payment->ID, '_wpinv_subscr_profile_id', true );
            $meta_transaction_id = get_post_meta( $last_payment->ID, '_wpinv_transaction_id', true );

            if ( !empty( $meta_profile_id ) ) {
                $profile_id  = $meta_profile_id;
            }

            if ( !empty( $meta_transaction_id ) ) {
                $transaction_id  = $meta_transaction_id;
            }
        } else {
            $invoice_date       = $invoice->get_invoice_date( false );
        }
        
        $profile_id             = empty( $profile_id ) ? $invoice->ID : $profile_id;
        $status                 = empty( $subscription_status ) ? 'pending' : $subscription_status;
        
        $period                 = $item->get_recurring_period( true );
        $interval               = $item->get_recurring_interval();
        $bill_times             = (int)$item->get_recurring_limit();
        $add_period             = $interval . ' ' . $period;
        $trial_period           = '';

        if ( $invoice->is_free_trial() ) {
            $trial_period       = $item->get_trial_period( true );
            $free_interval      = $item->get_trial_interval();
            $trial_period       = $free_interval . ' ' . $trial_period;

            if ( empty( $last_payment ) ) {
                $add_period     = $trial_period;
            }
        }

        $expiration             = date_i18n( 'Y-m-d H:i:s', strtotime( '+' . $add_period  . ' 23:59:59', strtotime( $invoice_date ) ) );
        if ( strtotime( $expiration ) <  strtotime( date_i18n( 'Y-m-d' ) ) ) {
            if ( $status == 'active' || $status == 'trialling' || $status == 'pending' ) {
                $status = 'expired';
            }
        }

        $args = array(
            'product_id'        => $item->ID,
            'customer_id'       => $invoice->user_id,
            'parent_payment_id' => $invoice->ID,
            'status'            => $status,
            'frequency'         => $interval,
            'period'            => $period,
            'initial_amount'    => $invoice->get_total(),
            'recurring_amount'  => $invoice->get_recurring_details( 'total' ),
            'bill_times'        => $bill_times,
            'created'           => $invoice_date,
            'expiration'        => $expiration,
            'trial_period'      => $trial_period,
            'profile_id'        => $profile_id,
            'transaction_id'    => $transaction_id,
        );

        $subs_db      = new WPInv_Subscriptions_DB;
        $subs         = $subs_db->get_subscriptions( array( 'parent_payment_id' => $invoice->ID, 'number' => 1 ) );
        $subscription = reset( $subs );

        if ( empty( $subscription ) || $subscription->id <= 0 ) {
            $subscription = new WPInv_Subscription();
            $new_sub = $subscription->create( $args );

            if ( !empty( $bill_times ) && $new_sub->get_times_billed() >= $bill_times && ( 'active' == $new_sub->status || 'trialling' == $new_sub->status ) ) {
                $new_sub->complete(); // Mark completed if all times billed
            }
        }
    }
}

function wpinv_update_new_email_settings() {
    global $wpinv_options;

    $current_options = get_option( 'wpinv_settings', array() );
    $options = array(
        'email_new_invoice_body' => __( '<p>Hi Admin,</p><p>You have received payment invoice from {name}.</p>', 'invoicing' ),
        'email_cancelled_invoice_body' => __( '<p>Hi Admin,</p><p>The invoice #{invoice_number} from {site_title} has been cancelled.</p>', 'invoicing' ),
        'email_failed_invoice_body' => __( '<p>Hi Admin,</p><p>Payment for invoice #{invoice_number} from {site_title} has been failed.</p>', 'invoicing' ),
        'email_onhold_invoice_body' => __( '<p>Hi {name},</p><p>Your invoice is on-hold until we confirm your payment has been received.</p>', 'invoicing' ),
        'email_processing_invoice_body' => __( '<p>Hi {name},</p><p>Your invoice has been received at {site_title} and is now being processed.</p>', 'invoicing' ),
        'email_refunded_invoice_body' => __( '<p>Hi {name},</p><p>Your invoice on {site_title} has been refunded.</p>', 'invoicing' ),
        'email_user_invoice_body' => __( '<p>Hi {name},</p><p>An invoice has been created for you on {site_title}. To view / pay for this invoice please use the following link: <a class="btn btn-success" href="{invoice_link}">View / Pay</a></p>', 'invoicing' ),
        'email_user_note_body' => __( '<p>Hi {name},</p><p>Following note has been added to your {invoice_label}:</p><blockquote class="wpinv-note">{customer_note}</blockquote>', 'invoicing' ),
        'email_overdue_body' => __( '<p>Hi {full_name},</p><p>This is just a friendly reminder that your invoice <a href="{invoice_link}">#{invoice_number}</a> {is_was} due on {invoice_due_date}.</p><p>The total of this invoice is {invoice_total}</p><p>To view / pay now for this invoice please use the following link: <a class="btn btn-success" href="{invoice_link}">View / Pay</a></p>', 'invoicing' ),
    );

    foreach ($options as $option => $value){
        if (!isset($current_options[$option])) {
            $current_options[$option] = $value;
        }
    }

    $wpinv_options = $current_options;

    update_option( 'wpinv_settings', $current_options );
}

/**
 * Version 119 upgrades.
 */
function wpinv_v119_upgrades() {
    //wpinv_create_invoices_table();
    wpinv_convert_old_invoices();
}

/**
 * Creates the invoices table.
 */
function wpinv_create_invoices_table() {
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Create invoices table.
    $table = $wpdb->prefix . 'getpaid_invoices';
    $sql   = "CREATE TABLE $table (

            post_id BIGINT(20) NOT NULL,
            number VARCHAR(100),
            `key` VARCHAR(100),
            `type` VARCHAR(100) NOT NULL DEFAULT 'invoice',
            mode VARCHAR(100) NOT NULL DEFAULT 'live',

            user_ip VARCHAR(100),
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            `address` VARCHAR(100),
            city VARCHAR(100),
            `state` VARCHAR(100),
            country VARCHAR(100),
            zip VARCHAR(100),
            adddress_confirmed INT(10),

            gateway VARCHAR(100),
            transaction_id VARCHAR(100),
            currency VARCHAR(10),
            subtotal FLOAT(100) NOT NULL DEFAULT 0,
            tax FLOAT(100) NOT NULL DEFAULT 0,
            fees_total FLOAT(100) NOT NULL DEFAULT 0,
            total FLOAT(100) NOT NULL DEFAULT 0,
            discount FLOAT(100) NOT NULL DEFAULT 0,
            discount_code VARCHAR(100),
            disable_taxes INT(2) NOT NULL DEFAULT 0,
            due_date DATETIME,
            completed_date DATETIME,
            company VARCHAR(100),
            vat_number VARCHAR(100),
            vat_rate VARCHAR(100),

            custom_meta TEXT,
            PRIMARY KEY  (post_id),
            KEY number (number),
            KEY `key` ( `key` )
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta( $sql );

    // Create invoice items table.
    $table = $wpdb->prefix . 'getpaid_invoice_items';
    $sql   = "CREATE TABLE $table (
            ID BIGINT(20) NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) NOT NULL,

            item_id BIGINT(20) NOT NULL,
            item_name TEXT NOT NULL,
            item_description TEXT NOT NULL,

            vat_rate FLOAT NOT NULL DEFAULT 0,
            vat_class VARCHAR(100),
            tax FLOAT NOT NULL DEFAULT 0,
            item_price FLOAT NOT NULL DEFAULT 0,
            custom_price FLOAT NOT NULL DEFAULT 0,
            quantity INT(20) NOT NULL DEFAULT 1,
            discount FLOAT NOT NULL DEFAULT 0,
            subtotal FLOAT NOT NULL DEFAULT 0,
            price FLOAT NOT NULL DEFAULT 0,
            meta TEXT,
            fees TEXT,
            PRIMARY KEY  (ID),
            KEY item_id (item_id),
            KEY post_id ( post_id )
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta( $sql );
}

/**
 * Copies data from meta tables to our custom tables.
 */
function wpinv_convert_old_invoices() {
    global $wpdb;

    $invoices = array_unique(
        get_posts(
            array(
                'post_type'      => array( 'wpi_invoice', 'wpi_quote' ),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post_status'    => array_keys( wpinv_get_invoice_statuses( true ) ),
            )
        )
    );
    $invoices_table = $wpdb->prefix . 'getpaid_invoices';
    $invoice_items_table = $wpdb->prefix . 'getpaid_invoice_items';

    if ( ! class_exists( 'WPInv_Legacy_Invoice' ) ) {
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-legacy-invoice.php' );
    }

    $invoice_rows = array();
    foreach ( $invoices as $invoice ) {

        $invoice = new WPInv_Legacy_Invoice( $invoice );
        $fields = array (
            'post_id'        => $invoice->ID,
            'number'         => $invoice->get_number(),
            'key'            => $invoice->get_key(),
            'type'           => str_replace( 'wpi_', '', $invoice->post_type ),
            'mode'           => $invoice->mode,
            'user_ip'        => $invoice->get_ip(),
            'first_name'     => $invoice->get_first_name(),
            'last_name'      => $invoice->get_last_name(),
            'address'        => $invoice->get_address(),
            'city'           => $invoice->city,
            'state'          => $invoice->state,
            'country'        => $invoice->country,
            'zip'            => $invoice->zip,
            'adddress_confirmed' => (int) $invoice->adddress_confirmed,
            'gateway'        => $invoice->get_gateway(),
            'transaction_id' => $invoice->get_transaction_id(),
            'currency'       => $invoice->get_currency(),
            'subtotal'       => $invoice->get_subtotal(),
            'tax'            => $invoice->get_tax(),
            'fees_total'     => $invoice->get_fees_total(),
            'total'          => $invoice->get_total(),
            'discount'       => $invoice->get_discount(),
            'discount_code'  => $invoice->get_discount_code(),
            'disable_taxes'  => $invoice->disable_taxes,
            'due_date'       => $invoice->get_due_date(),
            'completed_date' => $invoice->get_completed_date(),
            'company'        => $invoice->company,
            'vat_number'     => $invoice->vat_number,
            'vat_rate'       => $invoice->vat_rate,
            'custom_meta'    => $invoice->payment_meta
        );

        foreach ( $fields as $key => $val ) {
            if ( is_null( $val ) ) {
                $val = '';
            }
            $val = maybe_serialize( $val );
            $fields[ $key ] = $wpdb->prepare( '%s', $val );
        }

        $fields = implode( ', ', $fields );
        $invoice_rows[] = "($fields)";

        $item_rows    = array();
        $item_columns = array();
        foreach ( $invoice->get_cart_details() as $details ) {
            $fields = array(
                'post_id'          => $invoice->ID,
                'item_id'          => $details['id'],
                'item_name'        => $details['name'],
                'item_description' => empty( $details['meta']['description'] ) ? '' : $details['meta']['description'],
                'vat_rate'         => $details['vat_rate'],
                'vat_class'        => empty( $details['vat_class'] ) ? '_standard' : $details['vat_class'],
                'tax'              => $details['tax'],
                'item_price'       => $details['item_price'],
                'custom_price'     => $details['custom_price'],
                'quantity'         => $details['quantity'],
                'discount'         => $details['discount'],
                'subtotal'         => $details['subtotal'],
                'price'            => $details['price'],
                'meta'             => $details['meta'],
                'fees'             => $details['fees'],
            );

            $item_columns = array_keys ( $fields );

            foreach ( $fields as $key => $val ) {
                if ( is_null( $val ) ) {
                    $val = '';
                }
                $val = maybe_serialize( $val );
                $fields[ $key ] = $wpdb->prepare( '%s', $val );
            }

            $fields = implode( ', ', $fields );
            $item_rows[] = "($fields)";
        }

        $item_rows    = implode( ', ', $item_rows );
        $item_columns = implode( ', ', $item_columns );
        $wpdb->query( "INSERT INTO $invoice_items_table ($item_columns) VALUES $item_rows" );
    }

    $invoice_rows = implode( ', ', $invoice_rows );
    $wpdb->query( "INSERT INTO $invoices_table VALUES $invoice_rows" );

}
