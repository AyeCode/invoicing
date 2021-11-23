<?php
/**
 * Displays a date input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/date.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$label       = empty( $label ) ? '' : wp_kses_post( $label );
$label_class = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );
if ( ! empty( $required ) ) {
	$label .= "<span class='text-danger'> *</span>";
}

$disable_dates = array();

if ( ! empty( $disabled_dates ) ) {
	$disabled_dates = preg_replace( '/\s+/', '', $disabled_dates );
	$disabled_dates = str_ireplace( 'today', current_time( 'Y-m-d' ), $disabled_dates );
	$disabled_dates = array_filter( explode( ',', $disabled_dates ) );

	foreach ( $disabled_dates as $disabled_date ) {

		$disabled_date = trim( $disabled_date );

		if ( false === strpos( $disabled_date, '|' ) ) {
			$disable_dates[] = $disabled_date;
			continue;
		}

		$disabled_date   = explode( '|', $disabled_date );
		$disable_dates[] = array(
			'from' => trim( $disabled_date[0] ),
			'to'   => trim( $disabled_date[1] ),
		);

	}

}

$options = array(
	'data-default-date' => empty( 'default_date' ) ? false : $default_date,
	'data-min-date'     => empty( 'min_date' ) ? false : $min_date,
	'data-max-date'     => empty( 'max_date' ) ? false : $max_date,
	'data-mode'         => empty( 'mode' ) ? 'single' : $mode,
	'data-alt-format'   => get_option( 'date_format', 'F j, Y' ),
	'data-date-format'  => 'Y-m-d',
	'data-alt-input'    => 'true',
	'data-disable_alt'  => empty( $disabled_dates ) ? false : wp_json_encode( $disable_dates ),
	'data-disable_days_alt'  => empty( $disable_days ) ? false : wp_json_encode( wp_parse_id_list( $disable_days ) ),
);

echo aui()->input(
	array(
		'name'             => esc_attr( $id ),
		'id'               => esc_attr( $id ) . uniqid( '_' ),
		'placeholder'      => empty( $placeholder ) ? '' : esc_attr( $placeholder ),
		'required'         => ! empty( $required ),
		'label'            => $label,
		'label_type'       => 'vertical',
		'help_text'        => empty( $description ) ? '' : wp_kses_post( $description ),
		'type'             => 'datepicker',
		'class'            => $label_class . ' getpaid-init-flatpickr flatpickr-input',
		'extra_attributes' => array_filter( apply_filters( 'getpaid_date_field_attributes', $options ) ),
	)
);
