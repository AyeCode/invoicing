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
		$invoice      = $submission->get_invoice();
		$force_prices = array();

		// Prepare the selected items.
		$selected_items = array();
		if ( ! empty( $data['getpaid-items'] ) ) {
			$selected_items = wpinv_clean( $data['getpaid-items'] );

            if ( is_array( $submission->get_field( 'getpaid-variable-items' ) ) ) {
                $selected_prices = $submission->get_field( 'getpaid-variable-items' );

                $selected_items = array_filter(
                    $selected_items,
                    function ( $item ) use ( $selected_prices ) {
                        return isset( $item['price_id'] ) && in_array( (int) $item['price_id'], $selected_prices );
                    }
                );
            }

			if ( ! empty( $invoice ) && $submission->is_initial_fetch() ) {
				foreach ( $invoice->get_items() as $invoice_item ) {
                    if ( ! $invoice_item->has_variable_pricing() && isset( $selected_items[ $invoice_item->get_id() ] ) ) {
                        $selected_items[ $invoice_item->get_id() ]['quantity'] = $invoice_item->get_quantity();
                        $selected_items[ $invoice_item->get_id() ]['price']    = $invoice_item->get_price();

                        $force_prices[ $invoice_item->get_id() ] = $invoice_item->get_price();
                    }
				}
			}
		}

		// (Maybe) set form items.
		if ( isset( $data['getpaid-form-items'] ) ) {

			// Confirm items key.
			$form_items = wpinv_clean( $data['getpaid-form-items'] );
			if ( ! isset( $data['getpaid-form-items-key'] ) || md5( NONCE_KEY . AUTH_KEY . $form_items ) !== $data['getpaid-form-items-key'] ) {
				throw new Exception( __( 'We could not validate the form items. Please reload the page and try again.', 'invoicing' ) );
			}

			$items    = array();
            $item_ids = array();

            foreach ( getpaid_convert_items_to_array( $form_items ) as $item_id => $qty ) {
                if ( ! in_array( $item_id, $item_ids ) ) {
                    $item = new GetPaid_Form_Item( $item_id );

                    if ( ! $item->has_variable_pricing() ) {
                        $item->set_quantity( $qty );

                        if ( empty( $qty ) ) {
                            $item->set_allow_quantities( true );
                            $item->set_is_required( false );
                        }

                        if ( ! $item->user_can_set_their_price() && isset( $force_prices[ $item_id ] ) ) {
                            $item->set_is_dynamic_pricing( true );
                            $item->set_minimum_price( 0 );
                        }
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

        if ( $item->has_variable_pricing() ) {

            $selected_items = array_filter(
                $selected_items,
                function ( $selected_item ) use ( $item ) {
                    return (int) $selected_item['item_id'] === (int) $item->get_id();
                }
            );

            if ( ! $item->is_required() && ! count( $selected_items ) ) {
                return;
            }

            $price_options = $item->get_variable_prices();
            $price = current( $selected_items );

            $item->set_price_id( $price['price_id'] );
            $item->set_quantity( $price['quantity'] );

            $price = isset( $price_options[ $price['price_id'] ] ) ? $price_options[ $price['price_id'] ] : $price;
            $item->set_price( (float) $price['amount'] );

            if ( isset( $price['is-recurring'] ) && 'yes' === $price['is-recurring'] ) {
                if ( isset( $price['trial-interval'], $price['trial-period'] ) && $price['trial-interval'] > 0 ) {
                    $trial_interval = (int) $price['trial-interval'];
                    $trial_period = $price['trial-period'];

                    $item->set_is_free_trial( 1 );
                    $item->set_trial_interval( $trial_interval );
                    $item->set_trial_period( $trial_period );
                }

                if ( isset( $price['recurring-interval'], $price['recurring-period'] ) && $price['recurring-interval'] > 0 ) {
                    $recurring_interval = (int) $price['recurring-interval'];
                    $recurring_period = $price['recurring-period'];
                    $recurring_limit = isset( $price['recurring-limit'] ) ? (int) $price['recurring-limit'] : 0;

                    $item->set_is_recurring( 1 );
                    $item->set_recurring_interval( $recurring_interval );
                    $item->set_recurring_period( $recurring_period );
                    $item->set_recurring_limit( $recurring_limit );
                }
            }
        } else {
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
        }

		if ( 0 == $item->get_quantity() ) {
			return;
		}

		// Save the item.
		$this->items[] = apply_filters( 'getpaid_payment_form_submission_processed_item', $item, $submission );
	}
}
