<?php
/**
 * Template that prints a single subscription's details
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscription-details.php.
 *
 * @version 1.0.19
 * @var WPInv_Subscription $subscription
 * @var WPInv_Subscriptions_Widget $widget
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_single_subscription_before_notices', $subscription );

// Display errors and notices.
wpinv_print_errors();

$subscription_groups = getpaid_get_invoice_subscription_groups( $subscription->get_parent_invoice_id() );
$subscription_group  = getpaid_get_invoice_subscription_group( $subscription->get_parent_invoice_id(), $subscription->get_id() );

do_action( 'getpaid_before_single_subscription', $subscription, $subscription_groups );

?>

<style>
	.entry-header,
	.entry-title {
		display: none !important;
	}

</style>

<h2 class="mb-1 h4"><?php _e( 'Subscription Details', 'invoicing' ); ?></h2>
<table class="table table-bordered">
	<tbody>

		<?php foreach ( $widget->get_single_subscription_columns( $subscription ) as $key => $label ) : ?>

			<tr class="getpaid-subscription-meta-<?php echo sanitize_html_class( $key ); ?>">

				<th class="font-weight-bold" style="width: 35%">
					<?php echo esc_html( $label ); ?>
				</th>

				<td style="width: 65%">
					<?php

						switch ( $key ) {

							case 'status':
								echo esc_html( $subscription->get_status_label() );
								break;

							case 'start_date':
								echo esc_html( getpaid_format_date_value( $subscription->get_date_created() ) );
								break;

							case 'expiry_date':
								echo esc_html( getpaid_format_date_value( $subscription->get_next_renewal_date() ) );
								break;

							case 'initial_amount':
								echo wpinv_price( $subscription->get_initial_amount(), $subscription->get_parent_payment()->get_currency() );

								if ( $subscription->has_trial_period() ) {

									echo "<small class='text-muted'>&nbsp;";
									printf(
										_x( '( %1$s trial )', 'Subscription trial period. (e.g.: 1 month trial)', 'invoicing' ),
										esc_html( $subscription->get_trial_period() )
									);
									echo '</small>';

								}

								break;

							case 'recurring_amount':
								$frequency = getpaid_get_subscription_period_label( $subscription->get_period(), $subscription->get_frequency(), '' );
								$amount    = wpinv_price( $subscription->get_recurring_amount(), $subscription->get_parent_payment()->get_currency() );
								echo strtolower( "<strong style='font-weight: 500;'>$amount</strong> / <span class='getpaid-item-recurring-period'>$frequency</span>" );
								break;

							case 'item':

								if ( empty( $subscription_group ) ) {
									echo WPInv_Subscriptions_List_Table::generate_item_markup( $subscription->get_product_id() );
								} else {
									$markup = array_map( array( 'WPInv_Subscriptions_List_Table', 'generate_item_markup' ), array_keys( $subscription_group['items'] ) );
									echo implode( ' | ', $markup );
								}

								break;

							case 'payments':

								$max_activations = (int) $subscription->get_bill_times();
								echo (int) $subscription->get_times_billed() . ' / ' . ( empty( $max_activations ) ? "&infin;" : $max_activations );

								break;

						}
						do_action( "getpaid_render_single_subscription_column_$key", $subscription );

					?>
				</td>

			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<?php if ( ! empty( $subscription_group ) ) : ?>
	<h2 class='mt-5 mb-1 h4'><?php _e( 'Subscription Items', 'invoicing' ); ?></h2>
	<?php getpaid_admin_subscription_item_details_metabox( $subscription ); ?>
<?php endif; ?>

<h2 class='mt-5 mb-1 h4'><?php _e( 'Related Invoices', 'invoicing' ); ?></h2>

<?php echo getpaid_admin_subscription_invoice_details_metabox( $subscription ); ?>

<?php if ( 1 < count( $subscription_groups ) ) : ?>
	<h2 class='mt-5 mb-1 h4'><?php _e( 'Related Subscriptions', 'invoicing' ); ?></h2>
	<?php getpaid_admin_subscription_related_subscriptions_metabox( $subscription ); ?>
<?php endif; ?>

<span class="form-text">

	<?php
		if ( $subscription->can_cancel() ) {
			printf(
				'<a href="%s" class="btn btn-danger btn-sm" onclick="return confirm(\'%s\')">%s</a>&nbsp;&nbsp;',
				esc_url( $subscription->get_cancel_url() ),
				esc_attr__( 'Are you sure you want to cancel this subscription?', 'invoicing' ),
				__( 'Cancel Subscription', 'invoicing' )
			);
		}

		do_action( 'getpaid-single-subscription-page-actions', $subscription );
	?>

	<a href="<?php echo esc_url( getpaid_get_tab_url( 'gp-subscriptions', get_permalink( (int) wpinv_get_option( 'invoice_subscription_page' ) ) ) ); ?>" class="btn btn-secondary btn-sm"><?php _e( 'Go Back', 'invoicing' ); ?></a>
</span>
