<?php
function wpinv_is_subscription_payment( $invoice = '' ) {
	if ( empty( $invoice ) ) {
		return false;
	}

	if ( ! is_object( $invoice ) && is_scalar( $invoice ) ) {
		$invoice = wpinv_get_invoice( $invoice );
	}

	if ( empty( $invoice ) ) {
		return false;
	}

	if ( $invoice->is_renewal() ) {
		return true;
	}

	return false;
}

function wpinv_payment_link_transaction_id( $invoice = '' ) {
	if ( empty( $invoice ) ) {
		return false;
	}

	if ( ! is_object( $invoice ) && is_scalar( $invoice ) ) {
		$invoice = wpinv_get_invoice( $invoice );
	}

	if ( empty( $invoice ) ) {
		return false;
	}

	return apply_filters( 'wpinv_payment_details_transaction_id-' . $invoice->gateway, $invoice->get_transaction_id(), $invoice->ID, $invoice );
}

function wpinv_subscription_initial_payment_desc( $amount, $period, $interval, $trial_period = '', $trial_interval = 0 ) {
	$interval   = (int)$interval > 0 ? (int)$interval : 1;

	if ( $trial_interval > 0 && ! empty( $trial_period ) ) {
		$amount = __( 'Free', 'invoicing' );
		$interval = $trial_interval;
		$period = $trial_period;
	}

	$description = '';
	switch ( $period ) {
		case 'D':
		case 'day':
			$description = wp_sprintf( _n( '%s for the first day.', '%1$s for the first %2$d days.', $interval, 'invoicing' ), $amount, $interval );
			break;
		case 'W':
		case 'week':
			$description = wp_sprintf( _n( '%s for the first week.', '%1$s for the first %2$d weeks.', $interval, 'invoicing' ), $amount, $interval );
			break;
		case 'M':
		case 'month':
			$description = wp_sprintf( _n( '%s for the first month.', '%1$s for the first %2$d months.', $interval, 'invoicing' ), $amount, $interval );
			break;
		case 'Y':
		case 'year':
			$description = wp_sprintf( _n( '%s for the first year.', '%1$s for the first %2$d years.', $interval, 'invoicing' ), $amount, $interval );
			break;
	}

	return apply_filters( 'wpinv_subscription_initial_payment_desc', $description, $amount, $period, $interval, $trial_period, $trial_interval );
}

function wpinv_subscription_recurring_payment_desc( $amount, $period, $interval, $bill_times = 0, $trial_period = '', $trial_interval = 0 ) {
	$interval   = (int)$interval > 0 ? (int)$interval : 1;
	$bill_times = (int)$bill_times > 0 ? (int)$bill_times : 0;

	$description = '';
	switch ( $period ) {
		case 'D':
		case 'day':
			if ( (int)$bill_times > 0 ) {
				if ( $interval > 1 ) {
					if ( $bill_times > 1 ) {
						$description = wp_sprintf( __( '%1$s for each %2$d days, for %3$d installments.', 'invoicing' ), $amount, $interval, $bill_times );
					} else {
						$description = wp_sprintf( __( '%1$s for %2$d days.', 'invoicing' ), $amount, $interval );
					}
				} else {
					$description = wp_sprintf( _n( '%s for one day.', '%1$s for each day, for %2$d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
				}
			} else {
				$description = wp_sprintf( _n( '%s for each day.', '%1$s for each %2$d days.', $interval, 'invoicing' ), $amount, $interval );
			}
			break;
		case 'W':
		case 'week':
			if ( (int)$bill_times > 0 ) {
				if ( $interval > 1 ) {
					if ( $bill_times > 1 ) {
						$description = wp_sprintf( __( '%1$s for each %2$d weeks, for %3$d installments.', 'invoicing' ), $amount, $interval, $bill_times );
					} else {
						$description = wp_sprintf( __( '%1$s for %2$d weeks.', 'invoicing' ), $amount, $interval );
					}
				} else {
					$description = wp_sprintf( _n( '%s for one week.', '%1$s for each week, for %2$d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
				}
			} else {
				$description = wp_sprintf( _n( '%s for each week.', '%1$s for each %2$d weeks.', $interval, 'invoicing' ), $amount, $interval );
			}
			break;
		case 'M':
		case 'month':
			if ( (int)$bill_times > 0 ) {
				if ( $interval > 1 ) {
					if ( $bill_times > 1 ) {
						$description = wp_sprintf( __( '%1$s for each %2$d months, for %3$d installments.', 'invoicing' ), $amount, $interval, $bill_times );
					} else {
						$description = wp_sprintf( __( '%1$s for %2$d months.', 'invoicing' ), $amount, $interval );
					}
				} else {
					$description = wp_sprintf( _n( '%s for one month.', '%1$s for each month, for %2$d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
				}
			} else {
				$description = wp_sprintf( _n( '%s for each month.', '%1$s for each %2$d months.', $interval, 'invoicing' ), $amount, $interval );
			}
			break;
		case 'Y':
		case 'year':
			if ( (int)$bill_times > 0 ) {
				if ( $interval > 1 ) {
					if ( $bill_times > 1 ) {
						$description = wp_sprintf( __( '%1$s for each %2$d years, for %3$d installments.', 'invoicing' ), $amount, $interval, $bill_times );
					} else {
						$description = wp_sprintf( __( '%1$s for %2$d years.', 'invoicing' ), $amount, $interval );
					}
				} else {
					$description = wp_sprintf( _n( '%s for one year.', '%1$s for each year, for %2$d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
				}
			} else {
				$description = wp_sprintf( _n( '%s for each year.', '%1$s for each %2$d years.', $interval, 'invoicing' ), $amount, $interval );
			}
			break;
	}

	return apply_filters( 'wpinv_subscription_recurring_payment_desc', $description, $amount, $period, $interval, $bill_times, $trial_period, $trial_interval );
}

function wpinv_subscription_payment_desc( $invoice ) {
	if ( empty( $invoice ) ) {
		return null;
	}

	$description = '';
	if ( $invoice->is_parent() && $item = $invoice->get_recurring( true ) ) {
		if ( $item->has_free_trial() ) {
			$trial_period = $item->get_trial_period();
			$trial_interval = $item->get_trial_interval();
		} else {
			$trial_period = '';
			$trial_interval = 0;
		}

		$description = wpinv_get_billing_cycle( $invoice->get_total(), $invoice->get_recurring_details( 'total' ), $item->get_recurring_period(), $item->get_recurring_interval(), $item->get_recurring_limit(), $trial_period, $trial_interval, $invoice->get_currency() );
	}

	return apply_filters( 'wpinv_subscription_payment_desc', $description, $invoice );
}

function wpinv_get_billing_cycle( $initial, $recurring, $period, $interval, $bill_times, $trial_period = '', $trial_interval = 0, $currency = '' ) {
	$initial_total      = wpinv_round_amount( $initial );
	$recurring_total    = wpinv_round_amount( $recurring );

	if ( $trial_interval > 0 && ! empty( $trial_period ) ) {
		// Free trial
	} else {
		if ( $bill_times == 1 ) {
			$recurring_total = $initial_total;
		} elseif ( $bill_times > 1 && $initial_total != $recurring_total ) {
			$bill_times--;
		}
	}

	$initial_amount     = wpinv_price( $initial_total, $currency );
	$recurring_amount   = wpinv_price( $recurring_total, $currency );

	$recurring          = wpinv_subscription_recurring_payment_desc( $recurring_amount, $period, $interval, $bill_times, $trial_period, $trial_interval );

	if ( $initial_total != $recurring_total ) {
		$initial        = wpinv_subscription_initial_payment_desc( $initial_amount, $period, $interval, $trial_period, $trial_interval );

		$description    = wp_sprintf( __( '%1$s Then %2$s', 'invoicing' ), $initial, $recurring );
	} else {
		$description    = $recurring;
	}

	return apply_filters( 'wpinv_get_billing_cycle', $description, $initial, $recurring, $period, $interval, $bill_times, $trial_period, $trial_interval, $currency );
}

/**
 * Calculates the card name form a card number.
 *
 *
 * @param string $card_number Card number.
 * @return string
 */
function getpaid_get_card_name( $card_number ) {

	// Known regexes.
	$regexes = array(
		'/^4/'                     => __( 'Visa', 'invoicing' ),
		'/^5[1-5]/'                => __( 'Mastercard', 'invoicing' ),
		'/^3[47]/'                 => __( 'Amex', 'invoicing' ),
		'/^3(?:0[0-5]|[68])/'      => __( 'Diners Club', 'invoicing' ),
		'/^6(?:011|5)/'            => __( 'Discover', 'invoicing' ),
		'/^(?:2131|1800|35\d{3})/' => __( 'JCB', 'invoicing' ),
	);

	// Confirm if one matches.
	foreach ( $regexes as $regex => $card ) {
		if ( preg_match( $regex, $card_number ) >= 1 ) {
			return $card;
		}
	}

	// None matched.
	return __( 'Card', 'invoicing' );

}

/**
 * Sends an error response during checkout.
 *
 * @param WPInv_Invoice|int|null $invoice
 */
function wpinv_send_back_to_checkout( $invoice = null ) {
	$response = array( 'success' => false );
	$invoice  = wpinv_get_invoice( $invoice );

	// Was an invoice created?
	if ( ! empty( $invoice ) ) {
		$invoice             = is_scalar( $invoice ) ? new WPInv_Invoice( $invoice ) : $invoice;
		$response['invoice'] = $invoice->get_id();
		do_action( 'getpaid_checkout_invoice_exception', $invoice );
	}

	// Do we have any errors?
	if ( wpinv_get_errors() ) {
		$response['data'] = getpaid_get_errors_html( true, false );
	} else {
		$response['data'] = __( 'An error occured while processing your payment. Please try again.', 'invoicing' );
	}

	wp_send_json( $response );
}

/**
 * Returns the reCAPTCHA site key.
 *
 * @return string
 */
function getpaid_get_recaptcha_site_key() {
	return apply_filters( 'getpaid_recaptcha_site_key', wpinv_get_option( 'recaptcha_site_key', '' ) );
}

/**
 * Returns the reCAPTCHA secret key.
 *
 * @return string
 */
function getpaid_get_recaptcha_secret_key() {
	return apply_filters( 'getpaid_recaptcha_secret_key', wpinv_get_option( 'recaptcha_secret_key', '' ) );
}

/**
 * Checks if reCAPTCHA is enabled.
 *
 * @return bool
 */
function getpaid_is_recaptcha_enabled() {
	return wpinv_get_option( 'enable_recaptcha', false ) && getpaid_get_recaptcha_site_key() && getpaid_get_recaptcha_secret_key();
}

/**
 * Returns the reCAPTCHA version.
 *
 * @return string
 */
function getpaid_get_recaptcha_version() {
	return apply_filters( 'getpaid_recaptcha_version', wpinv_get_option( 'recaptcha_version', 'v2' ) );
}

function getpaid_recaptcha_api_url() {
	// Prevent conflicts with Ninja Forms recaptcha.
	if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_ninja_forms' ) {
		$url = '';
	} else {
		$url = getpaid_recaptcha_get_api_url();
	}

	return apply_filters( 'getpaid_recaptcha_api_url', $url );
}

function getpaid_recaptcha_get_api_url() {
	return add_query_arg(
		array(
			'render' => 'v2' === getpaid_get_recaptcha_version() ? 'explicit' : getpaid_get_recaptcha_site_key(),
		),
		'https://www.google.com/recaptcha/api.js'
	);
}

/**
 * Returns recaptcha settings.
 *
 * @return array
 */
function getpaid_get_recaptcha_settings() {
	$settings = array(
		'enabled' => getpaid_is_recaptcha_enabled(),
		'version' => getpaid_get_recaptcha_version(),
	);

	if ( ! getpaid_is_recaptcha_enabled() ) {
		return $settings;
	}

	$settings['sitekey'] = getpaid_get_recaptcha_site_key();

	// Version 2 render params.
	if ( 'v2' === getpaid_get_recaptcha_version() ) {
		$settings['render_params'] = array(
			'sitekey'  => getpaid_get_recaptcha_site_key(),
			'theme'    => 'light',
			'size'     => 'normal',
			'tabindex' => 0,
		);
	}

	return apply_filters( 'getpaid_recaptcha_settings', $settings );
}

/**
 * Displays reCAPTCHA before payment button.
 */
function getpaid_display_recaptcha_before_payment_button() {
	if ( ! getpaid_is_recaptcha_enabled() || 'v2' !== getpaid_get_recaptcha_version() ) {
		return;
	}

	printf(
		'<div class="getpaid-recaptcha-wrapper"><div class="g-recaptcha mw-100 overflow-hidden my-2" id="getpaid-recaptcha-%s"></div></div>',
		esc_attr( wp_unique_id() )
	);
}
add_action( 'getpaid_before_payment_form_pay_button', 'getpaid_display_recaptcha_before_payment_button' );

/**
 * Validates the reCAPTCHA response.
 *
 * @param GetPaid_Payment_Form_Submission $submission
 */
function getpaid_validate_recaptcha_response( $submission ) {

	// Check if reCAPTCHA is enabled.
	if ( ! getpaid_is_recaptcha_enabled() ) {
		return;
	}

	$token = $submission->get_field( 'g-recaptcha-response' );

	// Abort if no token was provided.
	if ( empty( $token ) ) {
		wp_send_json_error( 'v2' === getpaid_get_recaptcha_version() ? __( 'Please confirm that you are not a robot.', 'invoicing' ) : __( "Unable to verify that you're not a robot. Please try again.", 'invoicing' ) );
	}

	$result = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'body' => array(
				'secret'   => getpaid_get_recaptcha_secret_key(),
				'response' => $token,
			),
		)
	);

	// Site not reachable, give benefit of doubt.
	if ( is_wp_error( $result ) ) {
		return;
	}

	$result = json_decode( wp_remote_retrieve_body( $result ), true );

	if ( empty( $result['success'] ) && ! in_array( 'missing-input-secret', $result['error-codes'], true ) && ! in_array( 'invalid-input-secret', $result['error-codes'], true ) ) {
		wp_send_json_error( __( "Unable to verify that you're not a robot. Please try again.", 'invoicing' ) );
	}

	// For v3, check the score.
	$minimum_score = apply_filters( 'getpaid_recaptcha_minimum_score', 0.4 );
	if ( 'v3' === getpaid_get_recaptcha_version() && ( empty( $result['score'] ) || $result['score'] < $minimum_score ) ) {
		wp_send_json_error( __( "Unable to verify that you're not a robot. Please try again.", 'invoicing' ) );
	}
}
add_action( 'getpaid_checkout_error_checks', 'getpaid_validate_recaptcha_response' );
