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

		// (Maybe) set form items.
		if ( isset( $data['getpaid-form-items'] ) ) {

			// Confirm items key.
			$form_items = wpinv_clean( $data['getpaid-form-items'] );
			if ( ! isset( $data['getpaid-form-items-key'] ) || $data['getpaid-form-items-key'] !== md5( NONCE_KEY . AUTH_KEY . $form_items ) ) {
				throw new Exception( __( 'We could not validate the form items. Please reload the page and try again.', 'invoicing' ) );
			}

			$items    = array();
            $item_ids = array();

            foreach ( getpaid_convert_items_to_array( $form_items ) as $item_id => $qty ) {
                if ( ! in_array( $item_id, $item_ids ) ) {
                    $item = new GetPaid_Form_Item( $item_id );
                    $item->set_quantity( $qty );

                    if ( 0 == $qty ) {
                        $item->set_allow_quantities( true );
                        $item->set_is_required( false );
                    }

                    $item_ids[] = $item->get_id();
                    $items[]    = $item;
                }
            }

            if ( ! $payment_form->is_default() ) {

                foreach ( $payment_form->get_items() as $item ) {
                    if ( ! in_array( $item->get_id(), $item_ids ) ) {
                        $item_ids[] = $item->get_id();
                        $items[]    = $item;
                    }
                }

            }

            $payment_form->set_items( $items );

		}

		// Process each individual item.
		foreach ( $payment_form->get_items() as $item ) {
			$this->process_item( $item, $selected_items, $submission );
		}

	}

	/**
	 * Process a single item.
	 *
	 * @param GetPaid_Form_Item $item
	 * @param array $selected_items
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function process_item( $item, $selected_items, $submission ) {

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
					throw new Exception( sprintf( __( 'The minimum allowed amount is %s', 'invoicing' ), getpaid_unstandardize_amount( $item->get_minimum_price() ) ) );
				}

				$item->set_price( $price );

			}

		}

		if ( 0 == $item->get_quantity() ) {
			return;
		}

		// Save the item.
		$this->items[] = apply_filters( 'getpaid_payment_form_submission_processed_item' , $item, $submission );

	}

}
