<?php
/**
 * Subscriptions Widget Class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contains the subscriptions widget.
 *
 * @package INVOICING
 */
class WPInv_Subscriptions_Widget extends WP_Super_Duper {

	/**
	 * Register the widget with WordPress.
	 *
	 */
	public function __construct() {

		$options = array(
			'textdomain'    => 'invoicing',
			'block-icon'    => 'controls-repeat',
			'block-category'=> 'widgets',
			'block-keywords'=> "['invoicing','subscriptions', 'getpaid']",
			'class_name'     => __CLASS__,
			'base_id'       => 'wpinv_subscriptions',
			'name'          => __( 'GetPaid > Subscriptions', 'invoicing' ),
			'widget_ops'    => array(
				'classname'   => 'getpaid-subscriptions bsui',
				'description' => esc_html__( "Displays the current user's subscriptions.", 'invoicing' ),
			),
			'arguments'     => array(
				'title'  => array(
					'title'       => __( 'Widget title', 'invoicing' ),
					'desc'        => __( 'Enter widget title.', 'invoicing' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'default'     => '',
					'advanced'    => false
				),
			)

		);


		parent::__construct( $options );
	}

	/**
	 * Retrieves current user's subscriptions.
	 *
	 * @return GetPaid_Subscriptions_Query
	 */
	public function get_subscriptions() {

		// Prepare license args.
		$args  = array(
			'customer_in' => get_current_user_id(),
			'paged'       => ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1,
		);

		return new GetPaid_Subscriptions_Query( $args );

	}

	/**
	 * The Super block output function.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|bool
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {

		// Ensure that the user is logged in.
		if ( ! is_user_logged_in() ) {

			return aui()->alert(
				array(
					'content' => wp_kses_post( __( 'You need to log-in or create an account to view this section.', 'invoicing' ) ),
					'type'    => 'error',
				)
			);

		}

		// Are we displaying a single subscription?
		if ( isset( $_GET['subscription'] ) ) {
			return $this->display_single_subscription( trim( $_GET['subscription'] ) );
		}

		// Retrieve the user's subscriptions.
		$subscriptions = $this->get_subscriptions();

		// Start the output buffer.
		ob_start();

		// Backwards compatibility.
		do_action( 'wpinv_before_user_subscriptions' );

		// Display errors and notices.
		wpinv_print_errors();

		do_action( 'getpaid_license_manager_before_subscriptions', $subscriptions );

		// Print the table header.
		$this->print_table_header();

		// Print table body.
		$this->print_table_body( $subscriptions->get_results() );

		// Print table footer.
		$this->print_table_footer();

		// Print the navigation.
		$this->print_navigation( $subscriptions->get_total() );

		// Backwards compatibility.
		do_action( 'wpinv_after_user_subscriptions' );

		// Return the output.
		return ob_get_clean();

	}

	/**
	 * Retrieves the subscription columns.
	 *
	 * @return array
	 */
	public function get_subscriptions_table_columns() {

		$columns = array(
			'subscription'   => __( 'Subscription', 'invoicing' ),
			'amount'         => __( 'Amount', 'invoicing' ),
			'renewal-date'   => __( 'Next payment', 'invoicing' ),
			'status'         => __( 'Status', 'invoicing' ),
		);

		return apply_filters( 'getpaid_frontend_subscriptions_table_columns', $columns );
	}

	/**
	 * Displays the table header.
	 *
	 */
	public function print_table_header() {

		?>

			<table class="table table-bordered table-striped">

				<thead>
					<tr>
						<?php foreach ( $this->get_subscriptions_table_columns() as $key => $label ) : ?>
							<th scope="col" class="font-weight-bold getpaid-subscriptions-table-<?php echo sanitize_html_class( $key ); ?>">
								<?php echo sanitize_text_field( $label ); ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>

		<?php

	}

	/**
	 * Displays the table body.
	 *
	 * @param WPInv_Subscription[] $subscriptions
	 */
	public function print_table_body( $subscriptions ) {

		if ( empty( $subscriptions ) ) {
			$this->print_table_body_no_subscriptions();
		} else {
			$this->print_table_body_subscriptions( $subscriptions );
		}

	}

	/**
	 * Displays the table body if no subscriptions were found.
	 *
	 */
	public function print_table_body_no_subscriptions() {

		?>
		<tbody>

			<tr>
				<td colspan="<?php echo count( $this->get_subscriptions_table_columns() ); ?>">

					<?php
						echo aui()->alert(
							array(
								'content' => wp_kses_post( __( 'No subscriptions found.', 'invoicing' ) ),
								'type'    => 'warning',
							)
						);
					?>

				</td>
			</tr>

		</tbody>
		<?php
	}

	/**
	 * Displays the table body if subscriptions were found.
	 *
	 * @param WPInv_Subscription[] $subscriptions
	 */
	public function print_table_body_subscriptions( $subscriptions ) {

		?>
		<tbody>

			<?php foreach ( $subscriptions as $subscription ) : ?>
				<tr class="getpaid-subscriptions-table-row subscription-<?php echo (int) $subscription->get_id(); ?>">
					<?php
						wpinv_get_template(
							'subscriptions/subscriptions-table-row.php',
							array(
								'subscription' => $subscription,
								'widget'       => $this
							)
						);
					?>
				</tr>
			<?php endforeach; ?>

		</tbody>
		<?php
	}

	/**
	 * Adds row actions to a column
	 *
	 * @param string $content column content
	 * @param WPInv_Subscription $subscription
	 * @since       1.0.0
	 * @return      string
	 */
	public function add_row_actions( $content, $subscription ) {

		// Prepare row actions.
		$actions = array();

		// View subscription action.
		$view_url        = getpaid_get_tab_url( 'gp-subscriptions', get_permalink( (int) wpinv_get_option( 'invoice_subscription_page' ) ) );
		$view_url        = esc_url( add_query_arg( 'subscription', (int) $subscription->get_id(), $view_url ) );
		$actions['view'] = "<a href='$view_url' class='text-decoration-none'>" . __( 'Manage Subscription', 'invoicing' ) . '</a>';

		// Filter the actions.
		$actions = apply_filters( 'getpaid_subscriptions_table_subscription_actions', $actions, $subscription );

		$sanitized  = array();
		foreach ( $actions as $key => $action ) {
			$key         = sanitize_html_class( $key );
			$action      = wp_kses_post( $action );
			$sanitized[] = "<span class='$key'>$action</span>";
		}

		$row_actions  = "<small class='form-text getpaid-subscription-item-actions'>";
		$row_actions .= implode( ' | ', $sanitized );
		$row_actions .= '</small>';

		return $content . $row_actions;
	}

	/**
	 * Displays the table footer.
	 *
	 */
	public function print_table_footer() {

		?>

				<tfoot>
					<tr>
						<?php foreach ( $this->get_subscriptions_table_columns() as $key => $label ) : ?>
							<th class="font-weight-bold getpaid-subscriptions-<?php echo sanitize_html_class( $key ); ?>">
								<?php echo sanitize_text_field( $label ); ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</tfoot>

			</table>
		<?php

	}

	/**
	 * Displays the navigation.
	 *
	 * @param int $total
	 */
	public function print_navigation( $total ) {

		if ( $total < 1 ) {

			// Out-of-bounds, run the query again without LIMIT for total count.
			$args  = array(
				'customer_in' => get_current_user_id(),
				'fields'      => 'id',
			);

			$count_query = new GetPaid_Subscriptions_Query( $args );
			$total       = $count_query->get_total();
		}

		// Abort if we do not have pages.
		if ( 2 > $total ) {
			return;
		}

		?>

		<div class="getpaid-subscriptions-pagination">
			<?php
				$big = 999999;

				echo getpaid_paginate_links(
					array(
						'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'  => '?paged=%#%',
						'total'   => (int) ceil( $total / 10 ),
					)
				);
			?>
		</div>

		<?php
	}

	/**
	 * Returns a single subscription's columns.
	 *
	 * @param WPInv_Subscription $subscription
	 *
	 * @return array
	 */
	public function get_single_subscription_columns( $subscription ) {

		// Prepare subscription detail columns.
		$subscription_group = getpaid_get_invoice_subscription_group( $subscription->get_parent_invoice_id(), $subscription->get_id() );
		$items_count        = empty( $subscription_group ) ? 1 : count( $subscription_group['items'] );
		$fields             = apply_filters(
			'getpaid_single_subscription_details_fields',
			array(
				'status'           => __( 'Status', 'invoicing' ),
				'initial_amount'   => __( 'Initial amount', 'invoicing' ),
				'recurring_amount' => __( 'Recurring amount', 'invoicing' ),
				'start_date'       => __( 'Start date', 'invoicing' ),
				'expiry_date'      => __( 'Next payment', 'invoicing' ),
				'payments'         => __( 'Payments', 'invoicing' ),
				'item'             => _n( 'Item', 'Items', $items_count, 'invoicing' ),
			),
			$subscription
		);

		if ( isset( $fields['expiry_date'] ) ) {

			if ( ! $subscription->is_active() || $subscription->is_last_renewal() ) {
				$fields['expiry_date'] = __( 'End date', 'invoicing' );
			}

			if ( 'pending' == $subscription->get_status() ) {
				unset( $fields['expiry_date'] );
			}

		}

		if ( isset( $fields['start_date'] ) && 'pending' == $subscription->get_status() ) {
			unset( $fields['start_date'] );
		}

		if ( $subscription->get_initial_amount() == $subscription->get_recurring_amount() ) {
			unset( $fields['initial_amount'] );
		}

		return $fields;
	}

	/**
	 * Displays a single subscription.
	 *
	 * @param string $subscription
	 *
	 * @return string
	 */
	public function display_single_subscription( $subscription ) {

		// Fetch the subscription.
		$subscription = new WPInv_Subscription( (int) $subscription );

		if ( ! $subscription->exists() ) {

			return aui()->alert(
				array(
					'content' => wp_kses_post( __( 'Subscription not found.', 'invoicing' ) ),
					'type'    => 'error',
				)
			);

		}

		// Ensure that the user owns this subscription key.
		if ( get_current_user_id() != $subscription->get_customer_id() && ! wpinv_current_user_can_manage_invoicing() ) {

			return aui()->alert(
				array(
					'content' => wp_kses_post( __( 'You do not have permission to view this subscription. Ensure that you are logged in to the account that owns the subscription.', 'invoicing' ) ),
					'type'    => 'error',
				)
			);

		}

		return wpinv_get_template_html(
			'subscriptions/subscription-details.php',
			array(
				'subscription' => $subscription,
				'widget'       => $this
			)
		);

	}

}
