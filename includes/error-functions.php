<?php
/**
 * Contains error functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the errors as html
 *
 * @param bool $clear whether or not to clear the errors.
 * @param bool $wrap whether or not to wrap the errors.
 * @since  1.0.19
 */
function getpaid_get_errors_html( $clear = true, $wrap = true ) {

    $errors = '';
    foreach ( wpinv_get_errors() as $id => $error ) {
        $type     = 'error';

        if ( is_array( $error ) ) {
            $type  = $error['type'];
            $error = $error['text'];
        }

        if ( $wrap ) {

            $errors .= aui()->alert(
                array(
                    'content' => wp_kses_post( $error ),
                    'type'    => $type,
                )
            );

        } else {

            $id      = esc_attr( $id );
            $error   = wp_kses_post( $error );
            $errors .= "<div data-code='$id'>$error</div>";
        }
    }

    if ( $clear ) {
        wpinv_clear_errors();
    }

    return $errors;

}

/**
 * Prints (then clears) all available errors.
 */
function wpinv_print_errors() {
    echo wp_kses_post( getpaid_get_errors_html() );
}

/**
 * Returns all available errors.
 *
 * @return array
 */
function wpinv_get_errors() {

    // Contains known errors.
    $all_errors = array(
        'perm_cancel_subscription'   => array(
            'type' => 'error',
            'text' => __( 'You do not have permission to cancel this subscription', 'invoicing' ),
        ),
        'cannot_cancel_subscription' => array(
            'type' => 'error',
            'text' => __( 'This subscription cannot be cancelled as it is not active.', 'invoicing' ),
        ),
        'cancelled_subscription'     => array(
            'type' => 'success',
            'text' => __( 'Subscription cancelled successfully.', 'invoicing' ),
        ),
        'address_updated'            => array(
            'type' => 'success',
            'text' => __( 'Address updated successfully.', 'invoicing' ),
        ),
        'perm_delete_invoice'        => array(
            'type' => 'error',
            'text' => __( 'You do not have permission to delete this invoice', 'invoicing' ),
        ),
        'cannot_delete_invoice'      => array(
            'type' => 'error',
            'text' => __( 'This invoice cannot be deleted as it has already been paid.', 'invoicing' ),
        ),
        'deleted_invoice'            => array(
            'type' => 'success',
            'text' => __( 'Invoice deleted successfully.', 'invoicing' ),
        ),
        'card_declined'              => array(
            'type' => 'error',
            'text' => __( 'Your card was declined.', 'invoicing' ),
        ),
        'invalid_currency'           => array(
            'type' => 'error',
            'text' => __( 'The chosen payment gateway does not support this currency.', 'invoicing' ),
        ),
    );

    $errors = apply_filters( 'wpinv_errors', array() );

    if ( isset( $_GET['wpinv-notice'] ) && isset( $all_errors[ $_GET['wpinv-notice'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $errors[ $_GET['wpinv-notice'] ] = $all_errors[ $_GET['wpinv-notice'] ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    if ( isset( $GLOBALS['wpinv_notice'] ) && isset( $all_errors[ $GLOBALS['wpinv_notice'] ] ) ) {
        $errors[ $GLOBALS['wpinv_notice'] ] = $all_errors[ $GLOBALS['wpinv_notice'] ];
    }

    if ( isset( $GLOBALS['wpinv_custom_notice'] ) ) {
        $errors[ $GLOBALS['wpinv_custom_notice']['code'] ] = $GLOBALS['wpinv_custom_notice'];
    }

    return $errors;
}

/**
 * Adds an error to the list of errors.
 *
 * @param string $error_id The error id.
 * @param string $error The error message.
 * @param string $type The error type.
 */
function wpinv_set_error( $error_id, $message = '', $type = 'error' ) {

    if ( ! empty( $message ) ) {
        $GLOBALS['wpinv_custom_notice'] = array(
            'code' => $error_id,
            'type' => $type,
            'text' => $message,
        );
    } else {
        $GLOBALS['wpinv_notice'] = $error_id;
    }
}

/**
 * Checks if there is an error.
 *
 */
function wpinv_has_errors() {
    return count( wpinv_get_errors() ) > 0;
}

/**
 * Clears all error.
 *
 */
function wpinv_clear_errors() {
    unset( $GLOBALS['wpinv_notice'] );
}

/**
 * Clears a single error.
 *
 */
function wpinv_unset_error() {
    unset( $GLOBALS['wpinv_notice'] );
}

/**
 * Wrapper for _doing_it_wrong().
 *
 * @since  1.0.19
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function getpaid_doing_it_wrong( $function, $message, $version ) {

	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( esc_html( $function ), wp_kses_post( $message ), esc_html( $version ) );
	}

}

/**
 * Logs a debugging message.
 *
 * @param string $log The message to log.
 * @param string|bool $title The title of the message, or pass false to disable the backtrace.
 * @param string $file The file from which the error was logged.
 * @param string $line The line that contains the error.
 * @param bool $exit Whether or not to exit function execution.
 */
function wpinv_error_log( $log, $title = '', $file = '', $line = '', $exit = false ) {

    if ( true === apply_filters( 'wpinv_log_errors', true ) ) {

        // Ensure the log is a scalar.
        if ( ! is_scalar( $log ) ) {
            $log = print_r( $log, true );
        }

        // Add title.
        if ( ! empty( $title ) ) {
            $log  = $title . ' ' . trim( $log );
        }

        // Add the file to the label.
        if ( ! empty( $file ) ) {
            $log .= ' in ' . $file;
        }

        // Add the line number to the label.
        if ( ! empty( $line ) ) {
            $log .= ' on line ' . $line;
        }

        // Log the message.
        error_log( trim( $log ) );

        // ... and a backtrace.
        if ( false !== $title && false !== $file ) {
            error_log( 'Backtrace ' . wp_debug_backtrace_summary() );
        }
}

    // Maybe exit.
    if ( $exit ) {
        exit;
    }

}
