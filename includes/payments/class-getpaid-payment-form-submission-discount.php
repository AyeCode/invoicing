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
	 * Whether or not the submission has a discount.
	 * @var bool.
	 */
	public $has_discount = false;

	/**
	 * Whether or not the discount is valid.
	 * @var bool
	 */
	public $is_discount_valid = true;

	/**
	 * The discount validation error.
	 * @var string
	 */
	public $discount_error;

	/**
	 * The discount.
	 * @var WPInv_Discount
	 */
	public $discount;

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param float                           $amount
	 */
	public function __construct( $submission, $amount ) {

		// Do we have a discount?
		$submission_data = $submission->get_data();
		if ( ! empty( $submission_data['discount'] ) ) {
			$this->has_discount = true;
			$this->pre_process_discount( $submission, $submission_data['discount'], $amount );
		}

	}

	/**
	 * Preprocesses a submission discount.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param string                          $discount
	 * @param float                           $amount
	 */
	public function pre_process_discount( $submission, $discount, $amount ) {

		// Fetch the discount.
		$this->discount = new WPInv_Discount( $discount );

		// Ensure it is active.
        if ( ! $this->is_discount_active( $this->discount ) ) {
			return $this->set_error( __( 'Invalid or expired discount code', 'invoicing' ) );
		}

		// Exceeded limit.
		if ( $this->discount->has_exceeded_limit() ) {
			return $this->set_error( __( 'This discount code has been used up', 'invoicing' ) );
		}

		// Validate usages.
		$this->validate_single_use_discount( $submission, $this->discount );

		// Validate amount.
		$this->validate_discount_amount( $submission, $this->discount, $amount );
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
	 * Sets an error without overwriting the previous error.
	 *
	 * @param string $error
	 */
	public function set_error( $error ) {
		if ( $this->is_discount_valid ) {
			$this->is_discount_valid = false;
			$this->discount_error    = $error;
		}
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
		if ( ! empty( $user ) ) {
			$this->set_error( __( 'You need to either log in or enter your billing email before applying this discount', 'invoicing' ) );
		}

		// Has the user used this discount code before?
		if ( ! $discount->is_valid_for_user( $user ) ) {
			return $this->set_error( __( 'You have already used this discount', 'invoicing' ) );
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
			return $this->set_error( sprintf( __( 'The minimum total for using this discount is %s', 'invoicing' ), $min ) );
		}

		// Validate the maximum amount.
		if ( ! $discount->is_maximum_amount_met( $amount ) ) {
			$max = wpinv_price( $discount->get_maximum_total(), $submission->get_currency() );
			return $this->set_error( sprintf( __( 'The maximum total for using this discount is %s', 'invoicing' ), $max ) );
		}

	}

	/**
	 * Calculates the discount code's amount.
	 *
	 * Ensure that the discount exists and has been validated before calling this method.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @return array
	 */
	public function calculate_discount( $submission ) {

		$initial_discount   = 0;
		$recurring_discount = 0;

		foreach ( $submission->get_items() as $item ) {

			// Abort if it is not valid for this item.
			if ( ! $this->discount->is_valid_for_items( array( $item->get_id() ) ) ) {
				continue;
			}

			// Calculate the initial amount...
			$initial_discount += $this->discount->get_discounted_amount( $item->get_initial_price() * $item->get_quantity() );

			// ... and maybe the recurring amount.
			if ( $item->is_recurring() && $this->discount->is_recurring() ) {
				$recurring_discount += $this->discount->get_discounted_amount( $item->get_recurring_price() * $item->get_quantity() );
			}

		}

		return array(
			'name'               => 'discount_code',
			'discount_code'      => $this->discount->get_code(),
			'initial_discount'   => $initial_discount,
			'recurring_discount' => $recurring_discount,
		);

	}

}
