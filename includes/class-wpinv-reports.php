<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Reports {
    private $section = 'wpinv_reports';
    private $wp_filesystem;
    private $export_dir;
    private $export_url;
    private $export;
    public $filetype;
    public $per_page;

    public function __construct() {
        $this->init();
        $this->includes();
        $this->actions();
    }

    public function init() {
        global $wp_filesystem;

        if ( empty( $wp_filesystem ) ) {
            require_once( ABSPATH . '/wp-admin/includes/file.php' );
            WP_Filesystem();
            global $wp_filesystem;
        }
        $this->wp_filesystem    = $wp_filesystem;

        $this->export_dir       = $this->export_location();
        $this->export_url       = $this->export_location( true );
        $this->export           = 'invoicing';
        $this->filetype         = 'csv';
        $this->per_page         = 20;

        do_action( 'wpinv_class_reports_init', $this );
    }

    public function includes() {
        do_action( 'wpinv_class_reports_includes', $this );
    }

    public function actions() {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
            add_action( 'wpinv_reports_tab_reports', array( $this, 'reports' ) );
            add_action( 'wpinv_reports_tab_export', array( $this, 'export' ) );
            add_action( 'wp_ajax_wpinv_ajax_export', array( $this, 'ajax_export' ) );
            add_action( 'wp_ajax_wpinv_ajax_discount_use_export', array( $this, 'discount_use_export' ) );

            // Export Invoices.
            add_action( 'wpinv_export_set_params_invoices', array( $this, 'set_invoices_export' ) );
            add_filter( 'wpinv_export_get_columns_invoices', array( $this, 'get_invoices_columns' ) );
            add_filter( 'wpinv_export_get_data_invoices', array( $this, 'get_invoices_data' ) );
            add_filter( 'wpinv_get_export_status_invoices', array( $this, 'invoices_export_status' ) );

            // Reports.
            add_action( 'wpinv_reports_view_earnings', array( $this, 'earnings_report' ) );
            add_action( 'wpinv_reports_view_gateways', array( $this, 'gateways_report' ) );
            add_action( 'wpinv_reports_view_items', array( $this, 'items_report' ) );
            add_action( 'wpinv_reports_view_taxes', array( $this, 'tax_report' ) );
        }
        do_action( 'wpinv_class_reports_actions', $this );
    }

    public function add_submenu() {
        global $wpi_reports_page;
        $wpi_reports_page = add_submenu_page( 'wpinv', __( 'Reports', 'invoicing' ), __( 'Reports', 'invoicing' ), wpinv_get_capability(), 'wpinv-reports', array( $this, 'reports_page' ) );
    }

    public function reports_page() {

        if ( !wp_script_is( 'postbox', 'enqueued' ) ) {
            wp_enqueue_script( 'postbox' );
        }

        if ( !wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

        $current_page = admin_url( 'admin.php?page=wpinv-reports' );
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'reports';
        ?>
        <div class="wrap wpi-reports-wrap">
            <h1><?php echo esc_html( __( 'Reports', 'invoicing' ) ); ?></h1>
            <h2 class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'reports', 'settings-updated' => false ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'reports' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Reports', 'invoicing' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'export', 'settings-updated' => false ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Export', 'invoicing' ); ?></a>
                <?php do_action( 'wpinv_reports_page_tabs' ); ;?>
            </h2>
            <div class="wpi-reports-content wpi-reports-<?php echo esc_attr( $active_tab ); ?>">
            <?php
                do_action( 'wpinv_reports_page_top' );
                do_action( 'wpinv_reports_tab_' . $active_tab );
                do_action( 'wpinv_reports_page_bottom' );
            ?>
            </div>
        </div>
        <?php
    }

    /**
     * Displays the reports graphs.
     */
    public function reports() {

        $views = array(
            'earnings'   => __( 'Earnings', 'invoicing' ),
            'items'      => __( 'Items', 'invoicing' ),
            'gateways'   => __( 'Payment Methods', 'invoicing' ),
            'taxes'      => __( 'Taxes', 'invoicing' ),
        );

        $views   = apply_filters( 'wpinv_report_views', $views );
        $current = 'earnings';

        if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], $views ) )
		$current = $_GET['view'];

        ?>
	        <form id="wpinv-reports-filter" method="get" class="tablenav">
		        <select id="wpinv-reports-view" name="view">
			        <option value="-1" disabled><?php _e( 'Report Type', 'invoicing' ); ?></option>
			            <?php foreach ( $views as $view_id => $label ) : ?>
				            <option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current ); ?>><?php echo $label; ?></option>
			            <?php endforeach; ?>
		        </select>

		        <?php do_action( 'wpinv_report_view_actions' ); ?>

		        <input type="hidden" name="page" value="wpinv-reports"/>
		        <?php submit_button( __( 'Show', 'invoicing' ), 'secondary', 'submit', false ); ?>
	        </form>
        <?php

	    do_action( 'wpinv_reports_view_' . $current );

    }

    public function export() {
        $statuses = wpinv_get_invoice_statuses( true );
        $statuses = array_merge( array( 'any' => __( 'All Statuses', 'invoicing' ) ), $statuses );
        ?>
        <div class="metabox-holder">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_action( 'wpinv_reports_tab_export_content_top' ); ?>

                    <div class="postbox wpi-export-invoices">
                        <h2 class="hndle ui-sortabled-handle"><span><?php _e( 'Invoices','invoicing' ); ?></span></h2>
                        <div class="inside">
                            <p><?php _e( 'Download a CSV of all payment invoices.', 'invoicing' ); ?></p>
                            <form id="wpi-export-invoices" class="wpi-export-form" method="post">
                                <?php echo wpinv_html_date_field( array(
                                    'id' => 'wpi_export_from_date',
                                    'name' => 'from_date',
                                    'data' => array(
                                        'dateFormat' => 'yy-mm-dd'
                                    ),
                                    'placeholder' => __( 'From date', 'invoicing' ) )
                                ); ?>
                                <?php echo wpinv_html_date_field( array(
                                    'id' => 'wpi_export_to_date',
                                    'name' => 'to_date',
                                    'data' => array(
                                        'dateFormat' => 'yy-mm-dd'
                                    ),
                                    'placeholder' => __( 'To date', 'invoicing' ) )
                                ); ?>
                                <span id="wpinv-status-wrap">
                                <?php echo wpinv_html_select( array(
                                    'options'          => $statuses,
                                    'name'             => 'status',
                                    'id'               => 'wpi_export_status',
                                    'show_option_all'  => false,
                                    'show_option_none' => false,
                                    'class'            => 'wpi_select2',
                                ) ); ?>
                                <?php wp_nonce_field( 'wpi_ajax_export', 'wpi_ajax_export' ); ?>
                                </span>
                                <span id="wpinv-submit-wrap">
                                    <input type="hidden" value="invoices" name="export" />
                                    <input type="submit" value="<?php _e( 'Generate CSV', 'invoicing' ); ?>" class="button-primary" />
                                </span>
                            </form>
                        </div>
                    </div>

                    <div class="postbox wpi-export-discount-uses">
                        <h2 class="hndle ui-sortabled-handle"><span><?php _e( 'Discount Use','invoicing' ); ?></span></h2>
                        <div class="inside">
                            <p><?php _e( 'Download a CSV of discount uses.', 'invoicing' ); ?></p>
                            <a class="button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=wpinv_ajax_discount_use_export' ), 'wpi_discount_ajax_export', 'wpi_discount_ajax_export' ) ); ?>"><?php _e( 'Generate CSV', 'invoicing' ); ?></a>
                        </div>
                    </div>

                    <?php do_action( 'wpinv_reports_tab_export_content_bottom' ); ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function export_location( $relative = false ) {
        $upload_dir         = wp_upload_dir();
        $export_location    = $relative ? trailingslashit( $upload_dir['baseurl'] ) . 'cache' : trailingslashit( $upload_dir['basedir'] ) . 'cache';
        $export_location    = apply_filters( 'wpinv_export_location', $export_location, $relative );

        return trailingslashit( $export_location );
    }

    public function check_export_location() {
        try {
            if ( empty( $this->wp_filesystem ) ) {
                return __( 'Filesystem ERROR: Could not access filesystem.', 'invoicing' );
            }

            if ( is_wp_error( $this->wp_filesystem ) ) {
                return __( 'Filesystem ERROR: ' . $this->wp_filesystem->get_error_message(), 'invoicing' );
            }

            $is_dir         = $this->wp_filesystem->is_dir( $this->export_dir );
            $is_writeable   = $is_dir && is_writeable( $this->export_dir );

            if ( $is_dir && $is_writeable ) {
               return true;
            } else if ( $is_dir && !$is_writeable ) {
               if ( !$this->wp_filesystem->chmod( $this->export_dir, FS_CHMOD_DIR ) ) {
                   return wp_sprintf( __( 'Filesystem ERROR: Export location %s is not writable, check your file permissions.', 'invoicing' ), $this->export_dir );
               }

               return true;
            } else {
                if ( !$this->wp_filesystem->mkdir( $this->export_dir, FS_CHMOD_DIR ) ) {
                    return wp_sprintf( __( 'Filesystem ERROR: Could not create directory %s. This is usually due to inconsistent file permissions.', 'invoicing' ), $this->export_dir );
                }

                return true;
            }
        } catch ( Exception $e ) {
            return $e->getMessage();
        }
    }

    public function ajax_export() {
        $response               = array();
        $response['success']    = false;
        $response['msg']        = __( 'Invalid export request found.', 'invoicing' );

        if ( empty( $_POST['data'] ) || ! wpinv_current_user_can_manage_invoicing() ) {
            wp_send_json( $response );
        }

        parse_str( $_POST['data'], $data );

        $data['step']   = !empty( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

        $_REQUEST = (array)$data;
        if ( !( !empty( $_REQUEST['wpi_ajax_export'] ) && wp_verify_nonce( $_REQUEST['wpi_ajax_export'], 'wpi_ajax_export' ) ) ) {
            $response['msg']    = __( 'Security check failed.', 'invoicing' );
            wp_send_json( $response );
        }

        if ( ( $error = $this->check_export_location( true ) ) !== true ) {
            $response['msg'] = __( 'Filesystem ERROR: ' . $error, 'invoicing' );
            wp_send_json( $response );
        }

        $this->set_export_params( $_REQUEST );

        $return = $this->process_export_step();
        $done   = $this->get_export_status();

        if ( $return ) {
            $this->step += 1;

            $response['success']    = true;
            $response['msg']        = '';

            if ( $done >= 100 ) {
                $this->step     = 'done';
                $new_filename   = 'wpi-' . $this->export . '-' . date( 'y-m-d-H-i' ) . '.' . $this->filetype;
                $new_file       = $this->export_dir . $new_filename;

                if ( file_exists( $this->file ) ) {
                    $this->wp_filesystem->move( $this->file, $new_file, true );
                }

                if ( file_exists( $new_file ) ) {
                    $response['data']['file'] = array( 'u' => $this->export_url . $new_filename, 's' => size_format( filesize( $new_file ), 2 ) );
                }
            }

            $response['data']['step']   = $this->step;
            $response['data']['done']   = $done;
        } else {
            $response['msg']    = __( 'No data found for export.', 'invoicing' );
        }

        wp_send_json( $response );
    }

    /**
     * Handles discount exports.
     */
    public function discount_use_export() {

        if ( ! wp_verify_nonce( $_GET['wpi_discount_ajax_export'], 'wpi_discount_ajax_export' ) || ! wpinv_current_user_can_manage_invoicing() ) {
            wp_die( -1, 403 );
        }

        $args = array(
            'post_type'      => 'wpi_discount',
            'fields'         => 'ids',
            'posts_per_page' => -1,
        );

        $discounts = get_posts( $args );

        if ( empty( $discounts ) ) {
            die ( __( 'You have not set up any discounts', 'invoicing' ) );
        }

        $output  = fopen( 'php://output', 'w' ) or die( 'Unsupported server' );

        // Let the browser know what content we're streaming and how it should save the content.
		$name = time();
		header( "Content-Type:application/csv" );
        header( "Content-Disposition:attachment;filename=getpaid-discounts-$name.csv" );

        // Output the csv column headers.
		fputcsv(
            $output,
            array(
                __( 'Discount Id', 'invoicing' ),
                __( 'Discount Code', 'invoicing' ),
                __( 'Discount Type', 'invoicing' ),
                __( 'Discount Amount', 'invoicing' ),
                __( 'Uses', 'invoicing' ),
            )
        );

        foreach ( $discounts as $discount ) {

            $discount = (int) $discount;
            $row      = array(
                $discount,
                get_post_meta( $discount, '_wpi_discount_code', true ),
                get_post_meta( $discount, '_wpi_discount_type', true ),
                get_post_meta( $discount, '_wpi_discount_amount', true ),
                (int) get_post_meta( $discount, '_wpi_discount_uses', true )
            );
            fputcsv( $output, $row );
        }

        fclose( $output );
        exit;

    }

    public function set_export_params( $request ) {
        $this->empty    = false;
        $this->step     = !empty( $request['step'] ) ? absint( $request['step'] ) : 1;
        $this->export   = !empty( $request['export'] ) ? $request['export'] : $this->export;
        $this->filename = 'wpi-' . $this->export . '-' . $request['wpi_ajax_export'] . '.' . $this->filetype;
        $this->file     = $this->export_dir . $this->filename;

        do_action( 'wpinv_export_set_params_' . $this->export, $request );
    }

    public function get_columns() {
        $columns = array();

        return apply_filters( 'wpinv_export_get_columns_' . $this->export, $columns );
    }

    protected function get_export_file() {
        $file = '';

        if ( $this->wp_filesystem->exists( $this->file ) ) {
            $file = $this->wp_filesystem->get_contents( $this->file );
        } else {
            $this->wp_filesystem->put_contents( $this->file, '' );
        }

        return $file;
    }

    protected function attach_export_data( $data = '' ) {
        $filedata   = $this->get_export_file();
        $filedata   .= $data;

        $this->wp_filesystem->put_contents( $this->file, $filedata );

        $rows       = file( $this->file, FILE_SKIP_EMPTY_LINES );
        $columns    = $this->get_columns();
        $columns    = empty( $columns ) ? 0 : 1;

        $this->empty = count( $rows ) == $columns ? true : false;
    }

    public function print_columns() {
        $column_data    = '';
        $columns        = $this->get_columns();
        $i              = 1;
        foreach( $columns as $key => $column ) {
            $column_data .= '"' . addslashes( $column ) . '"';
            $column_data .= $i == count( $columns ) ? '' : ',';
            $i++;
        }
        $column_data .= "\r\n";

        $this->attach_export_data( $column_data );

        return $column_data;
    }

    public function process_export_step() {
        if ( $this->step < 2 ) {
            /** @scrutinizer ignore-unhandled */ @unlink( $this->file );
            $this->print_columns();
        }

        $return = $this->print_rows();

        if ( $return ) {
            return true;
        } else {
            return false;
        }
    }

    public function get_export_status() {
        $status = 100;
        return apply_filters( 'wpinv_get_export_status_' . $this->export, $status );
    }

    public function get_export_data() {
        $data = array();

        $data = apply_filters( 'wpinv_export_get_data', $data );
        $data = apply_filters( 'wpinv_export_get_data_' . $this->export, $data );

        return $data;
    }

    public function print_rows() {
        $row_data   = '';
        $data       = $this->get_export_data();
        $columns    = $this->get_columns();

        if ( $data ) {
            foreach ( $data as $row ) {
                $i = 1;
                foreach ( $row as $key => $column ) {
                    if ( array_key_exists( $key, $columns ) ) {
                        $row_data .= '"' . addslashes( preg_replace( "/\"/","'", $column ) ) . '"';
                        $row_data .= $i == count( $columns ) ? '' : ',';
                        $i++;
                    }
                }
                $row_data .= "\r\n";
            }

            $this->attach_export_data( $row_data );

            return $row_data;
        }

        return false;
    }

    // Export Invoices.
    public function set_invoices_export( $request ) {
        $this->from_date    = isset( $request['from_date'] ) ? sanitize_text_field( $request['from_date'] ) : '';
        $this->to_date      = isset( $request['to_date'] ) ? sanitize_text_field( $request['to_date'] ) : '';
        $this->status       = isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'publish';
    }

    public function get_invoices_columns( $columns = array() ) {
        $columns = array(
            'id'            => __( 'ID',   'invoicing' ),
            'number'        => __( 'Number',   'invoicing' ),
            'date'          => __( 'Date', 'invoicing' ),
            'due_date'      => __( 'Due Date', 'invoicing' ),
            'completed_date'=> __( 'Payment Done Date', 'invoicing' ),
            'amount'        => __( 'Amount', 'invoicing' ),
            'currency'      => __( 'Currency', 'invoicing' ),
            'items'        => __( 'Items', 'invoicing' ),
            'status_nicename'  => __( 'Status Nicename', 'invoicing' ),
            'status'        => __( 'Status', 'invoicing' ),
            'tax'           => __( 'Tax', 'invoicing' ),
            'discount'      => __( 'Discount', 'invoicing' ),
            'user_id'       => __( 'User ID', 'invoicing' ),
            'email'         => __( 'Email', 'invoicing' ),
            'first_name'    => __( 'First Name', 'invoicing' ),
            'last_name'     => __( 'Last Name', 'invoicing' ),
            'address'       => __( 'Address', 'invoicing' ),
            'city'          => __( 'City', 'invoicing' ),
            'state'         => __( 'State', 'invoicing' ),
            'country'       => __( 'Country', 'invoicing' ),
            'zip'           => __( 'Zipcode', 'invoicing' ),
            'phone'         => __( 'Phone', 'invoicing' ),
            'company'       => __( 'Company', 'invoicing' ),
            'vat_number'    => __( 'Vat Number', 'invoicing' ),
            'ip'            => __( 'IP', 'invoicing' ),
            'gateway'       => __( 'Gateway', 'invoicing' ),
            'gateway_nicename'       => __( 'Gateway Nicename', 'invoicing' ),
            'transaction_id'=> __( 'Transaction ID', 'invoicing' ),
        );

        return $columns;
    }

    public function get_invoices_data( $response = array() ) {
        $args = array(
            'limit'    => $this->per_page,
            'page'     => $this->step,
            'order'    => 'DESC',
            'orderby'  => 'date',
        );

        if ( $this->status != 'any' ) {
            $args['status'] = $this->status;
        } else {
            $args['status'] = array_keys( wpinv_get_invoice_statuses( true ) );
        }

        if ( !empty( $this->from_date ) || !empty( $this->to_date ) ) {
            $args['date_query'] = array(
                array(
                    'after'     => date( 'Y-n-d 00:00:00', strtotime( $this->from_date ) ),
                    'before'    => date( 'Y-n-d 23:59:59', strtotime( $this->to_date ) ),
                    'inclusive' => true
                )
            );
        }

        $invoices = wpinv_get_invoices( $args );

        $data = array();

        if ( !empty( $invoices ) ) {
            foreach ( $invoices as $invoice ) {
                $items = $this->get_invoice_items($invoice);
                $row = array(
                    'id'            => $invoice->ID,
                    'number'        => $invoice->get_number(),
                    'date'          => $invoice->get_invoice_date( false ),
                    'due_date'      => $invoice->get_due_date( false ),
                    'completed_date'=> $invoice->get_completed_date(),
                    'amount'        => wpinv_round_amount( $invoice->get_total() ),
                    'currency'      => $invoice->get_currency(),
                    'items'         => $items,
                    'status_nicename' => $invoice->get_status( true ),
                    'status'        => $invoice->get_status(),
                    'tax'           => $invoice->get_tax() > 0 ? wpinv_round_amount( $invoice->get_tax() ) : '',
                    'discount'      => $invoice->get_discount() > 0 ? wpinv_round_amount( $invoice->get_discount() ) : '',
                    'user_id'       => $invoice->get_user_id(),
                    'email'         => $invoice->get_email(),
                    'first_name'    => $invoice->get_first_name(),
                    'last_name'     => $invoice->get_last_name(),
                    'address'       => $invoice->get_address(),
                    'city'          => $invoice->city,
                    'state'         => $invoice->state,
                    'country'       => $invoice->country,
                    'zip'           => $invoice->zip,
                    'phone'         => $invoice->phone,
                    'company'       => $invoice->company,
                    'vat_number'    => $invoice->vat_number,
                    'ip'            => $invoice->get_ip(),
                    'gateway'       => $invoice->get_gateway(),
                    'gateway_nicename' => $invoice->get_gateway_title(),
                    'transaction_id'=> $invoice->gateway ? $invoice->get_transaction_id() : '',
                );

                $data[] = apply_filters( 'wpinv_export_invoice_row', $row, $invoice );
            }

            return $data;

        }

        return false;
    }

    public function invoices_export_status() {
        $args = array(
            'limit'    => -1,
            'return'   => 'ids',
        );

        if ( $this->status != 'any' ) {
            $args['status'] = $this->status;
        } else {
            $args['status'] = array_keys( wpinv_get_invoice_statuses( true ) );
        }

        if ( !empty( $this->from_date ) || !empty( $this->to_date ) ) {
            $args['date_query'] = array(
                array(
                    'after'     => date( 'Y-n-d 00:00:00', strtotime( $this->from_date ) ),
                    'before'    => date( 'Y-n-d 23:59:59', strtotime( $this->to_date ) ),
                    'inclusive' => true
                )
            );
        }

        $invoices   = wpinv_get_invoices( $args );
        $total      = !empty( $invoices ) ? count( $invoices ) : 0;
        $status     = 100;

        if ( $total > 0 ) {
            $status = ( ( $this->per_page * $this->step ) / $total ) * 100;
        }

        if ( $status > 100 ) {
            $status = 100;
        }

        return $status;
    }

    public function get_invoice_items($invoice){
        if(!$invoice){
            return '';
        }

        $cart_details = $invoice->get_cart_details();
        if(!empty($cart_details)){
            $cart_details = maybe_serialize($cart_details);
        } else {
            $cart_details = '';
        }

        return $cart_details;
    }

    /**
     * Returns the periods filter.
     */
    public function period_filter( $args = array() ) {

        ob_start();

        echo '<form id="wpinv-graphs-filter" method="get" style="margin-bottom: 10px;" class="tablenav">';
        echo '<input type="hidden" name="page" value="wpinv-reports">';

        foreach ( $args as $key => $val ) {
            $key = esc_attr($key);
            $val = esc_attr($val);
            echo "<input type='hidden' name='$key' value='$val'>";
        }

        echo '<select id="wpinv-graphs-date-options" name="range" style="min-width: 200px;" onChange="this.form.submit()">';

        $ranges = array(
            'today'        => __( 'Today', 'invoicing' ),
            'yesterday'    => __( 'Yesterday', 'invoicing' ),
            'this_week'    => __( 'This Week', 'invoicing' ),
            'last_week'    => __( 'Last Week', 'invoicing' ),
            '7_days_ago'   => __( 'Last 7 Days', 'invoicing' ),
            '30_days_ago'  => __( 'Last 30 Days', 'invoicing' ),
            'this_month'   => __( 'This Month', 'invoicing' ),
            'this_year'    => __( 'This Year', 'invoicing' ),
            'last_year'    => __( 'Last Year', 'invoicing' ),
        );

        $range = isset( $_GET['range'] ) && isset( $ranges[ $_GET['range'] ] ) ? $_GET['range'] : '7_days_ago';

        foreach ( $ranges as $val => $label ) {
            $selected = selected( $range, $val, false );
            echo "<option value='$val' $selected>$label</option>";
        }

        echo '</select></form>';

        return ob_get_clean();
    }

    /**
     * Returns the the current date range.
     */
    public function get_sql_clauses( $range ) {

        $date     = 'CAST(meta.completed_date AS DATE)';
        $datetime = 'meta.completed_date';

        // Prepare durations.
        $today                = current_time( 'Y-m-d' );
        $yesterday            = date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
        $sunday               = date( 'Y-m-d', strtotime( 'sunday this week', current_time( 'timestamp' ) ) );
        $monday               = date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ) );
        $last_sunday          = date( 'Y-m-d', strtotime( 'sunday last week', current_time( 'timestamp' ) ) );
        $last_monday          = date( 'Y-m-d', strtotime( 'monday last week', current_time( 'timestamp' ) ) );
        $seven_days_ago       = date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) );
        $thirty_days_ago      = date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
        $first_day_month  	  = date( 'Y-m-1', current_time( 'timestamp' ) );
        $last_day_month  	  = date( 'Y-m-t', current_time( 'timestamp' ) );
		$first_day_last_month = date( 'Y-m-d', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
        $last_day_last_month  = date( 'Y-m-d', strtotime( 'last day of last month', current_time( 'timestamp' ) ) );
        $first_day_year  	  = date( 'Y-1-1', current_time( 'timestamp' ) );
        $last_day_year  	  = date( 'Y-12-31', current_time( 'timestamp' ) );
		$first_day_last_year  = date( 'Y-m-d', strtotime( 'first day of last year', current_time( 'timestamp' ) ) );
		$last_day_last_year   = date( 'Y-m-d', strtotime( 'last day of last year', current_time( 'timestamp' ) ) );

        $ranges = array(

            'today'        => array(
                "HOUR($datetime)",
                "$date='$today'"
            ),

            'yesterday'    => array(
                "HOUR($datetime)",
                "$date='$yesterday'"
            ),

            'this_week'    => array(
                "DAYNAME($datetime)",
                "$date BETWEEN '$monday' AND '$sunday'"
            ),

            'last_week'    => array(
                "DAYNAME($datetime)",
                "$date BETWEEN '$last_monday' AND '$last_sunday'"
            ),

            '7_days_ago'   => array(
                "DAY($datetime)",
                "$date BETWEEN '$seven_days_ago' AND '$today'"
            ),

            '30_days_ago'  => array(
                "DAY($datetime)",
                "$date BETWEEN '$thirty_days_ago' AND '$today'"
            ),

            'this_month'   => array(
                "DAY($datetime)",
                "$date BETWEEN '$first_day_month' AND '$last_day_month'"
            ),

            'last_month'   => array(
                "DAY($datetime)",
                "$date BETWEEN '$first_day_last_month' AND '$last_day_last_month'"
            ),

            'this_year'    => array(
                "MONTH($datetime)",
                "$date BETWEEN '$first_day_year' AND '$last_day_year'"
            ),

            'last_year'    => array(
                "MONTH($datetime)",
                "$date BETWEEN '$first_day_last_year' AND '$last_day_last_year'"
            ),

        );

        if ( ! isset( $ranges[ $range ] ) ) {
            return $ranges['7_days_ago'];
        }
        return $ranges[ $range ];

    }

    /**
     * Returns the the current date ranges results.
     */
    public function get_report_results( $range ) {
        global $wpdb;

        $table   = $wpdb->prefix . 'getpaid_invoices';
        $clauses = $this->get_sql_clauses( $range );
        $sql     = "SELECT
                {$clauses[0]} AS completed_date,
                SUM( meta.total ) AS total,
                SUM( meta.discount ) AS discount,
                SUM( meta.tax ) AS tax,
                SUM( meta.fees_total ) AS fees_total
            FROM $wpdb->posts
            LEFT JOIN $table as meta ON meta.post_id = $wpdb->posts.ID
            WHERE meta.post_id IS NOT NULL
                AND $wpdb->posts.post_type = 'wpi_invoice'
                AND ( $wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'renewal' )
                AND {$clauses[1]}
            GROUP BY {$clauses[0]}
        ";

        return $wpdb->get_results( $sql );
    }

    /**
     * Fill nulls.
     */
    public function fill_nulls( $data, $range ) {

        $return = array();
        $time   = current_time('timestamp');

        switch ( $range ) {
            case 'today' :
            case 'yesterday' :
                $hour  = 0;

                while ( $hour < 23 ) {
                    $amount = 0;
                    if ( isset( $data[$hour] ) ) {
                        $amount = floatval( $data[$hour] );
                    }

                    $time = strtotime( "$range $hour:00:00" ) * 1000;
                    $return[] = array( $time, $amount );
                    $hour++;
                }

                break;

            case 'this_month' :
            case 'last_month' :
                $_range = str_replace( '_', ' ', $range );
                $month  = date( 'n', strtotime( $_range, $time ) );
                $year   = date( 'Y', strtotime( $_range, $time ) );
                $days   = cal_days_in_month(
                    defined( 'CAL_GREGORIAN' ) ? CAL_GREGORIAN : 1,
                    $month,
                    $year
                );

                $day = 1;
                while ( $days != $day ) {
                    $amount = 0;
                    if ( isset( $data[$day] ) ) {
                        $amount = floatval( $data[$day] );
                    }

                    $time = strtotime( "$year-$month-$day" ) * 1000;
                    $return[] = array( $time, $amount );
                    $day++;
                }

                break;

            case 'this_week' :
            case 'last_week' :
                $_range = str_replace( '_', ' ', $range );
                $days   = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

                foreach ( $days as $day ) {

                    $amount = 0;
                    if ( isset( $data[ ucfirst( $day ) ] ) ) {
                        $amount = floatval( $data[ ucfirst( $day ) ] );
                    }

                    $time = strtotime( "$_range $day" ) * 1000;
                    $return[] = array( $time, $amount );
                }

                break;

            case 'this_year' :
            case 'last_year' :
                $_range = str_replace( '_', ' ', $range );
                $year   = date( 'Y', strtotime( $_range, $time ) );
                $months = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );

                foreach ( $months as $month ) {

                    $amount = 0;
                    if ( isset( $data[$month] ) ) {
                        $amount = floatval( $data[$month] );
                    }

                    $_time     = strtotime("$year-$month-01") * 1000;
                    $return[] = array( $_time, $amount );
                }

                break;
            case '30_days_ago' :
                $days = 30;

                while ( $days > 1 ) {
                    $amount = 0;
                    $date   = date( 'j', strtotime( "-$days days", $time ) );
                    if ( isset( $data[$date] ) ) {
                        $amount = floatval( $data[$date] );
                    }

                    $_time = strtotime( "-$days days", $time ) * 1000;
                    $return[] = array( $_time, $amount );
                    $days--;
                }

                break;

            default:
                $days = 7;

                while ( $days > 1 ) {
                    $amount = 0;
                    $date   = date( 'j', strtotime( "-$days days", $time ) );
                    if ( isset( $data[$date] ) ) {
                        $amount = floatval( $data[$date] );
                    }

                    $_time = strtotime( "-$days days", $time ) * 1000;
                    $return[] = array( $_time, $amount );
                    $days--;
                }

                break;

        }

        return $return;
    }

    /**
     * Retrieves the stats.
     */
    public function get_stats() {
        $range     = isset( $_GET['range'] ) ? $_GET['range'] : '7_days_ago';
        $results   = $this->get_report_results( $range );
        $earnings  = wp_list_pluck( $results, 'total', 'completed_date' );
        $taxes     = wp_list_pluck( $results, 'tax', 'completed_date' );
        $discounts = wp_list_pluck( $results, 'discount', 'completed_date' );
        $fees      = wp_list_pluck( $results, 'fees_total', 'completed_date' );

        return array(

            array(
                'label' => __( 'Earnings', 'invoicing' ),
                'data'  => $this->fill_nulls( $earnings, $range ),
            ),

            array(
                'label' => __( 'Taxes', 'invoicing' ),
                'data'  => $this->fill_nulls( $taxes, $range ),
            ),

            array(
                'label' => __( 'Discounts', 'invoicing' ),
                'data'  => $this->fill_nulls( $discounts, $range ),
            ),

            array(
                'label' => __( 'Fees', 'invoicing' ),
                'data'  => $this->fill_nulls( $fees, $range ),
            )
        );

    }

    /**
     * Retrieves the time format for stats.
     */
    public function get_time_format() {
        $range    = isset( $_GET['range'] ) ? $_GET['range'] : '7_days_ago';

        switch ( $range ) {
            case 'today' :
            case 'yesterday' :
                return array( 'hour', '%h %p' );
                break;

            case 'this_month' :
            case 'last_month' :
                return array( 'day', '%b %d' );
                break;

            case 'this_week' :
            case 'last_week' :
                return array( 'day', '%b %d' );
                break;

            case 'this_year' :
            case 'last_year' :
                return array( 'month', '%b' );
                break;
            case '30_days_ago' :
                return array( 'day', '%b %d' );
                break;

            default:
                return array( 'day', '%b %d' );
                break;

        }
    }

    /**
     * Displays the earnings report.
     */
    public function earnings_report() {

        $data        = wp_json_encode( $this->get_stats() );
        $time_format = $this->get_time_format();
        echo '
            <div class="wpinv-report-container">
                <h3><span>' . __( 'Earnings Over Time', 'invoicing' ) .'</span></h3>
                ' . $this->period_filter() . '
                <div id="wpinv_report_graph" style="height: 450px;"></div>
            </div>

            <script>
                jQuery(document).ready( function() {
                    jQuery.plot(
                        jQuery("#wpinv_report_graph"),
                        ' . $data .',
                        {
                            xaxis:{
                                mode: "time",
                                timeformat: "' . $time_format[1] .'",
                                minTickSize: [0.5, "' . $time_format[0] .'"]
                            },

                            yaxis: {
                                min: 0
                            },

                            tooltip: true,

                            series: {
                                lines: { show: true },
                                points: { show: true }
                            },

                            grid: {
                                backgroundColor: { colors: [ "#fff", "#eee" ] },
                            }
                        }
                    );
                })
            </script>
        ';
    }

    /**
     * Displays the gateways report.
     */
    public function gateways_report() {
        require_once( WPINV_PLUGIN_DIR . 'includes/admin/class-wpinv-gateways-report-table.php' );

        $table = new WPInv_Gateways_Report_Table();
        $table->prepare_items();
        $table->display();
    }

    /**
     * Displays the items report.
     */
    public function items_report() {
        require_once( WPINV_PLUGIN_DIR . 'includes/admin/class-wpinv-items-report-table.php' );

        $table = new WPInv_Items_Report_Table();
        $table->prepare_items();
        $table->display();
        echo __( '* Items with no sales not shown.', 'invoicing' );
    }

    /**
     * Renders the Tax Reports
     *
     * @return void
     */
    public function tax_report() {

        require_once( WPINV_PLUGIN_DIR . 'includes/admin/class-wpinv-taxes-report-table.php' );
        $table = new WPInv_Taxes_Reports_Table();
        $table->prepare_items();
        $year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : date( 'Y' );
        ?>

        <div class="metabox-holder" style="padding-top: 0;">
            <div class="postbox">
                <h3><span><?php _e('Tax Report','invoicing' ); ?></span></h3>
                <div class="inside">
                    <p><?php _e( 'This report shows the total amount collected in sales tax for the given year.', 'invoicing' ); ?></p>
                    <form method="get">
                        <span><?php echo $year; ?></span>: <strong><?php echo wpinv_sales_tax_for_year( $year ); ?></strong>&nbsp;&mdash;&nbsp;
                        <select name="year">
                            <?php for ( $i = 2014; $i <= date( 'Y' ); $i++ ) : ?>
                            <option value="<?php echo $i; ?>"<?php selected( $year, $i ); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <input type="hidden" name="view" value="taxes" />
                        <input type="hidden" name="page" value="wpinv-reports"/>
                        <?php submit_button( __( 'Submit', 'invoicing' ), 'secondary', 'submit', false ); ?>
                    </form>
                </div><!-- .inside -->
            </div><!-- .postbox -->
        </div><!-- .metabox-holder -->
        <?php $table->display(); ?>
        <?php
    }

}
