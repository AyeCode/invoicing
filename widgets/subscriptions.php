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

			<style>
				.getpaid-subscriptions-table-column-subscription {
					width: 35%;
					font-weight: 500;
				}

				.getpaid-subscriptions-table-row span.label {
					font-weight: 500;
				}

				.getpaid-subscriptions.bsui .table-bordered thead th {
					border-bottom-width: 1px;
				}

				.getpaid-subscriptions.bsui .table-striped tbody tr:nth-of-type(odd) {
					background-color: rgb(0 0 0 / 0.01);
				}
			</style>

			<table class="table table-bordered">

				<thead>
					<tr>
						<?php foreach ( $this->get_subscriptions_table_columns() as $key => $label ) : ?>
							<th scope="col" class="getpaid-subscriptions-table-<?php echo sanitize_html_class( $key ); ?>">
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
					<?php foreach ( array_keys( $this->get_subscriptions_table_columns() ) as $column ) : ?>

						<td class="getpaid-subscriptions-table-column-<?php echo sanitize_html_class( $column ); ?>">
							<?php
								switch( $column ) :

									case 'subscription':
										echo $this->column_subscription( $subscription );
                                        break;
                                    
                                    case 'status':
                                        echo $subscription->get_status_label_html();
                                        break;
                                        
                                    case 'renewal-date':
                                        $renewal = $this->format_date_field( $subscription->get_next_renewal_date() );
                                        echo $subscription->is_active() ? $renewal : "&mdash;";
										break;

                                        
                                    case 'amount':
                                        $frequency = sanitize_text_field( WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->get_period(), $subscription->get_frequency(), true ) );
                                        $amount    = wpinv_price( wpinv_format_amount( wpinv_sanitize_amount( $subscription->get_recurring_amount() ) ), $subscription->get_parent_payment()->get_currency() );
										echo "<strong style='font-weight: 500;'>$amount</strong> / $frequency";
										break;

								endswitch;

								do_action( "getpaid_subscriptions_frontend_subscription_table_$column", $subscription );

							?>
						</td>

					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>

		</tbody>
		<?php
	}

	/**
	 * Formats a date field.
	 *
	 * @param string $date
	 */
	public function format_date_field( $date, $default = "&mdash;" ) {

		if ( empty( $date ) || '0000-00-00 00:00:00' == $date ) {
			return $default;
		} else {
			return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
		}

	}

	/**
	 * Subscription column
	 *
	 * @param WPInv_Subscription $subscription
	 * @since       1.0.0
	 * @return      string
	 */
	public function column_subscription( $subscription ) {
        $subscription_id = (int) $subscription->get_id();
        $url             = esc_url( add_query_arg( 'subscription', $subscription_id, get_permalink( (int) wpinv_get_option( 'invoice_subscription_page' ) ) ) );
        return $this->add_row_actions( "<a href='$url' class='text-decoration-none'>#$subscription_id</a>", $subscription );
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
        $view_url        = esc_url( add_query_arg( 'subscription', (int) $subscription->get_id(), get_permalink( (int) wpinv_get_option( 'invoice_subscription_page' ) ) ) );
        $actions['view'] = "<a href='$view_url' class='text-decoration-none'>" . __( 'Manage Subscription', 'invoicing' ) . '</a>';

        // View invoice action.
        $invoice = $subscription->get_parent_payment();

        if ( $invoice->get_id() ) {
            $view_url           = esc_url( $invoice->get_view_url() );
            $actions['invoice'] = "<a href='$view_url' class='text-decoration-none'>" . __( 'View Invoice', 'invoicing' ) . '</a>';
        }

        // Filter the actions.
        $actions = apply_filters( 'getpaid_subscriptions_table_subscription_actions', $actions, $subscription );

        if ( ! empty( $actions ) ) {

            $sanitized  = array();
            foreach ( $actions as $key => $action ) {
                $key         = sanitize_html_class( $key );
                $action      = wp_kses_post( $action );
                $sanitized[] = "<span class='$key'>$action</span>";
            }

            $row_actions  = "<small class='form-text getpaid-subscription-item-actions'>";
            $row_actions .= implode( ' | ', $sanitized );
            $row_actions .= '</small>';

        }

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
							<th class="getpaid-subscriptions-<?php echo sanitize_html_class( $key ); ?>">
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

				echo paginate_links(
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
	 * Displays a single subscription.
	 *
	 * @param string $subscription
	 *
	 * @return string
	 */
	public function display_single_subscription( $subscription ) {

		// Fetch the subscription.
		$subscription = new WPInv_Subscription( (int) $subscription );

		if ( ! $subscription->get_id() ) {

			return aui()->alert(
				array(
					'content' => wp_kses_post( __( 'Subscription not found.', 'invoicing' ) ),
					'type'    => 'error',
				)
			);

		}

		// Ensure that the user owns this subscription key.
		if ( get_current_user_id() != $subscription->get_customer_id() ) {

			return aui()->alert(
				array(
					'content' => wp_kses_post( __( 'You do not have permission to view this subscription. Ensure that you are logged in to the account that owns the subscription.', 'invoicing' ) ),
					'type'    => 'error',
				)
			);

		}

		// Start the output buffer.
		ob_start();

		do_action( 'getpaid_single_subscription_before_notices', $subscription );

		// Display errors and notices.
		wpinv_print_errors();

		do_action( 'getpaid_before_single_subscription', $subscription );

		// Prepare subscription detail columns.
		$fields = apply_filters(
			'getpaid_single_subscription_details_fields',
			array(
                'status'           => __( 'Status', 'invoicing' ),
                'start_date'       => __( 'Start date', 'invoicing' ),
				'expiry_date'      => __( 'Next payment', 'invoicing' ),
				'initial_amount'   => __( 'Initial amount', 'invoicing' ),
				'recurring_amount' => __( 'Recurring amount', 'invoicing' ),
				'payments'         => __( 'Payments', 'invoicing' ),
                'item'             => __( 'Item', 'invoicing' ),
                'invoice'          => __( 'Invoice', 'invoicing' ),
			),
			$subscription
		);

        if ( ! $subscription->is_active() || $subscription->is_last_renewal() ) {
            $fields['expiry_date'] = __( 'End date', 'invoicing' );
        }

        if ( $subscription->get_initial_amount() == $subscription->get_recurring_amount() ) {
            unset( $fields['initial_amount'] );
        }
		?>

		<table class="table table-bordered">
			<tbody>

				<?php foreach ( $fields as $key => $label ) : ?>

					<tr class="getpaid-subscription-meta-<?php echo sanitize_html_class( $key ); ?>">

						<th class="w-25" style="font-weight: 500;">
							<?php echo sanitize_text_field( $label ); ?>
						</th>

						<td class="w-75 text-muted">
							<?php
								switch ( $key ) {

									case 'status':
										echo sanitize_text_field( $subscription->get_status_label() );
										break;

									case 'start_date':
										echo sanitize_text_field( $this->format_date_field( $subscription->get_date_created() ) );
                                        break;
                                        
                                    case 'expiry_date':
										echo sanitize_text_field( $this->format_date_field( $subscription->get_next_renewal_date() ) );
                                        break;
                                        
                                    case 'initial_amount':
										echo wpinv_price( wpinv_format_amount( $subscription->get_initial_amount(), $subscription->get_parent_payment()->get_currency() ) );
                                        break;
                                        
                                    case 'recurring_amount':
                                        $frequency = sanitize_text_field( WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->get_period(), $subscription->get_frequency(), true ) );
                                        $amount    = wpinv_price( wpinv_format_amount( $subscription->get_recurring_amount() ), $subscription->get_parent_payment()->get_currency() );
										echo "<strong style='font-weight: 500;'>$amount</strong> / $frequency";
										break;
                                        
									case 'invoice':
										$invoice = $subscription->get_parent_invoice();

										if ( $invoice->get_id() ) {
											$view_url = esc_url( $invoice->get_view_url() );
											$number   = sanitize_text_field( $invoice->get_number() );
											echo "<a href='$view_url' class='text-decoration-none'>$number</a>";
										} else {
											echo "&mdash;";
										}

										break;

									case 'item':
										$item = get_post( $subscription->get_product_id() );

										if ( ! empty( $item ) ) {
											echo esc_html( get_the_title( $item ) );
										} else {
											echo sprintf( __( 'Item #%s', 'invoicing' ), $subscription->get_product_id() );
										}

										break;

									case 'payments':

										$max_activations = (int) $subscription->get_bill_times();
										echo (int) $subscription->get_times_billed() . ' / ' . ( empty( $max_activations ) ? "&infin;" : $max_activations );

										break;

									case 'activated_on':

										$activations = $license->get_activated_on();

										if ( empty( $activations ) ) {
											echo __( 'This license has not been activated anywhere yet', 'invoicing' );
										}

										foreach ( $activations as $website => $date ) {
											$website = sanitize_text_field( $website );
											$date    = date_i18n( get_option( 'date_format' ), strtotime( $date ) );
											$message = esc_attr__( 'Are you sure you want to delete this license from the app/website?', 'invoicing' );
											$url     = esc_url(
												wp_nonce_url(
													add_query_arg(
														array(
															'getpaid-action' => 'delete_license_activation',
															'app'            => urlencode( $website )
														)
													),
													'getpaid-nonce',
													'getpaid-nonce'
												)
											);
											echo "<span class='form-text text-monospace'>$website &mdash; $date <a href='$url' onclick=\"return confirm('$message')\" class='text-danger'><i class='fa fa-times'></i></a></span>";
										}

										break;

								}
								do_action( "getpaid_render_single_subscription_column_$key", $subscription );
							?>
						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>

	<?php

		// Return the output.
		return ob_get_clean();

	}

}
