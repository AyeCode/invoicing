<?php
/**
 * Displays a cart item in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/cart-item.php.
 *
 * @version 1.0.19
 * @var GetPaid_Payment_Form $form
 * @var GetPaid_Form_Item $item
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_before_payment_form_cart_item', $form, $item );

$currency = $form->get_currency();

?>
<div class='getpaid-payment-form-items-cart-item getpaid-<?php echo $item->is_required() ? 'required'  : 'selectable'; ?> item-<?php echo $item->get_id(); ?> border-bottom py-2 px-3'>

	<div class="form-row needs-validation">

		<?php foreach ( array_keys( $columns ) as $key ) : ?>

			<div class="<?php echo 'name' == $key ? 'col-6' : 'col' ?> <?php echo ( in_array( $key, array( 'subtotal', 'quantity', 'tax_rate' ) ) ) ? 'd-none d-sm-block' : '' ?> position-relative getpaid-form-cart-item-<?php echo sanitize_html_class( $key ); ?> getpaid-form-cart-item-<?php echo sanitize_html_class( $key ); ?>-<?php echo $item->get_id(); ?>">

				<?php

					// Fires before printing a line item column.
					do_action( "getpaid_form_cart_item_before_$key", $item, $form );

					// Item name.
					if ( 'name' == $key ) {

						ob_start();
						// And an optional description.
                        $description = $item->get_description();

                        if ( ! empty( $description ) ) {
                            $description = wp_kses_post( $description );
                            echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";
						}

						// Price help text.
                        $description = getpaid_item_recurring_price_help_text( $item, $currency );
                        if ( $description ) {
                            echo "<small class='getpaid-form-item-price-desc form-text text-muted pr-2 m-0'>$description</small>";
						}

						do_action( 'getpaid_payment_form_cart_item_description', $item, $form );

						if ( wpinv_current_user_can_manage_invoicing() ) {

							edit_post_link(
								__( 'Edit this item.', 'invoicing' ),
								'<small class="form-text text-muted">',
								'</small>',
								$item->get_id(),
								'text-danger'
							);

						}

						$description = ob_get_clean();

						// Display the name.
						$tootip = empty( $description ) ? '' : "&nbsp;" . '<i class="fas fa-xs fa-info gp-tooltip d-sm-none text-muted"></i>';
						echo '<div class="mb-1">' . esc_html( $item->get_name() ) . $tootip . '</div>';

						if ( ! empty( $description ) ) {
							printf( '<span class="d-none d-sm-block getpaid-item-desc">%s</span>', $description );
						}

						if ( $item->allows_quantities() ) {
							printf(
								'<small class="d-sm-none text-muted form-text">%s</small>',
								sprintf(
									__( 'Qty %s', 'invoicing' ),
									sprintf(
										'<input
											type="text"
											style="width: 48px;"
											class="getpaid-item-mobile-quantity-input p-1 m-0 text-center"
											value="%s"
											min="1">',
											(float) $item->get_quantity() == 0 ? 1 : (float) $item->get_quantity()
									)
								)
							);
						} else {
							printf(
								'<small class="d-sm-none text-muted form-text">%s</small>',
								sprintf(
									__( 'Qty %s', 'invoicing' ),
									(float) $item->get_quantity()
								)
							);
						}

					}

					// Item price.
					if ( 'price' == $key ) {

						// Set the currency position.
						$position = wpinv_currency_position();

						if ( $position == 'left_space' ) {
							$position = 'left';
						}

						if ( $position == 'right_space' ) {
							$position = 'right';
						}

						if ( $item->user_can_set_their_price() ) {
							$price            = max( (float) $item->get_price(), (float) $item->get_minimum_price() );
							$minimum          = (float) $item->get_minimum_price();
							$validate_minimum = '';
							$class            = '';
							$data_minimum     = '';

							if ( $minimum > 0 ) {
								$validate_minimum = sprintf(
									esc_attr__( 'The minimum allowed amount is %s', 'invoicing' ),
									strip_tags( wpinv_price( $minimum, $currency ) )
								);

								$class = 'getpaid-validate-minimum-amount';

								$data_minimum     = "data-minimum-amount='" . esc_attr( getpaid_unstandardize_amount( $minimum ) ) . "'";
							}

							?>
								<div class="input-group input-group-sm">
									<?php if( 'left' == $position ) : ?>
										<div class="input-group-prepend">
											<span class="input-group-text"><?php echo wpinv_currency_symbol( $currency ); ?></span>
										</div>
									<?php endif; ?>

									<input type="text" <?php echo $data_minimum; ?> name="getpaid-items[<?php echo (int) $item->get_id(); ?>][price]" value="<?php echo esc_attr( getpaid_unstandardize_amount( $price ) ); ?>" placeholder="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_minimum_price() ) ); ?>" class="getpaid-item-price-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border <?php echo $class; ?>" style="width: 64px; line-height: 1; min-height: 35px;">

									<?php if ( ! empty( $validate_minimum ) ) : ?>
										<div class="invalid-tooltip">
											<?php echo $validate_minimum; ?>
										</div>
									<?php endif; ?>

									<?php if( 'left' != $position ) : ?>
										<div class="input-group-append">
											<span class="input-group-text"><?php echo wpinv_currency_symbol( $currency ); ?></span>
										</div>
									<?php endif; ?>
								</div>

							<?php

						} else {
							echo wpinv_price( $item->get_price(), $currency );

							?>
								<input name='getpaid-items[<?php echo (int) $item->get_id(); ?>][price]' type='hidden' class='getpaid-item-price-input' value='<?php echo esc_attr( $item->get_price() ); ?>'>
							<?php
						}

						printf(
							'<small class="d-sm-none text-muted form-text getpaid-mobile-item-subtotal">%s</small>',
							sprintf( __( 'Subtotal: %s', 'invoicing' ), wpinv_price( $item->get_sub_total(), $currency ) )
						);
					}

					// Item quantity.
					if ( 'quantity' == $key ) {

						if ( $item->allows_quantities() ) {
							?>
								<input name='getpaid-items[<?php echo (int) $item->get_id(); ?>][quantity]' type='text' style='width: 64px; line-height: 1; min-height: 35px;' class='getpaid-item-quantity-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border' value='<?php echo (float) $item->get_quantity() == 0 ? 1 : (float) $item->get_quantity(); ?>' min='1' required>
							<?php
						} else {
							echo (float) $item->get_quantity();
							echo '&nbsp;&nbsp;&nbsp;';
							?>
								<input type='hidden' name='getpaid-items[<?php echo (int) $item->get_id(); ?>][quantity]' class='getpaid-item-quantity-input' value='<?php echo (float) $item->get_quantity(); ?>'>
							<?php
						}

					}

					// Item sub total.
					if ( 'subtotal' == $key ) {
						echo wpinv_price( $item->get_sub_total(), $currency );
					}

					do_action( "getpaid_payment_form_cart_item_$key", $item, $form );
				?>

			</div>

		<?php endforeach; ?>

	</div>

</div>
<?php
do_action(  'getpaid_payment_form_cart_item', $form, $item );
