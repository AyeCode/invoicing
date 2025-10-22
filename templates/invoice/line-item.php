<?php
/**
 * Displays a single line item in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/line-item.php.
 *
 * @version 2.8.17
 * @var WPInv_Invoice $invoice
 * @var GetPaid_Form_Item $item
 * @var array $columns
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

do_action( 'getpaid_before_invoice_line_item', $invoice, $item );
?>
<div class='getpaid-invoice-item item-<?php echo (int) $item->get_id(); ?> item-type-<?php echo esc_attr( $item->get_type() ); ?> border-bottom'>
	<div class="form-row row align-items-center">
		<?php foreach ( array_keys( $columns ) as $column ) : ?>
			<div class="<?php echo 'name' === $column ? 'col-12 col-sm-6' : 'col-12 col-sm'; ?> getpaid-invoice-item-<?php echo esc_attr( $column ); ?>">
				<?php
					// Fires before printing a line item column.
					do_action( "getpaid_invoice_line_item_before_$column", $item, $invoice );

					// Item name.
					if ( 'name' === $column ) {

						$has_featured_image = has_post_thumbnail( $item->get_id() );

						if ( $has_featured_image ) {
							echo '<div class="d-flex align-items-center getpaid-form-item-has-featured-image">';
							echo '<div class="getpaid-form-item-image-container ' . ( $aui_bs5 ? 'me-3' : 'mr-3' ) . '" style="min-width:75px;width:75px">';
							echo get_the_post_thumbnail( $item->get_id(), array( 75, 75 ), array( 'class' => 'getpaid-form-item-image mb-0' ) );
							echo '</div>';
							echo '<div class="getpaid-form-item-name-container">';
						}

						// Display the name.
						echo '<div class="mb-1">' . esc_html( $item->get_name() ) . '</div>';

						// And an optional description.
						$description = $item->get_description();

						if ( ! empty( $description ) ) {
							echo "<small class='form-text text-muted pr-1 pe-1 m-0 lh-sm'>" . wp_kses_post( $description ) . '</small>';
						}

						// Fires before printing the line item actions.
						do_action( 'getpaid_before_invoice_line_item_actions', $item, $invoice );

						$actions = apply_filters( 'getpaid-invoice-page-line-item-actions', array(), $item, $invoice );

						if ( ! empty( $actions ) ) {

							$sanitized  = array();
							foreach ( $actions as $key => $item_action ) {
								$key         = sanitize_html_class( $key );
								$item_action = wp_kses_post( $item_action );
								$sanitized[] = "<span class='$key'>$item_action</span>";
							}

							echo "<small class='text-primary'>";
							echo wp_kses_post( implode( ' | ', $sanitized ) );
							echo '</small>';

						}

						if ( $has_featured_image ) {
							echo '</div>';
							echo '</div>';
						}
					}

					// Item price.
					if ( 'price' === $column ) {

					// Display the item price (or recurring price if this is a renewal invoice)
					$price = $invoice->is_renewal() ? $item->get_price() : $item->get_initial_price();
					wpinv_the_price( $price, $invoice->get_currency() );

					}

					// Tax rate.
					if ( 'tax_rate' === $column ) {
					echo floatval( round( getpaid_get_invoice_tax_rate( $invoice, $item ), 2 ) ) . '%';
					}

					// Item quantity.
					if ( 'quantity' === $column ) {
					echo (float) $item->get_quantity();
					}

					// Item sub total.
					if ( 'subtotal' === $column ) {
					$subtotal = $invoice->is_renewal() ? $item->get_recurring_sub_total() : $item->get_sub_total();
					wpinv_the_price( $subtotal, $invoice->get_currency() );
					}

					// Fires when printing a line item column.
					do_action( "getpaid_invoice_line_item_$column", $item, $invoice );

					// Fires after printing a line item column.
					do_action( "getpaid_invoice_line_item_after_$column", $item, $invoice );
				?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php
