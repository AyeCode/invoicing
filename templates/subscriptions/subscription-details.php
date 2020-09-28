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
<table class="table table-bordered table-striped">
	<tbody>

		<?php foreach ( $widget->get_single_subscription_columns( $subscription ) as $key => $label ) : ?>

			<tr class="getpaid-subscription-meta-<?php echo sanitize_html_class( $key ); ?>">

				<th class="w-25" style="font-weight: 500;">
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
								echo wpinv_price( wpinv_format_amount( $subscription->get_initial_amount() ), $subscription->get_parent_payment()->get_currency() );

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
								$frequency = sanitize_text_field( WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->get_period(), $subscription->get_frequency(), true ) );
								$amount    = wpinv_price( wpinv_format_amount( $subscription->get_recurring_amount() ), $subscription->get_parent_payment()->get_currency() );
								echo strtolower( "<strong style='font-weight: 500;'>$amount</strong> / $frequency" );
								break;

							case 'invoice':
								$invoice = $subscription->get_parent_invoice();

								if ( $invoice->get_id() ) {
									$view_url = esc_url( $invoice->get_view_url() );
									$number   = sanitize_text_field( $invoice->get_number() );
									echo "<a href='$view_url' class='text-decoration-none'>$number</a>";
								} else {
									echo "&mdash;";
								}

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

<?php

	$payments = $subscription->get_child_payments();
	$parent   = $subscription->get_parent_invoice();

	if ( $parent->get_id() ) {
		$payments = array_merge( array( $parent ), $payments );
	}

	if ( empty( $payments ) ) {
		return;
	}

	$title = __( 'Related Invoices', 'invoicing' );

	echo "<h2 class='mt-4 mb-1 h4'>$title</h2>";

	foreach ( $payments as $payment ) {
		$payment = new WPInv_Invoice( $payment );
		$number  = sanitize_text_field( $payment->get_number() );
		$url     = esc_url( $payment->get_view_url() );
		$date    = sanitize_text_field( getpaid_format_date_value( $payment->get_date_created() ) );
		$status  = $payment->get_status_nicename();
		$amount  = wpinv_price( wpinv_format_amount( $payment->get_total() ), $payment->get_currency() );
	}

?>
