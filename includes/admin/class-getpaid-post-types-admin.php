<?php
/**
 * Post Types Admin.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Admin Class
 *
 */
class GetPaid_Post_Types_Admin {

    /**
	 * Hook in methods.
	 */
	public static function init() {

		// Init metaboxes.
		GetPaid_Metaboxes::init();

		// Filter the post updated messages.
		add_filter( 'post_updated_messages', 'GetPaid_Post_Types_Admin::post_updated_messages' );

		// Filter post actions.
		add_filter( 'post_row_actions', 'GetPaid_Post_Types_Admin::post_row_actions', 10, 2 );

		// Table columns.
		add_filter( 'manage_wpi_invoice_posts_columns', array( __CLASS__, 'invoice_columns' ), 100 );
		add_action( 'manage_wpi_invoice_posts_custom_column', array( __CLASS__, 'display_invoice_columns' ), 10, 2 );

		// Deleting posts.
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );
	}

	/**
	 * Post updated messages.
	 */
	public static function post_updated_messages( $messages ) {
		global $post;

		$messages['wpi_discount'] = array(
			0   => '',
			1   => __( 'Discount updated.', 'invoicing' ),
			2   => __( 'Custom field updated.', 'invoicing' ),
			3   => __( 'Custom field deleted.', 'invoicing' ),
			4   => __( 'Discount updated.', 'invoicing' ),
			5   => isset( $_GET['revision'] ) ? wp_sprintf( __( 'Discount restored to revision from %s', 'invoicing' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6   => __( 'Discount updated.', 'invoicing' ),
			7   => __( 'Discount saved.', 'invoicing' ),
			8   => __( 'Discount submitted.', 'invoicing' ),
			9   => wp_sprintf( __( 'Discount scheduled for: <strong>%1$s</strong>.', 'invoicing' ), date_i18n( __( 'M j, Y @ G:i', 'invoicing' ), strtotime( $post->post_date ) ) ),
			10  => __( 'Discount draft updated.', 'invoicing' ),
		);

		$messages['wpi_payment_form'] = array(
			0   => '',
			1   => __( 'Payment Form updated.', 'invoicing' ),
			2   => __( 'Custom field updated.', 'invoicing' ),
			3   => __( 'Custom field deleted.', 'invoicing' ),
			4   => __( 'Payment Form updated.', 'invoicing' ),
			5   => isset( $_GET['revision'] ) ? wp_sprintf( __( 'Payment Form restored to revision from %s', 'invoicing' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6   => __( 'Payment Form updated.', 'invoicing' ),
			7   => __( 'Payment Form saved.', 'invoicing' ),
			8   => __( 'Payment Form submitted.', 'invoicing' ),
			9   => wp_sprintf( __( 'Payment Form scheduled for: <strong>%1$s</strong>.', 'invoicing' ), date_i18n( __( 'M j, Y @ G:i', 'invoicing' ), strtotime( $post->post_date ) ) ),
			10  => __( 'Payment Form draft updated.', 'invoicing' ),
		);

		return $messages;

	}

	/**
	 * Post row actions.
	 */
	public static function post_row_actions( $actions, $post ) {

		$post = get_post( $post );

		// We do not want to edit the default payment form.
		if ( 'wpi_payment_form' == $post->post_type && $post->ID == wpinv_get_default_payment_form() ) {
			unset( $actions['trash'] );
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Returns an array of invoice table columns.
	 */
	public static function invoice_columns( $columns ) {

		$columns = array(
			'cb'                => $columns['cb'],
			'number'            => __( 'Invoice', 'invoicing' ),
			'customer'          => __( 'Customer', 'invoicing' ),
			'invoice_date'      => __( 'Date', 'invoicing' ),
			'amount'            => __( 'Amount', 'invoicing' ),
			'recurring'         => __( 'Recurring', 'invoicing' ),
			'status'            => __( 'Status', 'invoicing' ),
			'wpi_actions'       => __( 'Actions', 'invoicing' ),
		);

		return apply_filters( 'wpi_invoice_table_columns', $columns );
	}

	/**
	 * Displays invoice table columns.
	 */
	public static function display_invoice_columns( $column_name, $post_id ) {

		$invoice = new WPInv_Invoice( $post_id );

		switch ( $column_name ) {

			case 'invoice_date' :
				$date_time = sanitize_text_field( $invoice->get_created_date() );
				$date      = mysql2date( get_option( 'date_format' ), $date_time );
				echo "<span title='$date_time'>$date</span>";
				break;

			case 'amount' :

				$amount = $invoice->get_total();
				$formated_amount = wpinv_price( wpinv_format_amount( $amount ), $invoice->get_currency() );

				if ( $invoice->is_refunded() ) {
					$refunded_amount = wpinv_price( wpinv_format_amount( 0 ), $invoice->get_currency() );
					echo "<del>$formated_amount</del><ins>$refunded_amount</ins>";
				} else {

					$discount = $invoice->get_total_discount();

					if ( ! empty( $discount ) ) {
						$new_amount = wpinv_price( wpinv_format_amount( $amount + $discount ), $invoice->get_currency() );
						echo "<del>$new_amount</del><ins>$formated_amount</ins>";
					} else {
						echo $formated_amount;
					}

				}

				break;

			case 'status' :
				$status       = sanitize_text_field( $invoice->get_status() );
				$status_label = sanitize_text_field( $invoice->get_status_nicename() );

				// If it is paid, show the gateway title.
				if ( $invoice->is_paid() ) {
					$gateway = sanitize_text_field( $invoice->get_gateway_title() );
					$gateway = wp_sprintf( esc_attr__( 'Paid via %s', 'invoicing' ), $gateway );

					echo "<mark class='wpi-help-tip getpaid-invoice-status $status' title='$gateway'><span>$status_label</span></mark>";
				} else {
					echo "<mark class='getpaid-invoice-status $status'><span>$status_label</span></mark>";
				}

				// If it is not paid, display the overdue and view status.
				if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {

					// Invoice view status.
					if ( wpinv_is_invoice_viewed( $invoice->get_id() ) ) {
						echo '&nbsp;&nbsp;<i class="fa fa-eye wpi-help-tip" title="'. esc_attr__( 'Viewed by Customer', 'invoicing' ).'"></i>';
					} else {
						echo '&nbsp;&nbsp;<i class="fa fa-eye-slash wpi-help-tip" title="'. esc_attr__( 'Not Viewed by Customer', 'invoicing' ).'"></i>';
					}

					// Display the overview status.
					if ( wpinv_get_option( 'overdue_active' ) ) {
						$due_date = $invoice->get_due_date();

						if ( ! empty( $due_date ) ) {
							$date = mysql2date( get_option( 'date_format' ), $due_date );
							$date = wp_sprintf( __( 'Due %s', 'invoicing' ), $date );
							echo "<p class='description' style='color: #888;' title='$due_date'>$date</p>";
						}
					}

				}

				break;

			case 'recurring':

				if ( $invoice->is_recurring() ) {
					echo '<i class="fa fa-check" style="color:#43850a;"></i>';
				} else {
					echo '<i class="fa fa-times" style="color:#616161;"></i>';
				}
				break;

			case 'number' :

				$edit_link       = esc_url( get_edit_post_link( $invoice->get_id() ) );
				$invoice_number  = sanitize_text_field( $invoice->get_number() );
				$invoice_details = esc_attr__( 'View Invoice Details', 'invoicing' );

				echo "<a href='$edit_link' title='$invoice_details'><strong>$invoice_number</strong></a>";

				break;

			case 'customer' :
	
				$customer_name = $invoice->get_user_full_name();
	
				if ( empty( $customer_name ) ) {
					$customer_name = $invoice->get_email();
				}
	
				if ( ! empty( $customer_name ) ) {
					$customer_details = esc_attr__( 'View Customer Details', 'invoicing' );
					$view_link        = esc_url( add_query_arg( 'user_id', $invoice->get_user_id(), admin_url( 'user-edit.php' ) ) );
					echo "<a href='$view_link' title='$customer_details'><span>$customer_name</span></a>";
				} else {
					echo '<div>&mdash;</div>';
				}

				break;

			case 'wpi_actions' :

				if ( ! $invoice->is_draft() ) {
					$url    = esc_url( $invoice->get_view_url() );
					$print  = esc_attr__( 'Print invoice', 'invoicing' );
					echo "&nbsp;<a href='$url' title='$print' target='_blank' style='color:#757575'><i class='fa fa-print' style='font-size: 1.4em;'></i></a>";
				}

				if ( $invoice->get_email() ) {
					$url    = esc_url( add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $invoice->get_id() ) ) );
					$send   = esc_attr__( 'Send invoice to customer', 'invoicing' );
					echo "&nbsp;&nbsp;<a href='$url' title='$send' style='color:#757575'><i class='fa fa-envelope' style='font-size: 1.4em;'></i></a>";
				}
				
				break;
		}

	}

	/**
	 * Fired when deleting a post.
	 */
	public static function delete_post( $post_id ) {

		switch ( get_post_type( $post_id ) ) {

			case 'wpi_item' :
				do_action( "getpaid_before_delete_item", new WPInv_Item( $post_id ) );
				break;

			case 'wpi_payment_form' :
				do_action( "getpaid_before_delete_payment_form", new GetPaid_Payment_Form( $post_id ) );
				break;

			case 'wpi_discount' :
				do_action( "getpaid_before_delete_discount", new WPInv_Discount( $post_id ) );
				break;

			case 'wpi_invoice' :
				$invoice = new WPInv_Invoice( $post_id );
				do_action( "getpaid_before_delete_invoice", $invoice );
				$invoice->get_data_store()->delete_items( $invoice );
				$invoice->get_data_store()->delete_special_fields( $invoice );
				break;
		}
	}

}
