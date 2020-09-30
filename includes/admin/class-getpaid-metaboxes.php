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
		global $wpinv_euvat;

		// For invoices...
		if ( $post_type == 'wpi_invoice' ) {
			$invoice = new WPInv_Invoice( $post );

			// Resend invoice.
			if ( ! $invoice->is_draft() ) {
				add_meta_box( 'wpinv-mb-resend-invoice', __( 'Resend Invoice', 'invoicing' ), 'GetPaid_Meta_Box_Resend_Invoice::output', 'wpi_invoice', 'side', 'low' );
			}

			// Subscriptions.
			$subscription = getpaid_get_invoice_subscription( $invoice );
			if ( ! empty( $subscription ) ) {
				add_meta_box( 'wpinv-mb-subscriptions', __( 'Subscription Details', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Subscription::output', 'wpi_invoice', 'advanced' );
				add_meta_box( 'wpinv-mb-subscription-invoices', __( 'Related Payments', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Subscription::output_invoices', 'wpi_invoice', 'advanced' );
			}

			// Invoice details.
			add_meta_box( 'wpinv-details', __( 'Invoice Details', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Details::output', 'wpi_invoice', 'side', 'default' );
			
			// Payment details.
			add_meta_box( 'wpinv-payment-meta', __( 'Payment Meta', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Payment_Meta::output', 'wpi_invoice', 'side', 'default' );

			// Billing details.
			add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Address::output', 'wpi_invoice', 'normal', 'high' );
			
			// Invoice items.
			add_meta_box( 'wpinv-items', __( 'Invoice Items', 'invoicing' ), 'GetPaid_Meta_Box_Invoice_Items::output', 'wpi_invoice', 'normal', 'high' );
			
			// Invoice notes.
			add_meta_box( 'wpinv-notes', __( 'Invoice Notes', 'invoicing' ), 'WPInv_Meta_Box_Notes::output', 'wpi_invoice', 'side', 'low' );

			// Payment form information.
			if ( ! empty( $post->ID ) && get_post_meta( $post->ID, 'payment_form_data', true ) ) {
				add_meta_box( 'wpinv-invoice-payment-form-details', __( 'Payment Form Details', 'invoicing' ), 'WPInv_Meta_Box_Payment_Form::output_details', 'wpi_invoice', 'side', 'high' );
			}
		}

		// For payment forms.
		if ( $post_type == 'wpi_payment_form' ) {

			// Design payment form.
			add_meta_box( 'wpinv-payment-form-design', __( 'Payment Form', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form::output', 'wpi_payment_form', 'normal' );

			// Payment form information.
			add_meta_box( 'wpinv-payment-form-info', __( 'Details', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form_Info::output', 'wpi_payment_form', 'side' );

		}

		// For invoice items.
		if ( $post_type == 'wpi_item' ) {

			// Item details.
			add_meta_box( 'wpinv_item_details', __( 'Item Details', 'invoicing' ), 'GetPaid_Meta_Box_Item_Details::output', 'wpi_item', 'normal', 'high' );

			// If taxes are enabled, register the tax metabox.
			if ( $wpinv_euvat->allow_vat_rules() || $wpinv_euvat->allow_vat_classes() ) {
				add_meta_box( 'wpinv_item_vat', __( 'VAT / Tax', 'invoicing' ), 'GetPaid_Meta_Box_Item_VAT::output', 'wpi_item', 'normal', 'high' );
			}

			// Item info.
			add_meta_box( 'wpinv_field_item_info', __( 'Item info', 'invoicing' ), 'GetPaid_Meta_Box_Item_Info::output', 'wpi_item', 'side', 'core' );

		}

		// For invoice discounts.
		if ( $post_type == 'wpi_discount' ) {
			add_meta_box( 'wpinv_discount_details', __( 'Discount Details', 'invoicing' ), 'GetPaid_Meta_Box_Discount_Details::output', 'wpi_discount', 'normal', 'high' );
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
		if ( empty( $_POST['getpaid_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['getpaid_meta_nonce'] ), 'getpaid_meta_nonce' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Ensure this is our post type.
		$post_types_map = array(
			'wpi_invoice'      => 'GetPaid_Meta_Box_Invoice_Address',
			'wpi_quote'        => 'GetPaid_Meta_Box_Invoice_Address',
			'wpi_item'         => 'GetPaid_Meta_Box_Item_Details',
			'wpi_payment_form' => 'GetPaid_Meta_Box_Payment_Form',
			'wpi_discount'     => 'GetPaid_Meta_Box_Discount_Details',
		);

		// Is this our post type?
		if ( empty( $post->post_type ) || ! isset( $post_types_map[ $post->post_type ] ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		self::$saved_meta_boxes = true;
		
		// Save the post.
		$class = $post_types_map[ $post->post_type ];
		$class::save( $post_id, $_POST, $post );

	}

}
