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
	 * The discount associated with the submission.
	 *
	 * @var WPInv_Discount
	 */
	protected $discount = null;

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
	 * The total tax amount for the submission.
	 *
	 * @var float
	 */
	protected $total_tax_amount = 0;

	/**
	 * The total fees amount for the submission.
	 *
	 * @var float
	 */
	protected $total_fees_amount = 0;

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
	 * Is the discount valid?
	 *
	 * @var bool
	 */
	public $is_discount_valid = true;
	
	/**
	 * Checks if we have a digital vat rule.
	 *
	 * @var bool
	 */
	public $has_digital = false;
	
	/**
	 * Checks if we require vat.
	 *
	 * @var bool
	 */
    public $requires_vat = false;

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
        } else if ( $form->is_default() && isset( $data['form_items'] ) ) {
			$this->payment_form->set_items( $data['form_items'] );
		}

		// User's country.
		if ( ! empty( $data['wpinv_country'] ) ) {
			$this->country = $data['wpinv_country'];
		}

		// User's state.
		if ( ! empty( $data['wpinv_state'] ) ) {
			$this->country = $data['wpinv_state'];
		}

		// Handle discounts.
		$this->maybe_prepare_discount();

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

		// (Maybe) validate vat number.
		$this->maybe_validate_vat();

		// Fired when we are done processing a submission.
		do_action_ref_array( 'getpaid_process_submission', array( &$this ) );

		// Remove invalid discount.
		$this->maybe_remove_discount();

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
	 * Retrieves the vat number.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_vat_number() {

		// Retrieve from the posted data.
		if ( ! empty( $this->data['wpinv_vat_number'] ) ) {
			return wpinv_clean( $this->data['wpinv_vat_number'] );
		}

		// Retrieve from the invoice.
		return $this->has_invoice() ? $this->invoice->get_vat_number() : '';
	}

	/**
	 * Retrieves the company.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_company() {

		// Retrieve from the posted data.
		if ( ! empty( $this->data['wpinv_company'] ) ) {
			return wpinv_clean( $this->data['wpinv_company'] );
		}

		// Retrieve from the invoice.
		return $this->has_invoice() ? $this->invoice->get_company() : '';
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

		$this->process_item_discount( $item );

		$this->process_item_tax( $item );
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

	///////// TAXES //////////////

	/**
	 * Adds a tax to the submission.
	 *
	 * @since 1.0.19
	 */
	public function add_tax( $name, $amount ) {
		$amount = (float) wpinv_sanitize_amount( $amount );

		$this->total_tax_amount += $amount;

		if ( isset( $this->taxes[ $name ] ) ) {
			$this->taxes[ $name ] += $amount;
		} else {
			$this->taxes[ $name ] = $amount;
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
	 * Maybe process tax.
	 *
	 * @since 1.0.19 
	 * @param GetPaid_Form_Item $item
	 */
	public function process_item_tax( $item ) {

		// Abort early if we're not using taxes.
		if ( ! $this->use_taxes() ) {
			return;
		}

		$rate  = wpinv_get_tax_rate( $this->country, $this->state, $item->get_id() );
		$price = $item->get_sub_total();

		if ( wpinv_prices_include_tax() ) {
			$item_tax = $price - ( $price - $price * $rate * 0.01 );
		} else {
			$item_tax = $price * $rate * 0.01;
		}

		$this->add_tax( 'Tax', $item_tax );

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
	 * Retrieves a specific tax.
	 *
	 * @since 1.0.19
	 */
	public function get_tax( $name ) {
		return isset( $this->taxes[ $name ] ) ? $this->taxes[ $name ] : 0;
	}

	/**
	 * Returns all taxes.
	 *
	 * @since 1.0.19
	 */
	public function get_taxes() {
		return $this->taxes;
	}

	///////// DISCOUNTS //////////////

	/**
	 * Adds a discount to the submission.
	 *
	 * @since 1.0.19
	 */
	public function add_discount( $name, $amount ) {
		$amount = wpinv_sanitize_amount( $amount );

		$this->total_discount_amount += $amount;

		if ( isset( $this->discounts[ $name ] ) ) {
			$this->discounts[ $name ] += $amount;
		} else {
			$this->discounts[ $name ] = $amount;
		}

	}

	/**
	 * Removes a discount from the submission.
	 *
	 * @since 1.0.19
	 */
	public function remove_discount( $name ) {

		if ( isset( $this->discounts[ $name ] ) ) {
			$this->total_discount_amount -= $this->discounts[ $name ];
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
		return ! empty( $this->discount );
	}

	/**
	 * Returns the discount code.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function get_discount_code() {
		return $this->has_discount_code() ? $this->discount->code : '';
	}

	/**
	 * Prepares an item discount.
	 *
	 * @since 1.0.19
	 */
	public function maybe_prepare_discount() {

		// Do we have a discount?
		if ( empty( $this->data['discount'] ) ) {
			return;
		}

		// Fetch the discount.
		$discount = wpinv_get_discount_obj( $this->data['discount'] );

		// Ensure it is active.
        if ( ! $discount->exists() || ! $discount->is_active() || ! $discount->has_started() || $discount->is_expired() ) {
			$this->is_discount_valid = false;
			$this->last_error = __( 'Invalid or expired discount code', 'invoicing' );
			return;
		}

		// For single use discounts...
		if ( $discount->is_single_use ) {

			if ( ! $this->has_billing_email() ) {
				$this->is_discount_valid = false;
				$this->last_error = __( 'You need to enter your billing email before applying this discount', 'invoicing' );
				return;
			}

			if ( ! $discount->is_valid_for_user( $this->get_billing_email() ) ) {
				$this->is_discount_valid = false;
				$this->last_error = __( 'You have already used this discount', 'invoicing' );
				return;
			}

		}

		// Set the discount.
		$this->discount = $discount;

	}

	/**
	 * Removes an invalid discount code.
	 *
	 * @since 1.0.19
	 */
	public function maybe_remove_discount() {

		// Do we have a discount?
		if ( empty( $this->has_discount_code() ) ) {
			return;
		}

		// Fetch the discount amount.
		$amount = $this->get_discount( 'Discount' );

		// Abort early if this is a "zero" discount.
		if ( empty( $amount ) ) {
			return;
		}

		$total = $this->subtotal_amount + $this->get_total_fees() + $this->get_total_tax();

		if ( ! $this->discount->is_minimum_amount_met( $total ) ) {
			$this->is_discount_valid = false;
            $min = wpinv_price( wpinv_format_amount( $$this->discount->min_total ) );
			$this->last_error = sprintf( __( 'The minimum total for using this discount is %s', 'invoicing' ), $min );
        }

        if ( ! $this->discount->is_maximum_amount_met( $total ) ) {
			$this->is_discount_valid = false;
            $max = wpinv_price( wpinv_format_amount( $$this->discount->max_total ) );
			$this->last_error = sprintf( __( 'The maximum total for using this discount is %s', 'invoicing' ), $max );
		}

		if ( ! $this->is_discount_valid ) {
			$this->discount = null;
			$this->remove_discount( 'Discount' );
		}

    }

	/**
	 * Maybe process discount.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Form_Item $item
	 */
	public function process_item_discount( $item ) {

		// Abort early if there is no discount.
		if ( ! $this->has_discount_code() ) {
			return;
		}

		// Ensure that it is valid for this item.
		if ( ! $this->discount->is_valid_for_items( array( $item->get_id() ) ) ) {
			return;
		}

		// Fetch the discounted amount.
		$discount = $this->discount->get_discounted_amount( $item->get_price() * $item->get_quantity() );

		$this->add_discount( 'Discount', $discount );

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
	 * Gets a specific discount.
	 *
	 * @since 1.0.19
	 */
	public function get_discount( $name ) {
		return isset( $this->discounts[ $name ] ) ? $this->discounts[ $name ] : 0;
	}

	/**
	 * Returns all discounts.
	 *
	 * @since 1.0.19
	 */
	public function get_discounts() {
		return $this->discounts;
	}

	///////// FEES //////////////

	/**
	 * Adds a fee to the submission.
	 *
	 * @since 1.0.19
	 */
	public function add_fee( $name, $amount ) {
		$amount = wpinv_sanitize_amount( $amount );

		$this->total_fees_amount += $amount;

		if ( isset( $this->fees[ $name ] ) ) {
			$this->fees[ $name ] += $amount;
		} else {
			$this->fees[ $name ] = $amount;
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
	 * Retrieves a specific fee.
	 *
	 * @since 1.0.19
	 */
	public function get_fee( $name ) {
		return isset( $this->fees[ $name ] ) ? $this->fees[ $name ] : 0;
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

	/**
	 * Validate VAT data.
	 *
	 * @since 1.0.19
	 */
	public function maybe_validate_vat() {
		
		// Make sure that taxes are enabled.
		if ( ! wpinv_use_taxes() ) {
			return;
		}

		// Check if we have a digital VAT rule.
		$has_digital = false;

		foreach ( $this->get_items() as $item ) {

			if ( 'digital' == $item->get_vat_rule() ) {
				$has_digital = true;
				break;
			}

		}

		$this->has_digital = $has_digital;

		// Check if we require vat.
		$requires_vat = (
			( getpaid_is_eu_state( $this->country ) && ( getpaid_is_eu_state( wpinv_get_default_country() ) || $has_digital ) )
			|| ( getpaid_is_gst_country( $this->country ) && getpaid_is_gst_country( wpinv_get_default_country() ) )
		);

		$this->requires_vat = $requires_vat;

		// Abort if we are not calculating the taxes.
		if ( ! $has_digital && ! $requires_vat ) {
            return;
		}

		// Prepare variables.
		$vat_number = $this->get_vat_number();
		$company    = $this->get_company();
		$ip_country = WPInv_EUVat::get_country_by_ip();
        $is_eu      = getpaid_is_eu_state( $this->country );
        $is_ip_eu   = getpaid_is_eu_state( $ip_country );
		$is_non_eu  = ! $is_eu && ! $is_ip_eu;
		$prevent_b2c = wpinv_get_option( 'vat_prevent_b2c_purchase' );

		// If we're preventing business to consumer purchases...
		if ( ! empty( $prevent_b2c ) && ! $is_non_eu && ( empty( $vat_number ) || ! $requires_vat ) ) {

            if ( $is_eu ) {
				$this->last_error = wp_sprintf(
					__( 'Please enter your %s number to verify your purchase is by an EU business.', 'invoicing' ),
					getpaid_vat_name()
				);
            } else if ( $has_digital && $is_ip_eu ) {

				$this->last_error = wp_sprintf(
					__( 'Sales to non-EU countries cannot be completed because %s must be applied.', 'invoicing' ),
					getpaid_vat_name()
				);

			}

		}
		
		// Abort if we are not validating vat.
		if ( ! $is_eu || ! $requires_vat || empty( $vat_number ) ) {
            return;
		}

		$is_valid = WPInv_EUVat::validate_vat_number( $vat_number, $company, $this->country );

		if ( is_string( $is_valid ) ) {
			$this->last_error = $is_valid;
		}

	}

}
