<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_BP_Component {
    public $position;
    public $count;
    
    public function __construct() {

        if ( !defined( 'WPINV_BP_SLUG' ) ) {
            define( 'WPINV_BP_SLUG', 'invoices' );
        }

        add_action( 'wp_ajax_invoicing_filter', array( $this, 'invoices_content' ) );
        add_action( 'wp_ajax_nopriv_invoicing_filter', array( $this, 'invoices_content' ) );
        add_filter( 'wpinv_settings_sections_general', array( $this, 'bp_section' ), 10, 1 );
        add_filter( 'wpinv_settings_general', array( $this, 'bp_settings' ), 10, 1 );
        add_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ), 10, 2 );
        add_action('bp_setup_nav', array($this, 'setup_nav'), 15);
        
        $position       = wpinv_get_option( 'wpinv_menu_position' );
        $position       = $position !== '' && $position !== false ? $position : 91;
        $this->position = apply_filters( 'wpinv_bp_nav_position', $position );
        $this->id     = WPINV_BP_SLUG;
    }

    public function setup_nav() {

        if ( wpinv_get_option( 'wpinv_bp_hide_menu' ) || !is_user_logged_in()) {
            return;
        }

        if(bp_displayed_user_id() != bp_loggedin_user_id() && !current_user_can('administrator')){
            return;
        }

        $count = $this->get_invoice_count();
        $class = ( 0 === $count ) ? 'no-count' : 'count';

        $main_nav_name = sprintf(
            __( 'My Invoices %s', 'invoicing' ),
            sprintf(
                '<span class="%s">%s</span>',
                esc_attr( $class ),
                bp_core_number_format( $count )
            )
        );

        $main_nav = array(
            'name'                => $main_nav_name,
            'slug'                => WPINV_BP_SLUG,
            'position'            => $this->position,
            'screen_function'     => array( $this, 'invoices_screen' ),
            'default_subnav_slug' => 'invoices',
            'item_css_id'         => $this->id
        );

        bp_core_new_nav_item( $main_nav );
    }
    
    public function invoices_screen() {
        if ( wpinv_get_option( 'wpinv_bp_hide_menu' ) ) {
            return;
        }
        
        add_action( 'bp_template_content', array( $this, 'invoices_content' ) );

        $template = apply_filters( 'bp_core_template_plugin', 'members/single/plugins' );
        
        bp_core_load_template( apply_filters( 'wpinv_bp_core_template_plugin', $template ) );
    }
    
    public function invoices_content() {
        if ( $this->has_invoices( bp_ajax_querystring( 'invoices' ) ) ) {
            global $invoices_template;
            
            do_action( 'wpinv_bp_invoices_before_content' );
            ?>
            <div class="wpi-g wpi-bp-invoices invoices invoicing" style="position:relative">
                <div id="pag-top" class="pagination">
                    <div class="pag-count" id="invoice-dir-count-top">
                        <?php echo $this->pagination_count(); ?>
                    </div>
                    <div class="pagination-links" id="invoice-dir-pag-top">
                        <?php echo $this->pagination_links(); ?>
                    </div>
                </div>
                <table class="table table-bordered table-hover table-responsive wpi-user-invoices" style="margin:0">
                    <thead>
                        <tr>
                            <?php foreach ( wpinv_get_user_invoices_columns() as $column_id => $column_name ) : ?>
                                <th class="<?php echo esc_attr( $column_id ); ?> <?php echo (!empty($column_name['class']) ? $column_name['class'] : '');?>"><span class="nobr"><?php echo esc_html( $column_name['title'] ); ?></span></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $invoices_template->invoices as $invoice ) {
                            ?>
                            <tr class="wpinv-item wpinv-item-<?php echo $invoice_status = $invoice->get_status(); ?>">
                                <?php foreach ( wpinv_get_user_invoices_columns() as $column_id => $column_name ) : ?>
                                    <td class="<?php echo esc_attr( $column_id ); ?> <?php echo (!empty($column_name['class']) ? $column_name['class'] : '');?>" data-title="<?php echo esc_attr( $column_name['title'] ); ?>">
                                        <?php if ( has_action( 'wpinv_user_invoices_column_' . $column_id ) ) : ?>
                                            <?php do_action( 'wpinv_user_invoices_column_' . $column_id, $invoice ); ?>

                                        <?php elseif ( 'invoice-number' === $column_id ) : ?>
                                            <a href="<?php echo esc_url( $invoice->get_view_url() ); ?>">
                                                <?php echo _x( '#', 'hash before invoice number', 'invoicing' ) . $invoice->get_number(); ?>
                                            </a>

                                        <?php elseif ( 'created-date' === $column_id ) : $date = wpinv_get_date_created( $invoice->ID ); $dateYMD = wpinv_get_date_created( $invoice->ID, 'Y-m-d H:i:s' ); ?>
                                            <time datetime="<?php echo strtotime( $dateYMD ); ?>" title="<?php echo $dateYMD; ?>"><?php echo $date; ?></time>

                                        <?php elseif ( 'payment-date' === $column_id ) : $date = wpinv_get_invoice_date( $invoice->ID, '', false ); $dateYMD = wpinv_get_invoice_date( $invoice->ID, 'Y-m-d H:i:s', false ); ?>
                                            <time datetime="<?php echo strtotime( $dateYMD ); ?>" title="<?php echo $dateYMD; ?>"><?php echo $date; ?></time>

                                        <?php elseif ( 'invoice-status' === $column_id ) : ?>
                                            <?php echo wpinv_invoice_status_label( $invoice_status, $invoice->get_status( true ) ) ; ?>

                                        <?php elseif ( 'invoice-total' === $column_id ) : ?>
                                            <?php echo $invoice->get_total( true ); ?>

                                        <?php elseif ( 'invoice-actions' === $column_id ) : ?>
                                            <?php
                                                $actions = array(
                                                    'pay'    => array(
                                                        'url'  => $invoice->get_checkout_payment_url(),
                                                        'name' => __( 'Pay Now', 'invoicing' ),
                                                        'class' => 'btn-success'
                                                    ),
                                                    'print'   => array(
                                                        'url'  => $invoice->get_view_url(),
                                                        'name' => __( 'Print', 'invoicing' ),
                                                        'class' => 'btn-primary',
                                                        'attrs' => 'target="_blank"'
                                                    )
                                                );

                                                if ( ! $invoice->needs_payment() ) {
                                                    unset( $actions['pay'] );
                                                }

                                                if ( $actions = apply_filters( 'wpinv_user_invoices_actions', $actions, $invoice ) ) {
                                                    foreach ( $actions as $key => $action ) {
                                                        $class = !empty($action['class']) ? sanitize_html_class($action['class']) : '';
                                                        echo '<a href="' . esc_url( $action['url'] ) . '" class="btn btn-sm ' . $class . ' ' . sanitize_html_class( $key ) . '" ' . ( !empty($action['attrs']) ? $action['attrs'] : '' ) . '>' . $action['name'] . '</a>';
                                                    }
                                                }
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div id="pag-bottom" class="pagination">
                    <div class="pag-count" id="invoice-dir-count-bottom">
                        <?php echo $this->pagination_count(); ?>
                    </div>
                    <div class="pagination-links" id="invoice-dir-pag-bottom">
                        <?php echo $this->pagination_links(); ?>
                    </div>
                </div>
                <script type="text/javascript">
                jQuery('.wpi-bp-invoices .pagination-links').click(function(e) {
                    jQuery('table.wpi-user-invoices').css({'opacity': '0.67'});
                    jQuery('.wpi-bp-invoices').append('<span style="position:absolute;top:49.5%;left:49.5%;"><i class="fa fa-spin fa-refresh"></i></span>');
                });
                </script>
            </div>
            <?php
        
            do_action( 'wpinv_bp_invoices_after_content' );
        } else {
            ?>
            <div id="message" class="info">
                <p><?php _e( 'No invoice has been made yet.', 'invoicing' ); ?></p>
            </div>
            <?php
        }
        
        if ( defined( 'DOING_AJAX' ) ) {
            exit;
        }
    }
    
    public function has_invoices( $args = '' ) {
        global $invoices_template;

        $per_page = absint( wpinv_get_option( 'wpinv_bp_per_page' ) );
        // Parse arguments.
        $r = bp_parse_args( $args, array(
            'status'            => 'all',
            'page_arg'          => 'bpage',
            'page'              => 1,
            'per_page'          => $per_page > 0 ? $per_page : 20,
            'max'               => false,
            'user_id'           => bp_displayed_user_id(),
        ), 'has_invoices' );


        if ( ! empty( $r['max'] ) && ( (int)$r['per_page'] > (int)$r['max'] ) ) {
            $r['per_page'] = (int)$r['max'];
        }

        // Get the invoices.
        $invoices_template = new WPInv_BP_Invoices_Template( $r['status'], $r['page'], $r['per_page'], $r['max'], $r['user_id'], $r['page_arg'] );

        return apply_filters( 'wpinv_bp_has_invoices', $invoices_template->has_invoices(), $invoices_template, $r );
    }
    
    public function get_invoice_count() {
        $query      = apply_filters( 'wpinv_user_invoices_count_query', array( 'status' => 'all','user' => bp_displayed_user_id(), 'limit' => '-1', 'return' => 'ids', 'paginate' => false ) );
        $invoices   = wpinv_get_invoices( $query );
        
        return !empty( $invoices ) ? count( $invoices ) : 0;
    }
    
    public function pagination_count() {
        global $invoices_template;

        $start_num = intval( ( $invoices_template->pag_page - 1 ) * $invoices_template->pag_num ) + 1;
        $from_num  = bp_core_number_format( $start_num );
        $to_num    = bp_core_number_format( ( $start_num + ( $invoices_template->pag_num - 1 ) > $invoices_template->total_invoice_count ) ? $invoices_template->total_invoice_count : $start_num + ( $invoices_template->pag_num - 1 ) );
        $total     = bp_core_number_format( $invoices_template->total_invoice_count );

        if ( 1 == $invoices_template->total_invoice_count ) {
            $message = __( 'Viewing 1 invoice', 'invoicing' );
        } else {
            $message = sprintf( _n( 'Viewing %1$s - %2$s of %3$s invoice', 'Viewing %1$s - %2$s of %3$s invoices', $invoices_template->total_invoice_count, 'invoicing' ), $from_num, $to_num, $total );
        }

        return $message;
    }
    
    function pagination_links() {
        global $invoices_template;

        return apply_filters( 'wpinv_bp_get_pagination_links', $invoices_template->pag_links );
    }
    
    public function bp_section( $settings = array() ) {
        $settings['wpinv_bp'] = __( 'BuddyPress Integration', 'invoicing' );
        return $settings;
    }
    
    public function bp_settings( $settings = array() ) {
        $settings['wpinv_bp'] = array(
            'wpinv_bp_labels' => array(
                'id'   => 'wpinv_bp_settings',
                'name' => '<h3>' . __( 'BuddyPress Integration', 'invoicing' ) . '</h3>',
                'desc' => '',
                'type' => 'header',
            ),
            'wpinv_bp_hide_menu' => array(
                'id'   => 'wpinv_bp_hide_menu',
                'name' => __( 'Hide Invoices link', 'invoicing' ),
                'desc' => __( 'Hide Invoices link from BP Profile menu.', 'invoicing' ),
                'type' => 'checkbox',
            ),
            'wpinv_menu_position' => array(
                'id'   => 'wpinv_menu_position',
                'name' => __( 'Menu position', 'invoicing' ),
                'desc' => __( 'Menu position for the Invoices link in BP Profile menu.', 'invoicing' ),
                'type' => 'number',
                'size' => 'small',
                'min'  => '1',
                'max'  => '100000',
                'step' => '1',
                'std'  => '91'
            ),
            'wpinv_bp_per_page' => array(
                'id'   => 'wpinv_bp_per_page',
                'name' => __( 'Max invoices per page', 'invoicing' ),
                'desc' => __( 'Enter a number to lists the invoices for each page.', 'invoicing' ),
                'type' => 'number',
                'size' => 'small',
                'min'  => '1',
                'max'  => '1000',
                'step' => '1',
                'std'  => '20'
            ),
        );
        
        return $settings;
    }

    public function wp_nav_menu_objects($items, $args){
        if(!is_user_logged_in()){
            return $items;
        }

        if(!apply_filters('wpinv_bp_invoice_history_redirect', true, $items, $args)){
            return $items;
        }

        $user_id = get_current_user_id();
        $link = bp_core_get_user_domain( $user_id ).WPINV_BP_SLUG;
        $history_link = wpinv_get_history_page_uri();
        foreach ( $items as $item ) {
            $item->url = str_replace( $history_link, $link, $item->url );
        }

        return $items;
    }
}

class WPInv_BP_Invoices_Template {
    public $current_invoice = -1;
    public $invoice_count = 0;
    public $invoices = array();
    public $invoice;
    public $in_the_loop = false;
    public $pag_page = 1;
    public $pag_num = 20;
    public $pag_links = '';
    public $total_invoice_count = 0;
    
    public function __construct( $status, $page, $per_page, $max, $user_id, $page_arg = 'bpage' ) {
        $this->invoices = array( 'invoices' => array(), 'total' => 0 );
        
        $this->pag_arg  = sanitize_key( $page_arg );
        $this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $page );
        $this->pag_num  = bp_sanitize_pagination_arg( 'num', $per_page );

        $query_args     = array( 'user' => $user_id, 'page' => $this->pag_page, 'limit' => $this->pag_num, 'return' => 'self', 'paginate' => true );
        if ( !empty( $status ) && $status != 'all' ) {
           $query_args['status'] = $status;
        }
        $invoices  = wpinv_get_invoices( apply_filters( 'wpinv_bp_user_invoices_query', $query_args ) );
        
        if ( !empty( $invoices ) && !empty( $invoices->found_posts ) ) {
            $this->invoices['invoices'] = array_map( 'wpinv_get_invoice', $invoices->posts );
            $this->invoices['total']    = $invoices->found_posts;
        }

        if ( empty( $max ) || ( $max >= (int)$this->invoices['total'] ) ) {
            $this->total_invoice_count = (int)$this->invoices['total'];
        } else {
            $this->total_invoice_count = (int)$max;
        }

        $this->invoices = $this->invoices['invoices'];

        $invoice_count = count( $this->invoices );

        if ( empty( $max ) || ( $max >= (int)$invoice_count ) ) {
            $this->invoice_count = (int)$invoice_count;
        } else {
            $this->invoice_count = (int)$max;
        }
        
        if ( ! empty( $this->total_invoice_count ) && ! empty( $this->pag_num ) ) {
            $this->pag_links = paginate_links( array(
                'base'      => add_query_arg( $this->pag_arg, '%#%' ),
                'format'    => '',
                'total'     => ceil( (int)$this->total_invoice_count / (int)$this->pag_num ),
                'current'   => (int)$this->pag_page,
                'prev_text' => _x( '&larr;', 'Invoice pagination previous text', 'invoicing' ),
                'next_text' => _x( '&rarr;', 'Invoice pagination next text',     'invoicing' ),
                'mid_size'  => 1,
                'add_args'  => array(),
            ) );
        }
    }

    public function has_invoices() {
        return (bool) ! empty( $this->invoice_count );
    }

    public function next_invoice() {
        $this->current_invoice++;
        $this->invoice = $this->invoices[ $this->current_invoice ];

        return $this->invoice;
    }

    public function rewind_invoices() {
        $this->current_invoice = -1;
        if ( $this->invoice_count > 0 ) {
            $this->invoice = $this->invoices[0];
        }
    }

    public function invoices() {
        if ( ( $this->current_invoice + 1 ) < $this->invoice_count ) {
            return true;
        } elseif ( ( $this->current_invoice + 1 ) === $this->invoice_count ) {

            do_action( 'wpinv_bp_invoice_loop_end' );
            
            $this->rewind_invoices();
        }

        $this->in_the_loop = false;
        return false;
    }

    public function the_invoice() {

        $this->in_the_loop = true;
        $this->invoice     = $this->next_invoice();

        if ( 0 === $this->current_invoice ) {
            do_action( 'wpinv_bp_invoice_loop_start' );
        }
    }
}

function wpinv_bp_setup_component() {

    if(!class_exists( 'BuddyPress' )){
        return;
    }

    new WPInv_BP_Component();

}
add_action( 'bp_loaded', 'wpinv_bp_setup_component' );