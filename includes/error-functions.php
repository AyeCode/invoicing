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
                    'content'     => wp_kses_post( $error ),
                    'type'        => $type,
                )
            );

        } else {

            $id      = esc_attr( $id );
            $error   = wp_kses_post( $error );
            $errors .= "<div data-code='$id'>$error</div>";
        }

    }

    if ( $clear ){
        wpinv_clear_errors();
    }

    return $errors;

}

/**
 * Prints (then clears) all available errors.
 */
function wpinv_print_errors() {
    echo getpaid_get_errors_html();
}

/**
 * Returns all available errors.
 * 
 * @return array
 */
function wpinv_get_errors() {
    $errors = getpaid_session()->get( 'wpinv_errors' );
    return is_array( $errors ) ? $errors : array();
}

/**
 * Adds an error to the list of errors.
 * 
 * @param string $error_id The error id.
 * @param string $error_message The error message.
 * @param string $type Either error, info, warning, primary, dark, light or success.
 */
function wpinv_set_error( $error_id, $error_message, $type = 'error' ) {

    $errors              = wpinv_get_errors();
    $errors[ $error_id ] = array(
        'type' =>  $type,
        'text' =>  $error_message,
    );

    getpaid_session()->set( 'wpinv_errors', $errors );
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
    getpaid_session()->set( 'wpinv_errors', null );
}

/**
 * Clears a single error.
 * 
 */
function wpinv_unset_error( $error_id ) {
    $errors = wpinv_get_errors();

    if ( isset( $errors[ $error_id ] ) ) {
        unset( $errors[ $error_id ] );
    }

    getpaid_session()->set( 'wpinv_errors', $errors );
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
		_doing_it_wrong( $function, $message, $version );
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
        error_log( trim ( $log ) );

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
