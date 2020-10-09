<?php
/**
 * Processes taxes for a payment form submission.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment form submission taxes class
 *
 */
class GetPaid_Payment_Form_Submission_Taxes {

	/**
	 * The tax validation error.
	 * @var string
	 */
	public $tax_error;

	/**
	 * Submission taxes.
	 * @var array
	 */
	public $taxes = array();

	/**
	 * Initial tax.
	 * @var float
	 */
	protected $initial_tax = 0;

	/**
	 * Recurring tax.
	 * @var float
	 */
	protected $recurring_tax = 0;

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function __construct( $submission ) {

		// Make sure that taxes are enabled.
		if ( ! $submission->use_taxes() ) {
			return;
		}

		// Validate VAT number.
		$this->validate_vat( $submission );

		foreach ( $submission->get_items() as $item ) {
			$this->process_item_tax( $item, $submission );
		}

		// Process any existing invoice taxes.
		if ( $submission->has_invoice() ) {
			$this->taxes = $submission->get_invoice()->get_taxes();
		}

		// Add VAT.
		$this->taxes['vat'] = array(
			'name'          => 'vat',
			'initial_tax'   => $this->initial_tax,
			'recurring_tax' => $this->recurring_tax,
		);

	}

	/**
	 * Maybe process tax.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Form_Item $item
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function process_item_tax( $item, $submission ) {

		// Abort early if an error occurred.
		if ( ! empty( $this->tax_error ) ) {
			return;
		}

		$rate     = wpinv_get_tax_rate( $submission->country, $submission->state, $item->get_id() );
		$price    = $item->get_sub_total();
		$item_tax = $price * $rate * 0.01;

		if ( wpinv_prices_include_tax() ) {
			$item_tax = $price - ( $price - $price * $rate * 0.01 );
		}

		$this->initial_tax += $item_tax;

		if ( $item->is_recurring() ) {
			$this->recurring_tax += $item_tax;
		}

	}

	/**
	 * Sets an error without overwriting the previous error.
	 *
	 * @param string $error
	 */
	public function set_error( $error ) {
		if ( empty( $this->tax_error ) ) {
			$this->tax_error = $error;
		}
	}

	/**
	 * Checks if the submission has a digital item.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @since 1.0.19
	 * @return bool
	 */
	public function has_digital_item( $submission ) {

		foreach ( $submission->get_items() as $item ) {

			if ( 'digital' == $item->get_vat_rule() ) {
				return true;
				break;
			}

		}

		return false;
	}

	/**
	 * Checks if this is an eu purchase.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @param bool                            $has_digital
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_eu_transaction( $submission, $has_digital ) {

		// Both from EU.
		if ( getpaid_is_eu_state( $submission->country ) && ( getpaid_is_eu_state( wpinv_get_default_country() ) || $has_digital ) ) {
			return true;
		}

		// Both from GST.
		if ( getpaid_is_gst_country( $submission->country ) && getpaid_is_gst_country( wpinv_get_default_country() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the vat number.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @since 1.0.19
	 * @return string
	 */
	public function get_vat_number( $submission ) {

		$data = $submission->get_data();

		// Retrieve from the posted number.
		if ( ! empty( $data['wpinv_vat_number'] ) ) {
			return wpinv_clean( $data['wpinv_vat_number'] );
		}

		// Retrieve from the invoice.
		return $submission->has_invoice() ? $submission->get_invoice()->get_vat_number() : '';
	}

	/**
	 * Retrieves the company.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @since 1.0.19
	 * @return string
	 */
	public function get_company( $submission ) {

		$data = $submission->get_data();

		// Retrieve from the posted data.
		if ( ! empty( $data['wpinv_company'] ) ) {
			return wpinv_clean( $data['wpinv_company'] );
		}

		// Retrieve from the invoice.
		return $submission->has_invoice() ? $submission->get_invoice()->get_company() : '';
	}

	/**
	 * Retrieves the company.
	 *
	 * @param bool $ip_in_eu Whether the selected IP is from the EU
	 * @param bool $country_in_eu Whether the selected country is from the EU
	 * @since 1.0.19
	 * @return string
	 */
	public function requires_vat( $ip_in_eu, $country_in_eu ) {

		$prevent_b2c = wpinv_get_option( 'vat_prevent_b2c_purchase' );
		$prevent_b2c = ! empty( $prevent_b2c );
		$is_eu       = $ip_in_eu || $country_in_eu;

		return $prevent_b2c && $is_eu;
	}

	/**
	 * Validate VAT data.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 * @since 1.0.19
	 */
	public function validate_vat( $submission ) {

		$has_digital = $this->has_digital_item( $submission );
		$in_eu       = $this->is_eu_transaction( $submission, $has_digital );

		// Abort if we are not validating vat numbers.
		if ( ! $has_digital && ! $in_eu ) {
            return;
		}

		// Prepare variables.
		$vat_number  = $this->get_vat_number( $submission );
		$company     = $this->get_company( $submission );
		$ip_country  = WPInv_EUVat::get_country_by_ip();
        $is_eu       = getpaid_is_eu_state( $submission->country );
        $is_ip_eu    = getpaid_is_eu_state( $ip_country );

		// If we're preventing business to consumer purchases, ensure
		if ( $this->requires_vat( $is_ip_eu, $is_eu ) && empty( $vat_number ) ) {

			// Ensure that a vat number has been specified.
			return $this->set_error(
				wp_sprintf(
					__( 'Please enter your %s number to verify your purchase is by an EU business.', 'invoicing' ),
					getpaid_vat_name()
				)
			);

		}

		// Abort if we are not validating vat (vat number should exist, user should be in eu and business too).
		if ( ! $is_eu || ! $in_eu || empty( $vat_number ) ) {
            return;
		}

		$is_valid = WPInv_EUVat::validate_vat_number( $vat_number, $company, $submission->country );

		if ( is_string( $is_valid ) ) {
			$this->set_error( $is_valid );
		}

	}

}
