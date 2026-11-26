<?php
/**
 * Metaboxes Admin.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Metaboxes Admin Class
 *
 */
class GetPaid_Metaboxes {

	/**
	 * Only save metaboxes once.
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

    /**
	 * Hook in methods.
	 */
	public static function init() {
		// Register metaboxes.
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::add_meta_boxes', 50, 2 );

		// Remove metaboxes.
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::remove_meta_boxes', 30 );

		// Rename metaboxes.
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::rename_meta_boxes', 45 );

		// Save metaboxes.
		add_action( 'save_post', 'GetPaid_Metaboxes::save_meta_boxes', 1, 2 );

		// The edit screen's own status field must not overwrite an invoice's status.
		add_filter( 'wp_insert_post_data', 'GetPaid_Metaboxes::keep_invoice_status', 10, 2 );
	}

	/**
	 * Keeps an invoice's stored status when it is saved from the edit screen.
	 *
	 * The edit form still posts WordPress' own status field. It has no option for an invoice status (Paid renewal,
	 * Refunded, Cancelled...), so the browser sends its first option, "pending", and WordPress wrote that over the
	 * invoice's status before the invoice details were saved. The save then read the invoice as "pending": every
	 * save of such an invoice ran a status change from "pending" to the status it already had, with its emails
	 * (e.g. the refunded invoice email again on each save of a refunded invoice) and hooks, and a real change was
	 * recorded as coming from "pending", so the refund and payment reversal hooks did not run.
	 *
	 * The status chosen in the invoice details box is applied by the meta box save, from the invoice's true status.
	 * Both use is_edit_screen_submission(), so the status is only kept for a request the meta box save will handle.
	 *
	 * A draft is left alone on purpose: WordPress still moves it to "pending" first, as it always has, so an invoice
	 * created in wp-admin is saved as an update and not as a new invoice. Keeping "draft" here would start firing
	 * getpaid_new_invoice for those invoices, and with it the new invoice email to the admin.
	 *
	 * @since 2.8.68
	 * @param array $data    The post data about to be written.
	 * @param array $postarr The post data as submitted.
	 * @return array
	 */
	public static function keep_invoice_status( $data, $postarr ) {

		// Only an existing invoice (or quote etc.)...
		$post_id = empty( $postarr['ID'] ) ? 0 : absint( $postarr['ID'] );

		if ( empty( $post_id ) || empty( $data['post_type'] ) || ! getpaid_is_invoice_post_type( $data['post_type'] ) ) {
			return $data;
		}

		// ... submitted through its own edit screen.
		if ( ! self::is_edit_screen_submission( $post_id ) ) {
			return $data;
		}

		$current = get_post_field( 'post_status', $post_id );

		// A new, draft or trashed post is WordPress' to move on. "pending" is not an invoice status (that is wpi-pending).
		if ( empty( $current ) || in_array( $current, array( 'auto-draft', 'draft', 'pending', 'trash', 'inherit', 'future', 'private' ), true ) ) {
			return $data;
		}

		$data['post_status'] = $current;

		return $data;
	}

	/**
	 * Whether the current request is the edit screen saving the given post: not an ajax request, autosave or bulk
	 * edit, the form's post, a valid meta box nonce and a user who may edit it.
	 *
	 * @since 2.8.68
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function is_edit_screen_submission( $post_id ) {

		// Not for ajax requests, autosaves or bulk edits.
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return false;
		}

		// Only for posts submitted through the edit screen.
		if ( empty( $post_id ) || empty( $_POST['post_ID'] ) || empty( $_POST['getpaid_meta_nonce'] ) ) {
			return false;
		}

		// Check the nonce.
		if ( ! is_scalar( $_POST['getpaid_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['getpaid_meta_nonce'] ) ), 'getpaid_meta_nonce' ) ) {
			return false;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( ! is_scalar( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== absint( $post_id ) ) {
			return false;
		}

		// Check user has permission to edit.
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Register core metaboxes.
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		// For invoices.
		self::add_invoice_meta_boxes( $post_type, $post );

		// For payment forms.
		self::add_payment_form_meta_boxes( $post_type, $post );

		// For invoice items.
		self::add_item_meta_boxes( $post_type );

		// For invoice discounts.
		if ( 'wpi_discount' === $post_type ) {
			add_meta_box( 'wpinv_discount_details', __( 'Discount Details', 'invoicing' ), 'GetPaid_Meta_Box_Discount_Details::output', 'wpi_discount', 'normal', 'high' );
		}
	}

	/**
	 * Register core metaboxes.
	 */
	protected static function add_payment_form_meta_boxes( $post_type, $post ) {
		// For payment forms.
		if ( 'wpi_payment_form' === $post_type ) {
			// Design payment form.
			add_meta_box( 'wpinv-payment-form-design', __( 'Payment Form', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form::output', 'wpi_payment_form', 'normal' );

			// Payment form information.
			if ( wpinv_get_default_payment_form() !== $post->ID ) {
				add_meta_box( 'wpinv-payment-form-info', __( 'Details', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form_Info::output', 'wpi_payment_form', 'side' );
			}
		}
	}

	/**
	 * Register core metaboxes.
	 */
	protected static function add_item_meta_boxes( $post_type ) {
		if ( 'wpi_item' === $post_type ) {
			// Item details.
			add_meta_box( 'wpinv_item_details', __( 'Item Details', 'invoicing' ), 'GetPaid_Meta_Box_Item_Details::output', 'wpi_item', 'normal', 'high' );

			// If taxes are enabled, register the tax metabox.
			if ( wpinv_use_taxes() ) {
				add_meta_box( 'wpinv_item_vat', __( 'Tax', 'invoicing' ), 'GetPaid_Meta_Box_Item_VAT::output', 'wpi_item', 'normal', 'high' );
			}

			// Item info.
			add_meta_box( 'wpinv_field_item_info', __( 'Item info', 'invoicing' ), 'GetPaid_Meta_Box_Item_Info::output', 'wpi_item', 'side', 'core' );

			// Item description.
			add_meta_box( 'postexcerpt', __( 'Item Description', 'invoicing' ), 'GetPaid_Meta_Box_Description::output', 'wpi_item', 'normal' );
		}
	}

	/**
	 * Register invoice metaboxes.
	 */
	protected static function add_invoice_meta_boxes( $post_type, $post ) {
		// For invoices...
		if ( getpaid_is_invoice_post_type( $post_type ) ) {
			$invoice = new WPInv_Invoice( $post );

			// Resend invoice.
			if ( ! $invoice->is_draft() ) {
				add_meta_box(
					'wpinv-mb-resend-invoice',
					sprintf(
						// translators: %s is the invoice type.
						__( 'Resend %s', 'invoicing' ),
						ucfirst( $invoice->get_invoice_quote_type() )
					),
					'GetPaid_Meta_Box_Resend_Invoice::output',
					$post_type,
					'side',
					'low'
				);
			}

			// Subscriptions.
			$subscriptions = getpaid_get_invoice_subscriptions( $invoice );

			if ( ! empty( $subscriptions ) ) {
				if ( is_array( $subscriptions ) ) {
					add_meta_box( 'wpinv-mb-subscriptions', __( 'Related Subscriptions', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Subscription::output_related', $post_type, 'advanced' );
				} else {
					add_meta_box( 'wpinv-mb-subscriptions', __( 'Subscription Details', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Subscription::output', $post_type, 'advanced' );
				}

				if ( getpaid_count_subscription_invoices( $invoice->is_renewal() ? $invoice->get_parent_id() : $invoice->get_id() ) > 1 ) {
					add_meta_box( 'wpinv-mb-subscription-invoices', __( 'Related Payments', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Subscription::output_invoices', $post_type, 'advanced' );
				}
			}

			// Invoice details.
			add_meta_box(
				'wpinv-details',
				sprintf(
					// translators: %s is the invoice type.
					__( '%s Details', 'invoicing' ),
					ucfirst( $invoice->get_invoice_quote_type() )
				),
				'GetPaid_Meta_Box_Invoice_Details::output',
				$post_type,
				'side'
			);

			// Payment details.
			add_meta_box( 'wpinv-payment-meta', __( 'Payment Meta', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Payment_Meta::output', $post_type, 'side', 'default' );

			// Billing details.
			add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Address::output', $post_type, 'normal', 'high' );

			// Invoice items.
			add_meta_box(
				'wpinv-items',
				sprintf(
					// translators: %s is the invoice type.
					__( '%s Items', 'invoicing' ),
					ucfirst( $invoice->get_invoice_quote_type() )
				),
				'GetPaid_Meta_Box_Invoice_Items::output',
				$post_type,
				'normal',
				'high'
			);

			// Invoice notes.
			add_meta_box(
				'wpinv-notes',
				sprintf(
					// translators: %s is the invoice type.
					__( '%s Notes', 'invoicing' ),
					ucfirst( $invoice->get_invoice_quote_type() )
				),
				'WPInv_Meta_Box_Notes::output',
				$post_type,
				'side',
				'low'
			);

			// Shipping Address.
			if ( get_post_meta( $invoice->get_id(), 'shipping_address', true ) ) {
				add_meta_box( 'wpinv-invoice-shipping-details', __( 'Shipping Address', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Shipping_Address::output', $post_type, 'side', 'high' );
			}

			// Payment form information.
			if ( get_post_meta( $invoice->get_id(), 'payment_form_data', true ) ) {
				add_meta_box( 'wpinv-invoice-payment-form-details', __( 'Payment Form Details', 'invoicing' ), 'WPInv_Meta_Box_Payment_Form::output_details', $post_type, 'side', 'high' );
			}
		}
	}

	/**
	 * Remove some metaboxes.
	 */
	public static function remove_meta_boxes() {
		remove_meta_box( 'wpseo_meta', 'wpi_invoice', 'normal' );
		remove_meta_box( 'postexcerpt', 'wpi_item', 'normal' );
	}

	/**
	 * Rename other metaboxes.
	 */
	public static function rename_meta_boxes() {

	}

	/**
	 * Check if we're saving, then trigger an action based on the post type.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  object $post Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		// Only run once per request to avoid potential endless loops.
		if ( self::$saved_meta_boxes ) {
			return;
		}

		// $post_id and $post are required.
		$post_id = absint( $post_id );

		if ( empty( $post_id ) || empty( $post ) || empty( $post->post_type ) || ! is_string( $post->post_type ) ) {
			return;
		}

		// Only the edit screen saving this post: no ajax, autosave or bulk edit, a valid nonce and permission to edit.
		if ( ! self::is_edit_screen_submission( $post_id ) ) {
			return;
		}

		// Ensure this is our post type.
		$post_types_map = array(
			'wpi_item'         => 'GetPaid_Meta_Box_Item_Details',
			'wpi_payment_form' => 'GetPaid_Meta_Box_Payment_Form',
			'wpi_discount'     => 'GetPaid_Meta_Box_Discount_Details',
		);

		$is_invoice = ! isset( $post_types_map[ $post->post_type ] ) && getpaid_is_invoice_post_type( $post->post_type );

		if ( ! $is_invoice && ! isset( $post_types_map[ $post->post_type ] ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		self::$saved_meta_boxes = true;

		// Sanitize the request data only once all checks have passed.
		$data = wp_kses_post_deep( $_POST );

		if ( $is_invoice ) {
			return GetPaid_Meta_Box_Invoice_Address::save( $post_id, $data );
		}

		// Save the post.
		$class = $post_types_map[ $post->post_type ];

		$class::save( $post_id, $data, $post );
	}
}
