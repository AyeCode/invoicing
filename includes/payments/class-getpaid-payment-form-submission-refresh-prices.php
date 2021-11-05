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
			'submission_id'                    => $submission->id,
            'has_recurring'                    => $submission->has_recurring,
			'has_subscription_group'           => $submission->has_subscription_group(),
			'has_multiple_subscription_groups' => $submission->has_multiple_subscription_groups(),
            'is_free'                          => ! $submission->should_collect_payment_details(),
		);

		$this->add_totals( $submission );
		$this->add_texts( $submission );
		$this->add_items( $submission );
		$this->add_fees( $submission );
		$this->add_discounts( $submission );
		$this->add_taxes( $submission );
		$this->add_gateways( $submission );
		$this->add_data( $submission );

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

				'initial_amt'   => wpinv_round_amount( $submission->get_total(), null, true ),
				'currency'      => $submission->get_currency(),

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
		$groups  = getpaid_get_subscription_groups( $submission );

		if ( $submission->has_recurring && 2 > count( $groups ) ) {

			$recurring = new WPInv_Item( $submission->has_recurring );
			$period    = getpaid_get_subscription_period_label( $recurring->get_recurring_period( true ), $recurring->get_recurring_interval(), '' );
			$main_item = reset( $groups );

			if ( $submission->get_total() == $submission->get_recurring_total() ) {
				$payable = "$payable / $period";
			} else if ( $main_item ) {

				$main_item = reset( $main_item );

				// Calculate the next renewal date.
				$_period      = $main_item->get_recurring_period( true );
				$_interval    = $main_item->get_recurring_interval();

				// If the subscription item has a trial period...
				if ( $main_item->has_free_trial() ) {
					$_period   = $main_item->get_trial_period( true );
					$_interval = $main_item->get_trial_interval();
				}

				$payable = sprintf(
					__( '%1$s (renews at %2$s / %3$s)', 'invoicing' ),
					$submission->format_amount( $submission->get_total() ),
					$submission->format_amount( $submission->get_recurring_total() ),
					$period
				);

				$payable .= sprintf(
					'<small class="text-muted form-text">%s</small>',
					sprintf(
						__( 'First renewal on %s', 'invoicing' ),
						getpaid_format_date( date( 'Y-m-d H:i:s', strtotime( "+$_interval $_period", current_time( 'timestamp' ) ) ) )
					)
				);

			} else {
				$payable = sprintf(
					__( '%1$s (renews at %2$s / %3$s)', 'invoicing' ),
					$submission->format_amount( $submission->get_total() ),
					$submission->format_amount( $submission->get_recurring_total() ),
					$period
				);
			}

		}

		$texts = array(
			'.getpaid-checkout-total-payable' => $payable,
		);

		foreach ( $submission->get_items() as $item ) {
			$item_id                                               = $item->get_id();
			$initial_price                                         = $submission->format_amount( $item->get_sub_total() - $item->item_discount );
			$recurring_price                                       = $submission->format_amount( $item->get_recurring_sub_total() - $item->recurring_item_discount );
			$texts[".item-$item_id .getpaid-form-item-price-desc"] = getpaid_item_recurring_price_help_text( $item, $submission->get_currency(), $initial_price, $recurring_price );
			$texts[".item-$item_id .getpaid-mobile-item-subtotal"] = sprintf( __( 'Subtotal: %s', 'invoicing' ), $submission->format_amount( $item->get_sub_total() ) );

			if ( $item->get_quantity() == 1 ) {
				$texts[".item-$item_id .getpaid-mobile-item-subtotal"] = '';
			}

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

        foreach ( $submission->get_items() as $item ) {
			$item_id           = $item->get_id();
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

		$taxes  = array();
		$markup = '';
        foreach ( $submission->get_taxes() as $name => $data ) {
			$name          = sanitize_text_field( $name );
			$amount        = $submission->format_amount( $data['initial_tax'] );
			$taxes[$name]  = $amount;
			$markup       .= "<small class='form-text'>$name : $amount</small>";
		}

		if ( wpinv_display_individual_tax_rates() && ! empty( $taxes ) ) {
			$this->response['texts']['.getpaid-form-cart-totals-total-tax'] = $markup;
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

				if (
					! getpaid_payment_gateway_supports( $gateway, 'subscription' )
					|| ( $this->response['has_subscription_group'] && ! getpaid_payment_gateway_supports( $gateway, 'single_subscription_group' ) )
					|| ( $this->response['has_multiple_subscription_groups'] && ! getpaid_payment_gateway_supports( $gateway, 'multiple_subscription_groups' ) ) ) {
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

	/**
	 * Adds data to a response for submission refresh prices.
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function add_data( $submission ) {

		$this->response = array_merge(
			$this->response,
			array(
				'js_data' => apply_filters(
					'getpaid_submission_js_data',
					array(
						'is_recurring' => $this->response['has_recurring'],
					),
					$submission
				)
			)
		);

	}

}
