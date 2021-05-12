<?php
/**
 * Contains subscription functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */

/**
 * Retrieves an invoice's subscriptions.
 *
 * @param       WPInv_Invoice $invoice
 * @return      WPInv_Subscription[]|WPInv_Subscription|false
 * @since       2.3.0
 */
function getpaid_get_invoice_subscriptions( $invoice ) {

    // Retrieve subscription groups.
    $subscription_ids = wp_list_pluck( getpaid_get_invoice_subscription_groups( $invoice->get_id() ), 'subscription_id' );

    // No subscription groups, normal subscription.
    if ( empty( $subscription_ids ) ) {
        return getpaid_subscriptions()->get_invoice_subscription( $invoice );
    }

    // Subscription groups.
    return array_filter( array_map( 'getpaid_get_subscription', $subscription_ids ) );

}

/**
 * Retrieves an invoice's subscription groups.
 *
 * @param       int $invoice_id
 * @return      array
 * @since       2.3.0
 */
function getpaid_get_invoice_subscription_groups( $invoice_id ) {
    $subscription_groups = get_post_meta( $invoice_id, 'getpaid_subscription_groups', true );
    return empty( $subscription_groups ) ? array() : $subscription_groups;
}

/**
 * Retrieves an invoice's subscription's subscription groups.
 *
 * @param       int $invoice_id
 * @param       int $subscription_id
 * @return      array|false
 * @since       2.3.0
 */
function getpaid_get_invoice_subscription_group( $invoice_id, $subscription_id ) {
    $subscription_groups = getpaid_get_invoice_subscription_groups( $invoice_id );
	$matching_group      = wp_list_filter( $subscription_groups, compact( 'subscription_id' ) );
    return reset( $matching_group );
}

/**
 * Retrieves a subscription given an id.
 *
 * @param int|string|object|WPInv_Subscription $subscription Subscription object, id, profile_id, or object to read.
 * @since       2.3.0
 * @return WPInv_Subscription|false
 */
function getpaid_get_subscription( $subscription ) {

	if ( ! is_a( $subscription, 'WPInv_Subscription' ) ) {
		$subscription = new WPInv_Subscription( $subscription );
	}

	return $subscription->exists() ? $subscription : false;
}

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
 * @return WPInv_Subscription|false
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
 * @since 2.3.0
 * @return WPInv_Subscription|bool
 */
function wpinv_get_invoice_subscription( $invoice ) {

    // Retrieve the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Ensure it is a recurring invoice.
    if ( ! $invoice->is_recurring() ) {
        return false;
    }

	// Fetch the invoice subscription.
	$subscription = getpaid_get_subscriptions(
		array(
			'invoice_in' => $invoice->is_renewal() ? $invoice->get_parent_id() : $invoice->get_id(),
			'number'     => 1,
		)
	);

	return empty( $subscription ) ? false : $subscription[0];

}

/**
 * Construct a cart key based on the billing schedule of a subscription product.
 *
 * Subscriptions groups products by billing schedule when calculating cart totals, so that gateway fees and other "per invoice" amounts
 * can be calculated for each group of items for each renewal. This method constructs a cart key based on the billing schedule
 * to allow products on the same billing schedule to be grouped together - free trials are accounted for by
 * the trial interval and period of the subscription.
 *
 * @param GetPaid_Form_Item|WPInv_Item $cart_item
 * @return string
 */
function getpaid_get_recurring_item_key( $cart_item ) {

	$cart_key     = 'renews_';
	$interval     = $cart_item->get_recurring_interval();
	$period       = $cart_item->get_recurring_period( true );
	$length       = $cart_item->get_recurring_limit() * $interval;
	$trial_period = $cart_item->get_trial_period( true );
	$trial_length = $cart_item->get_trial_interval();

	// First start with the billing interval and period
	switch ( $interval ) {
		case 1 :
			if ( 'day' == $period ) {
				$cart_key .= 'daily';
			} else {
				$cart_key .= sprintf( '%sly', $period );
			}
			break;
		case 2 :
			$cart_key .= sprintf( 'every_2nd_%s', $period );
			break;
		case 3 :
			$cart_key .= sprintf( 'every_3rd_%s', $period );
		break;
		default:
			$cart_key .= sprintf( 'every_%dth_%s', $interval, $period );
			break;
	}

	// Maybe add the optional maximum billing periods...
	if ( $length > 0 ) {
		$cart_key .= '_for_';
		$cart_key .= sprintf( '%d_%s', $length, $period );
		if ( $length > 1 ) {
			$cart_key .= 's';
		}
	}

	// And an optional free trial.
	if ( $cart_item->has_free_trial() ) {
		$cart_key .= sprintf( '_after_a_%d_%s_trial', $trial_length, $trial_period );
	}

	return apply_filters( 'getpaid_get_recurring_item_key', $cart_key, $cart_item );
}

/**
 * Retrieves subscription groups for all items in an invoice/payment form submission.
 *
 * @param WPInv_Invoice|GetPaid_Payment_Form_Submission|GetPaid_Payment_Form $invoice
 * @return array
 */
function getpaid_get_subscription_groups( $invoice ) {

	// Generate subscription groups.
	$subscription_groups = array();
	foreach ( $invoice->get_items() as $item ) {

		if ( $item->is_recurring() ) {
			$subscription_groups[ getpaid_get_recurring_item_key( $item ) ][] = $item;
		}

	}

	return $subscription_groups;
}

/**
 * Calculate the initial and recurring totals for all subscription products in an invoice/payment form submission.
 *
 * We group subscriptions by billing schedule to make the display and creation of recurring totals sane,
 * when there are multiple subscriptions in the cart.
 *
 * @param WPInv_Invoice|GetPaid_Payment_Form_Submission|GetPaid_Payment_Form $invoice
 * @return array
 */
function getpaid_calculate_subscription_totals( $invoice ) {

	// Generate subscription groups.
	$subscription_groups = getpaid_get_subscription_groups( $invoice );

	// Now let's calculate the totals for each group of subscriptions
	$subscription_totals = array();

	foreach ( $subscription_groups as $subscription_key => $items ) {

		if ( empty( $subscription_totals[ $subscription_key ] ) ) {

			$subscription_totals[ $subscription_key ] = array(
				'initial_total'   => 0,
				'recurring_total' => 0,
				'items'           => array(),
				'trialling'       => false,
			);

		}

		/**
		 * Get the totals of the group.
		 * @var GetPaid_Form_Item $item
		 */
		foreach ( $items as $item ) {

			$subscription_totals[ $subscription_key ]['items'][$item->get_id()]  = $item->prepare_data_for_saving();
			$subscription_totals[ $subscription_key ]['item_id']                 = $item->get_id();
			$subscription_totals[ $subscription_key ]['period']                  = $item->get_recurring_period( true );
			$subscription_totals[ $subscription_key ]['interval']                = $item->get_recurring_interval();
			$subscription_totals[ $subscription_key ]['initial_total']          += $item->get_sub_total() + $item->item_tax - $item->item_discount;
			$subscription_totals[ $subscription_key ]['recurring_total']        += $item->get_recurring_sub_total() + $item->item_tax - $item->recurring_item_discount;
			$subscription_totals[ $subscription_key ]['recurring_limit']         = $item->get_recurring_limit();

			// Calculate the next renewal date.
			$period       = $item->get_recurring_period( true );
			$interval     = $item->get_recurring_interval();

			// If the subscription item has a trial period...
			if ( $item->has_free_trial() ) {
				$period   = $item->get_trial_period( true );
				$interval = $item->get_trial_interval();
				$subscription_totals[ $subscription_key ]['trialling'] = $interval . ' ' . $period;
			}

			$subscription_totals[ $subscription_key ]['renews_on'] = date( 'Y-m-d H:i:s', strtotime( "+$interval $period", current_time( 'timestamp' ) ) );

		}

	}

	return apply_filters( 'getpaid_calculate_subscription_totals', $subscription_totals, $invoice );
}

/**
 * Checks if we should group a subscription.
 *
 * @param WPInv_Invoice|GetPaid_Payment_Form_Submission|GetPaid_Payment_Form $invoice
 * @return array
 */
function getpaid_should_group_subscriptions( $invoice ) {

	$recurring_items = 0;

	foreach ( $invoice->get_items() as $item ) {

		if ( $item->is_recurring() ) {
			$recurring_items ++;
		}

	}

	return apply_filters( 'getpaid_should_group_subscriptions', $recurring_items > 1, $invoice );
}

/**
 * Counts the invoices belonging to a subscription.
 *
 * @param int $parent_invoice_id
 * @param int|false $subscription_id
 * @return int
 */
function getpaid_count_subscription_invoices( $parent_invoice_id, $subscription_id = false ) {
	global $wpdb;

	$parent_invoice_id = (int) $parent_invoice_id;

	if ( false === $subscription_id || ! (bool) get_post_meta( $parent_invoice_id, '_wpinv_subscription_id', true ) ) {

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM $wpdb->posts WHERE ( post_parent=%d OR ID=%d ) AND post_status IN ( 'publish', 'wpi-processing', 'wpi-renewal' )",
				$parent_invoice_id,
				$parent_invoice_id
			)
		);

	}
	
	$invoice_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE ( post_parent=%d OR ID=%d ) AND post_status IN ( 'publish', 'wpi-processing', 'wpi-renewal' )",
			$parent_invoice_id,
			$parent_invoice_id
		)
	);

	$count = 0;

	foreach ( wp_parse_id_list( $invoice_ids ) as $invoice_id ) {

		if ( $invoice_id == $parent_invoice_id || $subscription_id == (int) get_post_meta( $invoice_id, '_wpinv_subscription_id', true ) ) {
			$count ++;
			continue;
		}

	}

	return $count;
}
