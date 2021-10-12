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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqeue_scripts' ), 9 );
        add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
        add_action( 'admin_init', array( $this, 'init_ayecode_connect_helper' ) );
        add_action( 'admin_init', array( $this, 'activation_redirect') );
        add_action( 'admin_init', array( $this, 'maybe_do_admin_action') );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_action( 'getpaid_authenticated_admin_action_rate_plugin', array( $this, 'redirect_to_wordpress_rating_page' ) );
		add_action( 'getpaid_authenticated_admin_action_duplicate_form', array( $this, 'duplicate_payment_form' ) );
		add_action( 'getpaid_authenticated_admin_action_send_invoice', array( $this, 'send_customer_invoice' ) );
		add_action( 'getpaid_authenticated_admin_action_send_invoice_reminder', array( $this, 'send_customer_payment_reminder' ) );
        add_action( 'getpaid_authenticated_admin_action_reset_tax_rates', array( $this, 'admin_reset_tax_rates' ) );
		add_action( 'getpaid_authenticated_admin_action_create_missing_pages', array( $this, 'admin_create_missing_pages' ) );
		add_action( 'getpaid_authenticated_admin_action_create_missing_tables', array( $this, 'admin_create_missing_tables' ) );
		add_action( 'getpaid_authenticated_admin_action_migrate_old_invoices', array( $this, 'admin_migrate_old_invoices' ) );
		add_action( 'getpaid_authenticated_admin_action_download_customers', array( $this, 'admin_download_customers' ) );
		add_action( 'getpaid_authenticated_admin_action_recalculate_discounts', array( $this, 'admin_recalculate_discounts' ) );
		add_action( 'getpaid_authenticated_admin_action_install_plugin', array( $this, 'admin_install_plugin' ) );
		add_action( 'getpaid_authenticated_admin_action_connect_gateway', array( $this, 'admin_connect_gateway' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ) );
		do_action( 'getpaid_init_admin_hooks', $this );

		// Setup/welcome
		if ( ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case 'gp-setup' :
					include_once( dirname( __FILE__ ) . '/class-getpaid-admin-setup-wizard.php' );
					break;
			}
		}

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
        if ( false !== stripos( $page, 'wpi' ) || false !== stripos( $page, 'getpaid' ) || 'gp-setup' == $page ) {

            // Styles.
            $version = filemtime( WPINV_PLUGIN_DIR . 'assets/css/admin.css' );
            wp_enqueue_style( 'wpinv_admin_style', WPINV_PLUGIN_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), $version );
            wp_enqueue_style( 'select2', WPINV_PLUGIN_URL . 'assets/css/select2/select2.min.css', array(), '4.0.13', 'all' );

            // Scripts.
            wp_enqueue_script('select2', WPINV_PLUGIN_URL . 'assets/js/select2/select2.full.min.js', array( 'jquery' ), WPINV_VERSION );

            $version = filemtime( WPINV_PLUGIN_DIR . 'assets/js/admin.js' );
            wp_enqueue_script( 'wpinv-admin-script', WPINV_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-tooltip' ),  $version );
            wp_localize_script( 'wpinv-admin-script', 'WPInv_Admin', apply_filters( 'wpinv_admin_js_localize', $this->get_admin_i18() ) );

        }

        // Payment form scripts.
		if ( 'wpi_payment_form' == $page && $editing ) {
            $this->load_payment_form_scripts();
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

		$date_range = array(
			'period' => isset( $_GET['date_range'] ) ? sanitize_text_field( $_GET['date_range'] ) : '7_days'
		);

		if ( $date_range['period'] == 'custom' ) {
			
			if ( isset( $_GET['from'] ) ) {
				$date_range[ 'after' ] = date( 'Y-m-d', strtotime( sanitize_text_field( $_GET['from'] ), current_time( 'timestamp' ) ) - DAY_IN_SECONDS );
			}

			if ( isset( $_GET['to'] ) ) {
				$date_range[ 'before' ] = date( 'Y-m-d', strtotime( sanitize_text_field( $_GET['to'] ), current_time( 'timestamp' ) ) + DAY_IN_SECONDS );
			}

		}

        $i18n = array(
            'ajax_url'                  => admin_url( 'admin-ajax.php' ),
            'post_ID'                   => isset( $post->ID ) ? $post->ID : '',
			'wpinv_nonce'               => wp_create_nonce( 'wpinv-nonce' ),
			'rest_nonce'                => wp_create_nonce( 'wp_rest' ),
			'rest_root'                 => esc_url_raw( rest_url() ),
			'date_range'                => $date_range,
            'add_invoice_note_nonce'    => wp_create_nonce( 'add-invoice-note' ),
            'delete_invoice_note_nonce' => wp_create_nonce( 'delete-invoice-note' ),
            'invoice_item_nonce'        => wp_create_nonce( 'invoice-item' ),
            'billing_details_nonce'     => wp_create_nonce( 'get-billing-details' ),
            'tax'                       => wpinv_tax_amount(),
            'discount'                  => 0,
			'currency_symbol'           => wpinv_currency_symbol(),
			'currency'                  => wpinv_get_currency(),
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
			'loading'                   => __( 'Loading...', 'invoicing' ),
			'search_customers'          => __( 'Enter customer name or email', 'invoicing' ),
			'search_items'              => __( 'Enter item name', 'invoicing' ),
        );

		if ( ! empty( $post ) && getpaid_is_invoice_post_type( $post->post_type ) ) {

			$invoice              = new WPInv_Invoice( $post );
			$i18n['save_invoice'] = sprintf(
				__( 'Save %s', 'invoicing' ),
				ucfirst( $invoice->get_invoice_quote_type() )
			);

			$i18n['invoice_description'] = sprintf(
				__( '%s Description', 'invoicing' ),
				ucfirst( $invoice->get_invoice_quote_type() )
			);

		}
		return $i18n;
	}

	/**
	 * Change the admin footer text on GetPaid admin pages.
	 *
	 * @since  2.0.0
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		global $current_screen;

		$page    = isset( $_GET['page'] ) ? $_GET['page'] : '';

        if ( ! empty( $current_screen->post_type ) ) {
			$page = $current_screen->post_type;
        }

        // General styles.
        if ( apply_filters( 'getpaid_display_admin_footer_text', wpinv_current_user_can_manage_invoicing() ) && false !== stripos( $page, 'wpi' ) ) {

			// Change the footer text
			if ( ! get_user_meta( get_current_user_id(), 'getpaid_admin_footer_text_rated', true ) ) {

				$rating_url  = esc_url(
					wp_nonce_url(
						admin_url( 'admin.php?page=wpinv-reports&getpaid-admin-action=rate_plugin' ),
						'getpaid-nonce',
						'getpaid-nonce'
						)
				);

				$footer_text = sprintf(
					/* translators: %s: five stars */
					__( 'If you like <strong>GetPaid</strong>, please leave us a %s rating. A huge thanks in advance!', 'invoicing' ),
					"<a href='$rating_url'>&#9733;&#9733;&#9733;&#9733;&#9733;</a>"
				);

			} else {

				$footer_text = sprintf(
					/* translators: %s: GetPaid */
					__( 'Thank you for using %s!', 'invoicing' ),
					"<a href='https://wpgetpaid.com/' target='_blank'><strong>GetPaid</strong></a>"
				);

			}

		}

		return $footer_text;
	}

	/**
	 * Redirects to wp.org to rate the plugin.
	 *
	 * @since  2.0.0
	 */
	public function redirect_to_wordpress_rating_page() {
		update_user_meta( get_current_user_id(), 'getpaid_admin_footer_text_rated', 1 );
		wp_redirect( 'https://wordpress.org/support/plugin/invoicing/reviews?rate=5#new-post' );
		exit;
	}

    /**
	 * Loads payment form js.
	 *
	 */
	protected function load_payment_form_scripts() {
        global $post;

        wp_enqueue_script( 'vue', WPINV_PLUGIN_URL . 'assets/js/vue/vue.min.js', array(), WPINV_VERSION );
		wp_enqueue_script( 'sortable', WPINV_PLUGIN_URL . 'assets/js/sortable.min.js', array(), WPINV_VERSION );
		wp_enqueue_script( 'vue_draggable', WPINV_PLUGIN_URL . 'assets/js/vue/vuedraggable.min.js', array( 'sortable', 'vue' ), WPINV_VERSION );

		$version = filemtime( WPINV_PLUGIN_DIR . 'assets/js/admin-payment-forms.js' );
		wp_register_script( 'wpinv-admin-payment-form-script', WPINV_PLUGIN_URL . 'assets/js/admin-payment-forms.js', array( 'wpinv-admin-script', 'vue_draggable', 'wp-hooks' ),  $version );

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

		// Register with the deactivation survey class.
		AyeCode_Deactivation_Survey::instance(array(
			'slug'		        => 'invoicing',
			'version'	        => WPINV_VERSION,
			'support_url'       => 'https://wpgetpaid.com/support/',
			'documentation_url' => 'https://docs.wpgetpaid.com/',
			'activated'         => (int) get_option( 'gepaid_installed_on' ),
		));

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
	 * Redirect users to settings on activation.
	 *
	 * @return void
	 */
	public function activation_redirect() {

		$redirected = get_option( 'wpinv_redirected_to_settings' );

		if ( ! empty( $redirected ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

	    update_option( 'wpinv_redirected_to_settings', 1 );

        wp_safe_redirect( admin_url( 'index.php?page=gp-setup' ) );
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
    public function duplicate_payment_form( $args ) {

		if ( empty( $args['form_id'] ) ) {
			return;
		}

		$form = new GetPaid_Payment_Form( $args['form_id'] );

		if ( ! $form->exists() ) {
			return;
		}

		$new_form = new GetPaid_Payment_Form();
		$new_form->set_author( $form->get_author( 'edit' ) );
		$new_form->set_name( $form->get_name( 'edit' ) . __( '(copy)', 'invoicing' ) );
		$new_form->set_elements( $form->get_elements( 'edit' ) );
		$new_form->set_items( $form->get_items( 'edit' ) );
		$new_form->save();

		if ( $new_form->exists() ) {
			$this->show_success( __( 'Form duplicated successfully', 'invoicing' ) );
			$url = get_edit_post_link( $new_form->get_id(), 'edit' );
		} else {
			$this->show_error( __( 'Unable to duplicate form', 'invoicing' ) );
			$url = remove_query_arg( array( 'getpaid-admin-action', 'form_id', 'getpaid-nonce' ) );
		}

		wp_redirect( $url );
		exit;
	}

	/**
     * Sends a payment reminder to a customer.
	 * 
	 * @param array $args
     */
    public function send_customer_invoice( $args ) {
		$sent = getpaid()->get( 'invoice_emails' )->user_invoice( new WPInv_Invoice( $args['invoice_id'] ), true );

		if ( $sent ) {
			$this->show_success( __( 'Invoice was successfully sent to the customer', 'invoicing' ) );
		} else {
			$this->show_error( __( 'Could not send the invoice to the customer', 'invoicing' ) );
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
     * Resets admin pages.
	 * 
     */
    public function admin_create_missing_pages() {
		$installer = new GetPaid_Installer();
		$installer->create_pages();
		$this->show_success( __( 'GetPaid pages updated.', 'invoicing' ) );
		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce' ) ) );
		exit;
	}

	/**
     * Creates an missing admin tables.
	 * 
     */
    public function admin_create_missing_tables() {
		global $wpdb;
		$installer = new GetPaid_Installer();

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpinv_subscriptions'" ) != $wpdb->prefix . 'wpinv_subscriptions' ) {
			$installer->create_subscriptions_table();

			if ( $wpdb->last_error !== '' ) {
				$this->show_error( __( 'Your GetPaid tables have been updated:', 'invoicing' ) . ' ' . $wpdb->last_error );
			}
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}getpaid_invoices'" ) != $wpdb->prefix . 'getpaid_invoices' ) {
			$installer->create_invoices_table();

			if ( $wpdb->last_error !== '' ) {
				$this->show_error( __( 'Your GetPaid tables have been updated:', 'invoicing' ) . ' ' . $wpdb->last_error );
			}
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}getpaid_invoice_items'" ) != $wpdb->prefix . 'getpaid_invoice_items' ) {
			$installer->create_invoice_items_table();

			if ( $wpdb->last_error !== '' ) {
				$this->show_error( __( 'Your GetPaid tables have been updated:', 'invoicing' ) . ' ' . $wpdb->last_error );
			}
		}

		if ( ! $this->has_notices() ) {
			$this->show_success( __( 'Your GetPaid tables have been updated.', 'invoicing' ) );
		}

		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce' ) ) );
		exit;
	}

	/**
     * Migrates old invoices to the new database tables.
	 * 
     */
    public function admin_migrate_old_invoices() {

		// Migrate the invoices.
		$installer = new GetPaid_Installer();
		$installer->migrate_old_invoices();

		// Show an admin message.
		$this->show_success( __( 'Your invoices have been migrated.', 'invoicing' ) );

		// Redirect the admin.
		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce' ) ) );
		exit;

	}

	/**
     * Download customers.
	 * 
     */
    public function admin_download_customers() {
		global $wpdb;

		$output = fopen( 'php://output', 'w' ) or die( __( 'Unsupported server', 'invoicing' ) );

		header( "Content-Type:text/csv" );
		header( "Content-Disposition:attachment;filename=customers.csv" );

		$post_types = '';

		foreach ( array_keys( getpaid_get_invoice_post_types() ) as $post_type ) {
			$post_types .= $wpdb->prepare( "post_type=%s OR ", $post_type );
		}

		$post_types = rtrim( $post_types, ' OR' );

		$customers = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT( post_author ) FROM $wpdb->posts WHERE $post_types"
			)
		);

		$columns = array(
			'name'     => __( 'Name', 'invoicing' ),
			'email'    => __( 'Email', 'invoicing' ),
			'country'  => __( 'Country', 'invoicing' ),
			'state'    => __( 'State', 'invoicing' ),
			'city'     => __( 'City', 'invoicing' ),
			'zip'      => __( 'ZIP', 'invoicing' ),
			'address'  => __( 'Address', 'invoicing' ),
			'phone'    => __( 'Phone', 'invoicing' ),
			'company'  => __( 'Company', 'invoicing' ),
			'company_id'  => __( 'Company ID', 'invoicing' ),
			'invoices' => __( 'Invoices', 'invoicing' ),
			'total_raw' => __( 'Total Spend', 'invoicing' ),
			'signup'   => __( 'Date created', 'invoicing' ),
		);

		// Output the csv column headers.
		fputcsv( $output, array_values( $columns ) );

		// Loop through
		$table = new WPInv_Customers_Table();
		foreach ( $customers as $customer_id ) {

			$user = get_user_by( 'id', $customer_id );
			$row  = array();
			if ( empty( $user ) ) {
				continue;
			}

			foreach ( array_keys( $columns ) as $column ) {

				$method = 'column_' . $column;

				if ( 'name' == $column ) {
					$value = esc_html( $user->display_name );
				} else if( 'email' == $column ) {
					$value = sanitize_email( $user->user_email );
				} else if ( is_callable( array( $table, $method ) ) ) {
					$value = strip_tags( $table->$method( $user ) );
				}

				if ( empty( $value ) ) {
					$value = esc_html( get_user_meta( $user->ID, '_wpinv_' . $column, true ) );
				}

				$row[] = $value;

			}

			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;

	}

	/**
     * Installs a plugin.
	 *
	 * @param array $data
     */
    public function admin_install_plugin( $data ) {

		if ( ! empty( $data['plugins'] ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			wp_cache_flush();

			foreach ( $data['plugins'] as $slug => $file ) {
				$plugin_zip = esc_url( 'https://downloads.wordpress.org/plugin/' . $slug . '.latest-stable.zip' );
				$upgrader   = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
				$installed  = $upgrader->install( $plugin_zip );

				if ( ! is_wp_error( $installed ) && $installed ) {
					activate_plugin( $file, '', false, true );
				} else {
					wpinv_error_log( $upgrader->skin->get_upgrade_messages(), false );
				}

			}

		}

		$redirect = isset( $data['redirect'] ) ? esc_url_raw( $data['redirect'] ) : admin_url( 'plugins.php' );
		wp_safe_redirect( $redirect );
		exit;

	}

	/**
     * Connects a gateway.
	 *
	 * @param array $data
     */
    public function admin_connect_gateway( $data ) {

		if ( ! empty( $data['plugin'] ) ) {

			$gateway     = sanitize_key( $data['plugin'] );
			$connect_url = apply_filters( "getpaid_get_{$gateway}_connect_url", false, $data );

			if ( ! empty( $connect_url ) ) {
				wp_redirect( $connect_url );
				exit;
			}

			if ( 'stripe' == $data['plugin'] ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				wp_cache_flush();

				if ( ! array_key_exists( 'getpaid-stripe-payments/getpaid-stripe-payments.php', get_plugins() ) ) {
					$plugin_zip = esc_url( 'https://downloads.wordpress.org/plugin/getpaid-stripe-payments.latest-stable.zip' );
					$upgrader   = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
					$upgrader->install( $plugin_zip );
				}

				activate_plugin( 'getpaid-stripe-payments/getpaid-stripe-payments.php', '', false, true );
			}

			$connect_url = apply_filters( "getpaid_get_{$gateway}_connect_url", false, $data );
			if ( ! empty( $connect_url ) ) {
				wp_redirect( $connect_url );
				exit;
			}

		}

		$redirect = isset( $data['redirect'] ) ? esc_url_raw( urldecode( $data['redirect'] ) ) : admin_url( 'admin.php?page=wpinv-settings&tab=gateways' );
		wp_safe_redirect( $redirect );
		exit;

	}

	/**
     * Recalculates discounts.
	 * 
     */
    public function admin_recalculate_discounts() {
		global $wpdb;

		// Fetch all invoices that have discount codes.
		$table    = $wpdb->prefix . 'getpaid_invoices';
		$invoices = $wpdb->get_col( "SELECT `post_id` FROM `$table` WHERE `discount` = 0 && `discount_code` <> ''" );

		foreach ( $invoices as $invoice ) {

			$invoice = new WPInv_Invoice( $invoice );

			if ( ! $invoice->exists() ) {
				continue;
			}

			// Abort if the discount does not exist or does not apply here.
			$discount = new WPInv_Discount( $invoice->get_discount_code() );
			if ( ! $discount->exists() ) {
				continue;
			}

			$invoice->add_discount( getpaid_calculate_invoice_discount( $invoice, $discount ) );
			$invoice->recalculate_total();

			if ( $invoice->get_total_discount() > 0 ) {
				$invoice->save();
			}

		}

		// Show an admin message.
		$this->show_success( __( 'Discounts have been recalculated.', 'invoicing' ) );

		// Redirect the admin.
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
	 * Checks if we have any admin notices.
	 *
	 * @since       2.0.4
     * @return array
	 */
	public function has_notices() {
		return count( $this->get_notices() ) > 0;
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

		foreach ( array( 'checkout_page', 'invoice_history_page', 'success_page', 'failure_page', 'invoice_subscription_page' ) as $page ) {

			if ( ! is_numeric( wpinv_get_option( $page, false ) ) ) {
				$url     = wp_nonce_url(
					add_query_arg( 'getpaid-admin-action', 'create_missing_pages' ),
					'getpaid-nonce',
					'getpaid-nonce'
				);
				$message  = __( 'Some GetPaid pages are missing. To use GetPaid without any issues, click the button below to generate the missing pages.', 'invoicing' );
				$message2 = __( 'Generate Pages', 'invoicing' );
				echo "<div class='notice notice-warning is-dismissible'><p>$message<br><br><a href='$url' class='button button-primary'>$message2</a></p></div>";
				break;
			}

		}

	}

}
