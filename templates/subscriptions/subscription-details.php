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

do_action( 'getpaid_before_single_subscription', $subscription );

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

				<th class="w-25 font-weight-bold">
					<?php echo sanitize_text_field( $label ); ?>
				</th>

				<td class="w-75">
					<?php

						switch ( $key ) {

							case 'status':
								echo sanitize_text_field( $subscription->get_status_label() );
								break;

							case 'start_date':
								echo sanitize_text_field( getpaid_format_date_value( $subscription->get_date_created() ) );
								break;

							case 'expiry_date':
								echo sanitize_text_field( getpaid_format_date_value( $subscription->get_next_renewal_date() ) );
								break;

							case 'initial_amount':
								echo wpinv_price( $subscription->get_initial_amount(), $subscription->get_parent_payment()->get_currency() );

								if ( $subscription->has_trial_period() ) {

									echo "<small class='text-muted'>&nbsp;";
									printf(
										_x( '( %1$s trial )', 'Subscription trial period. (e.g.: 1 month trial)', 'invoicing' ),
										sanitize_text_field( $subscription->get_trial_period() )
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
								$item = get_post( $subscription->get_product_id() );

								if ( ! empty( $item ) ) {
									echo esc_html( get_the_title( $item ) );
								} else {
									echo sprintf( __( 'Item #%s', 'invoicing' ), $subscription->get_product_id() );
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

<h2 class='mt-5 mb-1 h4'><?php _e( 'Subscription Invoices', 'invoicing' ); ?></h2>

<?php echo getpaid_admin_subscription_invoice_details_metabox( $subscription ); ?>

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