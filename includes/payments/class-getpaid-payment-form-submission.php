<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment form submission class
 *
 */
class GetPaid_Payment_Form_Submission {

    /**
	 * Submission ID
	 *
	 * @var string
	 */
	public $id = null;

	/**
	 * Sets the associated payment form.
	 *
	 * @var GetPaid_Payment_Form
	 */
    protected $payment_form = null;

    /**
	 * The country for the submission.
	 *
	 * @var string
	 */
	public $country = null;

    /**
	 * The state for the submission.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $state = null;

	/**
	 * The invoice associated with the submission.
	 *
	 * @var WPInv_Invoice
	 */
	protected $invoice = null;

	/**
	 * The raw submission data.
	 *
	 * @var array
	 */
	protected $data = null;

	/**
	 * Whether this submission contains a recurring item.
	 *
	 * @var bool
	 */
	public $has_recurring = false;

	/**
	 * The sub total amount for the submission.
	 *
	 * @var float
	 */
	public $subtotal_amount = 0;

	/**
	 * The total discount amount for the submission.
	 *
	 * @var float
	 */
	protected $total_discount_amount = 0;

	/**
	 * The total recurring discount amount for the submission.
	 *
	 * @var float
	 */
	protected $total_recurring_discount_amount = 0;

	/**
	 * The total tax amount for the submission.
	 *
	 * @var float
	 */
	protected $total_tax_amount = 0;

	/**
	 * The total recurring tax amount for the submission.
	 *
	 * @var float
	 */
	protected $total_recurring_tax_amount = 0;

	/**
	 * An array of fees for the submission.
	 *
	 * @var array
	 */
	protected $fees = array();

	/**
	 * The total fees amount for the submission.
	 *
	 * @var float
	 */
	protected $total_fees_amount = 0;

	/**
	 * The total fees amount for the submission.
	 *
	 * @var float
	 */
	protected $total_recurring_fees_amount = 0;

	/**
	 * An array of discounts for the submission.
	 *
	 * @var array
	 */
	protected $discounts = array();

	/**
	 * An array of taxes for the submission.
	 *
	 * @var array
	 */
	protected $taxes = array();

	/**
	 * An array of items for the submission.
	 *
	 * @var GetPaid_Form_Item[]
	 */
	protected $items = array();

	/**
	 * The last error.
	 *
	 * @var string
	 */
	public $last_error = null;

	/**
	 * Is the discount valid?
	 *
	 * @var bool
	 */
	public $is_discount_valid = true;

    /**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Set the state and country to the default state and country.
		$this->country = wpinv_default_billing_country();
		$this->state = wpinv_get_default_state();

		// Do we have an actual submission?
		if ( isset( $_POST['getpaid_payment_form_submission'] ) ) {
			$this->load_data( $_POST );
		}

	}

	/**
	 * Loads submission data.
	 *
	 * @param array $data
	 */
	public function load_data( $data ) {

		// Prepare submitted data...
		$data = wp_unslash( $data );

		// Filter the data.
		$data = apply_filters( 'getpaid_submission_data', $data, $this );

		$this->data = $data;

		$this->id = md5( wp_json_encode( $data ) );

		// Every submission needs an active payment form.
		if ( empty( $data['form_id'] ) ) {
			$this->last_error = __( 'Missing payment form', 'invoicing' );
            return;
		}

		// Fetch the payment form.
		$form = new GetPaid_Payment_Form( $data['form_id'] );

		if ( ! $form->is_active() ) {
			$this->last_error = __( 'Payment form not active', 'invoicing' );
			return;
		}

		// Fetch the payment form.
		$this->payment_form = $form;

		// For existing invoices, make sure that it is valid.
        if ( ! empty( $data['invoice_id'] ) ) {
            $invoice = wpinv_get_invoice( $data['invoice_id'] );

            if ( empty( $invoice ) ) {
				$this->last_error = __( 'Invalid invoice', 'invoicing' );
                return;
			}

			if ( $invoice->is_paid() ) {
				$this->last_error = __( 'This invoice is already paid for.', 'invoicing' );
                return;
			}

			$this->payment_form->set_items( $invoice->get_items() );

			$this->country = $invoice->get_country();
			$this->state   = $invoice->get_state();
			$this->invoice = $invoice;

		// Default forms do not have items.
        } else if ( $form->is_default() && isset( $data['getpaid-items'] ) ) {
			$this->payment_form->set_items( wpinv_clean( $data['getpaid-items'] ) );
		}

		// User's country.
		if ( ! empty( $data['wpinv_country'] ) ) {
			$this->country = $data['wpinv_country'];
		}

		// User's state.
		if ( ! empty( $data['wpinv_state'] ) ) {
			$this->country = $data['wpinv_state'];
		}

		// Handle items.
		$selected_items = array();
		if ( ! empty( $data['getpaid-items'] ) ) {
			$selected_items = wpinv_clean( $data['getpaid-items'] );
		}

		foreach ( $this->payment_form->get_items() as $item ) {

			// Continue if this is an optional item and it has not been selected.
			if ( ! $item->is_required() && ! isset( $selected_items[ $item->get_id() ] ) ) {
				continue;
			}

			// (maybe) let customers change the quantities and prices.
			if ( isset( $selected_items[ $item->get_id() ] ) ) {

				// Maybe change the quantities.
				if ( $item->allows_quantities() && is_numeric( $selected_items[ $item->get_id() ]['quantity'] ) ) {
					$item->set_quantity( (int) $selected_items[ $item->get_id() ]['quantity'] );
				}

				// Maybe change the price.
				if ( $item->user_can_set_their_price() ) {
					$price = (float) wpinv_sanitize_amount( $selected_items[ $item->get_id() ]['price'] );

					// But don't get lower than the minimum price.
					if ( $price < $item->get_minimum_price() ) {
						$price = $item->get_minimum_price();
					}

					$item->set_price( $price );

				}

			}

			// Add the item to the form.
			$this->add_item( $item );

		}

		// Fired when we are done processing a submission.
		do_action_ref_array( 'getpaid_process_submission', array( &$this ) );

		// Handle discounts.
		$this->process_discount();

	}

    /**
	 * Returns the payment form.
	 *
	 * @since 1.0.19
	 * @return GetPaid_Payment_Form
	 */
	public function get_payment_form() {
		return $this->payment_form;
	}

	/**
	 * Returns the associated invoice.
	 *
	 * @since 1.0.19
	 * @return WPInv_Invoice
	 */
	public function get_invoice() {
		return $this->invoice;
	}

	/**
	 * Checks whether there is an invoice associated with this submission.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function has_invoice() {
		return ! empty( $this->invoice );
	}

	/**
	 * Returns the appropriate currency for the submission.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_currency() {
		if ( $this->has_invoice() ) {
			return $this->invoice->get_currency();
		}
		return wpinv_get_currency();
    }

    /**
	 * Returns the raw submission data.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Checks if a required field is set.
	 *
	 * @since 1.0.19
	 */
	public function is_required_field_set( $field ) {
		return empty( $field['required'] ) || ! empty( $this->data[ $field['id'] ] );
	}

	///////// Items //////////////

	/**
	 * Adds an item to the submission.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Form_Item $item
	 */
	public function add_item( $item ) {

		// Make sure that it is available for purchase.
		if ( ! $item->can_purchase() ) {
			return;
		}

		// Do we have a recurring item?
		if ( $item->is_recurring() ) {

			if ( $this->has_recurring ) {
				$this->last_error = __( 'You can only buy one recurring item at a time.', 'invoicing' );
			}

			$this->has_recurring = true;

		}

		$this->items[ $item->get_id() ] = $item;

		$this->subtotal_amount += $item->get_sub_total();

	}

	/**
	 * Retrieves a specific item.
	 *
	 * @since 1.0.19
	 */
	public function get_item( $item_id ) {
		return isset( $this->items[ $item_id ] ) ? $this->items[ $item_id ] : null;
	}

	/**
	 * Returns all items.
	 *
	 * @since 1.0.19
	 * @return GetPaid_Form_Item[]
	 */
	public function get_items() {
		return $this->items;
	}

	/*
	|--------------------------------------------------------------------------
	| Taxes
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with submission taxes. Taxes can be recurring
	| or only one-time.
    */

	/**
	 * Prepares the submission's taxes.
	 *
	 * @since 1.0.19
	 */
	public function process_taxes() {

		$tax_processor = new GetPaid_Payment_Form_Submission_Taxes( $this );

		if ( ! empty( $tax_processor->tax_error) ) {
			$this->last_error = $tax_processor->tax_error;
			return;
		}

		foreach ( $tax_processor->taxes as $tax ) {
			$this->add_tax( $tax );
		}

		do_action_ref_array( 'getpaid_submissions_process_taxes', array( &$this ) );
	}

	/**
	 * Adds a tax to the submission.
	 *
	 * @param array $tax An array of tax details. name, initial_tax, and recurring_tax are required.
	 * @since 1.0.19
	 */
	public function add_tax( $tax ) {

		$this->total_tax_amount           += wpinv_sanitize_amount( $tax['initial_tax'] );
		$this->total_recurring_tax_amount += wpinv_sanitize_amount( $tax['recurring_tax'] );
		$this->taxes[ $tax['name'] ]       = $tax;

	}

	/**
	 * Whether or not we'll use taxes for the submission.
	 *
	 * @since 1.0.19
	 */
	public function use_taxes() {

		$use_taxes = wpinv_use_taxes();

		if ( $this->has_invoice() && ! $this->invoice->is_taxable() ) {
			$use_taxes = false;
		}

		return apply_filters( 'getpaid_submission_use_taxes', $use_taxes, $this );

	}

	/**
	 * Returns the total tax amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_tax() {
		return $this->total_tax_amount;
	}

	/**
	 * Returns the total recurring tax amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_recurring_tax() {
		return $this->total_recurring_tax_amount;
	}

	/**
	 * Returns all taxes.
	 *
	 * @since 1.0.19
	 */
	public function get_taxes() {
		return $this->taxes;
	}

	/*
	|--------------------------------------------------------------------------
	| Discounts
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with submission discounts. Discounts can be recurring
	| or only one-time. They also do not have to come from a discount code.
    */

	/**
	 * Prepares the submission's discount.
	 *
	 * @since 1.0.19
	 */
	public function process_discount() {

		$total            = $this->subtotal_amount + $this->get_total_fees() + $this->get_total_tax();
		$discount_handler = new GetPaid_Payment_Form_Submission_Discount( $this, $total );

		if ( ! $discount_handler->is_discount_valid ) {
			$this->last_error = $discount_handler->discount_error;
			return;
		}

		// Process any existing invoice discounts.
		if ( $this->has_invoice() ) {
			$discounts = $this->get_invoice()->get_discounts();

			foreach ( $discounts as $discount ) {
				$this->add_discount( $discount );
			}

		}

		if ( $discount_handler->has_discount ) {
			$this->add_discount( $discount_handler->calculate_discount( $this ) );
		}

		do_action_ref_array( 'getpaid_submissions_process_discounts', array( &$this ) );
	}

	/**
	 * Adds a discount to the submission.
	 *
	 * @param array $discount An array of discount details. name, initial_discount, and recurring_discount are required. Include discount_code if the discount is from a discount code.
	 * @since 1.0.19
	 */
	public function add_discount( $discount ) {

		$this->total_discount_amount           += wpinv_sanitize_amount( $discount['initial_discount'] );
		$this->total_recurring_discount_amount += wpinv_sanitize_amount( $discount['recurring_discount'] );
		$this->discounts[ $discount['name'] ]   = $discount;

	}

	/**
	 * Removes a discount from the submission.
	 *
	 * @since 1.0.19
	 */
	public function remove_discount( $name ) {

		if ( isset( $this->discounts[ $name ] ) ) {
			$discount                               = $this->discounts[ $name ];
			$this->total_discount_amount           -= $discount['initial_discount'];
			$this->total_recurring_discount_amount -= $discount['recurring_discount'];
			unset( $this->discounts[ $name ] );
		}

	}

	/**
	 * Checks whether there is a discount code associated with this submission.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function has_discount_code() {
		return ! empty( $this->discounts['discount_code'] );
	}

	/**
	 * Returns the discount code.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_discount_code() {
		return $this->has_discount_code() ? $this->discounts['discount_code']['discount_code'] : '';
	}

	/**
	 * Returns the total discount amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_discount() {
		return $this->total_discount_amount;
	}

	/**
	 * Returns the total recurring discount amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_recurring_discount() {
		return $this->total_recurring_discount_amount;
	}

	/**
	 * Returns all discounts.
	 *
	 * @since 1.0.19
	 */
	public function get_discounts() {
		return $this->discounts;
	}

	/*
	|--------------------------------------------------------------------------
	| Fees
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with submission fees. Fees can be recurring
	| or only one-time. Price input and Price select elements are treated as 
	| fees.
    */

	/**
	 * Prepares the submission's fees.
	 *
	 * @since 1.0.19
	 */
	public function process_fees() {

		$fees_processor = new GetPaid_Payment_Form_Submission_Fees( $this );

		if ( ! empty( $fees_processor->fee_error) ) {
			$this->last_error = $fees_processor->fee_error;
			return;
		}

		foreach ( $fees_processor->fees as $fee ) {
			$this->add_fee( $fee );
		}

		do_action_ref_array( 'getpaid_submissions_process_fees', array( &$this ) );
	}

	/**
	 * Adds a fee to the submission.
	 *
	 * @param array $fee An array of fee details. name, initial_fee, and recurring_fee are required.
	 * @since 1.0.19
	 */
	public function add_fee( $fee ) {

		$this->total_fees_amount           += wpinv_sanitize_amount( $fee['initial_fee'] );
		$this->total_recurring_fees_amount += wpinv_sanitize_amount( $fee['recurring_fee'] );
		$this->fees[ $fee['name'] ]         = $fee;

	}

	/**
	 * Removes a fee from the submission.
	 *
	 * @since 1.0.19
	 */
	public function remove_fee( $name ) {

		if ( isset( $this->fees[ $name ] ) ) {
			$fee                                = $this->fees[ $name ];
			$this->total_fees_amount           -= $fee['initial_fee'];
			$this->total_recurring_fees_amount -= $fee['recurring_fee'];
			unset( $this->fees[ $name ] );
		}

	}

	/**
	 * Returns the total fees amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_fees() {
		return $this->total_fees_amount;
	}

	/**
	 * Returns the total recurring fees amount.
	 *
	 * @since 1.0.19
	 */
	public function get_total_recurring_fees() {
		return $this->total_recurring_fees_amount;
	}

	/**
	 * Returns all fees.
	 *
	 * @since 1.0.19
	 */
	public function get_fees() {
		return $this->fees;
	}

	// MISC //

	/**
	 * Returns the total amount to collect for this submission.
	 *
	 * @since 1.0.19
	 */
	public function get_total() {
		$total = $this->subtotal_amount + $this->get_total_fees() - $this->get_total_discount() + $this->get_total_tax();
		$total = apply_filters( 'getpaid_get_submission_total_amount', $total, $this  );
		return wpinv_sanitize_amount( $total );
	}

	/**
	 * Whether payment details should be collected for this submission.
	 *
	 * @since 1.0.19
	 */
	public function get_payment_details() {
		$collect = $this->subtotal_amount + $this->get_total_fees() - $this->get_total_discount() + $this->get_total_tax();

		if ( $this->has_recurring ) {
			$collect = true;
		}

		$collect = apply_filters( 'getpaid_submission_collect_payment_details', $collect, $this  );
		return $collect;
	}

	/**
	 * Returns the billing email of the user.
	 *
	 * @since 1.0.19
	 */
	public function get_billing_email() {
		$billing_email = empty( $this->data['billing_email'] ) ? '' : $this->data['billing_email'];
		return apply_filters( 'getpaid_get_submission_billing_email', $billing_email, $this  );
	}

	/**
	 * Checks if the submitter has a billing email.
	 *
	 * @since 1.0.19
	 */
	public function has_billing_email() {
		$billing_email = $this->get_billing_email();
		return ! empty( $billing_email );
	}

}
