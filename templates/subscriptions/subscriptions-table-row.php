<?php
/**
 * Template that prints a single column when viewing a subscription.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscriptions-table-column.php.
 *
 * @version 1.0.19
 * @var WPInv_Subscription $subscription
 * @var WPInv_Subscriptions_Widget $widget
 */

defined( 'ABSPATH' ) || exit;

foreach ( array_keys( $widget->get_subscriptions_table_columns() ) as $column ) :

	$class = sanitize_html_class( $column );
	echo "<td class='getpaid-subscriptions-table-column-$class'>";

		do_action( "getpaid_subscriptions_before_frontend_subscription_table_$column", $subscription );

		switch( $column ) :

			case 'subscription':
				$subscription_id = (int) $subscription->get_id();
				$url             = esc_url( $subscription->get_view_url() );
				echo $widget->add_row_actions( "<a href='$url' class='text-decoration-none'>#$subscription_id</a>", $subscription );
				break;

			case 'status':
				echo $subscription->get_status_label();
				break;

			case 'renewal-date':
				$renewal = getpaid_format_date_value( $subscription->get_next_renewal_date() );
				echo $subscription->is_active() ? $renewal : "&mdash;";
				break;

			case 'amount':
				$frequency = getpaid_get_subscription_period_label( $subscription->get_period(), $subscription->get_frequency(), '' );
				$amount    = wpinv_price( wpinv_format_amount( wpinv_sanitize_amount( $subscription->get_recurring_amount() ) ), $subscription->get_parent_payment()->get_currency() );
				echo "<strong style='font-weight: 500;'>$amount</strong> / $frequency";
				break;

		endswitch;

		do_action( "getpaid_subscriptions_frontend_subscription_table_$column", $subscription );

	echo '</td>';

endforeach;
