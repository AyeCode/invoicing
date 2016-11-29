<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_BP_Component extends BP_Component {
    public $position;
    public $slug;
    
    public function __construct() {
        global $bp;
        
        if ( !defined( 'WPINV_BP_SLUG' ) ) {
            define( 'WPINV_BP_SLUG', 'invoices' );
        }
        
        $this->position = apply_filters( 'wpinv_bp_nav_position', 91 );
        $this->slug     = WPINV_BP_SLUG;
        
        parent::start(
            'invoicing',
            _x( 'Invoices', 'Invoices screen page <title>', 'invoicing' ),
            WPINV_PLUGIN_DIR . 'includes',
            array(
                'adminbar_myaccount_order' => $this->position
            )
        );
    }
    
    public function includes( $includes = array() ) {
        parent::includes( $includes );
    }
    
    public function setup_globals( $args = array() ) {
        global $bp;

        $args = array(
            'slug' => $this->slug,
        );

        parent::setup_globals( $args );
    }
    
    public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
        if ( !bp_is_my_profile() ) {
            return;
        }

        $user_id        = bp_loggedin_user_id();
        $user_domain    = bp_loggedin_user_domain();
        $invoices_link  = trailingslashit( $user_domain . $this->slug );
        $count          = friends_get_total_friend_count();
        $class          = ( 0 === $count ) ? 'no-count' : 'count';

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
            'slug'                => $this->slug,
            'position'            => $this->position,
            'screen_function'     => array( $this, 'invoices_screen' ),
            'default_subnav_slug' => 'invoices',
            'item_css_id'         => $this->id
        );
        
        $sub_nav[] = array(
            'name'            => _x( 'My Invoices', 'Invoices screen sub nav', 'invoicing' ),
            'slug'            => 'invoices',
            'parent_url'      => $invoices_link,
            'parent_slug'     => $this->slug,
            'screen_function' => array( $this, 'invoices_screen' ),
            'position'        => 10,
            'item_css_id'     => 'invoices-my-invoices'
        );

        parent::setup_nav( $main_nav, $sub_nav );
    }
    
    public function setup_title() {
        // Adjust title.
        if ( (bool)bp_is_current_component( 'invoicing' ) ) {
            global $bp;
            
            $bp->bp_options_title = __( 'My Invoices', 'invoicing' );
        }

        parent::setup_title();
    }
    
    public function invoices_screen() {
        global $bp;

        add_action( 'bp_template_content', array( $this, 'invoices_content' ) );

        $template = apply_filters( 'bp_core_template_plugin', 'members/single/plugins' );
        
        bp_core_load_template( apply_filters( 'wpinv_bp_core_template_plugin', $template ) );
    }
    
    public function invoices_content() {
        do_action( 'wpinv_bp_invoices_before_content' );
    
        ?>
        <div class="wpi-bp-invoices invoices">
        <?php echo do_shortcode( '[wpinv_history]' ); ?>
        </div>
        <?php
        
        do_action( 'wpinv_bp_invoices_after_content' );
    }
}

function wpinv_bp_setup_component() {
    global $bp;

    $bp->invoicing = new WPInv_BP_Component();
}
add_action( 'bp_loaded', 'wpinv_bp_setup_component' );
