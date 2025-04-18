<?php
/**
 * Post Types Admin.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Admin Class
 *
 */
class GetPaid_Post_Types_Admin {

    /**
	 * Hook in methods.
	 */
	public static function init() {

		// Init metaboxes.
		GetPaid_Metaboxes::init();

		// Filter the post updated messages.
		add_filter( 'post_updated_messages', 'GetPaid_Post_Types_Admin::post_updated_messages' );

		// Filter post actions.
		add_filter( 'post_row_actions', 'GetPaid_Post_Types_Admin::post_row_actions', 10, 2 );
		add_filter( 'post_row_actions', 'GetPaid_Post_Types_Admin::filter_invoice_row_actions', 90, 2 );

		// Invoice table columns.
		add_filter( 'manage_wpi_invoice_posts_columns', array( __CLASS__, 'invoice_columns' ), 100 );
		add_action( 'manage_wpi_invoice_posts_custom_column', array( __CLASS__, 'display_invoice_columns' ), 10, 2 );
		add_filter( 'bulk_actions-edit-wpi_invoice', array( __CLASS__, 'invoice_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-wpi_invoice', array( __CLASS__, 'handle_invoice_bulk_actions' ), 10, 3 );

		// Items table columns.
		add_filter( 'manage_wpi_item_posts_columns', array( __CLASS__, 'item_columns' ), 100 );
		add_filter( 'manage_edit-wpi_item_sortable_columns', array( __CLASS__, 'sortable_item_columns' ), 20 );
		add_action( 'manage_wpi_item_posts_custom_column', array( __CLASS__, 'display_item_columns' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_item_filters' ), 100 );
		add_action( 'parse_query', array( __CLASS__, 'filter_item_query' ), 100 );
		add_action( 'request', array( __CLASS__, 'reorder_items' ), 100 );

		// Payment forms columns.
		add_filter( 'manage_wpi_payment_form_posts_columns', array( __CLASS__, 'payment_form_columns' ), 100 );
		add_action( 'manage_wpi_payment_form_posts_custom_column', array( __CLASS__, 'display_payment_form_columns' ), 10, 2 );
		add_filter( 'display_post_states', array( __CLASS__, 'filter_payment_form_state' ), 10, 2 );

		// Discount table columns.
		add_filter( 'manage_wpi_discount_posts_columns', array( __CLASS__, 'discount_columns' ), 100 );
		add_filter( 'bulk_actions-edit-wpi_discount', '__return_empty_array', 100 );

		// Deleting posts.
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'filter_discount_state' ), 10, 2 );

		add_filter( 'display_post_states', array( __CLASS__, 'add_display_post_states' ), 10, 2 );
	}

	/**
	 * Post updated messages.
	 */
	public static function post_updated_messages( $messages ) {
		global $post;

		$messages['wpi_discount'] = array(
			0  => '',
			1  => __( 'Discount updated.', 'invoicing' ),
			2  => __( 'Custom field updated.', 'invoicing' ),
			3  => __( 'Custom field deleted.', 'invoicing' ),
			4  => __( 'Discount updated.', 'invoicing' ),
			5  => isset( $_GET['revision'] ) ? wp_sprintf( __( 'Discount restored to revision from %s', 'invoicing' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Discount updated.', 'invoicing' ),
			7  => __( 'Discount saved.', 'invoicing' ),
			8  => __( 'Discount submitted.', 'invoicing' ),
			9  => wp_sprintf( __( 'Discount scheduled for: <strong>%1$s</strong>.', 'invoicing' ), date_i18n( __( 'M j, Y @ G:i', 'invoicing' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Discount draft updated.', 'invoicing' ),
		);

		$messages['wpi_payment_form'] = array(
			0  => '',
			1  => __( 'Payment Form updated.', 'invoicing' ),
			2  => __( 'Custom field updated.', 'invoicing' ),
			3  => __( 'Custom field deleted.', 'invoicing' ),
			4  => __( 'Payment Form updated.', 'invoicing' ),
			5  => isset( $_GET['revision'] ) ? wp_sprintf( __( 'Payment Form restored to revision from %s', 'invoicing' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Payment Form updated.', 'invoicing' ),
			7  => __( 'Payment Form saved.', 'invoicing' ),
			8  => __( 'Payment Form submitted.', 'invoicing' ),
			9  => wp_sprintf( __( 'Payment Form scheduled for: <strong>%1$s</strong>.', 'invoicing' ), date_i18n( __( 'M j, Y @ G:i', 'invoicing' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Payment Form draft updated.', 'invoicing' ),
		);

		return $messages;

	}

	/**
	 * Post row actions.
	 */
	public static function post_row_actions( $actions, $post ) {

		$post = get_post( $post );

		// We do not want to edit the default payment form.
		if ( 'wpi_payment_form' == $post->post_type ) {

			if ( wpinv_get_default_payment_form() === $post->ID ) {
				unset( $actions['trash'] );
				unset( $actions['inline hide-if-no-js'] );
			}

			$actions['duplicate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'getpaid-admin-action' => 'duplicate_form',
								'form_id'              => $post->ID,
							)
						),
						'getpaid-nonce',
						'getpaid-nonce'
					)
				),
				esc_html( __( 'Duplicate', 'invoicing' ) )
			);

			$actions['reset'] = sprintf(
				'<a href="%1$s" style="color: #800">%2$s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'getpaid-admin-action' => 'reset_form_stats',
								'form_id'              => $post->ID,
							)
						),
						'getpaid-nonce',
						'getpaid-nonce'
					)
				),
				esc_html( __( 'Reset Stats', 'invoicing' ) )
			);
		}

		// Link to item payment form.
		if ( 'wpi_item' == $post->post_type ) {
			if ( getpaid_item_type_supports( get_post_meta( $post->ID, '_wpinv_type', true ), 'buy_now' ) ) {
				$actions['buy'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( getpaid_embed_url( false, $post->ID . '|0' ) ),
					esc_html( __( 'Buy', 'invoicing' ) )
				);
			}
		}

		return $actions;
	}

	/**
     * Remove bulk edit option from admin side quote listing
     *
     * @since    1.0.0
     * @param array $actions post actions
	 * @param WP_Post $post
     * @return array $actions actions without edit option
     */
    public static function filter_invoice_row_actions( $actions, $post ) {

        if ( getpaid_is_invoice_post_type( $post->post_type ) ) {

			$actions = array();
			$invoice = new WPInv_Invoice( $post );

			$actions['edit'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( get_edit_post_link( $invoice->get_id() ) ),
				esc_html( __( 'Edit', 'invoicing' ) )
			);

			if ( ! $invoice->is_draft() ) {

				$actions['view'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $invoice->get_view_url() ),
					sprintf(
						// translators: %s is the invoice type
						esc_html__( 'View %s', 'invoicing' ),
						getpaid_get_post_type_label( $invoice->get_post_type(), false )
					)
				);

				$actions['send'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'getpaid-admin-action' => 'send_invoice',
									'invoice_id'           => $invoice->get_id(),
								)
							),
							'getpaid-nonce',
							'getpaid-nonce'
						)
					),
					esc_html( __( 'Send to Customer', 'invoicing' ) )
				);

			}

			$actions['duplicate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'getpaid-admin-action' => 'duplicate_invoice',
								'invoice_id'           => $post->ID,
							)
						),
						'getpaid-nonce',
						'getpaid-nonce'
					)
				),
				esc_html( __( 'Duplicate', 'invoicing' ) )
			);

        }

        return $actions;
	}

	/**
	 * Returns an array of invoice table columns.
	 */
	public static function invoice_columns( $columns ) {

		$columns = array(
			'cb'           => $columns['cb'],
			'number'       => __( 'Invoice', 'invoicing' ),
			'customer'     => __( 'Customer', 'invoicing' ),
			'invoice_date' => __( 'Created', 'invoicing' ),
			'payment_date' => __( 'Completed', 'invoicing' ),
			'amount'       => __( 'Amount', 'invoicing' ),
			'recurring'    => __( 'Recurring', 'invoicing' ),
			'status'       => __( 'Status', 'invoicing' ),
		);

		return apply_filters( 'wpi_invoice_table_columns', $columns );
	}

	/**
	 * Displays invoice table columns.
	 */
	public static function display_invoice_columns( $column_name, $post_id ) {

		$invoice = new WPInv_Invoice( $post_id );

		switch ( $column_name ) {

			case 'invoice_date':
				$date_time = esc_attr( $invoice->get_created_date() );
				$date      = esc_html( getpaid_format_date_value( $date_time, '&mdash;', true ) );
				echo wp_kses_post( "<span title='$date_time'>$date</span>" );
				break;

			case 'payment_date':
				if ( $invoice->is_paid() || $invoice->is_refunded() ) {
					$date_time = esc_attr( $invoice->get_completed_date() );
					$date      = esc_html( getpaid_format_date_value( $date_time, '&mdash;', true ) );
					echo wp_kses_post( "<span title='$date_time'>$date</span>" );

					if ( $_gateway = $invoice->get_gateway() ) {
						$gateway_label = wpinv_get_gateway_admin_label( $_gateway );

						if ( $transaction_url = $invoice->get_transaction_url() ) {
							$gateway_label = '<a href="' . esc_url( $transaction_url ) . '" target="_blank" title="' . esc_attr__( 'Open transaction link', 'invoicing' ) . '">' . $gateway_label . '</a>';
						}

						$gateway = '<small class="meta bsui"><span class="fs-xs text-muted fst-normal">' . wp_sprintf( _x( 'Via %s', 'Paid via gateway', 'invoicing' ), $gateway_label ) . '</span></small>';
					} else {
						$gateway = '';
					}

					$gateway = apply_filters( 'getpaid_admin_invoices_list_table_gateway', $gateway, $invoice );

					if ( $gateway ) {
						echo wp_kses_post( $gateway ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				} else {
					echo '&mdash;';
				}

				break;

			case 'amount':
				$amount = $invoice->get_total();
				$formated_amount = wp_kses_post( wpinv_price( $amount, $invoice->get_currency() ) );

				if ( $invoice->is_refunded() ) {
					$refunded_amount = wpinv_price( 0, $invoice->get_currency() );
					echo wp_kses_post( "<del>$formated_amount</del>&nbsp;<ins>$refunded_amount</ins>" );
				} else {

					$discount = $invoice->get_total_discount();

					if ( ! empty( $discount ) ) {
						$new_amount = wpinv_price( $amount + $discount, $invoice->get_currency() );
						echo wp_kses_post( "<del>$new_amount</del>&nbsp;<ins>$formated_amount</ins>" );
					} else {
						echo wp_kses_post( $formated_amount );
					}
				}

				break;

			case 'status':
				$status = esc_html( $invoice->get_status() );

				// If it is paid, show the gateway title.
				if ( $invoice->is_paid() ) {
					$gateway = esc_html( $invoice->get_gateway_title() );
					$gateway = wp_sprintf( esc_attr__( 'Paid via %s', 'invoicing' ), esc_html( $gateway ) );

					echo wp_kses_post( "<span class='bsui wpi-help-tip getpaid-invoice-statuss $status' title='$gateway'><span class='fs-base'>" . $invoice->get_status_label_html() . "</span></span>" );
				} else {
					echo wp_kses_post( "<span class='bsui getpaid-invoice-statuss $status'><span class='fs-base'>" . $invoice->get_status_label_html() . "</span></span>" );
				}

				// If it is not paid, display the overdue and view status.
				if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {

					// Invoice view status.
					if ( wpinv_is_invoice_viewed( $invoice->get_id() ) ) {
						echo '&nbsp;&nbsp;<i class="fa fa-eye wpi-help-tip" title="' . esc_attr__( 'Viewed by Customer', 'invoicing' ) . '"></i>';
					} else {
						echo '&nbsp;&nbsp;<i class="fa fa-eye-slash wpi-help-tip" title="' . esc_attr__( 'Not Viewed by Customer', 'invoicing' ) . '"></i>';
					}

					// Display the overview status.
					if ( wpinv_get_option( 'overdue_active' ) ) {
						$due_date = $invoice->get_due_date();
						$fomatted = getpaid_format_date( $due_date );

						if ( ! empty( $fomatted ) ) {
							$date = wp_sprintf(
								// translators: %s is the due date.
								__( 'Due %s', 'invoicing' ),
								$fomatted
							);
							echo wp_kses_post( "<p class='description' style='color: #888;' title='$due_date'>$fomatted</p>" );
						}
					}
				}

				break;

			case 'recurring':
				if ( $invoice->is_recurring() ) {
					echo '<i class="fa fa-check" style="color:#43850a;"></i>';
				} else {
					echo '<i class="fa fa-times" style="color:#616161;"></i>';
				}
				break;

			case 'number':
				$edit_link       = esc_url( get_edit_post_link( $invoice->get_id() ) );
				$invoice_number  = esc_html( $invoice->get_number() );
				$invoice_details = esc_attr__( 'View Invoice Details', 'invoicing' );

				echo wp_kses_post( "<a href='$edit_link' title='$invoice_details'><strong>$invoice_number</strong></a>" );

				do_action( 'getpaid_admin_table_invoice_number_column', $invoice );
				break;

			case 'customer':
				$customer_name = $invoice->get_user_full_name();

				if ( empty( $customer_name ) ) {
					$customer_name = $invoice->get_email();
				}

				if ( ! empty( $customer_name ) ) {
					$customer_details = esc_attr__( 'View Customer Details', 'invoicing' );
					$view_link        = esc_url( add_query_arg( 'user_id', $invoice->get_user_id(), admin_url( 'user-edit.php' ) ) );
					echo wp_kses_post( "<a href='$view_link' title='$customer_details'><span>$customer_name</span></a>" );
				} else {
					echo '<div>&mdash;</div>';
				}

				break;

		}

	}

	/**
	 * Displays invoice bulk actions.
	 */
	public static function invoice_bulk_actions( $actions ) {
		$actions['resend-invoice'] = __( 'Send to Customer', 'invoicing' );
		return $actions;
	}

	/**
	 * Processes invoice bulk actions.
	 */
	public static function handle_invoice_bulk_actions( $redirect_url, $action, $post_ids ) {

		if ( 'resend-invoice' === $action ) {
			foreach ( $post_ids as $post_id ) {
				getpaid()->get( 'invoice_emails' )->user_invoice( new WPInv_Invoice( $post_id ), true );
			}
		}

		return $redirect_url;

	}

	/**
	 * Returns an array of payment forms table columns.
	 */
	public static function payment_form_columns( $columns ) {

		$columns = array(
			'cb'        => $columns['cb'],
			'title'     => __( 'Name', 'invoicing' ),
			'shortcode' => __( 'Shortcode', 'invoicing' ),
			'earnings'  => __( 'Revenue', 'invoicing' ),
			'refunds'   => __( 'Refunded', 'invoicing' ),
			'items'     => __( 'Items', 'invoicing' ),
			'date'      => __( 'Date', 'invoicing' ),
		);

		return apply_filters( 'wpi_payment_form_table_columns', $columns );

	}

	/**
	 * Displays payment form table columns.
	 */
	public static function display_payment_form_columns( $column_name, $post_id ) {

		// Retrieve the payment form.
		$form = new GetPaid_Payment_Form( $post_id );

		switch ( $column_name ) {

			case 'earnings':
				echo wp_kses_post( wpinv_price( $form->get_earned() ) );
				break;

			case 'refunds':
				echo wp_kses_post( wpinv_price( $form->get_refunded() ) );
				break;

			case 'refunds':
				echo wp_kses_post( wpinv_price( $form->get_refunded() ) );
				break;

			case 'shortcode':
				if ( $form->is_default() ) {
					echo '&mdash;';
				} else {
					echo '<input onClick="this.select()" type="text" value="[getpaid form=' . esc_attr( $form->get_id() ) . ']" style="width: 100%;" readonly/>';
				}

				break;

			case 'items':
				$items = $form->get_items();

				if ( $form->is_default() || empty( $items ) ) {
					echo '&mdash;';
					return;
				}

				$_items = array();

				foreach ( $items as $item ) {
					$url = $item->get_edit_url();

					if ( empty( $url ) ) {
						$_items[] = esc_html( $item->get_name() );
					} else {
						$_items[] = sprintf(
							'<a href="%s">%s</a>',
							esc_url( $url ),
							esc_html( $item->get_name() )
						);
					}
}

				echo wp_kses_post( implode( '<br>', $_items ) );

				break;

		}

	}

	/**
	 * Filters post states.
	 */
	public static function filter_payment_form_state( $post_states, $post ) {

		if ( 'wpi_payment_form' === $post->post_type && wpinv_get_default_payment_form() === $post->ID ) {
			$post_states['default_form'] = __( 'Default Payment Form', 'invoicing' );
		}

		return $post_states;

	}

	/**
	 * Returns an array of coupon table columns.
	 */
	public static function discount_columns( $columns ) {

		$columns = array(
			'cb'          => $columns['cb'],
			'title'       => __( 'Name', 'invoicing' ),
			'code'        => __( 'Code', 'invoicing' ),
			'amount'      => __( 'Amount', 'invoicing' ),
			'usage'       => __( 'Usage / Limit', 'invoicing' ),
			'start_date'  => __( 'Start Date', 'invoicing' ),
			'expiry_date' => __( 'Expiry Date', 'invoicing' ),
		);

		return apply_filters( 'wpi_discount_table_columns', $columns );
	}

	/**
	 * Filters post states.
	 */
	public static function filter_discount_state( $post_states, $post ) {

		if ( 'wpi_discount' === $post->post_type ) {

			$discount = new WPInv_Discount( $post );

			$status = $discount->is_expired() ? 'expired' : $discount->get_status();

			if ( 'publish' !== $status ) {
				return array(
					'discount_status' => wpinv_discount_status( $status ),
				);
			}

			return array();

		}

		return $post_states;

	}

	/**
	 * Returns an array of items table columns.
	 */
	public static function item_columns( $columns ) {

		$columns = array(
			'cb'        => $columns['cb'],
			'title'     => __( 'Name', 'invoicing' ),
			'price'     => __( 'Price', 'invoicing' ),
			'vat_rule'  => __( 'Tax Rule', 'invoicing' ),
			'vat_class' => __( 'Tax Class', 'invoicing' ),
			'type'      => __( 'Type', 'invoicing' ),
			'shortcode' => __( 'Shortcode', 'invoicing' ),
		);

		if ( ! wpinv_use_taxes() ) {
			unset( $columns['vat_rule'] );
			unset( $columns['vat_class'] );
		}

		return apply_filters( 'wpi_item_table_columns', $columns );
	}

	/**
	 * Returns an array of sortable items table columns.
	 */
	public static function sortable_item_columns( $columns ) {

		return array_merge(
			$columns,
			array(
				'price'     => 'price',
				'vat_rule'  => 'vat_rule',
				'vat_class' => 'vat_class',
				'type'      => 'type',
			)
		);

	}

	/**
	 * Displays items table columns.
	 */
	public static function display_item_columns( $column_name, $post_id ) {

		$item = new WPInv_Item( $post_id );

		switch ( $column_name ) {

			case 'price':
				if ( ! $item->is_recurring() ) {
					echo wp_kses_post( $item->get_the_price() );
					break;
				}

				$price = wp_sprintf(
					__( '%1$s / %2$s', 'invoicing' ),
					$item->get_the_price(),
					getpaid_get_subscription_period_label( $item->get_recurring_period(), $item->get_recurring_interval(), '' )
				);

				if ( $item->get_the_price() == $item->get_the_initial_price() ) {
					echo wp_kses_post( $price );
					break;
				}

				echo wp_kses_post( $item->get_the_initial_price() );

				echo '<span class="meta">' . wp_sprintf( esc_html__( 'then %s', 'invoicing' ), wp_kses_post( $price ) ) . '</span>';
				break;

			case 'vat_rule':
				echo wp_kses_post( getpaid_get_tax_rule_label( $item->get_vat_rule() ) );
				break;

			case 'vat_class':
				echo wp_kses_post( getpaid_get_tax_class_label( $item->get_vat_class() ) );
				break;

			case 'shortcode':
				if ( $item->is_type( array( '', 'fee', 'custom' ) ) ) {
					echo '<input onClick="this.select()" type="text" value="[getpaid item=' . esc_attr( $item->get_id() ) . ' button=\'Buy Now\']" style="width: 100%;" readonly/>';
				} else {
					echo '&mdash;';
				}

				break;

			case 'type':
				echo wp_kses_post( wpinv_item_type( $item->get_id() ) . '<span class="meta">' . $item->get_custom_singular_name() . '</span>' );
				break;

		}

	}

	/**
	 * Lets users filter items using taxes.
	 */
	public static function add_item_filters( $post_type ) {

		// Abort if we're not dealing with items.
		if ( 'wpi_item' !== $post_type ) {
			return;
		}

		// Filter by vat rules.
		if ( wpinv_use_taxes() ) {

			// Sanitize selected vat rule.
			$vat_rule   = '';
			$vat_rules  = getpaid_get_tax_rules();
			if ( isset( $_GET['vat_rule'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$vat_rule   = sanitize_text_field( $_GET['vat_rule'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			// Filter by VAT rule.
			wpinv_html_select(
				array(
					'options'          => array_merge(
						array(
							'' => __( 'All Tax Rules', 'invoicing' ),
						),
						$vat_rules
					),
					'name'             => 'vat_rule',
					'id'               => 'vat_rule',
					'selected'         => in_array( $vat_rule, array_keys( $vat_rules ), true ) ? $vat_rule : '',
					'show_option_all'  => false,
					'show_option_none' => false,
				)
			);

			// Filter by VAT class.

			// Sanitize selected vat rule.
			$vat_class   = '';
			$vat_classes = getpaid_get_tax_classes();
			if ( isset( $_GET['vat_class'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$vat_class   = sanitize_text_field( $_GET['vat_class'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			wpinv_html_select(
				array(
					'options'          => array_merge(
						array(
							'' => __( 'All Tax Classes', 'invoicing' ),
						),
						$vat_classes
					),
					'name'             => 'vat_class',
					'id'               => 'vat_class',
					'selected'         => in_array( $vat_class, array_keys( $vat_classes ), true ) ? $vat_class : '',
					'show_option_all'  => false,
					'show_option_none' => false,
				)
			);

		}

		// Filter by item type.
		$type   = '';
		if ( isset( $_GET['type'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$type   = sanitize_text_field( $_GET['type'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		wpinv_html_select(
			array(
				'options'          => array_merge(
					array(
						'' => __( 'All item types', 'invoicing' ),
					),
					wpinv_get_item_types()
				),
				'name'             => 'type',
				'id'               => 'type',
				'selected'         => in_array( $type, wpinv_item_types(), true ) ? $type : '',
				'show_option_all'  => false,
				'show_option_none' => false,
			)
		);

	}

	/**
	 * Filters the item query.
	 */
	public static function filter_item_query( $query ) {

		// modify the query only if it admin and main query.
		if ( ! ( is_admin() && $query->is_main_query() ) ) {
			return $query;
		}

		// we want to modify the query for our items.
		if ( empty( $query->query['post_type'] ) || 'wpi_item' !== $query->query['post_type'] ) {
			return $query;
		}

		if ( empty( $query->query_vars['meta_query'] ) ) {
			$query->query_vars['meta_query'] = array();
		}

		// Filter vat rule type
        if ( ! empty( $_GET['vat_rule'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $query->query_vars['meta_query'][] = array(
                'key'     => '_wpinv_vat_rule',
                'value'   => sanitize_text_field( $_GET['vat_rule'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'compare' => '=',
            );
        }

        // Filter vat class
        if ( ! empty( $_GET['vat_class'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $query->query_vars['meta_query'][] = array(
                'key'     => '_wpinv_vat_class',
                'value'   => sanitize_text_field( $_GET['vat_class'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'compare' => '=',
            );
        }

        // Filter item type
        if ( ! empty( $_GET['type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $query->query_vars['meta_query'][] = array(
                'key'     => '_wpinv_type',
                'value'   => sanitize_text_field( $_GET['type'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'compare' => '=',
            );
		}

		$query->query_vars['meta_query'][] = array(
			'key'     => '_wpinv_one_time',
			'compare' => 'NOT EXISTS',
		);
	}

	/**
	 * Reorders items.
	 */
	public static function reorder_items( $vars ) {
		global $typenow;

		if ( 'wpi_item' !== $typenow || empty( $vars['orderby'] ) ) {
			return $vars;
		}

		// By item type.
		if ( 'type' === $vars['orderby'] ) {
			return array_merge(
				$vars,
				array(
					'meta_key' => '_wpinv_type',
					'orderby'  => 'meta_value',
				)
			);
		}

		// By vat class.
		if ( 'vat_class' === $vars['orderby'] ) {
			return array_merge(
				$vars,
				array(
					'meta_key' => '_wpinv_vat_class',
					'orderby'  => 'meta_value',
				)
			);
		}

		// By vat rule.
		if ( 'vat_rule' === $vars['orderby'] ) {
			return array_merge(
				$vars,
				array(
					'meta_key' => '_wpinv_vat_rule',
					'orderby'  => 'meta_value',
				)
			);
		}

		// By price.
		if ( 'price' === $vars['orderby'] ) {
			return array_merge(
				$vars,
				array(
					'meta_key' => '_wpinv_price',
					'orderby'  => 'meta_value_num',
				)
			);
		}

		return $vars;

	}

	/**
	 * Fired when deleting a post.
	 */
	public static function delete_post( $post_id ) {

		switch ( get_post_type( $post_id ) ) {

			case 'wpi_item':
				do_action( 'getpaid_before_delete_item', new WPInv_Item( $post_id ) );
				break;

			case 'wpi_payment_form':
				do_action( 'getpaid_before_delete_payment_form', new GetPaid_Payment_Form( $post_id ) );
				break;

			case 'wpi_discount':
				do_action( 'getpaid_before_delete_discount', new WPInv_Discount( $post_id ) );
				break;

			case 'wpi_invoice':
				$invoice = new WPInv_Invoice( $post_id );
				do_action( 'getpaid_before_delete_invoice', $invoice );
				$invoice->get_data_store()->delete_items( $invoice );
				$invoice->get_data_store()->delete_special_fields( $invoice );
				break;
		}
	}

	/**
     * Add a post display state for special GetPaid pages in the page list table.
     *
     * @param array   $post_states An array of post display states.
     * @param WP_Post $post        The current post object.
     *
     * @return mixed
     */
    public static function add_display_post_states( $post_states, $post ) {

        if ( wpinv_get_option( 'success_page', 0 ) == $post->ID ) {
            $post_states['getpaid_success_page'] = __( 'GetPaid Receipt Page', 'invoicing' );
        }

		foreach ( getpaid_get_invoice_post_types() as $post_type => $label ) {

			if ( wpinv_get_option( "{$post_type}_history_page", 0 ) == $post->ID ) {
				$post_states[ "getpaid_{$post_type}_history_page" ] = sprintf(
					__( 'GetPaid %s History Page', 'invoicing' ),
					$label
				);
			}
}

		if ( wpinv_get_option( 'invoice_subscription_page', 0 ) == $post->ID ) {
            $post_states['getpaid_invoice_subscription_page'] = __( 'GetPaid Subscription Page', 'invoicing' );
        }

		if ( wpinv_get_option( 'checkout_page', 0 ) == $post->ID ) {
            $post_states['getpaid_checkout_page'] = __( 'GetPaid Checkout Page', 'invoicing' );
        }

        return $post_states;
    }

}
