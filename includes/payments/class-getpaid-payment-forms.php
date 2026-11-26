<?php
/**
 * Contains the payment forms controller class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment forms controller class
 *
 */
class GetPaid_Payment_Forms {

    /**
	 * Class constructor
	 *
	 */
	public function __construct() {

		// Update a payment form's revenue whenever an invoice is paid for or refunded.
		add_action( 'getpaid_invoice_payment_status_changed', array( $this, 'increment_form_revenue' ) );
		add_action( 'getpaid_invoice_payment_status_reversed', array( $this, 'decrease_form_revenue' ) );

		// Sync form amount whenever invoice statuses change.
		add_action( 'getpaid_invoice_status_changed', array( $this, 'update_form_failed_amount' ), 10, 3 );
		add_action( 'getpaid_invoice_status_changed', array( $this, 'update_form_refunded_amount' ), 10, 3 );
		add_action( 'getpaid_invoice_status_changed', array( $this, 'update_form_cancelled_amount' ), 10, 3 );

	}

	/**
	 * Increments a form's revenue whenever there is a payment.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function increment_form_revenue( $invoice ) {

		$form = new GetPaid_Payment_Form( $invoice->get_payment_form() );
		if ( $form->get_id() ) {
			$form->set_earned( $form->get_earned() + $invoice->get_total() );
			$form->save();
		}

	}

	/**
	 * Decreases form revenue whenever invoice payment changes.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function decrease_form_revenue( $invoice ) {

		$form = new GetPaid_Payment_Form( $invoice->get_payment_form() );
		if ( $form->get_id() ) {
			$form->set_earned( $form->get_earned() - $invoice->get_total() );
			$form->save();
		}

	}

	/**
	 * Updates a form's failed amount.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $from
	 * @param string $to
	 */
	public function update_form_failed_amount( $invoice, $from, $to ) {

		$form = new GetPaid_Payment_Form( $invoice->get_payment_form() );

		// Only for an invoice that came from a payment form which still exists. Saving an empty form would create one.
		if ( ! $form->get_id() ) {
			return;
		}

		if ( 'wpi-failed' == $from ) {
			$form->set_failed( max( 0, (float) $form->get_failed() - (float) $invoice->get_total() ) ); // Never below zero: the amount may predate this being counted.
			$form->save();
		}

		if ( 'wpi-failed' == $to ) {
			$form->set_failed( $form->get_failed() + $invoice->get_total() );
			$form->save();
		}

	}

	/**
	 * Updates a form's refunded amount.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $from
	 * @param string $to
	 */
	public function update_form_refunded_amount( $invoice, $from, $to ) {

		$form = new GetPaid_Payment_Form( $invoice->get_payment_form() );

		// Only for an invoice that came from a payment form which still exists. Saving an empty form would create one.
		if ( ! $form->get_id() ) {
			return;
		}

		if ( 'wpi-refunded' == $from ) {
			$form->set_refunded( max( 0, (float) $form->get_refunded() - (float) $invoice->get_total() ) ); // Never below zero: the amount may predate this being counted.
			$form->save();
		}

		if ( 'wpi-refunded' == $to ) {
			$form->set_refunded( $form->get_refunded() + $invoice->get_total() );
			$form->save();
		}

	}

	/**
	 * Updates a form's cancelled amount.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $from
	 * @param string $to
	 */
	public function update_form_cancelled_amount( $invoice, $from, $to ) {

		$form = new GetPaid_Payment_Form( $invoice->get_payment_form() );

		// Only for an invoice that came from a payment form which still exists. Saving an empty form would create one.
		if ( ! $form->get_id() ) {
			return;
		}

		if ( 'wpi-cancelled' == $from ) {
			$form->set_cancelled( max( 0, (float) $form->get_cancelled() - (float) $invoice->get_total() ) ); // Never below zero: the amount may predate this being counted.
			$form->save();
		}

		if ( 'wpi-cancelled' == $to ) {
			$form->set_cancelled( $form->get_cancelled() + $invoice->get_total() );
			$form->save();
		}

	}

}
