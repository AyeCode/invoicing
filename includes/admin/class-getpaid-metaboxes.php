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
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::add_meta_boxes', 5, 2 );

		// Remove metaboxes.
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::remove_meta_boxes', 30 );

		// Rename metaboxes.
		add_action( 'add_meta_boxes', 'GetPaid_Metaboxes::rename_meta_boxes', 45 );

		// Save metaboxes.
		add_action( 'save_post', 'GetPaid_Metaboxes::save_meta_boxes', 1, 2 );
	}

	/**
	 * Register core metaboxes.
	 */
	public static function add_meta_boxes( $post_type, $post ) {

		// For invoices...
		self::add_invoice_meta_boxes( $post_type, $post );

		// For payment forms.
		self::add_payment_form_meta_boxes( $post_type, $post );

		// For invoice items.
		self::add_item_meta_boxes( $post_type );

		// For invoice discounts.
		if ( $post_type == 'wpi_discount' ) {
			add_meta_box( 'wpinv_discount_details', __( 'Discount Details', 'invoicing' ), 'GetPaid_Meta_Box_Discount_Details::output', 'wpi_discount', 'normal', 'high' );
		}

	}

	/**
	 * Register core metaboxes.
	 */
	protected static function add_payment_form_meta_boxes( $post_type, $post ) {

		// For payment forms.
		if ( $post_type == 'wpi_payment_form' ) {

			// Design payment form.
			add_meta_box( 'wpinv-payment-form-design', __( 'Payment Form', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form::output', 'wpi_payment_form', 'normal' );

			// Payment form information.
			if ( $post->ID != wpinv_get_default_payment_form() ) {
				add_meta_box( 'wpinv-payment-form-info', __( 'Details', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form_Info::output', 'wpi_payment_form', 'side' );
			}

		}

	}

	/**
	 * Register core metaboxes.
	 */
	protected static function add_item_meta_boxes( $post_type ) {

		if ( $post_type == 'wpi_item' ) {

			// Item details.
			add_meta_box( 'wpinv_item_details', __( 'Item Details', 'invoicing' ), 'GetPaid_Meta_Box_Item_Details::output', 'wpi_item', 'normal', 'high' );

			// If taxes are enabled, register the tax metabox.
			if ( wpinv_use_taxes() ) {
				add_meta_box( 'wpinv_item_vat', __( 'Tax', 'invoicing' ), 'GetPaid_Meta_Box_Item_VAT::output', 'wpi_item', 'normal', 'high' );
			}

			// Item info.
			add_meta_box( 'wpinv_field_item_info', __( 'Item info', 'invoicing' ), 'GetPaid_Meta_Box_Item_Info::output', 'wpi_item', 'side', 'core' );

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
		$post_id = absint( $post_id );
		$data    = wp_kses_post_deep( wp_unslash( $_POST ) );

		// Do not save for ajax requests.
		if ( ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( empty( $data['getpaid_meta_nonce'] ) || ! wp_verify_nonce( $data['getpaid_meta_nonce'], 'getpaid_meta_nonce' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $data['post_ID'] ) || absint( $data['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( getpaid_is_invoice_post_type( $post->post_type ) ) {

			// We need this save event to run once to avoid potential endless loops.
			self::$saved_meta_boxes = true;

			return GetPaid_Meta_Box_Invoice_Address::save( $post_id );

		}

		// Ensure this is our post type.
		$post_types_map = array(
			'wpi_item'         => 'GetPaid_Meta_Box_Item_Details',
			'wpi_payment_form' => 'GetPaid_Meta_Box_Payment_Form',
			'wpi_discount'     => 'GetPaid_Meta_Box_Discount_Details',
		);

		// Is this our post type?
		if ( ! isset( $post_types_map[ $post->post_type ] ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		self::$saved_meta_boxes = true;
		
		// Save the post.
		$class = $post_types_map[ $post->post_type ];
		$class::save( $post_id, $_POST, $post );

	}

}
