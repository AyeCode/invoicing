<?php
/**
 * Contains subscription functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */

/**
 * Queries the subscriptions database.
 *
 * @param array $args Query arguments.For a list of all supported args, refer to GetPaid_Subscriptions_Query::prepare_query()
 * @param string $return 'results' returns the found subscriptions, $count returns the total count while 'query' returns GetPaid_Subscriptions_Query
 *
 *
 * @return int|array|WPInv_Subscription[]|GetPaid_Subscriptions_Query
 */
function getpaid_get_subscriptions( $args = array(), $return = 'results' ) {

	// Do not retrieve all fields if we just want the count.
	if ( 'count' == $return ) {
		$args['fields'] = 'id';
		$args['number'] = 1;
	}

	// Do not count all matches if we just want the results.
	if ( 'results' == $return ) {
		$args['count_total'] = false;
	}

	$query = new GetPaid_Subscriptions_Query( $args );

	if ( 'results' == $return ) {
		return $query->get_results();
	}

	if ( 'count' == $return ) {
		return $query->get_total();
	}

	return $query;
}

/**
 * Returns an array of valid subscription statuses.
 *
 * @return array
 */
function getpaid_get_subscription_statuses() {

	return apply_filters(
		'getpaid_get_subscription_statuses',
		array(
			'pending'    => __( 'Pending', 'invoicing' ),
			'trialling'  => __( 'Trialing', 'invoicing' ),
			'active'     => __( 'Active', 'invoicing' ),
			'failing'    => __( 'Failing', 'invoicing' ),
			'expired'    => __( 'Expired', 'invoicing' ),
			'completed'  => __( 'Complete', 'invoicing' ),
			'cancelled'  => __( 'Cancelled', 'invoicing' ),
		)
	);

}

/**
 * Returns a subscription status label
 *
 * @return string
 */
function getpaid_get_subscription_status_label( $status ) {
	$statuses = getpaid_get_subscription_statuses();
	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucfirst( sanitize_text_field( $status ) );
}

/**
 * Returns an array of valid subscription status classes.
 *
 * @return array
 */
function getpaid_get_subscription_status_classes() {

	return apply_filters(
		'getpaid_get_subscription_status_classes',
		array(
			'pending'    => 'badge-dark',
			'trialling'  => 'badge-info',
			'active'     => 'badge-success',
			'failing'    => 'badge-warning',
			'expired'    => 'badge-danger',
			'completed'  => 'badge-primary',
			'cancelled'  => 'badge-secondary',
		)
	);

}

/**
 * Counts subscriptions in each status.
 *
 * @return array
 */
function getpaid_get_subscription_status_counts( $args = array() ) {

	$statuses = array_keys( getpaid_get_subscription_statuses() );
	$counts   = array();

	foreach ( $statuses as $status ) {
		$_args             = wp_parse_args( "status=$status", $args );
		$counts[ $status ] = getpaid_get_subscriptions( $_args, 'count' );
	}

	return $counts;

}

/**
 * Returns valid subscription periods.
 *
 * @return array
 */
function getpaid_get_subscription_periods() {

	return apply_filters(
		'getpaid_get_subscription_periods',
		array(

			'day'   => array(
				'singular' => __( '%s day', 'invoicing' ),
				'plural'   => __( '%d days', 'invoicing' ),
			),

			'week'   => array(
				'singular' => __( '%s week', 'invoicing' ),
				'plural'   => __( '%d weeks', 'invoicing' ),
			),

			'month'   => array(
				'singular' => __( '%s month', 'invoicing' ),
				'plural'   => __( '%d months', 'invoicing' ),
			),

			'year'   => array(
				'singular' => __( '%s year', 'invoicing' ),
				'plural'   => __( '%d years', 'invoicing' ),
			),

		)
	);

}

/**
 * Given a subscription trial, e.g, 1 month, returns the interval (1)
 *
 * @param string $trial_period
 * @return int
 */
function getpaid_get_subscription_trial_period_interval( $trial_period ) {
	return (int) preg_replace( '/[^0-9]/', '', $trial_period );
}

/**
 * Given a subscription trial, e.g, 1 month, returns the period (month)
 *
 * @param string $trial_period
 * @return string
 */
function getpaid_get_subscription_trial_period_period( $trial_period ) {
	return preg_replace( '/[^a-z]/', '', strtolower( $trial_period ) );
}

/**
 * Returns a singular period label..
 *
 * @param string $period
 * @param int $interval
 * @return string
 */
function getpaid_get_subscription_period_label( $period, $interval = 1, $singular_prefix = '1' ) {
	$label = (int) $interval > 1 ? getpaid_get_plural_subscription_period_label(  $period, $interval ) : getpaid_get_singular_subscription_period_label( $period, $singular_prefix );
	return strtolower( sanitize_text_field( $label ) );
}

/**
 * Returns a singular period label..
 *
 * @param string $period
 * @return string
 */
function getpaid_get_singular_subscription_period_label( $period, $singular_prefix = '1' ) {

	$periods = getpaid_get_subscription_periods();
	$period  = strtolower( $period );

	if ( isset( $periods[ $period ] ) ) {
		return sprintf( $periods[ $period ]['singular'], $singular_prefix );
	}

	// Backwards compatibility.
	foreach ( $periods as $key => $data ) {
		if ( strpos( $key, $period ) === 0 ) {
			return sprintf( $data['singular'], $singular_prefix );
		}
	}

	// Invalid string.
	return '';
}

/**
 * Returns a plural period label..
 *
 * @param string $period
 * @param int $interval
 * @return string
 */
function getpaid_get_plural_subscription_period_label( $period, $interval ) {

	$periods = getpaid_get_subscription_periods();
	$period  = strtolower( $period );

	if ( isset( $periods[ $period ] ) ) {
		return sprintf( $periods[ $period ]['plural'], $interval );
	}

	// Backwards compatibility.
	foreach ( $periods as $key => $data ) {
		if ( strpos( $key, $period ) === 0 ) {
			return sprintf( $data['plural'], $interval );
		}
	}

	// Invalid string.
	return '';
}

/**
 * Returns formatted subscription amout
 *
 * @param WPInv_Subscription $subscription
 * @return string
 */
function getpaid_get_formatted_subscription_amount( $subscription ) {

	$initial    = wpinv_price( $subscription->get_initial_amount(), $subscription->get_parent_payment()->get_currency() );
	$recurring  = wpinv_price( $subscription->get_recurring_amount(), $subscription->get_parent_payment()->get_currency() );
	$period     = getpaid_get_subscription_period_label( $subscription->get_period(), $subscription->get_frequency(), '' );
	$bill_times = $subscription->get_bill_times();

	if ( ! empty( $bill_times ) ) {
		$bill_times = $subscription->get_frequency() * $bill_times;
		$bill_times = getpaid_get_subscription_period_label( $subscription->get_period(), $bill_times );
	}

	// Trial periods.
	if ( $subscription->has_trial_period() ) {

		$trial_period   = getpaid_get_subscription_trial_period_period( $subscription->get_trial_period() );
		$trial_interval = getpaid_get_subscription_trial_period_interval( $subscription->get_trial_period() );

		if ( empty( $bill_times ) ) {

			return sprintf(

				// translators: $1: is the initial amount, $2: is the trial period, $3: is the recurring amount, $4: is the recurring period
				_x( '%1$s trial for %2$s then %3$s / %4$s', 'Subscription amount. (e.g.: $10 trial for 1 month then $120 / year)', 'invoicing' ),
				$initial,
				getpaid_get_subscription_period_label( $trial_period, $trial_interval ),
				$recurring,
				$period
	
			);

		}

		return sprintf(

			// translators: $1: is the initial amount, $2: is the trial period, $3: is the recurring amount, $4: is the recurring period, $5: is the bill times
			_x( '%1$s trial for %2$s then %3$s / %4$s for %5$s', 'Subscription amount. (e.g.: $10 trial for 1 month then $120 / year for 4 years)', 'invoicing' ),
			$initial,
			getpaid_get_subscription_period_label( $trial_period, $trial_interval ),
			$recurring,
			$period,
			$bill_times
		);

	}

	if ( $initial != $recurring ) {

		if ( empty( $bill_times ) ) {

			return sprintf(

				// translators: $1: is the initial amount, $2: is the recurring amount, $3: is the recurring period
				_x( 'Initial payment of %1$s which renews at %2$s / %3$s', 'Subscription amount. (e.g.:Initial payment of $100 which renews at $120 / year)', 'invoicing' ),
				$initial,
				$recurring,
				$period
	
			);

		}

		return sprintf(

			// translators: $1: is the initial amount, $2: is the recurring amount, $3: is the recurring period, $4: is the bill times
			_x( 'Initial payment of %1$s which renews at %2$s / %3$s for %4$s', 'Subscription amount. (e.g.:Initial payment of $100 which renews at $120 / year for 5 years)', 'invoicing' ),
			$initial,
			$recurring,
			$period,
			$bill_times

		);

	}

	if ( empty( $bill_times ) ) {

		return sprintf(

			// translators: $1: is the recurring amount, $2: is the recurring period
			_x( '%1$s / %2$s', 'Subscription amount. (e.g.: $120 / year)', 'invoicing' ),
			$initial,
			$period
	
		);

	}

	return sprintf(

		// translators: $1: is the bill times, $2: is the recurring amount, $3: is the recurring period
		_x( '%2$s / %3$s for %1$s', 'Subscription amount. (e.g.: $120 / year for 5 years)', 'invoicing' ),
		$bill_times,
		$initial,
		$period

	);

}

/**
 * Returns an invoice subscription.
 *
 * @param WPInv_Invoice $invoice
 * @return WPInv_Subscription|bool
 */
function getpaid_get_invoice_subscription( $invoice ) {
	return getpaid_subscriptions()->get_invoice_subscription( $invoice );
}

/**
 * Activates an invoice subscription.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_activate_invoice_subscription( $invoice ) {
	$subscription = getpaid_get_invoice_subscription( $invoice );
	if ( is_a( $subscription, 'WPInv_Subscription' ) ) {
		$subscription->activate();
	}
}

/**
 * Returns the subscriptions controller.
 *
 * @return WPInv_Subscriptions
 */
function getpaid_subscriptions() {
	return getpaid()->get( 'subscriptions' );
}

/**
 * Fetchs an invoice subscription from the database.
 *
 * @return WPInv_Subscription|bool
 */
function wpinv_get_subscription( $invoice ) {

    // Retrieve the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Ensure it is a recurring invoice.
    if ( ! $invoice->is_recurring() ) {
        return false;
    }

	// Fetch the invoiec subscription.
	$subscription = getpaid_get_subscriptions(
		array(
			'invoice_in' => $invoice->is_renewal() ? $invoice->get_parent_id() : $invoice->get_id(),
			'number'     => 1,
		)
	);

	return empty( $subscription ) ? false : $subscription[0];

}
