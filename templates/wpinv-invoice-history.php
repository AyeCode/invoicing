<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !( $user_id = get_current_user_id() ) ) {
    ?>
    <div class="wpinv-empty alert alert-error"><?php _e( 'You are not allowed to access this section', 'invoicing' ) ;?></div>
    <?php
    return;
}

global $current_page;
$current_page   = empty( $current_page ) ? 1 : absint( $current_page );
$query          = apply_filters( 'wpinv_user_invoices_query', array( 'user' => $user_id, 'page' => $current_page, 'paginate' => true ) );
$user_invoices  = wpinv_get_invoices( $query );
$has_invoices   = 0 < $user_invoices->total;
    
do_action( 'wpinv_before_user_invoices', $has_invoices ); ?>

<?php if ( $has_invoices ) { ?>
	<table class="table table-bordered table-hover wpi-user-invoices">
		<thead>
			<tr>
				<?php foreach ( wpinv_get_user_invoices_columns() as $column_id => $column_name ) : ?>
					<th class="<?php echo esc_attr( $column_id ); ?> <?php echo (!empty($column_name['class']) ? $column_name['class'] : '');?>"><span class="nobr"><?php echo esc_html( $column_name['title'] ); ?></span></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<tbody>
			<?php foreach ( $user_invoices->invoices as $invoice ) {
				?>
				<tr class="wpinv-item wpinv-item-<?php echo $invoice_status = $invoice->get_status(); ?>">
					<?php foreach ( wpinv_get_user_invoices_columns() as $column_id => $column_name ) : ?>
						<td class="<?php echo esc_attr( $column_id ); ?> <?php echo (!empty($column_name['class']) ? $column_name['class'] : '');?>" data-title="<?php echo esc_attr( $column_name['title'] ); ?>">
							<?php if ( has_action( 'wpinv_user_invoices_column_' . $column_id ) ) : ?>
								<?php do_action( 'wpinv_user_invoices_column_' . $column_id, $invoice ); ?>

							<?php elseif ( 'invoice-number' === $column_id ) : ?>
								<a href="<?php echo esc_url( $invoice->get_view_url() ); ?>">
									<?php echo _x( '#', 'hash before invoice number', 'invoicing' ) . $invoice->get_number(); ?>
								</a>

							<?php elseif ( 'invoice-date' === $column_id ) : $date = wpinv_get_invoice_date( $invoice->ID ); $dateYMD = wpinv_get_invoice_date( $invoice->ID, 'Y-m-d H:i:s' ); ?>
								<time datetime="<?php echo strtotime( $dateYMD ); ?>" title="<?php echo $dateYMD; ?>"><?php echo $date; ?></time>

							<?php elseif ( 'invoice-status' === $column_id ) : ?>
								<?php echo wpinv_invoice_status_label( $invoice_status, $invoice->get_status( true ) ) ; ?>

							<?php elseif ( 'invoice-total' === $column_id ) : ?>
								<?php echo $invoice->get_total( true ); ?>

							<?php elseif ( 'invoice-actions' === $column_id ) : ?>
								<?php
									$actions = array(
										'pay'    => array(
											'url'  => $invoice->get_checkout_payment_url(),
											'name' => __( 'Pay Now', 'invoicing' ),
                                            'class' => 'btn-success'
										),
                                        'print'   => array(
											'url'  => $invoice->get_view_url(),
											'name' => __( 'Print', 'invoicing' ),
                                            'class' => 'btn-primary',
                                            'attrs' => 'target="_blank"'
										)
									);

									if ( ! $invoice->needs_payment() ) {
										unset( $actions['pay'] );
									}

									if ( $actions = apply_filters( 'wpinv_user_invoices_actions', $actions, $invoice ) ) {
										foreach ( $actions as $key => $action ) {
											$class = !empty($action['class']) ? sanitize_html_class($action['class']) : '';
                                            echo '<a href="' . esc_url( $action['url'] ) . '" class="btn btn-sm ' . $class . ' ' . sanitize_html_class( $key ) . '" ' . ( !empty($action['attrs']) ? $action['attrs'] : '' ) . '>' . $action['name'] . '</a>';
										}
									}
								?>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>

	<?php do_action( 'wpinv_before_user_invoices_pagination' ); ?>

	<?php if ( 1 < $user_invoices->max_num_pages ) : ?>
		<div class="invoicing-Pagination">
			<?php
			$big = 999999;

			if (get_query_var('paged'))
				$current_page = get_query_var('paged');
			elseif (get_query_var('page'))
				$current_page = get_query_var('page');
			else
				$current_page = 1;

			echo paginate_links( array(
				'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'  => '?paged=%#%',
				'current' => max( 1, $current_page ),
				'total'   => $user_invoices->max_num_pages,
			) );
			?>
		</div>
	<?php endif; ?>

<?php } else { ?>
	<div class="wpinv-empty alert-info">
		<?php _e( 'No invoice has been made yet.', 'invoicing' ); ?>
	</div>
<?php } ?>

<?php do_action( 'wpinv_after_user_invoices', $has_invoices ); ?>
