<?php
/**
 * Render the Subscriptions table
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_subscriptions_page() {

	if ( ! empty( $_GET['id'] ) ) {

        wpinv_recurring_subscription_details();

		return;

	}
	?>
	<div class="wrap">

		<h1>
			<?php _e( 'Subscriptions', 'invoicing' ); ?>
		</h1>
		<?php
		$subscribers_table = new WPInv_Subscription_Reports_Table();
		$subscribers_table->prepare_items();
		?>

		<form id="subscribers-filter" method="get">

			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="wpinv-subscriptions" />
			<?php $subscribers_table->views(); ?>
			<?php $subscribers_table->search_box( __( 'Search', 'wpinvoicing' ), 'subscriptions' ); ?>
			<?php $subscribers_table->display(); ?>

		</form>
	</div>
	<?php
}

/**
 * Recurring Subscription Details
 * @description Outputs the subscriber details
 * @since       1.0.0
 *
 */
function wpinv_recurring_subscription_details() {

	$render = true;

	if ( ! current_user_can( 'manage_invoicing' ) ) {
		die( __( 'You are not permitted to view this data.', 'invoicing' ) );
	}

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
        die( __( 'Invalid subscription ID Provided.', 'invoicing' ) );
	}

	$sub_id  = (int) $_GET['id'];
	$sub     = new WPInv_Subscription( $sub_id );

	if ( empty( $sub ) ) {
		die( __( 'Invalid subscription ID Provided.', 'invoicing' ) );
	}

	?>
	<div class="wrap">
		<h2><?php _e( 'Subscription Details', 'invoicing' ); ?></h2>

		<?php if ( $sub ) : ?>

			<div id="wpinv-item-card-wrapper">

				<?php do_action( 'wpinv_subscription_card_top', $sub ); ?>

				<div class="info-wrapper item-section">

					<form id="edit-item-info" method="post" action="<?php echo admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $sub->id ); ?>">

						<div class="item-info">

							<table class="widefat striped">
								<tbody>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Billing Cycle:', 'invoicing' ); ?></label>
										</td>
										<td>
											<?php
											$frequency = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $sub->period, $sub->frequency );
											$billing   = wpinv_price( wpinv_format_amount( $sub->recurring_amount ), wpinv_get_invoice_currency_code( $sub->parent_payment_id ) ) . ' / ' . $frequency;
											$initial   = wpinv_price( wpinv_format_amount( $sub->initial_amount ), wpinv_get_invoice_currency_code( $sub->parent_payment_id ) );
											printf( _x( '%s then %s', 'Initial subscription amount then billing cycle and amount', 'invoicing' ), $initial, $billing );
											?>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Times Billed:', 'invoicing' ); ?></label>
										</td>
										<td><?php echo $sub->get_times_billed() . ' / ' . ( ( $sub->bill_times == 0 ) ? 'Until Cancelled' : $sub->bill_times ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Customer:', 'invoicing' ); ?></label>
										</td>
										<td>
											<?php $subscriber = get_userdata( $sub->customer_id ); ?>
											<a href="<?php echo esc_url( get_edit_user_link( $sub->customer_id ) ); ?>" target="_blank"><?php echo ! empty( $subscriber->display_name ) ? $subscriber->display_name : $subscriber->user_email; ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Initial Invoice:', 'invoicing' ); ?></label>
										</td>
										<td>
                                            <a target="_blank" title="<?php _e( 'View invoice', 'invoicing' ); ?>" href="<?php echo esc_url( get_permalink( $sub->parent_payment_id ) ); ?>"><?php echo wpinv_get_invoice_number( $sub->parent_payment_id ); ?></a>&nbsp;&nbsp;&nbsp;<?php echo wp_sprintf( __( '( ID: %s )', 'invoicing' ), '<a title="' . esc_attr( __( 'View invoice details', 'invoicing' ) ) . '" href="' . get_edit_post_link( $sub->parent_payment_id ) . '" target="_blank">' . $sub->parent_payment_id . '</a>' ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Item:', 'invoicing' ); ?></label>
										</td>
										<td>
											<?php
                                            echo wpinv_item_dropdown( array(
                                                'name'              => 'product_id',
                                                'id'                => 'wpinv_invoice_item',
                                                'with_packages'     => false,
                                                'show_recurring'    => true,
                                                'selected'          => $sub->product_id,
                                                'class'             => 'wpinv-sub-product-id wpi_select2',
                                            ) );

                                            ?>
											<a href="<?php echo esc_url( add_query_arg( array(
													'post'   => $sub->product_id,
													'action' => 'edit'
												), admin_url( 'post.php' ) ) ); ?>" target="_blank"><?php _e( 'View Item', 'invoicing' ) ; ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Payment Method:', 'invoicing' ); ?></label>
										</td>
										<td><?php echo wpinv_get_gateway_admin_label( wpinv_get_payment_gateway( $sub->parent_payment_id ) ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Profile ID:', 'invoicing' ); ?></label>
										</td>
										<td>
											<span class="wpinv-sub-profile-id">
												<?php echo apply_filters( 'wpinv_subscription_profile_link_' . $sub->gateway, $sub->profile_id, $sub ); ?>
											</span>
											<input type="text" name="profile_id" class="hidden wpinv-sub-profile-id" value="<?php echo esc_attr( $sub->profile_id ); ?>" />
											<span>&nbsp;&ndash;&nbsp;</span>
											<a href="#" class="wpinv-edit-sub-profile-id"><?php _e( 'Edit', 'invoicing' ); ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Transaction ID:', 'invoicing' ); ?></label>
										</td>
										<td>
											<span class="wpinv-sub-transaction-id"><?php echo apply_filters( 'wpinv_subscription_transaction_link_' . $sub->gateway, $sub->get_transaction_id(), $sub ); ?></span>
											<input type="text" name="transaction_id" class="hidden wpinv-sub-transaction-id" value="<?php echo esc_attr( $sub->get_transaction_id() ); ?>" />
											<span>&nbsp;&ndash;&nbsp;</span>
											<a href="#" class="wpinv-edit-sub-transaction-id"><?php _e( 'Edit', 'invoicing' ); ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Date Created:', 'invoicing' ); ?></label>
										</td>
										<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub->created, current_time( 'timestamp' ) ) ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell">
												<?php if( 'trialling' == $sub->status ) : ?>
													<?php _e( 'Trialling Until:', 'invoicing' ); ?>
												<?php else: ?>
													<?php _e( 'Expiration Date:', 'invoicing' ); ?>
												<?php endif; ?>
											</label>
										</td>
										<td>
											<span class="wpinv-sub-expiration"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub->expiration, current_time( 'timestamp' ) ) ); ?></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Subscription Status:', 'invoicing' ); ?></label>
										</td>
										<td>
											<select name="status" class="wpi_select2">
												<option value="pending"<?php selected( 'pending', $sub->status ); ?>><?php _e( 'Pending', 'invoicing' ); ?></option>
												<option value="active"<?php selected( 'active', $sub->status ); ?>><?php _e( 'Active', 'invoicing' ); ?></option>
												<option value="cancelled"<?php selected( 'cancelled', $sub->status ); ?>><?php _e( 'Cancelled', 'invoicing' ); ?></option>
												<option value="expired"<?php selected( 'expired', $sub->status ); ?>><?php _e( 'Expired', 'invoicing' ); ?></option>
												<option value="trialling"<?php selected( 'trialling', $sub->status ); ?>><?php _e( 'Trialling', 'invoicing' ); ?></option>
												<option value="failing"<?php selected( 'failing', $sub->status ); ?>><?php _e( 'Failing', 'invoicing' ); ?></option>
												<option value="completed"<?php selected( 'completed', $sub->status ); ?>><?php _e( 'Completed', 'invoicing' ); ?></option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div id="wpinv-sub-notices">
							<div class="notice notice-info inline hidden" id="wpinv-sub-product-update-notice"><p><?php _e( 'Changing the product assigned will not automatically adjust any pricing.', 'invoicing' ); ?></p></div>
							<div class="notice notice-warning inline hidden" id="wpinv-sub-profile-id-update-notice"><p><?php _e( 'Changing the profile ID can result in renewals not being processed. Do this with caution.', 'invoicing' ); ?></p></div>
						</div>
						<div id="item-edit-actions" class="edit-item" style="float:right; margin: 10px 0 0; display: block;">
							<?php wp_nonce_field( 'wpinv-recurring-update', 'wpinv-recurring-update-nonce', false, true ); ?>
							<input type="submit" name="wpinv_update_subscription" id="wpinv_update_subscription" class="button button-primary" value="<?php _e( 'Update Subscription', 'invoicing' ); ?>"/>
							<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>" />
							<?php if( $sub->can_cancel() ) : ?>
								<a class="button button-primary" href="<?php echo $sub->get_cancel_url(); ?>" ><?php _e( 'Cancel Subscription', 'invoicing' ); ?></a>
							<?php endif; ?>
							&nbsp;<input type="submit" name="wpinv_delete_subscription" class="wpinv-delete-subscription button" value="<?php _e( 'Delete Subscription', 'invoicing' ); ?>"/>
						</div>

					</form>
				</div>

				<?php do_action( 'wpinv_subscription_before_stats', $sub ); ?>

				<?php do_action( 'wpinv_subscription_before_tables_wrapper', $sub ); ?>

				<div id="item-tables-wrapper" class="item-section">

					<?php do_action( 'wpinv_subscription_before_tables', $sub ); ?>

					<h3><?php _e( 'Renewal Payments:', 'invoicing' ); ?></h3>
					<?php $payments = $sub->get_child_payments(); ?>
					<?php if ( 'manual' == $sub->gateway ) : ?>
						<p><strong><?php _e( 'Note:', 'invoicing' ); ?></strong> <?php _e( 'Subscriptions purchased with the Test Payment gateway will not renew automatically.', 'invoicing' ); ?></p>
					<?php endif; ?>
					<table class="wp-list-table widefat striped payments">
						<thead>
						<tr>
							<th><?php _e( 'ID', 'invoicing' ); ?></th>
							<th><?php _e( 'Amount', 'invoicing' ); ?></th>
							<th><?php _e( 'Date', 'invoicing' ); ?></th>
							<th><?php _e( 'Status', 'invoicing' ); ?></th>
                            <th><?php _e( 'Invoice', 'invoicing' ); ?></th>
							<th class="column-wpi_actions"><?php _e( 'Actions', 'invoicing' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php if ( ! empty( $payments ) ) : ?>
							<?php foreach ( $payments as $payment ) : $invoice = wpinv_get_invoice( $payment->ID ); if ( empty( $invoice->ID ) ) continue; ?>
								<tr>
									<td><?php echo $payment->ID; ?></td>
									<td><?php echo $invoice->get_total( true ); ?></td>
									<td><?php echo $invoice->get_invoice_date(); ?></td>
									<td><?php echo $invoice->get_status( true ); ?></td>
									<td>
										<a target="_blank" title="<?php _e( 'View invoice', 'invoicing' ); ?>" href="<?php echo esc_url( get_permalink( $payment->ID ) ); ?>"><?php echo $invoice->get_number(); ?></a>
										<?php do_action( 'wpinv_subscription_payments_actions', $sub, $payment ); ?>
									</td>
									<td class="column-wpi_actions">
										<a title="<?php echo esc_attr( wp_sprintf( __( 'View details for invoice: %s', 'invoicing' ), $invoice->get_number() ) ); ?>" href="<?php echo get_edit_post_link( $payment->ID ); ?>"><?php _e( 'View Details', 'invoicing' ); ?>
										</a>
										<?php do_action( 'wpinv_subscription_payments_actions', $sub, $payment ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="5"><?php _e( 'No Invoices Found.', 'invoicing' ); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
						<tfoot></tfoot>
					</table>

					<?php do_action( 'wpinv_subscription_after_tables', $sub ); ?>

				</div>

				<?php do_action( 'wpinv_subscription_card_bottom', $sub ); ?>
			</div>

		<?php endif; ?>

	</div>
	<?php
}

/**
 * Handles subscription update
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_recurring_process_subscription_update() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( empty( $_POST['wpinv_update_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'manage_invoicing') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['wpinv-recurring-update-nonce'], 'wpinv-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
	}

	$profile_id      = sanitize_text_field( $_POST['profile_id'] );
	$transaction_id  = sanitize_text_field( $_POST['transaction_id'] );
	$product_id      = absint( $_POST['product_id'] );
	$subscription    = new WPInv_Subscription( absint( $_POST['sub_id'] ) );
	$subscription->update( array(
		'status'         => sanitize_text_field( $_POST['status'] ),
		'profile_id'     => $profile_id,
		'product_id'     => $product_id,
		'transaction_id' => $transaction_id,
	) );

	$status = sanitize_text_field( $_POST['status'] );

	switch( $status ) {

		case 'cancelled' :

			$subscription->cancel();
			break;

		case 'expired' :

			$subscription->expire();
			break;

		case 'completed' :

			$subscription->complete();
			break;

	}

	wp_redirect( admin_url( 'admin.php?page=wpinv-subscriptions&wpinv-message=updated&id=' . $subscription->id ) );
	exit;

}
add_action( 'admin_init', 'wpinv_recurring_process_subscription_update', 1 );

/**
 * Handles subscription deletion
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_recurring_process_subscription_deletion() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( empty( $_POST['wpinv_delete_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'manage_invoicing') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['wpinv-recurring-update-nonce'], 'wpinv-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
	}

	$subscription = new WPInv_Subscription( absint( $_POST['sub_id'] ) );

	delete_post_meta( $subscription->parent_payment_id, '_wpinv_subscription_payment' );

	$subscription->delete();

	wp_redirect( admin_url( 'admin.php?page=wpinv-subscriptions&wpinv-message=deleted' ) );
	exit;

}
add_action( 'admin_init', 'wpinv_recurring_process_subscription_deletion', 2 );
