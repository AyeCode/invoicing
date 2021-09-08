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
	 * The raw submission data.
	 *
	 * @var array
	 */
	protected $data = null;

	/**
	 * Submission totals
	 *
	 * @var array
	 */
	protected $totals = array(

		'subtotal'      => array(
			'initial'   => 0,
			'recurring' => 0,
		),

		'discount'      => array(
			'initial'   => 0,
			'recurring' => 0,
		),

		'fees'          => array(
			'initial'   => 0,
			'recurring' => 0,
		),

		'taxes'         => array(
			'initial'   => 0,
			'recurring' => 0,
		),

	);

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
	 * The recurring item for the submission.
	 *
	 * @var int
	 */
	public $has_recurring = 0;

	/**
	 * An array of fees for the submission.
	 *
	 * @var array
	 */
	protected $fees = array();

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
	 * The last error code.
	 *
	 * @var string
	 */
	public $last_error_code = null;

    /**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Set the state and country to the default state and country.
		$this->country = wpinv_default_billing_country();
		$this->state   = wpinv_get_default_state();

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

		// Remove slashes from the submitted data...
		$data       = wp_kses_post_deep( wp_unslash( $data ) );

		// Allow plugins to filter the data.
		$data       = apply_filters( 'getpaid_submission_data', $data, $this );

		// Cache it...
		$this->data = $data;

		// Then generate a unique id from the data.
		$this->id   = md5( wp_json_encode( $data ) );

		// Finally, process the submission.
		try {

			// Each process is passed an instance of the class (with reference)
			// and should throw an Exception whenever it encounters one.
			$processors = apply_filters(
				'getpaid_payment_form_submission_processors',
				array(
					array( $this, 'process_payment_form' ),
					array( $this, 'process_invoice' ),
					array( $this, 'process_fees' ),
					array( $this, 'process_items' ),
					array( $this, 'process_discount' ),
					array( $this, 'process_taxes' ),
				),
				$this		
			);

			foreach ( $processors as $processor ) {
				call_user_func_array( $processor, array( &$this ) );
			}

		} catch( GetPaid_Payment_Exception $e ) {
			$this->last_error      = $e->getMessage();
			$this->last_error_code = $e->getErrorCode();
		} catch ( Exception $e ) {
			$this->last_error      = $e->getMessage();
			$this->last_error_code = $e->getCode();
		}

		// Fired when we are done processing a submission.
		do_action_ref_array( 'getpaid_process_submission', array( &$this ) );

	}

	/*
	|--------------------------------------------------------------------------
	| Payment Forms.
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with the submission's payment form. Ensure that the
	| submission has an active payment form etc.
    */

	/**
	 * Prepares the submission's payment form.
	 *
	 * @since 1.0.19
	 */
	public function process_payment_form() {

		// Every submission needs an active payment form.
		if ( empty( $this->data['form_id'] ) ) {
			throw new Exception( __( 'Missing payment form', 'invoicing' ) );
		}

		// Fetch the payment form.
		$this->payment_form = new GetPaid_Payment_Form( $this->data['form_id'] );

		if ( ! $this->payment_form->is_active() ) {
			throw new Exception( __( 'Payment form not active', 'invoicing' ) );
		}

		do_action_ref_array( 'getpaid_submissions_process_payment_form', array( &$this ) );
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

	/*
	|--------------------------------------------------------------------------
	| Invoices.
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with the submission's invoice. Some submissions
	| might be for an existing invoice.
	*/

	/**
	 * Prepares the submission's invoice.
	 *
	 * @since 1.0.19
	 */
	public function process_invoice() {

		// Abort if there is no invoice.
		if ( empty( $this->data['invoice_id'] ) ) {
			return;
		}

		// If the submission is for an existing invoice, ensure that it exists
		// and that it is not paid for.
		$invoice = wpinv_get_invoice( $this->data['invoice_id'] );

        if ( empty( $invoice ) ) {
			throw new Exception( __( 'Invalid invoice', 'invoicing' ) );
		}

		if ( $invoice->is_paid() ) {
			throw new Exception( __( 'This invoice is already paid for.', 'invoicing' ) );
		}

		$this->payment_form->invoice = $invoice;
		if ( ! $this->payment_form->is_default() ) {

			$items    = array();
			$item_ids = array();
	
			foreach ( $invoice->get_items() as $item ) {
				if ( ! in_array( $item->get_id(), $item_ids ) ) {
					$item_ids[] = $item->get_id();
					$items[]    = $item;
				}
			}
	
			foreach ( $this->payment_form->get_items() as $item ) {
				if ( ! in_array( $item->get_id(), $item_ids ) ) {
					$item_ids[] = $item->get_id();
					$items[]    = $item;
				}
			}
	
			$this->payment_form->set_items( $items );
	
		} else {
			$this->payment_form->set_items( $invoice->get_items() );
		}

		$this->country = $invoice->get_country();
		$this->state   = $invoice->get_state();
		$this->invoice = $invoice;

		do_action_ref_array( 'getpaid_submissions_process_invoice', array( &$this ) );
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

	/*
	|--------------------------------------------------------------------------
	| Items.
	|--------------------------------------------------------------------------
	|
	| Functions for dealing with the submission's items. Submissions can only have one
	| recurring item. But can have an unlimited number of non-recurring items.
	*/

	/**
	 * Prepares the submission's items.
	 *
	 * @since 1.0.19
	 */
	public function process_items() {

		$processor = new GetPaid_Payment_Form_Submission_Items( $this );

		foreach ( $processor->items as $item ) {
			$this->add_item( $item );
		}

		do_action_ref_array( 'getpaid_submissions_process_items', array( &$this ) );
	}

	/**
	 * Adds an item to the submission.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Form_Item $item
	 */
	public function add_item( $item ) {

		// Make sure that it is available for purchase.
		if ( ! $item->can_purchase() || isset( $this->items[ $item->get_id() ] ) ) {
			return;
		}

		// Each submission can only contain one recurring item.
		if ( $item->is_recurring() ) {
			$this->has_recurring = $item->get_id();
		}

		// Update the items and totals.
		$this->items[ $item->get_id() ]         = $item;
		$this->totals['subtotal']['initial']   += $item->get_sub_total();
		$this->totals['subtotal']['recurring'] += $item->get_recurring_sub_total();

	}

	/**
	 * Removes a specific item.
	 * 
	 * You should not call this method after the discounts and taxes
	 * have been calculated.
	 *
	 * @since 1.0.19
	 */
	public function remove_item( $item_id ) {

		if ( isset( $this->items[ $item_id ] ) ) {
			$this->totals['subtotal']['initial']   -= $this->items[ $item_id ]->get_sub_total();
			$this->totals['subtotal']['recurring'] -= $this->items[ $item_id ]->get_recurring_sub_total();

			if ( $this->items[ $item_id ]->is_recurring() ) {
				$this->has_recurring = 0;
			}

			unset( $this->items[ $item_id ] );
		}

	}

	/**
	 * Returns the subtotal.
	 *
	 * @since 1.0.19
	 */
	public function get_subtotal() {

		if ( wpinv_prices_include_tax() ) {
			return $this->totals['subtotal']['initial'] - $this->totals['taxes']['initial'];
		}

		return $this->totals['subtotal']['initial'];
	}

	/**
	 * Returns the recurring subtotal.
	 *
	 * @since 1.0.19
	 */
	public function get_recurring_subtotal() {

		if ( wpinv_prices_include_tax() ) {
			return $this->totals['subtotal']['recurring'] - $this->totals['taxes']['recurring'];
		}

		return $this->totals['subtotal']['recurring'];
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

	/**
	 * Checks if there's a single subscription group in the submission.
	 *
	 * @since 2.3.0
	 * @return bool
	 */
	public function has_subscription_group() {
		return $this->has_recurring && getpaid_should_group_subscriptions( $this ) && 1 == count( getpaid_get_subscription_groups( $this ) );
	}

	/**
	 * Checks if there are multipe subscription groups in the submission.
	 *
	 * @since 2.3.0
	 * @return bool
	 */
	public function has_multiple_subscription_groups() {
		return $this->has_recurring && 1 < count( getpaid_get_subscription_groups( $this ) );
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

		// Abort if we're not using taxes.
		if ( ! $this->use_taxes() ) {
			return;
		}

		// If a custom country && state has been passed in, use it to calculate taxes.
		$country = $this->get_field( 'wpinv_country', 'billing' );
		if ( ! empty( $country ) ) {
			$this->country = $country;
		}

		$state = $this->get_field( 'wpinv_state', 'billing' );
		if ( ! empty( $state ) ) {
			$this->state = $state;
		}

		// Confirm if the provided country and the ip country are similar.
		$address_confirmed = $this->get_field( 'confirm-address' );
		if ( isset( $_POST['billing']['country'] ) && wpinv_should_validate_vat_number() && getpaid_get_ip_country() != $this->country && empty( $address_confirmed ) ) {
			throw new Exception( __( 'The country of your current location must be the same as the country of your billing location or you must confirm the billing address is your home country.', 'invoicing' ) );
		}

		// Abort if the country is not taxable.
		if ( ! wpinv_is_country_taxable( $this->country ) ) {
			return;
		}

		$processor = new GetPaid_Payment_Form_Submission_Taxes( $this );

		foreach ( $processor->taxes as $tax ) {
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

		if ( wpinv_round_tax_per_tax_rate() ) {
			$tax['initial_tax']   = wpinv_round_amount( $tax['initial_tax'] );
			$tax['recurring_tax'] = wpinv_round_amount( $tax['recurring_tax'] );
		}

		$this->taxes[ $tax['name'] ]         = $tax;
		$this->totals['taxes']['initial']   += wpinv_sanitize_amount( $tax['initial_tax'] );
		$this->totals['taxes']['recurring'] += wpinv_sanitize_amount( $tax['recurring_tax'] );

	}

	/**
	 * Removes a specific tax.
	 *
	 * @since 1.0.19
	 */
	public function remove_tax( $tax_name ) {

		if ( isset( $this->taxes[ $tax_name ] ) ) {
			$this->totals['taxes']['initial']   -= $this->taxes[ $tax_name ]['initial_tax'];
			$this->totals['taxes']['recurring'] -= $this->taxes[ $tax_name ]['recurring_tax'];
			unset( $this->taxes[ $tax_name ] );
		}

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
	 * Returns the tax.
	 *
	 * @since 1.0.19
	 */
	public function get_tax() {
		return $this->totals['taxes']['initial'];
	}

	/**
	 * Returns the recurring tax.
	 *
	 * @since 1.0.19
	 */
	public function get_recurring_tax() {
		return $this->totals['taxes']['recurring'];
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

		$initial_total    = $this->get_subtotal() + $this->get_fee() + $this->get_tax();
		$recurring_total  = $this->get_recurring_subtotal() + $this->get_recurring_fee() + $this->get_recurring_tax();
		$processor        = new GetPaid_Payment_Form_Submission_Discount( $this, $initial_total, $recurring_total );

		foreach ( $processor->discounts as $discount ) {
			$this->add_discount( $discount );
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
		$this->discounts[ $discount['name'] ]   = $discount;
		$this->totals['discount']['initial']   += wpinv_sanitize_amount( $discount['initial_discount'] );
		$this->totals['discount']['recurring'] += wpinv_sanitize_amount( $discount['recurring_discount'] );
	}

	/**
	 * Removes a discount from the submission.
	 *
	 * @since 1.0.19
	 */
	public function remove_discount( $name ) {

		if ( isset( $this->discounts[ $name ] ) ) {
			$this->totals['discount']['initial']   -= $this->discounts[ $name ]['initial_discount'];
			$this->totals['discount']['recurring'] -= $this->discounts[ $name ]['recurring_discount'];
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
	 * Returns the discount.
	 *
	 * @since 1.0.19
	 */
	public function get_discount() {
		return $this->totals['discount']['initial'];
	}

	/**
	 * Returns the recurring discount.
	 *
	 * @since 1.0.19
	 */
	public function get_recurring_discount() {
		return $this->totals['discount']['recurring'];
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

		$this->fees[ $fee['name'] ]         = $fee;
		$this->totals['fees']['initial']   += wpinv_sanitize_amount( $fee['initial_fee'] );
		$this->totals['fees']['recurring'] += wpinv_sanitize_amount( $fee['recurring_fee'] );

	}

	/**
	 * Removes a fee from the submission.
	 *
	 * @since 1.0.19
	 */
	public function remove_fee( $name ) {

		if ( isset( $this->fees[ $name ] ) ) {
			$this->totals['fees']['initial']   -= $this->fees[ $name ]['initial_fee'];
			$this->totals['fees']['recurring'] -= $this->fees[ $name ]['recurring_fee'];
			unset( $this->fees[ $name ] );
		}

	}

	/**
	 * Returns the fees.
	 *
	 * @since 1.0.19
	 */
	public function get_fee() {
		return $this->totals['fees']['initial'];
	}

	/**
	 * Returns the recurring fees.
	 *
	 * @since 1.0.19
	 */
	public function get_recurring_fee() {
		return $this->totals['fees']['recurring'];
	}

	/**
	 * Returns all fees.
	 *
	 * @since 1.0.19
	 */
	public function get_fees() {
		return $this->fees;
	}

	/**
	 * Checks if there are any fees for the form.
	 *
	 * @return bool
	 * @since 1.0.19
	 */
	public function has_fees() {
		return count( $this->fees ) !== 0;
	}

	/*
	|--------------------------------------------------------------------------
	| MISC
	|--------------------------------------------------------------------------
	|
	| Extra submission functions.
    */

	/**
	 * Checks if this is the initial fetch.
	 *
	 * @return bool
	 * @since 1.0.19
	 */
	public function is_initial_fetch() {
		return empty( $this->data['initial_state'] );
	}

	/**
	 * Returns the total amount to collect for this submission.
	 *
	 * @since 1.0.19
	 */
	public function get_total() {
		$total = $this->get_subtotal() + $this->get_fee() + $this->get_tax() - $this->get_discount();
		return max( $total, 0 );
	}

	/**
	 * Returns the recurring total amount to collect for this submission.
	 *
	 * @since 1.0.19
	 */
	public function get_recurring_total() {
		$total = $this->get_recurring_subtotal() + $this->get_recurring_fee() + $this->get_recurring_tax() - $this->get_recurring_discount();
		return max( $total, 0 );
	}

	/**
	 * Whether payment details should be collected for this submission.
	 *
	 * @since 1.0.19
	 */
	public function should_collect_payment_details() {
		$initial   = $this->get_total();
		$recurring = $this->get_recurring_total();

		if ( $this->has_recurring == 0 ) {
			$recurring = 0;
		}

		$collect = $initial > 0 || $recurring > 0;
		return apply_filters( 'getpaid_submission_should_collect_payment_details', $collect, $this  );
	}

	/**
	 * Returns the billing email of the user.
	 *
	 * @since 1.0.19
	 */
	public function get_billing_email() {
		return apply_filters( 'getpaid_get_submission_billing_email', $this->get_field( 'billing_email' ), $this  );
	}

	/**
	 * Checks if the submitter has a billing email.
	 *
	 * @since 1.0.19
	 */
	public function has_billing_email() {
		$billing_email = $this->get_billing_email();
		return ! empty( $billing_email ) && is_email( $billing_email );
	}

	/**
	 * Returns the appropriate currency for the submission.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_currency() {
		return $this->has_invoice() ? $this->invoice->get_currency() : wpinv_get_currency();
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
	 * Returns a field from the submission data
	 *
	 * @param string $field
	 * @since 1.0.19
	 * @return mixed|null
	 */
	public function get_field( $field, $sub_array_key = null ) {
		return getpaid_get_array_field( $this->data, $field, $sub_array_key );
	}

	/**
	 * Checks if a required field is set.
	 *
	 * @since 1.0.19
	 */
	public function is_required_field_set( $field ) {
		return empty( $field['required'] ) || ! empty( $this->data[ $field['id'] ] );
	}

	/**
	 * Formats an amount
	 *
	 * @since 1.0.19
	 */
	public function format_amount( $amount ) {
		return wpinv_price( $amount, $this->get_currency() );
	}

}
