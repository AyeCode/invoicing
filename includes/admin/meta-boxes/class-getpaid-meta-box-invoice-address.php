<?php

/**
 * Invoice Address
 *
 * Display the invoice address meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Address Class.
 */
class GetPaid_Meta_Box_Invoice_Address {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {

		// Prepare the invoice.
		$invoice  = new WPInv_Invoice( $post );
		$customer = $invoice->exists() ? $invoice->get_user_id( 'edit' ) : get_current_user_id();
		$customer = new WP_User( $customer );
		$display  = sprintf( _x( '%1$s (%2$s)', 'user dropdown', 'invoicing' ), $customer->display_name, $customer->user_email );
		wp_nonce_field( 'getpaid_meta_nonce', 'getpaid_meta_nonce' );

		// Address fields.
		$address_fields = array(
			'first_name' => array(
				'label' => __( 'First Name', 'invoicing' ),
				'type'  => 'text',
			),
			'last_name'  => array(
				'label' => __( 'Last Name', 'invoicing' ),
				'type'  => 'text',
			),
			'company'    => array(
				'label' => __( 'Company', 'invoicing' ),
				'type'  => 'text',
				'class' => 'getpaid-recalculate-prices-on-change',
			),
			'vat_number' => array(
				'label' => __( 'VAT Number', 'invoicing' ),
				'type'  => 'text',
				'class' => 'getpaid-recalculate-prices-on-change',
			),
			'address'    => array(
				'label' => __( 'Address', 'invoicing' ),
				'type'  => 'text',
			),
			'city'       => array(
				'label' => __( 'City', 'invoicing' ),
				'type'  => 'text',
			),
			'country'    => array(
				'label'       => __( 'Country', 'invoicing' ),
				'type'        => 'select',
				'class'       => 'getpaid-recalculate-prices-on-change',
				'options'     => wpinv_get_country_list(),
				'placeholder' => __( 'Choose a country', 'invoicing' ),
			),
			'state'      => array(
				'label' => __( 'State', 'invoicing' ),
				'type'  => 'text',
				'class' => 'getpaid-recalculate-prices-on-change',
			),
			'zip'        => array(
				'label' => __( 'Zip', 'invoicing' ),
				'type'  => 'text',
			),
			'phone'      => array(
				'label' => __( 'Phone', 'invoicing' ),
				'type'  => 'text',
			),
		);

		$states = wpinv_get_country_states( $invoice->get_country( 'edit' ) );

		if ( ! empty( $states ) ) {
			$address_fields['state']['type']        = 'select';
			$address_fields['state']['options']     = $states;
			$address_fields['state']['placeholder'] = __( 'Choose a state', 'invoicing' );
		}

		// Maybe remove the VAT field.
		if ( ! wpinv_use_taxes() ) {
			unset( $address_fields['vat_number'] );
		}

		$address_fields = apply_filters( 'getpaid_admin_edit_invoice_address_fields', $address_fields, $invoice );
		?>

		<style>
			#wpinv-address label {
				margin-bottom: 3px;
				font-weight: 600;
			}
		</style>
			<div class="bsui" style="margin-top: 1.5rem; max-width: 820px;">
					<div class="row">
						<div class="col-12 col-sm-6">
							<div id="getpaid-invoice-user-id-wrapper" class="form-group mb-3">
								<div>
									<label for="post_author_override"><?php esc_html_e( 'Customer', 'invoicing' ); ?></label>
								</div>
								<div>
									<select name="post_author_override" id="wpinv_post_author_override" class="getpaid-customer-search form-control regular-text" data-placeholder="<?php esc_attr_e( 'Search for a customer by email or name', 'invoicing' ); ?>">
										<option selected="selected" value="<?php echo (int) $customer->ID; ?>"><?php echo esc_html( $display ); ?> </option>)
									</select>
								</div>
							</div>

							<div id="getpaid-invoice-email-wrapper" class="d-none">
								<input type="hidden" id="getpaid-invoice-create-new-user" name="wpinv_new_user" value="" />
								<?php
									aui()->input(
										array(
											'type'        => 'text',
											'id'          => 'getpaid-invoice-new-user-email',
											'name'        => 'wpinv_email',
											'label'       => __( 'Email', 'invoicing' ) . '<span class="required">*</span>',
											'label_type'  => 'vertical',
											'placeholder' => 'john@doe.com',
											'class'       => 'form-control-sm',
										),
										true
									);
								?>
							</div>
						</div>
						<div class="col-12 col-sm-6 form-group mb-3 mt-sm-4">
							<?php if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) : ?>
								<a id="getpaid-invoice-fill-user-details" class="button button-small button-secondary" href="javascript:void(0)">
									<i aria-hidden="true" class="fa fa-refresh"></i>
									<?php esc_html_e( 'Fill User Details', 'invoicing' ); ?>
								</a>
								<a id="getpaid-invoice-create-new-user-button" class="button button-small button-secondary" href="javascript:void(0)">
									<i aria-hidden="true" class="fa fa-plus"></i>
									<?php esc_html_e( 'Add New User', 'invoicing' ); ?>
								</a>
								<a id="getpaid-invoice-cancel-create-new-user" class="button button-small button-secondary d-none" href="javascript:void(0)">
									<i aria-hidden="true" class="fa fa-close"></i>
									<?php esc_html_e( 'Cancel', 'invoicing' ); ?>
								</a>
							<?php endif; ?>
						</div>

						<?php foreach ( $address_fields as $key => $field ) : ?>
							<div class="col-12 col-sm-6 getpaid-invoice-address-field__<?php echo esc_attr( $key ); ?>--wrapper">
								<?php

									if ( 'select' === $field['type'] ) {
										aui()->select(
											array(
												'id'               => 'wpinv_' . $key,
												'name'             => 'wpinv_' . $key,
												'label'            => $field['label'],
												'label_type'       => 'vertical',
												'placeholder'      => isset( $field['placeholder'] ) ? $field['placeholder'] : '',
												'class'            => 'form-control-sm ' . ( isset( $field['class'] ) ? $field['class'] : '' ),
												'value'            => $invoice->get( $key, 'edit' ),
												'options'          => $field['options'],
												'data-allow-clear' => 'false',
												'select2'          => true,
											),
											true
										);
									} else {
										aui()->input(
											array(
												'type'        => $field['type'],
												'id'          => 'wpinv_' . $key,
												'name'        => 'wpinv_' . $key,
												'label'       => $field['label'],
												'label_type'  => 'vertical',
												'placeholder' => isset( $field['placeholder'] ) ? $field['placeholder'] : '',
												'class'       => 'form-control-sm ' . ( isset( $field['class'] ) ? $field['class'] : '' ),
												'value'       => $invoice->get( $key, 'edit' ),
											),
											true
										);
									}

								?>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( ! apply_filters( 'getpaid_use_new_invoice_items_metabox', false ) ) : ?>
						<?php do_action( 'wpinv_meta_box_before_invoice_template_row', $invoice->get_id() ); ?>

						<div class="row">
							<div class="col-12 col-sm-6">
								<?php
									aui()->select(
										array(
											'id'          => 'wpinv_template',
											'name'        => 'wpinv_template',
											'label'       => __( 'Template', 'invoicing' ),
											'label_type'  => 'vertical',
											'placeholder' => __( 'Choose a template', 'invoicing' ),
											'class'       => 'form-control-sm',
											'value'       => $invoice->get_template( 'edit' ),
											'options'     => array(
												'quantity' => __( 'Quantity', 'invoicing' ),
												'hours'    => __( 'Hours', 'invoicing' ),
											),
											'data-allow-clear' => 'false',
											'select2'     => true,
										),
										true
									);
								?>
							</div>
							<div class="col-12 col-sm-6">
								<?php

									// Set currency.
									aui()->select(
										array(
											'id'          => 'wpinv_currency',
											'name'        => 'wpinv_currency',
											'label'       => __( 'Currency', 'invoicing' ),
											'label_type'  => 'vertical',
											'placeholder' => __( 'Select Invoice Currency', 'invoicing' ),
											'class'       => 'form-control-sm getpaid-recalculate-prices-on-change',
											'value'       => $invoice->get_currency( 'edit' ),
											'required'    => false,
											'data-allow-clear' => 'false',
											'select2'     => true,
											'options'     => wpinv_get_currencies(),
										),
										true
									);

								?>
							</div>
						</div>

						<?php do_action( 'wpinv_meta_box_invoice_template_row', $invoice->get_id() ); ?>
					<?php endif; ?>

					<div class="row">
						<div class="col-12 col-sm-6">
							<?php
								aui()->input(
									array(
										'type'        => 'text',
										'id'          => 'wpinv_company_id',
										'name'        => 'wpinv_company_id',
										'label'       => __( 'Company ID', 'invoicing' ),
										'label_type'  => 'vertical',
										'placeholder' => '',
										'class'       => 'form-control-sm',
										'value'       => $invoice->get_company_id( 'edit' ),
									),
									true
								);
							?>
						</div>
					</div>

					<?php do_action( 'getpaid_after_metabox_invoice_address', $invoice ); ?>
			</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param array $posted the posted data.
	 */
	public static function save( $post_id, $posted ) {

		// Prepare the invoice.
		$invoice = new WPInv_Invoice( $post_id );

		// Load new data.
		$invoice->set_props(
			array(
				'template'       => isset( $posted['wpinv_template'] ) ? wpinv_clean( $posted['wpinv_template'] ) : null,
				'email_cc'       => isset( $posted['wpinv_cc'] ) ? wpinv_clean( $posted['wpinv_cc'] ) : null,
				'disable_taxes'  => ! empty( $posted['disable_taxes'] ),
				'currency'       => isset( $posted['wpinv_currency'] ) ? wpinv_clean( $posted['wpinv_currency'] ) : null,
				'gateway'        => ( $invoice->needs_payment() && isset( $posted['wpinv_gateway'] ) ) ? wpinv_clean( $posted['wpinv_gateway'] ) : null,
				'address'        => isset( $posted['wpinv_address'] ) ? wpinv_clean( $posted['wpinv_address'] ) : null,
				'vat_number'     => isset( $posted['wpinv_vat_number'] ) ? wpinv_clean( $posted['wpinv_vat_number'] ) : null,
				'company'        => isset( $posted['wpinv_company'] ) ? wpinv_clean( $posted['wpinv_company'] ) : null,
				'company_id'     => isset( $posted['wpinv_company_id'] ) ? wpinv_clean( $posted['wpinv_company_id'] ) : null,
				'zip'            => isset( $posted['wpinv_zip'] ) ? wpinv_clean( $posted['wpinv_zip'] ) : null,
				'state'          => isset( $posted['wpinv_state'] ) ? wpinv_clean( $posted['wpinv_state'] ) : null,
				'city'           => isset( $posted['wpinv_city'] ) ? wpinv_clean( $posted['wpinv_city'] ) : null,
				'country'        => isset( $posted['wpinv_country'] ) ? wpinv_clean( $posted['wpinv_country'] ) : null,
				'phone'          => isset( $posted['wpinv_phone'] ) ? wpinv_clean( $posted['wpinv_phone'] ) : null,
				'first_name'     => isset( $posted['wpinv_first_name'] ) ? wpinv_clean( $posted['wpinv_first_name'] ) : null,
				'last_name'      => isset( $posted['wpinv_last_name'] ) ? wpinv_clean( $posted['wpinv_last_name'] ) : null,
				'author'         => isset( $posted['post_author_override'] ) ? wpinv_clean( $posted['post_author_override'] ) : null,
				'date_created'   => isset( $posted['date_created'] ) ? wpinv_clean( $posted['date_created'] ) : null,
				'date_completed' => isset( $posted['wpinv_date_completed'] ) ? wpinv_clean( $posted['wpinv_date_completed'] ) : null,
				'due_date'       => isset( $posted['wpinv_due_date'] ) ? wpinv_clean( $posted['wpinv_due_date'] ) : null,
				'number'         => isset( $posted['wpinv_number'] ) ? wpinv_clean( $posted['wpinv_number'] ) : null,
				'status'         => isset( $posted['wpinv_status'] ) ? wpinv_clean( $posted['wpinv_status'] ) : null,
			)
		);

		// Discount code.
		if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {

			if ( isset( $posted['wpinv_discount_code'] ) ) {
				$invoice->set_discount_code( wpinv_clean( $posted['wpinv_discount_code'] ) );
			}

			$discount = new WPInv_Discount( $invoice->get_discount_code() );
			if ( $discount->exists() ) {
				$invoice->add_discount( getpaid_calculate_invoice_discount( $invoice, $discount ) );
			} else {
				$invoice->remove_discount( 'discount_code' );
			}

			// Recalculate totals.
			$invoice->recalculate_total();

		}

		// If we're creating a new user...
		if ( ! empty( $posted['wpinv_new_user'] ) && is_email( stripslashes( $posted['wpinv_email'] ) ) ) {

			// Attempt to create the user.
			$user = wpinv_create_user( sanitize_email( stripslashes( $posted['wpinv_email'] ) ), $invoice->get_first_name() . $invoice->get_last_name() );

			// If successful, update the invoice author.
			if ( is_numeric( $user ) ) {
				$invoice->set_author( $user );
			} else {
				wpinv_error_log( $user->get_error_message(), __( 'Invoice add new user', 'invoicing' ), __FILE__, __LINE__ );
			}
		}

		// Do not send new invoice notifications.
		$GLOBALS['wpinv_skip_invoice_notification'] = true;

		// Save the invoice.
		$invoice->save();

		// Save the user address.
		getpaid_save_invoice_user_address( $invoice );

		// Undo do not send new invoice notifications.
		$GLOBALS['wpinv_skip_invoice_notification'] = false;

		// (Maybe) send new user notification.
		$should_send_notification = wpinv_get_option( 'disable_new_user_emails' );
		if ( ! empty( $user ) && is_numeric( $user ) && apply_filters( 'getpaid_send_new_user_notification', empty( $should_send_notification ) ) ) {
			wp_send_new_user_notifications( $user, 'user' );
		}

		if ( ! empty( $posted['send_to_customer'] ) && ! $invoice->is_draft() ) {
			getpaid()->get( 'invoice_emails' )->user_invoice( $invoice, true );
		}

		// Fires after an invoice is saved.
		do_action( 'wpinv_invoice_metabox_saved', $invoice );
	}
}
