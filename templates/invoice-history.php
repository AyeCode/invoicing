<?php
/**
 * Template that prints the invoice history page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice-history.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Current page.
$current_page   = empty( $_GET[ 'page' ] ) ? 1 : absint( $_GET[ 'page' ] );

// Fires before displaying user invoices.
do_action( 'wpinv_before_user_invoices', $invoices->invoices, $invoices->total, $invoices->max_num_pages, $post_type );

?>


	<div class="table-responsive">
		<table class="table table-bordered table-hover getpaid-user-invoices <?php echo sanitize_html_class( $post_type ); ?>">


			<thead>
				<tr>

					<?php foreach ( wpinv_get_user_invoices_columns( $post_type ) as $column_id => $column_name ) : ?>
						<th class="<?php echo sanitize_html_class( $column_id ); ?> <?php echo ( ! empty( $column_name['class'] ) ? sanitize_html_class( $column_name['class'] ) : '');?> border-bottom-0">
							<span class="nobr"><?php echo esc_html( $column_name['title'] ); ?></span>
						</th>
					<?php endforeach; ?>

				</tr>
			</thead>



			<tbody>
				<?php foreach ( $invoices->invoices as $invoice ) : ?>

					<tr class="wpinv-item wpinv-item-<?php echo $invoice_status = $invoice->get_status(); ?>">
						<?php

							foreach ( wpinv_get_user_invoices_columns( $post_type ) as $column_id => $column_name ) :

								$column_id = sanitize_html_class( $column_id );
								$class     = empty( $column_name['class'] ) ? '' : sanitize_html_class( $column_name['class'] );

								echo "<td class='$column_id $class'>";
								switch ( $column_id ) {

									case 'invoice-number':
										echo wpinv_invoice_link( $invoice );
										break;

									case 'created-date':
										echo getpaid_format_date_value( $invoice->get_date_created() );
										break;

									case 'payment-date':

										if ( $invoice->needs_payment() ) {
											echo "&mdash;";
										} else {
											echo getpaid_format_date_value( $invoice->get_date_completed() );
										}

										break;

									case 'invoice-status':
										echo $invoice->get_status_label_html();

										break;

									case 'invoice-total':
										echo wpinv_price( $invoice->get_total(), $invoice->get_currency() );

										break;

									case 'invoice-actions':

										$actions = array(

											'pay'       => array(
												'url'   => $invoice->get_checkout_payment_url(),
												'name'  => __( 'Pay Now', 'invoicing' ),
												'class' => 'btn-success'
											),

											'print'     => array(
												'url'   => $invoice->get_view_url(),
												'name'  => __( 'View', 'invoicing' ),
												'class' => 'btn-secondary',
												'attrs' => 'target="_blank"'
											)
										);

										if ( ! $invoice->needs_payment() ) {
											unset( $actions['pay'] );
										}

										$actions = apply_filters( 'wpinv_user_invoices_actions', $actions, $invoice, $post_type );

										foreach ( $actions as $key => $action ) {
											$class = !empty($action['class']) ? sanitize_html_class($action['class']) : '';
											echo '<a href="' . esc_url( $action['url'] ) . '" class="btn btn-sm btn-block ' . $class . ' ' . sanitize_html_class( $key ) . '" ' . ( !empty($action['attrs']) ? $action['attrs'] : '' ) . '>' . $action['name'] . '</a>';
										}

										break;

									default:
										do_action( "wpinv_user_invoices_column_$column_id", $invoice );
										break;


								}

								do_action( "wpinv_user_invoices_column_after_$column_id", $invoice );

								echo '</td>';

							endforeach;
						?>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>

	<?php do_action( 'wpinv_before_user_invoices_pagination' ); ?>

	<?php if ( 1 < $invoices->max_num_pages ) : ?>
		<div class="invoicing-Pagination">
			<?php
			$big = 999999;

			echo paginate_links( array(
				'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'  => '?paged=%#%',
				'total'   => $invoices->max_num_pages,
			) );
			?>
		</div>
	<?php endif; ?>

<?php do_action( 'wpinv_after_user_invoices', $invoices->invoices, $invoices->total, $invoices->max_num_pages, $post_type  ); ?>
