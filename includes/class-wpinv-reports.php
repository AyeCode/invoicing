<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Reports {
    private $section = 'wpinv_reports';
    
    public function __construct() {
        $this->init();
        $this->includes();
        $this->actions();
    }
    
    public function init() {
        do_action( 'wpinv_class_reports_init', $this );
    }
    
    public function includes() {
        do_action( 'wpinv_class_reports_includes', $this );
    }
    
    public function actions() {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_submenu' ), 10 );
            add_action( 'wpinv_reports_tab_export', array( $this, 'export' ), 10 );
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
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'export';
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
                                    'id' => 'wpi_export_start', 
                                    'name' => 'wpi_export[start]',
                                    'data' => array(
                                        'dateFormat' => 'yy-mm-dd'
                                    ),
                                    'placeholder' => __( 'Start date', 'invoicing' ) )
                                ); ?>
                                <?php echo wpinv_html_date_field( array( 
                                    'id' => 'wpi_export_end',
                                    'name' => 'wpi_export[end]',
                                    'data' => array(
                                        'dateFormat' => 'yy-mm-dd'
                                    ),
                                    'placeholder' => __( 'End date', 'invoicing' ) )
                                ); ?>
                                <span id="wpinv-wpi_exportstatus-wrap">
                                <?php echo wpinv_html_select( array(
                                    'options'          => $statuses,
                                    'name'             => 'wpi_export[status]',
                                    'id'               => 'wpi_export_status',
                                    'show_option_all'  => false,
                                    'show_option_none' => false,
                                    'class'            => '',
                                ) ); ?>
                                <?php wp_nonce_field( 'wpi_ajax_export', 'wpi_ajax_export' ); ?>
                                </span>
                                <span id="wpinv-wpi_submit-wrap">
                                    <input type="submit" value="<?php _e( 'Generate CSV', 'invoicing' ); ?>" class="button-primary" />
                                </span>
                            </form>
                        </div>
                    </div>
                    
                    <?php do_action( 'wpinv_reports_tab_export_content_bottom' ); ?>
                </div>
            </div>
        </div>
<style>
.wpi-reports-export {
    padding-top: 10px;
}
.wpi-export-invoices form > span {
    margin-right: 5px;
}
.wpi-export-invoices form > span > select {
    vertical-align: inherit;
}
.wpi-reports-export .wpiDatepicker {
    width: 12em;
}
.wpi-export-form {
	position: relative;
}
.wpi-export-form .wpi-progress {
	width: calc(100% - 50px);
	height: 16px;
    background-color: #ddd;
    float: left;
}
.wpi-export-form .wpi-progress div {
	background-color: #ccc;
	height: 100%;
	width: 0;
    display: block
}
.wpi-export-form .wpi-export-msg {
    padding: 12px;
    background-color: #f4f4f4;
	border-style: solid;
	border-width: 1px 0;
	border-color: #eae9e9;
	overflow: auto;
	margin: 20px -12px -23px;
    position: relative;
}
.wpi-export-form .wpi-export-msg .wpi-export-loader {
    display: inline-block;
    float: right;
    margin: 0;
    vertical-align: middle;
    width: 40px;
    height: 16px;
    text-align: center;
}
.wpi-export-form .wpi-export-loader .fa-spin {
    font-size: 17px;
    color: #000;
}
.wpi-export-msg .updated {
    margin: 0 0 2px 0 !important;
}
        </style>
        <?php
    }
}
