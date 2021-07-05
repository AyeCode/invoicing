<?php
/**
 * Processes fees for a payment form submission.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment form submission fees class
 *
 */
class GetPaid_Payment_Form_Submission_Fees {

	/**
	 * The fee validation error.
	 * @var string
	 */
	public $fee_error;

	/**
	 * Submission fees.
	 * @var array
	 */
	public $fees = array();

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function __construct( $submission ) {

		// Process any existing invoice fees.
		if ( $submission->has_invoice() ) {
			$this->fees = $submission->get_invoice()->get_fees();
		}

		// Process price fields.
		$data         = $submission->get_data();
		$payment_form = $submission->get_payment_form();

		foreach ( $payment_form->get_elements() as $element ) {

			if ( 'price_input' == $element['type'] ) {
				$this->process_price_input( $element, $data, $submission );
			}

			if ( 'price_select' == $element['type'] ) {
				$this->process_price_select( $element, $data );
			}

		}

	}

	/**
	 * Process a price input field.
	 *
	 * @param array $element
	 * @param array $data
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function process_price_input( $element, $data, $submission ) {

		// Abort if not passed.
		if ( empty( $data[ $element['id'] ] ) ) {
			return;
		}

		$amount  = (float) wpinv_sanitize_amount( $data[ $element['id'] ] );
		$minimum = empty( $element['minimum'] ) ? 0 : (float) wpinv_sanitize_amount( $element['minimum'] );

		if ( $amount < $minimum ) {
			throw new Exception( sprintf( __( 'The minimum allowed amount is %s', 'invoicing' ), getpaid_unstandardize_amount( $minimum, $submission->get_currency() ) ) );
		}

		$this->fees[ $element['label'] ] = array(
			'name'          => $element['label'],
			'initial_fee'   => $amount,
			'recurring_fee' => 0,
		);

	}

	/**
	 * Process a price select field.
	 *
	 * @param array $element
	 * @param array $data
	 */
	public function process_price_select( $element, $data ) {

		// Abort if not passed.
		if ( empty( $data[ $element['id'] ] ) ) {
			return;
		}

		$options    = getpaid_convert_price_string_to_options( $element['options'] );
		$selected   = array_filter( array_map( 'trim', explode( ',', $data[ $element['id'] ] ) ) );
		$total      = 0;
		$sub_labels = array();

		foreach ( $selected as $price ) {

			if ( ! isset( $options[ $price ] ) ) {
				throw new Exception( __( 'You have selected an invalid amount', 'invoicing' ) );
			}

			$price = explode( '|', $price );

			$sub_labels[] = $price[0];
			$total += (float) wpinv_sanitize_amount( $price[1] );
		}

		$this->fees[ $element['label'] ] = array(
			'name'          => $element['label'],
			'initial_fee'   => $total,
			'recurring_fee' => 0,
			'description'   => implode( ', ', $sub_labels ),
		);

	}

}
