<?php
/**
 * Contains the main installer class.
 *
 * @package GetPaid
 * @subpackage Admin
 * @version 2.0.2
 * @since   2.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main installer/updater class.
 *
 * @package GetPaid
 * @subpackage Admin
 * @version 2.0.2
 * @since   2.0.2
 */
class GetPaid_Installer {

	private static $schema = null;
	private static $schema_version = null;

	/**
	 * Upgrades the install.
	 *
	 * @param string $upgrade_from The current invoicing version.
	 */
	public function upgrade_db( $upgrade_from ) {

		// Save the current invoicing version.
		update_option( 'wpinv_version', WPINV_VERSION );

		// Setup the invoice Custom Post Type.
		GetPaid_Post_Types::register_post_types();

		// Clear the permalinks
		flush_rewrite_rules();

		// Maybe create new/missing pages.
		$this->create_pages();

		// Maybe re(add) admin capabilities.
		$this->add_capabilities();

		// Maybe create the default payment form.
		wpinv_get_default_payment_form();

		// Create any missing database tables.
		$method = "upgrade_from_$upgrade_from";

		$installed = get_option( 'gepaid_installed_on' );

		if ( empty( $installed ) ) {
			update_option( 'gepaid_installed_on', time() );
		}

		if ( method_exists( $this, $method ) ) {
			$this->$method();
		}

	}

	/**
	 * Do a fresh install.
	 *
	 */
	public function upgrade_from_0() {

		// Save default tax rates.
		update_option( 'wpinv_tax_rates', wpinv_get_data( 'tax-rates' ) );
	}

	/**
	 * Upgrade to 0.0.5
	 *
	 */
	public function upgrade_from_004() {
		global $wpdb;

		// Invoices.
		$results = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );
		if ( ! empty( $results ) ) {
			$wpdb->query( "UPDATE {$wpdb->posts} SET post_status = CONCAT( 'wpi-', post_status ) WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );

			// Clean post cache
			foreach ( $results as $row ) {
				clean_post_cache( $row->ID );
			}
		}

		// Item meta key changes
		$query = 'SELECT DISTINCT post_id FROM ' . $wpdb->postmeta . " WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id', '_wpinv_cpt_name', '_wpinv_cpt_singular_name' )";
		$results = $wpdb->get_results( $query );

		if ( ! empty( $results ) ) {
			$wpdb->query( 'UPDATE ' . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_id' WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id' )" );
			$wpdb->query( 'UPDATE ' . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_name' WHERE meta_key = '_wpinv_cpt_name'" );
			$wpdb->query( 'UPDATE ' . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_singular_name' WHERE meta_key = '_wpinv_cpt_singular_name'" );

			foreach ( $results as $row ) {
				clean_post_cache( $row->post_id );
			}
		}

		$this->upgrade_from_118();
	}

	/**
	 * Upgrade to version 2.0.0.
	 *
	 */
	public function upgrade_from_118() {
		$this->migrate_old_invoices();
		$this->upgrade_from_279();
	}

	/**
	 * Upgrade to version 2.0.0.
	 *
	 */
	public function upgrade_from_279() {
		self::migrate_old_customers();
	}

	/**
	 * Give administrators the capability to manage GetPaid.
	 *
	 */
	public function add_capabilities() {
		$GLOBALS['wp_roles']->add_cap( 'administrator', 'manage_invoicing' );
	}

	/**
	 * Retreives GetPaid pages.
	 *
	 */
	public static function get_pages() {

		return apply_filters(
			'wpinv_create_pages',
			array(

				// Checkout page.
				'checkout_page'             => array(
					'name'    => _x( 'gp-checkout', 'Page slug', 'invoicing' ),
					'title'   => _x( 'Checkout', 'Page title', 'invoicing' ),
					'content' => '
						<!-- wp:shortcode -->
						[wpinv_checkout]
						<!-- /wp:shortcode -->
					',
					'parent'  => '',
				),

				// Invoice history page.
				'invoice_history_page'      => array(
					'name'    => _x( 'gp-invoices', 'Page slug', 'invoicing' ),
					'title'   => _x( 'My Invoices', 'Page title', 'invoicing' ),
					'content' => '
					<!-- wp:shortcode -->
					[wpinv_history]
					<!-- /wp:shortcode -->
				',
					'parent'  => '',
				),

				// Success page content.
				'success_page'              => array(
					'name'    => _x( 'gp-receipt', 'Page slug', 'invoicing' ),
					'title'   => _x( 'Payment Confirmation', 'Page title', 'invoicing' ),
					'content' => '
					<!-- wp:shortcode -->
					[wpinv_receipt]
					<!-- /wp:shortcode -->
				',
					'parent'  => 'gp-checkout',
				),

				// Failure page content.
				'failure_page'              => array(
					'name'    => _x( 'gp-transaction-failed', 'Page slug', 'invoicing' ),
					'title'   => _x( 'Transaction Failed', 'Page title', 'invoicing' ),
					'content' => __( 'Your transaction failed, please try again or contact site support.', 'invoicing' ),
					'parent'  => 'gp-checkout',
				),

				// Subscriptions history page.
				'invoice_subscription_page' => array(
					'name'    => _x( 'gp-subscriptions', 'Page slug', 'invoicing' ),
					'title'   => _x( 'My Subscriptions', 'Page title', 'invoicing' ),
					'content' => '
					<!-- wp:shortcode -->
					[wpinv_subscriptions]
					<!-- /wp:shortcode -->
				',
					'parent'  => '',
				),

			)
		);

	}

	/**
	 * Re-create GetPaid pages.
	 *
	 */
	public function create_pages() {

		foreach ( self::get_pages() as $key => $page ) {
			wpinv_create_page( esc_sql( $page['name'] ), $key, $page['title'], $page['content'], $page['parent'] );
		}

	}

	/**
	 * Migrates old invoices to new invoices.
	 *
	 */
	public function migrate_old_invoices() {
		global $wpdb;

		$invoices_table      = $wpdb->prefix . 'getpaid_invoices';
		$invoice_items_table = $wpdb->prefix . 'getpaid_invoice_items';
		$migrated            = $wpdb->get_col( "SELECT post_id FROM $invoices_table" );
		$invoices            = array_unique(
			get_posts(
				array(
					'post_type'      => array( 'wpi_invoice', 'wpi_quote' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post_status'    => array_keys( get_post_stati() ),
					'exclude'        => (array) $migrated,
				)
			)
		);

		// Abort if we do not have any invoices.
		if ( empty( $invoices ) ) {
			return;
		}

		require_once WPINV_PLUGIN_DIR . 'includes/class-wpinv-legacy-invoice.php';

		$invoice_rows = array();
		foreach ( $invoices as $invoice ) {

			$invoice = new WPInv_Legacy_Invoice( $invoice );

			if ( empty( $invoice->ID ) ) {
				return;
			}

			$fields = array(
				'post_id'            => $invoice->ID,
				'number'             => $invoice->get_number(),
				'key'                => $invoice->get_key(),
				'type'               => str_replace( 'wpi_', '', $invoice->post_type ),
				'mode'               => $invoice->mode,
				'user_ip'            => $invoice->get_ip(),
				'first_name'         => $invoice->get_first_name(),
				'last_name'          => $invoice->get_last_name(),
				'address'            => $invoice->get_address(),
				'city'               => $invoice->city,
				'state'              => $invoice->state,
				'country'            => $invoice->country,
				'zip'                => $invoice->zip,
				'adddress_confirmed' => (int) $invoice->adddress_confirmed,
				'gateway'            => $invoice->get_gateway(),
				'transaction_id'     => $invoice->get_transaction_id(),
				'currency'           => $invoice->get_currency(),
				'subtotal'           => $invoice->get_subtotal(),
				'tax'                => $invoice->get_tax(),
				'fees_total'         => $invoice->get_fees_total(),
				'total'              => $invoice->get_total(),
				'discount'           => $invoice->get_discount(),
				'discount_code'      => $invoice->get_discount_code(),
				'disable_taxes'      => $invoice->disable_taxes,
				'due_date'           => $invoice->get_due_date(),
				'completed_date'     => $invoice->get_completed_date(),
				'company'            => $invoice->company,
				'vat_number'         => $invoice->vat_number,
				'vat_rate'           => $invoice->vat_rate,
				'custom_meta'        => $invoice->payment_meta,
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

				$item_columns = array_keys( $fields );

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

		if ( empty( $invoice_rows ) ) {
			return;
		}

		$invoice_rows = implode( ', ', $invoice_rows );
		$wpdb->query( "INSERT INTO $invoices_table VALUES $invoice_rows" );

	}

	/**
	 * Migrates old customers to new table.
	 *
	 */
	public static function migrate_old_customers() {
		global $wpdb;

		// Fetch post_id from $wpdb->prefix . 'getpaid_invoices' where customer_id = 0 or null.
		$invoice_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->prefix}getpaid_invoices WHERE customer_id = 0 OR customer_id IS NULL" );

		foreach ( $invoice_ids as $invoice_id ) {
			$invoice = wpinv_get_invoice( $invoice_id );

			if ( empty( $invoice ) ) {
				continue;
			}

			// Fetch customer from the user ID.
			$user_id = $invoice->get_user_id();

			if ( empty( $user_id ) ) {
				continue;
			}

			$customer = getpaid_get_customer_by_user_id( $user_id );

			// Create if not exists.
			if ( empty( $customer ) ) {
				$customer = new GetPaid_Customer( 0 );
				$customer->clone_user( $user_id );
				$customer->save();
			}

			$invoice->set_customer_id( $customer->get_id() );
			$invoice->save();
		}

	}

	/**
	 * Migrates old invoices to new invoices.
	 *
	 */
	public static function rename_gateways_label() {
		global $wpdb;

		foreach ( array_keys( wpinv_get_payment_gateways() ) as $gateway ) {

			$wpdb->update(
				$wpdb->prefix . 'getpaid_invoices',
				array( 'gateway' => $gateway ),
				array( 'gateway' => wpinv_get_gateway_admin_label( $gateway ) ),
				'%s',
				'%s'
			);

		}
	}

	/**
	 * Returns the DB schema.
	 *
	 */
	public static function get_db_schema() {
		global $wpdb;

		if ( ! empty( self::$schema ) ) {
			return self::$schema;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$schema = array();

		// Subscriptions.
		$schema['subscriptions'] = "CREATE TABLE {$wpdb->prefix}wpinv_subscriptions (
			id bigint(20) unsigned NOT NULL auto_increment,
			customer_id bigint(20) NOT NULL,
			frequency int(11) NOT NULL DEFAULT '1',
			period varchar(20) NOT NULL,
			initial_amount DECIMAL(16,4) NOT NULL,
			recurring_amount DECIMAL(16,4) NOT NULL,
			bill_times bigint(20) NOT NULL,
			transaction_id varchar(60) NOT NULL,
			parent_payment_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
			created datetime NOT NULL,
			expiration datetime NOT NULL,
			trial_period varchar(20) NOT NULL,
			profile_id varchar(60) NOT NULL,
			status varchar(20) NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_id (profile_id),
			KEY customer (customer_id),
			KEY transaction (transaction_id),
			KEY customer_and_status (customer_id, status)
		  ) $charset_collate;";

		// Invoices.
		$schema['invoices'] = "CREATE TABLE {$wpdb->prefix}getpaid_invoices (
			post_id BIGINT(20) NOT NULL,
			customer_id BIGINT(20) NOT NULL DEFAULT 0,
            `number` VARCHAR(100),
            invoice_key VARCHAR(100),
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
            subtotal DECIMAL(16,4) NOT NULL DEFAULT 0,
            tax DECIMAL(16,4) NOT NULL DEFAULT 0,
            fees_total DECIMAL(16,4) NOT NULL DEFAULT 0,
            total DECIMAL(16,4) NOT NULL DEFAULT 0,
            discount DECIMAL(16,4) NOT NULL DEFAULT 0,
            discount_code VARCHAR(100),
            disable_taxes INT(2) NOT NULL DEFAULT 0,
            due_date DATETIME,
            completed_date DATETIME,
            company VARCHAR(100),
            vat_number VARCHAR(100),
            vat_rate VARCHAR(100),
            custom_meta TEXT,
			PRIMARY KEY  (post_id),
			KEY `number` (`number`),
			KEY invoice_key (invoice_key)
		  ) $charset_collate;";

		// Invoice items.
		$schema['items'] = "CREATE TABLE {$wpdb->prefix}getpaid_invoice_items (
			ID BIGINT(20) NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) NOT NULL,
            item_id BIGINT(20) NOT NULL,
            item_name TEXT NOT NULL,
            item_description TEXT NOT NULL,
            vat_rate FLOAT NOT NULL DEFAULT 0,
            vat_class VARCHAR(100),
            tax DECIMAL(16,4) NOT NULL DEFAULT 0,
            item_price DECIMAL(16,4) NOT NULL DEFAULT 0,
            custom_price DECIMAL(16,4) NOT NULL DEFAULT 0,
            quantity DECIMAL(16,4) NOT NULL DEFAULT 1,
            discount DECIMAL(16,4) NOT NULL DEFAULT 0,
            subtotal DECIMAL(16,4) NOT NULL DEFAULT 0,
            price DECIMAL(16,4) NOT NULL DEFAULT 0,
            meta TEXT,
            fees TEXT,
			PRIMARY KEY  (ID),
			KEY item_id (item_id),
			KEY post_id (post_id)
		  ) $charset_collate;";

		// Customers.
		$schema['customers'] = "CREATE TABLE {$wpdb->prefix}getpaid_customers (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			email VARCHAR(100) NOT NULL,
			email_cc TEXT,
			status VARCHAR(100) NOT NULL DEFAULT 'active',
			purchase_value DECIMAL(18,9) NOT NULL DEFAULT 0,
			purchase_count BIGINT(20) NOT NULL DEFAULT 0,
			";

		// Add address fields.
		foreach ( array_keys( getpaid_user_address_fields( true ) ) as $field ) {
			// Skip id, user_id and email.
			if ( in_array( $field, array( 'id', 'user_id', 'email', 'purchase_value', 'purchase_count', 'date_created', 'date_modified', 'uuid' ), true ) ) {
				continue;
			}

			$field   = sanitize_key( $field );
			$length  = 100;
			$default = '';

			// Country.
			if ( 'country' === $field ) {
				$length  = 2;
				$default = wpinv_get_default_country();
			}

			// State.
			if ( 'state' === $field ) {
				$default = wpinv_get_default_state();
			}

			// Phone, zip.
			if ( in_array( $field, array( 'phone', 'zip' ), true ) ) {
				$length = 20;
			}

			$schema['customers'] .= "`$field` VARCHAR($length) NOT NULL DEFAULT '$default',
			";
		}

		$schema['customers'] .= "date_created DATETIME NOT NULL,
			date_modified DATETIME NOT NULL,
			uuid VARCHAR(100) NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY email (email)
		  ) $charset_collate;";

		// Customer meta.
		$schema['customer_meta'] = "CREATE TABLE {$wpdb->prefix}getpaid_customer_meta (
			meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
			customer_id BIGINT(20) NOT NULL,
			meta_key VARCHAR(255) NOT NULL,
			meta_value LONGTEXT,
			PRIMARY KEY  (meta_id),
			KEY customer_id (customer_id),
			KEY meta_key (meta_key(191))
		  ) $charset_collate;";

		// Filter.
		$schema = apply_filters( 'getpaid_db_schema', $schema );

		self::$schema         = implode( "\n", array_values( $schema ) );
		self::$schema_version = md5( sanitize_key( self::$schema ) );

		return self::$schema;
	}

	/**
	 * Returns the DB schema version.
	 *
	 */
	public static function get_db_schema_version() {
		if ( ! empty( self::$schema_version ) ) {
			return self::$schema_version;
		}

		self::get_db_schema();

		return self::$schema_version;
	}

	/**
	 * Checks if the db schema is up to date.
	 *
	 * @return bool
	 */
	public static function is_db_schema_up_to_date() {
		return self::get_db_schema_version() === get_option( 'getpaid_db_schema' );
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	public static function create_db_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schema = self::get_db_schema();

		// If invoices table exists, rename key to invoice_key.
		$invoices_table = "{$wpdb->prefix}getpaid_invoices";

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}getpaid_invoices'" ) === $invoices_table ) {
			$fields = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}getpaid_invoices" );

			foreach ( $fields as $field ) {
				if ( 'key' === $field->Field ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}getpaid_invoices CHANGE `key` `invoice_key` VARCHAR(100)" );
					break;
				}
			}
		}

		dbDelta( $schema );
		wp_cache_flush();
		update_option( 'getpaid_db_schema', self::get_db_schema_version() );
	}

	/**
	 * Creates tables if schema is not up to date.
	 */
	public static function maybe_create_db_tables() {
		if ( ! self::is_db_schema_up_to_date() ) {
			self::create_db_tables();
		}
	}
}
