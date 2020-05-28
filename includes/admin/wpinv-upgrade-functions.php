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
 * Creates the invoices table.
 */
function wpinv_create_invoices_table() {
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Create invoices table.
    $table = $wpdb->prefix . 'getpaid_invoices';
    $sql   = "CREATE TABLE $table (

            post_id BIGINT(20) NOT NULL,
            number VARCHAR(20),
            `key` VARCHAR(20),
            `type` VARCHAR(20) NOT NULL DEFAULT 'invoice',
            mode VARCHAR(20) NOT NULL DEFAULT 'live',

            user_ip VARCHAR(100),
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            `address` VARCHAR(100),
            address2 VARCHAR(100),
            city VARCHAR(100),
            `state` VARCHAR(100),
            country VARCHAR(100),
            zip VARCHAR(100),
            adddress_confirmed INT(10),

            gateway VARCHAR(100),
            transaction_id VARCHAR(100),
            currency VARCHAR(10),
            subtotal FLOAT(20) NOT NULL DEFAULT 0,
            tax FLOAT(20) NOT NULL DEFAULT 0,
            fees_total FLOAT(20) NOT NULL DEFAULT 0,
            total FLOAT(20) NOT NULL DEFAULT 0,
            discount FLOAT(20) NOT NULL DEFAULT 0,
            discount_code VARCHAR(20),
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

            // Links with external post types. 
            item_id BIGINT(20) NOT NULL,
            invoice_id BIGINT(20) NOT NULL,

            // How this specific item appears on invoices. 
            invoice_item_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            invoice_item_name TEXT NOT NULL,
            invoice_item_description TEXT NOT NULL,

            // Used to calculate the cost of the item on invoices.
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
            PRIMARY KEY  (invoice_item_id),
            KEY item_id (item_id),
            KEY invoice_id ( invoice_id )
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta( $sql );
}

/**
 * Copies data from meta tables to our custom tables.
 */
function wpinv_convert_old_invoices() {
    global $wpdb;

    $invoices = get_posts('post_type=wpi_invoice&posts_per_page=-1&fields=ids');
    $currency = wpinv_get_currency();
    foreach ( $invoices as $invoice ) {

        $meta    = get_post_meta( $invoice );

        $fields = array(
            'invoice_id'     => '',
            'type'           => '',
            'number'         => '',
            'mode'           => '',
            'key'            => '',
            'total'          => 0,
            'subtotal'       => 0,
            'disable_taxes'  => '0',
            'tax'            => '0',
            'fees_total'     => '0',
            'discount'       => '0',
            'discount_code'  => '',
            'due_date'       => '',
            'completed_date' => '',
            'address'        => '',
            'city'           => '',
            'country'        => '',
            'state'          => '',
            'zip'            => '',
            'transaction_id' => '',
            'user_ip'        => '',
            'gateway'        => '',
            'currency'       => $currency,
            'payment_meta'   => array(),
            'company'        => '',
            'vat_number'     => '',
            'vat_rate'       => '',
            'adddress_confirmed' => 0,
        );

        foreach ( array_keys( $fields ) as $field ) {
            if ( isset( $meta[ $field ] ) ) {
                $fields[ $field ] = $meta[ $field ][0];
            }
        }

        if ( is_array( $fields['payment_meta'] ) ) {

            if ( ! empty( $fields['payment_meta']['currency'] ) ) {
                $fields['currency'] = $fields['payment_meta']['currency'];
            }

            if ( ! empty( $fields['payment_meta']['tax'] ) ) {
                $fields['tax'] = $fields['payment_meta']['tax'];
            }

            if ( ! empty( $fields['payment_meta']['amount'] ) ) {
                $fields['total'] = $fields['payment_meta']['amount'];
            }

            if ( ! empty( $fields['payment_meta']['fees'] ) ) {
                $fees = 0;
                foreach ( $fields['payment_meta']['fees'] as $fee ) {
                    $fees += (float) $fee['amount'];
                }
                $fields['fees_total'] = $fees;
            } else {
                $fields['fees_total'] = $fields['total'];
            }

            if ( is_array( $fields['payment_meta']['cart_details'] ) ) {
                foreach ( $fields['payment_meta']['cart_details'] as $item ) {
                    if ( isset( $item['subtotal'] ) ) {
                        $fields['subtotal'] += $item['subtotal'];
                    }
                }
            }

        } else {
            $fields['payment_meta'] = array();
        }

        $fields['tax'] = (float) $fields['tax'];
        $fields['fees_total'] = (float) $fields['fees_total'];
        $fields['total'] = (float) $fields['total'];
        $fields['subtotal'] = (float) $fields['subtotal'];
        $fields['discount'] = $fields['subtotal'] - $fields['total'] - $fields['tax'] - $fields['fees_total'];
    }

}
