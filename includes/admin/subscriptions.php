<?php
/**
 * Contains functions that display the subscriptions admin page.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the Subscriptions page
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_subscriptions_page() {

	?>

	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div class="bsui">

			<?php

				// Verify user permissions.
				if ( ! wpinv_current_user_can_manage_invoicing() ) {

					echo aui()->alert(
						array(
							'type'    => 'danger',
							'content' => __( 'You are not permitted to view this page.', 'invoicing' ),
						)
					);

				} else if ( ! empty( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {

					// Display a single subscription.
					wpinv_recurring_subscription_details();
				} else {

					// Display a list of available subscriptions.
					getpaid_print_subscriptions_list();
				}

			?>

		</div>
	</div>

	<?php
}

/**
 * Render the Subscriptions table
 *
 * @access      public
 * @since       1.0.19
 * @return      void
 */
function getpaid_print_subscriptions_list() {

	$subscribers_table = new WPInv_Subscriptions_List_Table();
	$subscribers_table->prepare_items();

	?>
	<form id="subscribers-filter" class="bsui" method="get">
		<input type="hidden" name="page" value="wpinv-subscriptions" />
		<?php $subscribers_table->views(); ?>
		<?php $subscribers_table->display(); ?>
	</form>
	<?php
}

/**
 * Render a single subscription.
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_recurring_subscription_details() {

	// Fetch the subscription.
	$sub = new WPInv_Subscription( (int) $_GET['id'] );
	if ( ! $sub->exists() ) {

		echo aui()->alert(
			array(
				'type'    => 'danger',
				'content' => __( 'Subscription not found.', 'invoicing' ),
			)
		);

		return;
	}

	// Use metaboxes to display the subscription details.
	add_meta_box( 'getpaid_admin_subscription_details_metabox', __( 'Subscription Details', 'invoicing' ), 'getpaid_admin_subscription_details_metabox', get_current_screen(), 'normal', 'high' );
	add_meta_box( 'getpaid_admin_subscription_update_metabox', __( 'Change Status', 'invoicing' ), 'getpaid_admin_subscription_update_metabox', get_current_screen(), 'side' );

	$subscription_id     = $sub->get_id();
	$subscription_groups = getpaid_get_invoice_subscription_groups( $sub->get_parent_invoice_id() );
	$subscription_group  = wp_list_filter( $subscription_groups, compact( 'subscription_id' ) );

	if ( 1 < count( $subscription_groups ) ) {
		add_meta_box( 'getpaid_admin_subscription_related_subscriptions_metabox', __( 'Related Subscriptions', 'invoicing' ), 'getpaid_admin_subscription_related_subscriptions_metabox', get_current_screen(), 'advanced' );
	}

	if ( ! empty( $subscription_group ) ) {
		add_meta_box( 'getpaid_admin_subscription_item_details_metabox', __( 'Subscription Items', 'invoicing' ), 'getpaid_admin_subscription_item_details_metabox', get_current_screen(), 'normal', 'low' );
	}

	add_meta_box( 'getpaid_admin_subscription_invoice_details_metabox', __( 'Related Invoices', 'invoicing' ), 'getpaid_admin_subscription_invoice_details_metabox', get_current_screen(), 'advanced' );

	do_action( 'getpaid_admin_single_subscription_register_metabox', $sub );

	?>

		<form method="post" action="<?php echo admin_url( 'admin.php?page=wpinv-subscriptions&id=' . absint( $sub->get_id() ) ); ?>">

			<?php wp_nonce_field( 'getpaid-nonce', 'getpaid-nonce' ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<input type="hidden" name="getpaid-admin-action" value="update_single_subscription" />
			<input type="hidden" name="subscription_id" value="<?php echo (int) $sub->get_id() ;?>" />

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

					<div id="postbox-container-1" class="postbox-container">
						<?php do_meta_boxes( get_current_screen(), 'side', $sub ); ?>
					</div>

					<div id="postbox-container-2" class="postbox-container">
						<?php do_meta_boxes( get_current_screen(), 'normal', $sub ); ?>
						<?php do_meta_boxes( get_current_screen(), 'advanced', $sub ); ?>
					</div>

				</div>
			</div>

		</form>

		<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('getpaid_page_wpinv-subscriptions'); });</script>

	<?php

}

/**
 * Displays the subscription details metabox.
 *
 * @param WPInv_Subscription $sub
 */
function getpaid_admin_subscription_details_metabox( $sub ) {

	// Subscription items.
	$subscription_group = getpaid_get_invoice_subscription_group( $sub->get_parent_invoice_id(), $sub->get_id() );
	$items_count        = empty( $subscription_group ) ? 1 : count( $subscription_group['items'] );

	// Prepare subscription detail columns.
	$fields = apply_filters(
		'getpaid_subscription_admin_page_fields',
		array(
			'subscription'   => __( 'Subscription', 'invoicing' ),
			'customer'       => __( 'Customer', 'invoicing' ),
			'amount'         => __( 'Amount', 'invoicing' ),
			'start_date'     => __( 'Start Date', 'invoicing' ),
			'renews_on'      => __( 'Next Payment', 'invoicing' ),
			'renewals'       => __( 'Payments', 'invoicing' ),
			'item'           => _n( 'Item', 'Items', $items_count,  'invoicing' ),
			'gateway'        => __( 'Payment Method', 'invoicing' ),
			'profile_id'     => __( 'Profile ID', 'invoicing' ),
			'status'         => __( 'Status', 'invoicing' ),
		)
	);

	if ( ! $sub->is_active() ) {

		if ( isset( $fields['renews_on'] ) ) {
			unset( $fields['renews_on'] );
		}

		if ( isset( $fields['gateway'] ) ) {
			unset( $fields['gateway'] );
		}

	}

	$profile_id = $sub->get_profile_id();
	if ( empty( $profile_id ) && isset( $fields['profile_id'] ) ) {
		unset( $fields['profile_id'] );
	}

	?>

		<table class="table table-borderless" style="font-size: 14px;">
			<tbody>

				<?php foreach ( $fields as $key => $label ) : ?>

					<tr class="getpaid-subscription-meta-<?php echo sanitize_html_class( $key ); ?>">

						<th class="w-25" style="font-weight: 500;">
							<?php echo esc_html( $label ); ?>
						</th>

						<td class="w-75 text-muted">
							<?php do_action( 'getpaid_subscription_admin_display_' . sanitize_key( $key ), $sub, $subscription_group ); ?>
						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>

	<?php
}

/**
 * Displays the subscription customer.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_customer( $subscription ) {

	$username = __( '(Missing User)', 'invoicing' );

	$user = get_userdata( $subscription->get_customer_id() );
	if ( $user ) {

		$username = sprintf(
			'<a href="user-edit.php?user_id=%s">%s</a>',
			absint( $user->ID ),
			! empty( $user->display_name ) ? esc_html( $user->display_name ) : sanitize_email( $user->user_email )
		);

	}

	echo  $username;
}
add_action( 'getpaid_subscription_admin_display_customer', 'getpaid_admin_subscription_metabox_display_customer' );

/**
 * Displays the subscription amount.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_amount( $subscription ) {
	$amount    = wp_kses_post( getpaid_get_formatted_subscription_amount( $subscription ) );
	echo "<span>$amount</span>";
}
add_action( 'getpaid_subscription_admin_display_amount', 'getpaid_admin_subscription_metabox_display_amount' );

/**
 * Displays the subscription id.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_id( $subscription ) {
	echo  '#' . absint( $subscription->get_id() );
}
add_action( 'getpaid_subscription_admin_display_subscription', 'getpaid_admin_subscription_metabox_display_id' );

/**
 * Displays the subscription renewal date.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_start_date( $subscription ) {
	echo getpaid_format_date_value( $subscription->get_date_created() );
}
add_action( 'getpaid_subscription_admin_display_start_date', 'getpaid_admin_subscription_metabox_display_start_date' );

/**
 * Displays the subscription renewal date.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_renews_on( $subscription ) {
	echo getpaid_format_date_value( $subscription->get_expiration() );
}
add_action( 'getpaid_subscription_admin_display_renews_on', 'getpaid_admin_subscription_metabox_display_renews_on' );

/**
 * Displays the subscription renewal count.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_renewals( $subscription ) {
	$max_bills = $subscription->get_bill_times();
	echo $subscription->get_times_billed() . ' / ' . ( empty( $max_bills ) ? "&infin;" : $max_bills );
}
add_action( 'getpaid_subscription_admin_display_renewals', 'getpaid_admin_subscription_metabox_display_renewals' );
/**
 * Displays the subscription item.
 *
 * @param WPInv_Subscription $subscription
 * @param false|array $subscription_group
 */
function getpaid_admin_subscription_metabox_display_item( $subscription, $subscription_group = false ) {

	if ( empty( $subscription_group ) ) {
		echo WPInv_Subscriptions_List_Table::generate_item_markup( $subscription->get_product_id() );
		return;
	}

	$markup = array_map( array( 'WPInv_Subscriptions_List_Table', 'generate_item_markup' ), array_keys( $subscription_group['items'] ) );
	echo implode( ' | ', $markup );

}
add_action( 'getpaid_subscription_admin_display_item', 'getpaid_admin_subscription_metabox_display_item', 10, 2 );

/**
 * Displays the subscription gateway.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_gateway( $subscription ) {

	$gateway = $subscription->get_gateway();

	if ( ! empty( $gateway ) ) {
		echo esc_html( wpinv_get_gateway_admin_label( $gateway ) );
	} else {
		echo "&mdash;";
	}

}
add_action( 'getpaid_subscription_admin_display_gateway', 'getpaid_admin_subscription_metabox_display_gateway' );

/**
 * Displays the subscription status.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_status( $subscription ) {
	echo $subscription->get_status_label_html();
}
add_action( 'getpaid_subscription_admin_display_status', 'getpaid_admin_subscription_metabox_display_status' );

/**
 * Displays the subscription profile id.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_metabox_display_profile_id( $subscription ) {

	$profile_id = $subscription->get_profile_id();

	$input = aui()->input(
		array(
			'type'        => 'text',
			'id'          => 'wpinv_subscription_profile_id',
			'name'        => 'wpinv_subscription_profile_id',
			'label'       => __( 'Profile Id', 'invoicing' ),
			'label_type'  => 'hidden',
			'placeholder' => __( 'Profile Id', 'invoicing' ),
			'value'       => esc_attr( $profile_id ),
			'input_group_right' => '',
			'no_wrap'     => true,
		)
	);

	echo str_ireplace( 'form-control', 'regular-text', $input );

	$url = apply_filters( 'getpaid_remote_subscription_profile_url', '', $subscription );
	if ( ! empty( $url ) ) {
		$url = esc_url_raw( $url );
		echo '&nbsp;<a href="' . $url . '" title="' . __( 'View in Gateway', 'invoicing' ) . '" target="_blank"><i class="fas fa-external-link-alt fa-xs fa-fw align-top"></i></a>';
	}

}
add_action( 'getpaid_subscription_admin_display_profile_id', 'getpaid_admin_subscription_metabox_display_profile_id' );

/**
 * Displays the subscriptions update metabox.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_update_metabox( $subscription ) {

	?>
	<div class="mt-3">

		<?php
			echo aui()->select(
				array(
					'options'          => getpaid_get_subscription_statuses(),
					'name'             => 'subscription_status',
					'id'               => 'subscription_status_update_select',
					'required'         => true,
					'no_wrap'          => false,
					'label'            => __( 'Subscription Status', 'invoicing' ),
					'help_text'        => __( 'Updating the status will trigger related actions and hooks', 'invoicing' ),
					'select2'          => true,
					'value'            => $subscription->get_status( 'edit' ),
				)
			);
		?>

		<div class="mt-2 px-3 py-2 bg-light border-top" style="margin: -12px;">

		<?php
			submit_button( __( 'Update', 'invoicing' ), 'primary', 'submit', false );

			$url    = esc_url( wp_nonce_url( add_query_arg( 'getpaid-admin-action', 'subscription_manual_renew' ), 'getpaid-nonce', 'getpaid-nonce' ) );
			$anchor = __( 'Renew Subscription', 'invoicing' );
			$title  = esc_attr__( 'Are you sure you want to extend the subscription and generate a new invoice that will be automatically marked as paid?', 'invoicing' );

			if ( $subscription->is_active() ) {
				echo "<a href='$url' class='float-right text-muted' onclick='return confirm(\"$title\")'>$anchor</a>";
			}

	echo '</div></div>';
}

/**
 * Displays the subscriptions invoices metabox.
 *
 * @param WPInv_Subscription $subscription
 * @param bool $strict Whether or not to skip invoices of sibling subscriptions
 */
function getpaid_admin_subscription_invoice_details_metabox( $subscription, $strict = true ) {

	$columns = apply_filters(
		'getpaid_subscription_related_invoices_columns',
		array(
			'invoice'      => __( 'Invoice', 'invoicing' ),
			'relationship' => __( 'Relationship', 'invoicing' ),
			'date'         => __( 'Date', 'invoicing' ),
			'status'       => __( 'Status', 'invoicing' ),
			'total'        => __( 'Total', 'invoicing' ),
		),
		$subscription
	);

	// Prepare the invoices.
	$payments = $subscription->get_child_payments( ! is_admin() );
	$parent   = $subscription->get_parent_invoice();

	if ( $parent->exists() ) {
		$payments = array_merge( array( $parent ), $payments );
	}

	$table_class = 'w-100 bg-white';

	if ( ! is_admin() ) {
		$table_class = 'table table-bordered';
	}

	?>
		<div class="m-0" style="overflow: auto;">

			<table class="<?php echo $table_class; ?>">

				<thead>
					<tr>
						<?php
							foreach ( $columns as $key => $label ) {
								$key   = esc_attr( $key );
								$label = esc_html( $label );
								$class = 'text-left';

								echo "<th class='subscription-invoice-field-$key bg-light p-2 $class color-dark font-weight-bold'>$label</th>";
							}
						?>
					</tr>
				</thead>

				<tbody>

					<?php if ( empty( $payments ) ) : ?>
						<tr>
							<td colspan="<?php echo count($columns); ?>" class="p-2 text-left text-muted">
								<?php _e( 'This subscription has no invoices.', 'invoicing' ); ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php

						foreach( $payments as $payment ) :

							// Ensure that we have an invoice.
							$payment = new WPInv_Invoice( $payment );

							// Abort if the invoice is invalid...
							if ( ! $payment->exists() ) {
								continue;
							}

							// ... or belongs to a different subscription.
							if ( $strict && $payment->is_renewal() && $payment->get_subscription_id() && $payment->get_subscription_id() != $subscription->get_id() ) {
								continue;
							}

							echo '<tr>';

								foreach ( array_keys( $columns ) as $key ) {

									$class = 'text-left';

									echo "<td class='p-2 $class'>";

										switch( $key ) {

											case 'total':
												echo '<strong>' . wpinv_price( $payment->get_total(), $payment->get_currency() ) . '</strong>';
												break;

											case 'relationship':
												echo $payment->is_renewal() ? __( 'Renewal Invoice', 'invoicing' ) : __( 'Initial Invoice', 'invoicing' );
												break;

											case 'date':
												echo getpaid_format_date_value( $payment->get_date_created() );
												break;

											case 'status':

												$status = $payment->get_status_nicename();
												if ( is_admin() ) {
													$status = $payment->get_status_label_html();
												}

												echo $status;
												break;

											case 'invoice':
												$link    = esc_url( get_edit_post_link( $payment->get_id() ) );

												if ( ! is_admin() ) {
													$link = esc_url( $payment->get_view_url() );
												}

												$invoice = esc_html( $payment->get_number() );
												echo "<a href='$link'>$invoice</a>";
												break;
										}

									echo '</td>';

								}

							echo '</tr>';

						endforeach;
					?>

				</tbody>

			</table>

		</div>

	<?php
}

/**
 * Displays the subscriptions items metabox.
 *
 * @param WPInv_Subscription $subscription
 */
function getpaid_admin_subscription_item_details_metabox( $subscription ) {

	// Fetch the subscription group.
	$subscription_group = getpaid_get_invoice_subscription_group( $subscription->get_parent_payment_id(), $subscription->get_id() );

	if ( empty( $subscription_group ) || empty( $subscription_group['items'] ) ) {
		return;
	}

	// Prepare table columns.
	$columns = apply_filters(
		'getpaid_subscription_item_details_columns',
		array(
			'item_name'    => __( 'Item', 'invoicing' ),
			'price'        => __( 'Price', 'invoicing' ),
			'tax'          => __( 'Tax', 'invoicing' ),
			'discount'     => __( 'Discount', 'invoicing' ),
			//'initial'      => __( 'Initial Amount', 'invoicing' ),
			'recurring'    => __( 'Subtotal', 'invoicing' ),
		),
		$subscription
	);

	// Prepare the invoices.

	$invoice = $subscription->get_parent_invoice();

	if ( ( ! wpinv_use_taxes() || ! $invoice->is_taxable() ) && isset( $columns['tax'] ) ) {
		unset( $columns['tax'] );
	}

	$table_class = 'w-100 bg-white';

	if ( ! is_admin() ) {
		$table_class = 'table table-bordered';
	}

	?>
		<div class="m-0" style="overflow: auto;">

			<table class="<?php echo $table_class; ?>">

				<thead>
					<tr>
						<?php

							foreach ( $columns as $key => $label ) {
								$key   = esc_attr( $key );
								$label = esc_html( $label );
								$class = 'text-left';

								echo "<th class='subscription-item-field-$key bg-light p-2 $class color-dark font-weight-bold'>$label</th>";
							}
						?>
					</tr>
				</thead>

				<tbody>

					<?php

						foreach( $subscription_group['items'] as $subscription_group_item ) :

							echo '<tr>';

								foreach ( array_keys( $columns ) as $key ) {

									$class = 'text-left';

									echo "<td class='p-2 $class'>";

										switch( $key ) {

											case 'item_name':
												$item_name = get_the_title( $subscription_group_item['item_id'] );
												$item_name = empty( $item_name ) ? $subscription_group_item['item_name'] : $item_name;

												if ( $invoice->get_template() == 'amount' || 1 == (float) $subscription_group_item['quantity'] ) {
													echo esc_html( $item_name );
												} else {
													printf( '%1$s x %2$d', esc_html( $item_name ), (float) $subscription_group_item['quantity'] );
												}

												break;

											case 'price':
												echo wpinv_price( $subscription_group_item['item_price'], $invoice->get_currency() );
												break;

											case 'tax':
												echo wpinv_price( $subscription_group_item['tax'], $invoice->get_currency() );
												break;

											case 'discount':
												echo wpinv_price( $subscription_group_item['discount'], $invoice->get_currency() );
												break;

											case 'initial':
												echo wpinv_price( $subscription_group_item['price'] * $subscription_group_item['quantity'], $invoice->get_currency() );
												break;

											case 'recurring':
												echo '<strong>' . wpinv_price( $subscription_group_item['price'] * $subscription_group_item['quantity'], $invoice->get_currency() ) . '</strong>';
												break;

										}

									echo '</td>';

								}

							echo '</tr>';

						endforeach;

						foreach( $subscription_group['fees'] as $subscription_group_fee ) :

							echo '<tr>';

								foreach ( array_keys( $columns ) as $key ) {

									$class = 'text-left';

									echo "<td class='p-2 $class'>";

										switch( $key ) {

											case 'item_name':
												echo esc_html( $subscription_group_fee['name'] );
												break;

											case 'price':
												echo wpinv_price( $subscription_group_fee['initial_fee'], $invoice->get_currency() );
												break;

											case 'tax':
												echo "&mdash;";
												break;

											case 'discount':
												echo "&mdash;";
												break;

											case 'initial':
												echo wpinv_price( $subscription_group_fee['initial_fee'], $invoice->get_currency() );
												break;

											case 'recurring':
												echo '<strong>' . wpinv_price( $subscription_group_fee['recurring_fee'], $invoice->get_currency() ) . '</strong>';
												break;

										}

									echo '</td>';

								}

							echo '</tr>';

						endforeach;
					?>

				</tbody>

			</table>

		</div>

	<?php
}

/**
 * Displays the related subscriptions metabox.
 *
 * @param WPInv_Subscription $subscription
 * @param bool $skip_current
 */
function getpaid_admin_subscription_related_subscriptions_metabox( $subscription, $skip_current = true ) {

	// Fetch the subscription groups.
	$subscription_groups = getpaid_get_invoice_subscription_groups( $subscription->get_parent_payment_id() );

	if ( empty( $subscription_groups ) ) {
		return;
	}

	// Prepare table columns.
	$columns = apply_filters(
		'getpaid_subscription_related_subscriptions_columns',
		array(
			'subscription'      => __( 'Subscription', 'invoicing' ),
			'start_date'        => __( 'Start Date', 'invoicing' ),
			'renewal_date'      => __( 'Next Payment', 'invoicing' ),
			'renewals'          => __( 'Payments', 'invoicing' ),
			'item'              => __( 'Items', 'invoicing' ),
			'status'            => __( 'Status', 'invoicing' ),
		),
		$subscription
	);

	if ( $subscription->get_status() == 'pending' ) {
		unset( $columns['start_date'], $columns['renewal_date'] );
	}

	$table_class = 'w-100 bg-white';

	if ( ! is_admin() ) {
		$table_class = 'table table-bordered';
	}

	?>
		<div class="m-0" style="overflow: auto;">

			<table class="<?php echo $table_class; ?>">

				<thead>
					<tr>
						<?php

							foreach ( $columns as $key => $label ) {
								$key   = esc_attr( $key );
								$label = esc_html( $label );
								$class = 'text-left';

								echo "<th class='related-subscription-field-$key bg-light p-2 $class color-dark font-weight-bold'>$label</th>";
							}
						?>
					</tr>
				</thead>

				<tbody>

					<?php

						foreach( $subscription_groups as $subscription_group ) :

							// Do not list current subscription.
							if ( $skip_current && (int) $subscription_group['subscription_id'] === $subscription->get_id() ) {
								continue;
							}

							// Ensure the subscription exists.
							$_suscription = new WPInv_Subscription( $subscription_group['subscription_id'] );

							if ( ! $_suscription->exists() ) {
								continue;
							}

							echo '<tr>';

								foreach ( array_keys( $columns ) as $key ) {

									$class = 'text-left';

									echo "<td class='p-2 $class'>";

										switch( $key ) {

											case 'status':
												echo $_suscription->get_status_label_html();
												break;

											case 'item':
												$markup = array_map( array( 'WPInv_Subscriptions_List_Table', 'generate_item_markup' ), array_keys( $subscription_group['items'] ) );
												echo implode( ' | ', $markup );
												break;

											case 'renewals':
												$max_bills = $_suscription->get_bill_times();
												echo $_suscription->get_times_billed() . ' / ' . ( empty( $max_bills ) ? "&infin;" : $max_bills );
												break;

											case 'renewal_date':
												echo $_suscription->is_active() ? getpaid_format_date_value( $_suscription->get_expiration() ) : "&mdash;";
												break;

											case 'start_date':
												echo 'pending' == $_suscription->get_status() ? "&mdash;" : getpaid_format_date_value( $_suscription->get_date_created() );
												break;

											case 'subscription':
												$url = is_admin() ? admin_url( 'admin.php?page=wpinv-subscriptions&id=' . absint( $_suscription->get_id() ) ) : $_suscription->get_view_url();
												printf(
													'%1$s#%2$s%3$s',
													'<a href="' . esc_url( $url ) . '">',
													'<strong>' . intval( $_suscription->get_id() ) . '</strong>',
													'</a>'
												);

												echo WPInv_Subscriptions_List_Table::column_amount( $_suscription );
												break;

										}

									echo '</td>';

								}

							echo '</tr>';

						endforeach;
					?>

				</tbody>

			</table>

		</div>

	<?php
}
