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
            add_action( 'admin_menu', array( $this, 'add_submenu' ), 10 );
            add_action( 'wpinv_reports_tab_export', array( $this, 'export' ) );
            add_action( 'wp_ajax_wpinv_ajax_export', array( $this, 'ajax_export' ) );
            
            // Export Invoices.
            add_action( 'wpinv_export_set_params_invoices', array( $this, 'set_invoices_export' ) );
            add_filter( 'wpinv_export_get_columns_invoices', array( $this, 'get_invoices_columns' ) );
            add_filter( 'wpinv_export_get_data_invoices', array( $this, 'get_invoices_data' ) );
            add_filter( 'wpinv_get_export_status_invoices', array( $this, 'invoices_export_status' ) );
        }
        do_action( 'wpinv_class_reports_actions', $this );
    }
    
    public function add_submenu() {
        global $wpi_reports_page;
        $wpi_reports_page = add_submenu_page( 'wpinv', __( 'Reports', 'invoicing' ), __( 'Reports', 'invoicing' ), 'manage_options', 'wpinv-reports', array( $this, 'reports_page' ) );
    }
    
    public function reports_page() {
        if ( !wp_script_is( 'postbox', 'enqueued' ) ) {
            wp_enqueue_script( 'postbox' );
        }
        if ( !wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }
        
        $current_page = admin_url( 'admin.php?page=wpinv-reports' );
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'export';
        ?>
        <div class="wrap wpi-reports-wrap">
            <h1><?php echo esc_html( __( 'Reports', 'invoicing' ) ); ?></h1>
            <h2 class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo add_query_arg( array( 'tab' => 'export', 'settings-updated' => false ), $current_page ); ?>" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Export', 'invoicing' ); ?></a>
                <?php do_action( 'wpinv_reports_page_tabs' ); ;?>
            </h2>
            <div class="wpi-reports-content wpi-reports-<?php echo $active_tab; ?>">
            <?php
                do_action( 'wpinv_reports_page_top' );
                do_action( 'wpinv_reports_tab_' . $active_tab );
                do_action( 'wpinv_reports_page_bottom' );
            ?>
        </div>
        <?php
    }
    
    public function export() {
        $statuses = wpinv_get_invoice_statuses();
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
                                    'class'            => '',
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
        
        if ( empty( $_POST['data'] ) || !current_user_can( 'manage_options' ) ) {
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
    
    public function set_export_params( $request ) {
        $this->empty    = false;
        $this->step     = !empty( $request['step'] ) ? absint( $request['step'] ) : 1;
        $this->export   = !empty( $request['export'] ) ? $request['export'] : $this->export;
        $this->filename = 'wpi-' . $this->export . '-' . $request['wpi_ajax_export'] . '.' . $this->filetype;
        $this->file     = $this->export_dir . $this->filename;
        
        do_action( 'wpinv_export_set_params_' . $this->export, $request );
    }
    
    public function get_columns() {
        $columns = array(
            'id'   => __( 'ID',   'invoicing' ),
            'date' => __( 'Date', 'invoicing' )
        );
        
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
            @unlink( $this->file );
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
        $data = array(
            0 => array(
                'id'   => '',
                'data' => date( 'F j, Y' )
            ),
            1 => array(
                'id'   => '',
                'data' => date( 'F j, Y' )
            )
        );

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
            'amount'        => __( 'Amount', 'invoicing' ),
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
            'currency'      => __( 'Currency', 'invoicing' ),
            'due_date'      => __( 'Due Date', 'invoicing' ),
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
                $row = array(
                    'id'            => $invoice->ID,
                    'number'        => $invoice->get_number(),
                    'date'          => $invoice->get_invoice_date( false ),
                    'amount'        => wpinv_round_amount( $invoice->get_total() ),
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
                    'currency'      => $invoice->get_currency(),
                    'due_date'      => $invoice->needs_payment() ? $invoice->get_due_date() : '',
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
}
