<?php
/**
 * Processes discounts for a payment form submission.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment form submission discount class
 *
 */
class GetPaid_Payment_Form_Submission_Discount {

	/**
	 * Submission discounts.
	 * @var array
	 */
	public $discounts = array();

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param float                           $initial_total
	 * @param float                           $recurring_total
	 */
	public function __construct( $submission, $initial_total, $recurring_total ) {

		// Process any existing invoice discounts.
		if ( $submission->has_invoice() ) {
			$this->discounts = $submission->get_invoice()->get_discounts();
		}

		// Do we have a discount?
		$discount = $submission->get_field( 'discount' );

		if ( empty( $discount ) ) {

			if ( isset( $this->discounts['discount_code'] ) ) {
				unset( $this->discounts['discount_code'] );
			}

			return;
		}

		// Processes the discount code.
		$amount = max( $initial_total, $recurring_total );
		$this->process_discount( $submission, $discount, $amount );

	}

	/**
	 * Processes a submission discount.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param string                          $discount
	 * @param float                           $amount
	 */
	public function process_discount( $submission, $discount, $amount ) {

		// Fetch the discount.
		$discount = new WPInv_Discount( $discount );

		// Ensure it is active.
        if ( ! $this->is_discount_active( $discount ) ) {
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', __( 'Invalid or expired discount code', 'invoicing' ) );
		}

		// Exceeded limit.
		if ( $discount->has_exceeded_limit() ) {
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', __( 'This discount code has been used up', 'invoicing' ) );
		}

		// Validate usages.
		$this->validate_single_use_discount( $submission, $discount );

		// Validate amount.
		$this->validate_discount_amount( $submission, $discount, $amount );

		// Save the discount.
		$this->discounts['discount_code'] = $this->calculate_discount( $submission, $discount );
	}

	/**
	 * Validates a single use discount.
	 *
	 * @param WPInv_Discount                  $discount
	 * @return bool
	 */
	public function is_discount_active(  $discount ) {
		return $discount->exists() && $discount->is_active() && $discount->has_started() && ! $discount->is_expired();
	}

	/**
	 * Returns a user's id or email.
	 *
	 * @param string $email
	 * @return int|string|false
	 */
	public function get_user_id_or_email( $email ) {

		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		return empty( $email ) ? false : sanitize_email( $email );
	}

	/**
	 * Validates a single use discount.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param WPInv_Discount                  $discount
	 */
	public function validate_single_use_discount( $submission, $discount ) {

		// Abort if it is not a single use discount.
		if ( ! $discount->is_single_use() ) {
			return;
		}

		// Ensure there is a valid billing email.
		$user = $this->get_user_id_or_email( $submission->get_billing_email() );

		if ( empty( $user ) ) {
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', __( 'You need to either log in or enter your billing email before applying this discount', 'invoicing' ) );
		}

		// Has the user used this discount code before?
		if ( ! $discount->is_valid_for_user( $user ) ) {
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', __( 'You have already used this discount', 'invoicing' ) );
		}

	}

	/**
	 * Validates the discount's amount.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param WPInv_Discount         $discount
	 * @param float                  $amount
	 */
	public function validate_discount_amount( $submission, $discount, $amount ) {

		// Validate minimum amount.
		if ( ! $discount->is_minimum_amount_met( $amount ) ) {
			$min = wpinv_price( $discount->get_minimum_total(), $submission->get_currency() );
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', sprintf( __( 'The minimum total for using this discount is %s', 'invoicing' ), $min ) );
		}

		// Validate the maximum amount.
		if ( ! $discount->is_maximum_amount_met( $amount ) ) {
			$max = wpinv_price( $discount->get_maximum_total(), $submission->get_currency() );
			throw new GetPaid_Payment_Exception( '.getpaid-discount-field .getpaid-custom-payment-form-errors', sprintf( __( 'The maximum total for using this discount is %s', 'invoicing' ), $max ) );
		}

	}

	/**
	 * Calculates the discount code's amount.
	 *
	 * Ensure that the discount exists and has been validated before calling this method.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param WPInv_Discount                  $discount
	 * @return array
	 */
	public function calculate_discount( $submission, $discount ) {
		return getpaid_calculate_invoice_discount( $submission, $discount );
	}

}
