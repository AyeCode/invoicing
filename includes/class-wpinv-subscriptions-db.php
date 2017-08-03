<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Subscriptions_DB extends WPInv_DB {

    public function __construct() {
        global $wpdb;

        $this->table_name  = $wpdb->prefix . 'wpi_subscriptions';
        $this->primary_key = 'id';
        $this->version     = '1.0.0';

    }

    public function get_columns() {
        return array(
            'id'                => '%d',
            'user_id'           => '%d',
            'interval'         => '%d',
            'period'            => '%s',
            'trial_interval'   => '%d',
            'trial_period'      => '%s',
            'initial_amount'    => '%s',
            'recurring_amount'  => '%s',
            'bill_times'        => '%d',
            'transaction_id'    => '%s',
            'parent_invoice_id' => '%d',
            'item_id'           => '%d',
            'created'           => '%s',
            'expiration'        => '%s',
            'status'            => '%s',
            'profile_id'        => '%s',
            'notes'             => '%s',
        );
    }

    public function get_column_defaults() {
        return array(
            'user_id'           => 0,
            'interval'         => 1,
            'period'            => '',
            'trial_interval'   => 0,
            'trial_period'      => '',
            'initial_amount'    => '',
            'recurring_amount'  => '',
            'bill_times'        => 0,
            'transaction_id'    => '',
            'parent_invoice_id' => 0,
            'item_id'           => 0,
            'created'           => date_i18n( 'Y-m-d H:i:s' ),
            'expiration'        => date_i18n( 'Y-m-d H:i:s' ),
            'status'            => '',
            'profile_id'        => '',
            'notes'             => '',
        );
    }

    public function get_subscriptions( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'       => 20,
            'offset'       => 0,
            'search'       => '',
            'user_id'      => 0,
            'orderby'      => 'id',
            'order'        => 'DESC'
        );

        $args  = wp_parse_args( $args, $defaults );

        if ( $args['number'] < 1 ) {
            $args['number'] = 99999999999999;
        }

        $where = ' WHERE 1=1 ';

        if ( ! empty( $args['id'] ) ) {
            if( is_array( $args['id'] ) ) {
                $ids = implode( ',', array_map('intval', $args['id'] ) );
            } else {
                $ids = intval( $args['id'] );
            }

            $where .= " AND `id` IN( " . $ids . " ) ";
        }

        if ( ! empty( $args['item_id'] ) ) {
            if( is_array( $args['item_id'] ) ) {
                $item_ids = implode( ',', array_map('intval', $args['item_id'] ) );
            } else {
                $item_ids = intval( $args['item_id'] );
            }

            $where .= " AND `item_id` IN( " . $item_ids . " ) ";
        }

        if ( ! empty( $args['parent_invoice_id'] ) ) {
            if( is_array( $args['parent_invoice_id'] ) ) {
                $parent_payment_ids = implode( ',', array_map('intval', $args['parent_invoice_id'] ) );
            } else {
                $parent_payment_ids = intval( $args['parent_invoice_id'] );
            }

            $where .= " AND `parent_invoice_id` IN( " . $parent_payment_ids . " ) ";
        }

        if ( ! empty( $args['transaction_id'] ) ) {
            if( is_array( $args['transaction_id'] ) ) {
                $transaction_ids = implode( "','", array_map('sanitize_text_field', $args['transaction_id'] ) );
            } else {
                $transaction_ids = sanitize_text_field( $args['transaction_id'] );
            }

            $where .= " AND `transaction_id` IN ( '" . $transaction_ids . "' ) ";
        }

        if ( ! empty( $args['user_id'] ) ) {
            if( is_array( $args['user_id'] ) ) {
                $user_ids = implode( ',', array_map('intval', $args['user_id'] ) );
            } else {
                $user_ids = intval( $args['user_id'] );
            }

            $where .= " AND `user_id` IN( " . $user_ids . " ) ";
        }

        if ( ! empty( $args['profile_id'] ) ) {
            if( is_array( $args['profile_id'] ) ) {
                $profile_ids = implode( "','", array_map('sanitize_text_field', $args['profile_id'] ) );
            } else {
                $profile_ids = sanitize_text_field( $args['profile_id'] );
            }

            $where .= " AND `profile_id` IN( '" . $profile_ids . "' ) ";
        }

        if ( ! empty( $args['status'] ) ) {
            if( is_array( $args['status'] ) ) {
                $statuses = implode( "','", array_map( 'sanitize_text_field', $args['status'] ) );
            } else {
                $statuses = sanitize_text_field( $args['status'] );
            }

            $where .= " AND `status` IN( '" . $statuses . "' ) ";
        }

        if ( ! empty( $args['date'] ) ) {
            if ( is_array( $args['date'] ) ) {
                if ( ! empty( $args['date']['start'] ) ) {
                    $start = date_i18n( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

                    $where .= " AND `created` >= '" . $start . "'";
                }

                if ( ! empty( $args['date']['end'] ) ) {
                    $end = date_i18n( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

                    $where .= " AND `created` <= '" . $end . "'";
                }
            } else {
                $year  = date_i18n( 'Y', strtotime( $args['date'] ) );
                $month = date_i18n( 'm', strtotime( $args['date'] ) );
                $day   = date_i18n( 'd', strtotime( $args['date'] ) );

                $where .= " AND " . $year . " = YEAR ( created ) AND " . $month . " = MONTH ( created ) AND " . $day . " = DAY ( created )";
            }
        }

        if ( ! empty( $args['expiration'] ) ) {
            if ( is_array( $args['expiration'] ) ) {
                if ( ! empty( $args['expiration']['start'] ) ) {
                    $start = date_i18n( 'Y-m-d H:i:s', strtotime( $args['expiration']['start'] ) );

                    $where .= " AND `expiration` >= '" . $start . "'";
                }

                if ( ! empty( $args['expiration']['end'] ) ) {
                    $end = date_i18n( 'Y-m-d H:i:s', strtotime( $args['expiration']['end'] ) );

                    $where .= " AND `expiration` <= '" . $end . "'";
                }
            } else {
                $year  = date_i18n( 'Y', strtotime( $args['expiration'] ) );
                $month = date_i18n( 'm', strtotime( $args['expiration'] ) );
                $day   = date_i18n( 'd', strtotime( $args['expiration'] ) );

                $where .= " AND " . $year . " = YEAR ( expiration ) AND " . $month . " = MONTH ( expiration ) AND " . $day . " = DAY ( expiration )";
            }
        }

        if ( ! empty( $args['search'] ) ) {
            if ( is_email( $args['search'] ) ) {
                $user = get_user_by( 'email', $args['search'] )
                
                if ( !empty( $user ) && $user->ID > 0 ) {
                    $where .= " AND `user_id` = '" . esc_sql( $user->ID ) . "'";
                }

            } else if( false !== strpos( $args['search'], 'txn:' ) ) {
                $args['search'] = trim( str_replace( 'txn:', '', $args['search'] ) );
                $where .= " AND `transaction_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'profile_id:' ) ) {
                $args['search'] = trim( str_replace( 'profile_id:', '', $args['search'] ) );
                $where .= " AND `profile_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'item_id:' ) ) {
                $args['search'] = trim( str_replace( 'item_id:', '', $args['search'] ) );
                $where .= " AND `item_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'user_id:' ) ) {
                $args[ 'search' ] = trim( str_replace( 'user_id:', '', $args[ 'search' ] ) );
                $where .= " AND `user_id` = '" . esc_sql( $args[ 'search' ] ) . "'";
            } else if ( false !== strpos( $args['search'], 'id:' ) ) {
                $args['search'] = trim( str_replace( 'id:', '', $args['search'] ) );
                $where .= " AND `id` = '" . esc_sql( $args['search'] ) . "'";
            } else {
                $item = get_page_by_title( trim( $args['search'] ), OBJECT, 'wpi_item' );

                if ( $item ) {
                    $args['search'] = $item->ID;
                    $where .= " AND `item_id` = '" . esc_sql( $args['search'] ) . "'";
                } else {
                    $where .= " AND ( `parent_invoice_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `profile_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `transaction_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `item_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `id` = '" . esc_sql( $args['search'] ) . "' )";
                }
            }
        }

        $args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'id' : $args['orderby'];

        if ( 'amount' == $args['orderby'] ) {
            $args['orderby'] = 'amount + 0';
        }

        $cache_key = md5( 'wpinv_subscriptions_' . serialize( $args ) );

        $subscriptions = wp_cache_get( $cache_key, 'wpi_subscriptions' );

        $args['orderby'] = esc_sql( $args['orderby'] );
        $args['order']   = esc_sql( $args['order'] );

        if ( $subscriptions === false ) {
            $subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ), OBJECT );

            if ( ! empty( $subscriptions ) ) {
                foreach( $subscriptions as $key => $subscription ) {
                    $subscriptions[ $key ] = new EDD_Subscription( $subscription );
                }

                wp_cache_set( $cache_key, $subscriptions, 'wpi_subscriptions', 3600 );
            }
        }

        return $subscriptions;
    }

    public function count( $args = array() ) {
        global $wpdb;

        $where = ' WHERE 1=1 ';

        if ( ! empty( $args['id'] ) ) {
            if( is_array( $args['id'] ) ) {
                $ids = implode( ',', array_map('intval', $args['id'] ) );
            } else {
                $ids = intval( $args['id'] );
            }

            $where .= " AND `id` IN( " . $ids . " ) ";
        }

        if ( ! empty( $args['item_id'] ) ) {
            if( is_array( $args['item_id'] ) ) {
                $item_ids = implode( ',', array_map('intval', $args['item_id'] ) );
            } else {
                $item_ids = intval( $args['item_id'] );
            }

            $where .= " AND `item_id` IN( " . $item_ids . " ) ";
        }

        if ( ! empty( $args['parent_invoice_id'] ) ) {
            if( is_array( $args['parent_invoice_id'] ) ) {
                $parent_payment_ids = implode( ',', array_map('intval', $args['parent_invoice_id'] ) );
            } else {
                $parent_payment_ids = intval( $args['parent_invoice_id'] );
            }

            $where .= " AND `parent_invoice_id` IN( " . $parent_payment_ids . " ) ";
        }

        if ( ! empty( $args['transaction_id'] ) ) {
            if( is_array( $args['transaction_id'] ) ) {
                $transaction_ids = implode( "','", array_map('sanitize_text_field', $args['transaction_id'] ) );
            } else {
                $transaction_ids = sanitize_text_field( $args['transaction_id'] );
            }

            $where .= " AND `transaction_id` IN ( '" . $transaction_ids . "' ) ";
        }

        if ( ! empty( $args['user_id'] ) ) {
            if( is_array( $args['user_id'] ) ) {
                $user_ids = implode( ',', array_map('intval', $args['user_id'] ) );
            } else {
                $user_ids = intval( $args['user_id'] );
            }

            $where .= " AND `user_id` IN( " . $user_ids . " ) ";
        }

        if ( ! empty( $args['profile_id'] ) ) {
            if( is_array( $args['profile_id'] ) ) {
                $profile_ids = implode( "','", array_map('sanitize_text_field', $args['profile_id'] ) );
            } else {
                $profile_ids = sanitize_text_field( $args['profile_id'] );
            }

            $where .= " AND `profile_id` IN( '" . $profile_ids . "' ) ";
        }

        if ( ! empty( $args['status'] ) ) {
            if( is_array( $args['status'] ) ) {
                $statuses = implode( "','", array_map( 'sanitize_text_field', $args['status'] ) );
            } else {
                $statuses = sanitize_text_field( $args['status'] );
            }

            $where .= " AND `status` IN( '" . $statuses . "' ) ";
        }

        if ( ! empty( $args['date'] ) ) {
            if ( is_array( $args['date'] ) ) {
                if ( ! empty( $args['date']['start'] ) ) {
                    $start = date_i18n( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

                    $where .= " AND `created` >= '" . $start . "'";
                }

                if ( ! empty( $args['date']['end'] ) ) {
                    $end = date_i18n( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

                    $where .= " AND `created` <= '" . $end . "'";
                }
            } else {
                $year  = date_i18n( 'Y', strtotime( $args['date'] ) );
                $month = date_i18n( 'm', strtotime( $args['date'] ) );
                $day   = date_i18n( 'd', strtotime( $args['date'] ) );

                $where .= " AND " . $year . " = YEAR ( created ) AND " . $month . " = MONTH ( created ) AND " . $day . " = DAY ( created )";
            }
        }

        if ( ! empty( $args['expiration'] ) ) {
            if ( is_array( $args['expiration'] ) ) {
                if ( ! empty( $args['expiration']['start'] ) ) {
                    $start = date_i18n( 'Y-m-d H:i:s', strtotime( $args['expiration']['start'] ) );

                    $where .= " AND `expiration` >= '" . $start . "'";
                }

                if ( ! empty( $args['expiration']['end'] ) ) {
                    $end = date_i18n( 'Y-m-d H:i:s', strtotime( $args['expiration']['end'] ) );

                    $where .= " AND `expiration` <= '" . $end . "'";
                }
            } else {
                $year  = date_i18n( 'Y', strtotime( $args['expiration'] ) );
                $month = date_i18n( 'm', strtotime( $args['expiration'] ) );
                $day   = date_i18n( 'd', strtotime( $args['expiration'] ) );

                $where .= " AND " . $year . " = YEAR ( expiration ) AND " . $month . " = MONTH ( expiration ) AND " . $day . " = DAY ( expiration )";
            }
        }

        if ( ! empty( $args['search'] ) ) {
            if ( is_email( $args['search'] ) ) {
                $user = get_user_by( 'email', $args['search'] )
                
                if ( !empty( $user ) && $user->ID > 0 ) {
                    $where .= " AND `user_id` = '" . esc_sql( $user->ID ) . "'";
                }

            } else if( false !== strpos( $args['search'], 'txn:' ) ) {
                $args['search'] = trim( str_replace( 'txn:', '', $args['search'] ) );
                $where .= " AND `transaction_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'profile_id:' ) ) {
                $args['search'] = trim( str_replace( 'profile_id:', '', $args['search'] ) );
                $where .= " AND `profile_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'item_id:' ) ) {
                $args['search'] = trim( str_replace( 'item_id:', '', $args['search'] ) );
                $where .= " AND `item_id` = '" . esc_sql( $args['search'] ) . "'";
            } else if ( false !== strpos( $args['search'], 'user_id:' ) ) {
                $args[ 'search' ] = trim( str_replace( 'user_id:', '', $args[ 'search' ] ) );
                $where .= " AND `user_id` = '" . esc_sql( $args[ 'search' ] ) . "'";
            } else if ( false !== strpos( $args['search'], 'id:' ) ) {
                $args['search'] = trim( str_replace( 'id:', '', $args['search'] ) );
                $where .= " AND `id` = '" . esc_sql( $args['search'] ) . "'";
            } else {
                $item = get_page_by_title( trim( $args['search'] ), OBJECT, 'wpi_item' );

                if ( $item ) {
                    $args['search'] = $item->ID;
                    $where .= " AND `item_id` = '" . esc_sql( $args['search'] ) . "'";
                } else {
                    $where .= " AND ( `parent_invoice_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `profile_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `transaction_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `item_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `id` = '" . esc_sql( $args['search'] ) . "' )";
                }
            }
        }

        $cache_key = md5( 'wpi_subscriptions_count' . serialize( $args ) );

        $count = wp_cache_get( $cache_key, 'wpi_subscriptions' );

        if ( $count === false ) {
            $sql   = "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$where};";
            $count = $wpdb->get_var( $sql );

            wp_cache_set( $cache_key, $count, 'wpi_subscriptions', 3600 );
        }

        return absint( $count );
    }

    public function create_table() {
        global $wpdb;

        if ( !function_exists( 'dbDelta' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        $sql = "CREATE TABLE " . $this->table_name . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            interval int(11) NOT NULL DEFAULT '1',
            period varchar(20) NOT NULL,
            initial_amount mediumtext NOT NULL,
            recurring_amount mediumtext NOT NULL,
            bill_times bigint(20) NOT NULL,
            transaction_id varchar(60) NOT NULL,
            parent_invoice_id bigint(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            created datetime NOT NULL,
            expiration datetime NOT NULL,
            trial_interval int(11) NOT NULL DEFAULT '0',
            trial_period varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            profile_id varchar(60) NOT NULL,
            notes longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY profile_id (profile_id),
            KEY user (user_id),
            KEY transaction (transaction_id),
            KEY user_and_status ( user_id, status)
            ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}
