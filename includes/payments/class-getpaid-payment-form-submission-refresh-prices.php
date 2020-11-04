<?php
/**
 * Generates the AJAX response for refreshing submission prices.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment form submission refresh prices class
 *
 */
class GetPaid_Payment_Form_Submission_Refresh_Prices {

	/**
	 * Contains the response for refreshing prices.
	 * @var array
	 */
	public $response = array();

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function __construct( $submission ) {

		$this->response = array(
			'submission_id' => $submission->id,
            'has_recurring' => $submission->has_recurring,
            'is_free'       => ! $submission->should_collect_payment_details(),
		);

		$this->add_totals( $submission );
		$this->add_texts( $submission );
		$this->add_items( $submission );
		$this->add_fees( $submission );
		$this->add_discounts( $submission );
		$this->add_taxes( $submission );
		$this->add_gateways( $submission );

	}

	/**
	 * Adds totals to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_totals( $submission ) {

		$this->response = array_merge(
			$this->response,
			array(

				'totals'        => array(
					'subtotal'  => $submission->format_amount( $submission->get_subtotal() ),
					'discount'  => $submission->format_amount( $submission->get_discount() ),
					'fees'      => $submission->format_amount( $submission->get_fee() ),
					'tax'       => $submission->format_amount( $submission->get_tax() ),
					'total'     => $submission->format_amount( $submission->get_total() ),
					'raw_total' => html_entity_decode( sanitize_text_field( $submission->format_amount( $submission->get_total() ) ), ENT_QUOTES ),
				),

				'recurring'     => array(
					'subtotal'  => $submission->format_amount( $submission->get_recurring_subtotal() ),
					'discount'  => $submission->format_amount( $submission->get_recurring_discount() ),
					'fees'      => $submission->format_amount( $submission->get_recurring_fee() ),
					'tax'       => $submission->format_amount( $submission->get_recurring_tax() ),
					'total'     => $submission->format_amount( $submission->get_recurring_total() ),
				),

			)
		);

	}

	/**
	 * Adds texts to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_texts( $submission ) {

		$payable = $submission->format_amount( $submission->get_total() );

		if ( $submission->has_recurring != 0 ) {

			$recurring = new WPInv_Item( $submission->has_recurring );
			$period    = getpaid_get_subscription_period_label( $recurring->get_recurring_period( true ), $recurring->get_recurring_interval(), '' );

			if ( $submission->get_total() == $submission->get_recurring_total() ) {
				$payable = "$payable / $period";
			} else {
				$payable = sprintf(
					__( '%1$s (renews at %2$s / %3$s)'),
					$submission->format_amount( $submission->get_total() ),
					$submission->format_amount( $submission->get_recurring_total() ),
					$period
				);
			}

		}

		$texts = array(
			'.getpaid-checkout-total-payable' => $payable,
		);

		foreach ( $submission->get_items() as $item_id => $item ) {
			$texts[".item-$item_id .getpaid-item-initial-price"]   = $submission->format_amount( $item->get_sub_total() );
			$texts[".item-$item_id .getpaid-item-recurring-price"] = $submission->format_amount( $item->get_recurring_sub_total() );
		}

		$this->response = array_merge( $this->response, array( 'texts' => $texts ) );

	}

	/**
	 * Adds items to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_items( $submission ) {

		// Add items.
		$items = array();

        foreach ( $submission->get_items() as $item_id => $item ) {
			$items["$item_id"] = $submission->format_amount( $item->get_sub_total() );
		}

		$this->response = array_merge(
			$this->response,
			array( 'items' => $items )
		);

	}

	/**
	 * Adds fees to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_fees( $submission ) {

		$fees = array();

        foreach ( $submission->get_fees() as $name => $data ) {
			$fees[$name] = $submission->format_amount( $data['initial_fee'] );
		}

		$this->response = array_merge(
			$this->response,
			array( 'fees' => $fees )
		);

	}

	/**
	 * Adds discounts to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_discounts( $submission ) {

		$discounts = array();

        foreach ( $submission->get_discounts() as $name => $data ) {
			$discounts[$name] = $submission->format_amount( $data['initial_discount'] );
		}

		$this->response = array_merge(
			$this->response,
			array( 'discounts' => $discounts )
		);

	}

	/**
	 * Adds taxes to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_taxes( $submission ) {

		$taxes = array();

        foreach ( $submission->get_taxes() as $name => $data ) {
			$taxes[$name] = $submission->format_amount( $data['initial_tax'] );
		}

		$this->response = array_merge(
			$this->response,
			array( 'taxes' => $taxes )
		);

	}

	/**
	 * Adds gateways to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_gateways( $submission ) {

		$gateways = array_keys( wpinv_get_enabled_payment_gateways() );

		if ( $this->response['has_recurring'] ) {

			foreach ( $gateways as $i => $gateway ) {

				if ( ! wpinv_gateway_support_subscription( $gateway ) ) {
					unset( $gateways[ $i ] );
				}

			}

		}


		$gateways = apply_filters( 'getpaid_submission_gateways', $gateways, $submission );
		$this->response = array_merge(
			$this->response,
			array( 'gateways' => $gateways )
		);

	}

}
