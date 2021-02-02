<?php
/**
 * Processes items for a payment form submission.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment form submission itemss class
 *
 */
class GetPaid_Payment_Form_Submission_Items {

	/**
	 * Submission items.
	 * @var GetPaid_Form_Item[]
	 */
	public $items = array();

    /**
	 * Class constructor
	 *
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function __construct( $submission ) {

		$data         = $submission->get_data();
		$payment_form = $submission->get_payment_form();

		// Prepare the selected items.
		$selected_items = array();
		if ( ! empty( $data['getpaid-items'] ) ) {
			$selected_items = wpinv_clean( $data['getpaid-items'] );
		}

		// For default forms, ensure that an item has been set.
		if ( $payment_form->is_default() && ! $submission->has_invoice() && isset( $data['getpaid-form-items'] ) ) {
			$form_items = wpinv_clean( $data['getpaid-form-items'] );
			$payment_form->set_items( getpaid_convert_items_to_array( $form_items ) );
		}

		// Process each individual item.
		foreach ( $payment_form->get_items() as $item ) {
			$this->process_item( $item, $selected_items );
		}

	}

	/**
	 * Process a single item.
	 *
	 * @param GetPaid_Form_Item $item
	 * @param array $selected_items
	 */
	public function process_item( $item, $selected_items ) {

		// Abort if this is an optional item and it has not been selected.
		if ( ! $item->is_required() && ! isset( $selected_items[ $item->get_id() ] ) ) {
			return;
		}

		// (maybe) let customers change the quantities and prices.
		if ( isset( $selected_items[ $item->get_id() ] ) ) {

			// Maybe change the quantities.
			if ( $item->allows_quantities() ) {
				$item->set_quantity( (float) $selected_items[ $item->get_id() ]['quantity'] );
			}

			// Maybe change the price.
			if ( $item->user_can_set_their_price() ) {
				$price = (float) wpinv_sanitize_amount( $selected_items[ $item->get_id() ]['price'] );

				if ( $item->get_minimum_price() > $price ) {
					throw new Exception( sprintf( __( 'The minimum allowed amount is %s', 'invoicing' ), wpinv_sanitize_amount( $item->get_minimum_price() ) ) );
				}

				$item->set_price( $price );

			}

		}

		// Save the item.
		$this->items[] = $item;

	}

}
