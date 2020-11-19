<?php
/**
 * Contains the admin class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main admin class.
 *
 * @since       1.0.19
 */
class GetPaid_Admin {

    /**
	 * Local path to this plugins admin directory
	 *
	 * @var         string
	 */
	public $admin_path;

	/**
	 * Web path to this plugins admin directory
	 *
	 * @var         string
	 */
	public $admin_url;
	
	/**
	 * Reports components.
	 *
	 * @var GetPaid_Reports
	 */
    public $reports;

    /**
	 * Class constructor.
	 */
	public function __construct(){

        $this->admin_path  = plugin_dir_path( __FILE__ );
		$this->admin_url   = plugins_url( '/', __FILE__ );
		$this->reports     = new GetPaid_Reports();

        if ( is_admin() ) {
			$this->init_admin_hooks();
        }

    }

    /**
	 * Init action and filter hooks
	 *
	 */
	private function init_admin_hooks() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqeue_scripts' ) );
        add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
        add_action( 'admin_init', array( $this, 'init_ayecode_connect_helper' ) );
        add_action( 'admin_init', array( $this, 'activation_redirect') );
        add_action( 'admin_init', array( $this, 'maybe_do_admin_action') );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_action( 'getpaid_authenticated_admin_action_send_invoice', array( $this, 'send_customer_invoice' ) );
		add_action( 'getpaid_authenticated_admin_action_send_invoice_reminder', array( $this, 'send_customer_payment_reminder' ) );
        add_action( 'getpaid_authenticated_admin_action_reset_tax_rates', array( $this, 'admin_reset_tax_rates' ) );
		do_action( 'getpaid_init_admin_hooks', $this );

    }

    /**
	 * Register admin scripts
	 *
	 */
	public function enqeue_scripts() {
        global $current_screen, $pagenow;

		$page    = isset( $_GET['page'] ) ? $_GET['page'] : '';
		$editing = $pagenow == 'post.php' || $pagenow == 'post-new.php';

        if ( ! empty( $current_screen->post_type ) ) {
			$page = $current_screen->post_type;
        }

        // General styles.
        if ( false !== stripos( $page, 'wpi' ) ) {

            // Styles.
            $version = filemtime( WPINV_PLUGIN_DIR . 'assets/css/admin.css' );
            wp_enqueue_style( 'wpinv_admin_style', WPINV_PLUGIN_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), $version );
            wp_enqueue_style( 'select2', WPINV_PLUGIN_URL . 'assets/css/select2/select2.min.css', array(), '4.0.13', 'all' );
            wp_enqueue_style( 'wp_enqueue_style', WPINV_PLUGIN_URL . 'assets/css/meta-box.css', array(), WPINV_VERSION );
            wp_enqueue_style( 'jquery-ui-css', WPINV_PLUGIN_URL . 'assets/css/jquery-ui.min.css', array(), '1.8.16' );

            // Scripts.
            wp_register_script( 'jquery-blockui', WPINV_PLUGIN_URL . 'assets/js/jquery.blockUI.min.js', array( 'jquery' ), '4.0.13', true );
            wp_enqueue_script('select2', WPINV_PLUGIN_URL . 'assets/js/select2/select2.full.min.js', array( 'jquery' ), WPINV_VERSION );

            $version = filemtime( WPINV_PLUGIN_DIR . 'assets/js/admin.js' );
            wp_enqueue_script( 'wpinv-admin-script', WPINV_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-blockui','jquery-ui-tooltip', 'wp-color-picker', 'jquery-ui-datepicker' ),  $version );
            wp_localize_script( 'wpinv-admin-script', 'WPInv_Admin', apply_filters( 'wpinv_admin_js_localize', $this->get_admin_i18() ) );

        }

        // Payment form scripts.
		if ( 'wpi_payment_form' == $page && $editing ) {
            $this->load_payment_form_scripts();
        }

        if ( $page == 'wpinv-subscriptions' ) {
			wp_register_script( 'wpinv-sub-admin-script', WPINV_PLUGIN_URL . 'assets/js/subscriptions.js', array( 'wpinv-admin-script' ),  WPINV_VERSION );
			wp_enqueue_script( 'wpinv-sub-admin-script' );
		}

		if ( $page == 'wpinv-subscriptions' ) {
			wp_enqueue_script( 'postbox' );
		}

    }

    /**
	 * Returns admin js translations.
	 *
	 */
	protected function get_admin_i18() {
        global $post;

        $i18n = array(
            'ajax_url'                  => admin_url( 'admin-ajax.php' ),
            'post_ID'                   => isset( $post->ID ) ? $post->ID : '',
            'wpinv_nonce'               => wp_create_nonce( 'wpinv-nonce' ),
            'add_invoice_note_nonce'    => wp_create_nonce( 'add-invoice-note' ),
            'delete_invoice_note_nonce' => wp_create_nonce( 'delete-invoice-note' ),
            'invoice_item_nonce'        => wp_create_nonce( 'invoice-item' ),
            'billing_details_nonce'     => wp_create_nonce( 'get-billing-details' ),
            'tax'                       => wpinv_tax_amount(),
            'discount'                  => 0,
            'currency_symbol'           => wpinv_currency_symbol(),
            'currency_pos'              => wpinv_currency_position(),
            'thousand_sep'              => wpinv_thousands_separator(),
            'decimal_sep'               => wpinv_decimal_separator(),
            'decimals'                  => wpinv_decimals(),
            'save_invoice'              => __( 'Save Invoice', 'invoicing' ),
            'status_publish'            => wpinv_status_nicename( 'publish' ),
            'status_pending'            => wpinv_status_nicename( 'wpi-pending' ),
            'delete_tax_rate'           => __( 'Are you sure you wish to delete this tax rate?', 'invoicing' ),
            'status_pending'            => wpinv_status_nicename( 'wpi-pending' ),
            'FillBillingDetails'        => __( 'Fill the user\'s billing information? This will remove any currently entered billing information', 'invoicing' ),
            'confirmCalcTotals'         => __( 'Recalculate totals? This will recalculate totals based on the user billing country. If no billing country is set it will use the base country.', 'invoicing' ),
            'AreYouSure'                => __( 'Are you sure?', 'invoicing' ),
            'errDeleteItem'             => __( 'This item is in use! Before delete this item, you need to delete all the invoice(s) using this item.', 'invoicing' ),
            'delete_subscription'       => __( 'Are you sure you want to delete this subscription?', 'invoicing' ),
            'action_edit'               => __( 'Edit', 'invoicing' ),
            'action_cancel'             => __( 'Cancel', 'invoicing' ),
            'item_description'          => __( 'Item Description', 'invoicing' ),
            'invoice_description'       => __( 'Invoice Description', 'invoicing' ),
            'discount_description'      => __( 'Discount Description', 'invoicing' ),
            'searching'                 => __( 'Searching', 'invoicing' ),
        );

		if ( ! empty( $post ) && getpaid_is_invoice_post_type( $post->post_type ) ) {

			$invoice              = new WPInv_Invoice( $post );
			$i18n['save_invoice'] = sprintf(
				__( 'Save %s', 'invoicing' ),
				ucfirst( $invoice->get_type() )
			);

			$i18n['invoice_description'] = sprintf(
				__( '%s Description', 'invoicing' ),
				ucfirst( $invoice->get_type() )
			);

		}
		return $i18n;
    }

    /**
	 * Loads payment form js.
	 *
	 */
	protected function load_payment_form_scripts() {
        global $post;

        wp_enqueue_script( 'vue', WPINV_PLUGIN_URL . 'assets/js/vue/vue.js', array(), WPINV_VERSION );
		wp_enqueue_script( 'sortable', WPINV_PLUGIN_URL . 'assets/js/sortable.min.js', array(), WPINV_VERSION );
		wp_enqueue_script( 'vue_draggable', WPINV_PLUGIN_URL . 'assets/js/vue/vuedraggable.min.js', array( 'sortable', 'vue' ), WPINV_VERSION );

		$version = filemtime( WPINV_PLUGIN_DIR . 'assets/js/admin-payment-forms.js' );
		wp_register_script( 'wpinv-admin-payment-form-script', WPINV_PLUGIN_URL . 'assets/js/admin-payment-forms.js', array( 'wpinv-admin-script', 'vue_draggable' ),  $version );

		wp_localize_script(
            'wpinv-admin-payment-form-script',
            'wpinvPaymentFormAdmin',
            array(
				'elements'      => wpinv_get_data( 'payment-form-elements' ),
				'form_elements' => getpaid_get_payment_form_elements( $post->ID ),
				'currency'      => wpinv_currency_symbol(),
				'position'      => wpinv_currency_position(),
				'decimals'      => (int) wpinv_decimals(),
				'thousands_sep' => wpinv_thousands_separator(),
				'decimals_sep'  => wpinv_decimal_separator(),
				'form_items'    => gepaid_get_form_items( $post->ID ),
				'is_default'    => $post->ID == wpinv_get_default_payment_form(),
            )
        );

        wp_enqueue_script( 'wpinv-admin-payment-form-script' );

    }

    /**
	 * Add our classes to admin pages.
     *
     * @param string $classes
     * @return string
	 *
	 */
    public function admin_body_class( $classes ) {
		global $pagenow, $post, $current_screen;


        $page = isset( $_GET['page'] ) ? $_GET['page'] : '';

        if ( ! empty( $current_screen->post_type ) ) {
			$page = $current_screen->post_type;
        }

        if ( false !== stripos( $page, 'wpi' ) ) {
            $classes .= ' wpi-' . sanitize_key( $page );
        }

        if ( in_array( $page, wpinv_parse_list( 'wpi_invoice wpi_payment_form wpi_quote' ) ) ) {
            $classes .= ' wpinv-cpt wpinv';
		}
		
		if ( getpaid_is_invoice_post_type( $page ) ) {
            $classes .= ' getpaid-is-invoice-cpt';
        }

		return $classes;
    }

    /**
	 * Maybe show the AyeCode Connect Notice.
	 */
	public function init_ayecode_connect_helper(){

        new AyeCode_Connect_Helper(
            array(
				'connect_title' => __("WP Invoicing - an AyeCode product!","invoicing"),
				'connect_external'  => __( "Please confirm you wish to connect your site?","invoicing" ),
				'connect'           => sprintf( __( "<strong>Have a license?</strong> Forget about entering license keys or downloading zip files, connect your site for instant access. %slearn more%s","invoicing" ),"<a href='https://ayecode.io/introducing-ayecode-connect/' target='_blank'>","</a>" ),
				'connect_button'    => __("Connect Site","invoicing"),
				'connecting_button'    => __("Connecting...","invoicing"),
				'error_localhost'   => __( "This service will only work with a live domain, not a localhost.","invoicing" ),
				'error'             => __( "Something went wrong, please refresh and try again.","invoicing" ),
            ),
            array( 'wpi-addons' )
        );

    }

    /**
     * Maybe redirect users to our admin settings page.
     */
    public function activation_redirect() {

		// Bail if no activation redirect.
		if ( ! get_transient( '_wpinv_activation_redirect' ) || wp_doing_ajax() ) {
			return;
		}

		// Delete the redirect transient.
		delete_transient( '_wpinv_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpinv-settings&tab=general' ) );
		exit;
    }

    /**
     * Fires an admin action after verifying that a user can fire them.
     */
    public function maybe_do_admin_action() {

        if ( wpinv_current_user_can_manage_invoicing() && isset( $_REQUEST['getpaid-admin-action'] ) && isset( $_REQUEST['getpaid-nonce'] ) && wp_verify_nonce( $_REQUEST['getpaid-nonce'], 'getpaid-nonce' ) ) {
            $key = sanitize_key( $_REQUEST['getpaid-admin-action'] );
            do_action( "getpaid_authenticated_admin_action_$key", $_REQUEST );
        }

    }

	/**
     * Sends a payment reminder to a customer.
	 * 
	 * @param array $args
     */
    public function send_customer_invoice( $args ) {
		$sent = getpaid()->get( 'invoice_emails' )->user_invoice( new WPInv_Invoice( $args['invoice_id'] ) );

		if ( $sent ) {
			$this->show_success( __( 'Invoice was successfully sent to the customer', 'invoicing' ) );
		} else {
			$this->show_error( __( 'Could not sent the invoice to the customer', 'invoicing' ) );
		}

		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce', 'invoice_id' ) ) );
		exit;
	}

	/**
     * Sends a payment reminder to a customer.
	 * 
	 * @param array $args
     */
    public function send_customer_payment_reminder( $args ) {
		$sent = getpaid()->get( 'invoice_emails' )->force_send_overdue_notice( new WPInv_Invoice( $args['invoice_id'] ) );

		if ( $sent ) {
			$this->show_success( __( 'Payment reminder was successfully sent to the customer', 'invoicing' ) );
		} else {
			$this->show_error( __( 'Could not sent payment reminder to the customer', 'invoicing' ) );
		}

		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce', 'invoice_id' ) ) );
		exit;
	}

	/**
     * Resets tax rates.
	 * 
     */
    public function admin_reset_tax_rates() {

		update_option( 'wpinv_tax_rates', wpinv_get_data( 'tax-rates' ) );
		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce' ) ) );
		exit;

	}

    /**
	 * Returns an array of admin notices.
	 *
	 * @since       1.0.19
     * @return array
	 */
	public function get_notices() {
		$notices = get_option( 'wpinv_admin_notices' );
        return is_array( $notices ) ? $notices : array();
	}

	/**
	 * Clears all admin notices
	 *
	 * @access      public
	 * @since       1.0.19
	 */
	public function clear_notices() {
		delete_option( 'wpinv_admin_notices' );
	}

	/**
	 * Saves a new admin notice
	 *
	 * @access      public
	 * @since       1.0.19
	 */
	public function save_notice( $type, $message ) {
		$notices = $this->get_notices();

		if ( empty( $notices[ $type ] ) || ! is_array( $notices[ $type ]) ) {
			$notices[ $type ] = array();
		}

		$notices[ $type ][] = $message;

		update_option( 'wpinv_admin_notices', $notices );
	}

	/**
	 * Displays a success notice
	 *
	 * @param       string $msg The message to qeue.
	 * @access      public
	 * @since       1.0.19
	 */
	public function show_success( $msg ) {
		$this->save_notice( 'success', $msg );
	}

	/**
	 * Displays a error notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.0.19
	 */
	public function show_error( $msg ) {
		$this->save_notice( 'error', $msg );
	}

	/**
	 * Displays a warning notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.0.19
	 */
	public function show_warning( $msg ) {
		$this->save_notice( 'warning', $msg );
	}

	/**
	 * Displays a info notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.0.19
	 */
	public function show_info( $msg ) {
		$this->save_notice( 'info', $msg );
	}

	/**
	 * Show notices
	 *
	 * @access      public
	 * @since       1.0.19
	 */
	public function show_notices() {

        $notices = $this->get_notices();
        $this->clear_notices();

		foreach ( $notices as $type => $messages ) {

			if ( ! is_array( $messages ) ) {
				continue;
			}

            $type  = sanitize_key( $type );
			foreach ( $messages as $message ) {
                $message = wp_kses_post( $message );
				echo "<div class='notice notice-$type is-dismissible'><p>$message</p></div>";
            }

        }

	}

}
