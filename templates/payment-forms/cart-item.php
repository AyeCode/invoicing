<?php
/**
 * Displays a cart item in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/cart-item.php.
 *
 * @version 2.8.17
 * @var GetPaid_Payment_Form $form
 * @var GetPaid_Form_Item $item
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_before_payment_form_cart_item', $form, $item );

$currency = $form->get_currency();
$max_qty  = wpinv_item_max_buyable_quantity( $item->get_id() );
?>
<div class='getpaid-payment-form-items-cart-item getpaid-<?php echo $item->is_required() ? 'required' : 'selectable'; ?> item-<?php echo (int) $item->get_id(); ?> border-bottom py-2 px-3'>

	<div class="form-row row align-items-center needs-validation">

		<?php foreach ( array_keys( $columns ) as $key ) : ?>

			<div class="<?php echo 'name' === $key ? 'col-6' : 'col'; ?> <?php echo ( in_array( $key, array( 'subtotal', 'quantity', 'tax_rate' ), true ) ) ? 'd-none d-sm-block' : ''; ?> position-relative getpaid-form-cart-item-<?php echo esc_attr( $key ); ?> getpaid-form-cart-item-<?php echo esc_attr( $key ); ?>-<?php echo (int) $item->get_id(); ?>">

				<?php

					// Fires before printing a line item column.
					do_action( "getpaid_form_cart_item_before_$key", $item, $form );

					// Item name.
					if ( 'name' === $key ) {


						ob_start();

						// Add an optional description.
						$description = $item->get_description();

						if ( ! empty( $description ) ) {
							echo "<small class='form-text text-muted pr-2 m-0'>" . wp_kses_post( $description ) . '</small>';
						}

						// Price help text.
						$description = getpaid_item_recurring_price_help_text( $item, $currency );
						if ( $description ) {
							echo "<small class='getpaid-form-item-price-desc form-text text-muted font-italic pr-2 m-0'>" . wp_kses_post( $description ) . '</small>';
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
						$tootip = empty( $description ) ? '' : '&nbsp;<i class="fas fa-xs fa-info gp-tooltip d-sm-none text-muted"></i>';

						$has_featured_image = has_post_thumbnail( $item->get_id() );

						if ( $has_featured_image ) {
							echo '<div class="d-flex align-items-center getpaid-form-item-has-featured-image">';
							echo '<div class="getpaid-form-item-image-container mr-2" style="width:85px;">';
							echo get_the_post_thumbnail( $item->get_id(), array( 75, 75 ), array( 'class' => 'getpaid-form-item-image mb-0' ) );
							echo '</div>';
							echo '<div class="getpaid-form-item-name-container">';
						}

						echo '<div class="mb-1 font-weight-bold">' . esc_html( $item->get_name() ) . wp_kses_post( $tootip ) . '</div>';

						if ( ! empty( $description ) ) {
							printf( '<span class="d-none d-sm-block getpaid-item-desc">%s</span>', wp_kses_post( $description ) );
						}

						if ( $item->allows_quantities() ) {
							printf(
								'<small class="d-sm-none text-muted form-text">%s</small>',
								sprintf(
									// translators: %s is the item quantity.
									esc_html__( 'Qty %s', 'invoicing' ),
									sprintf(
										'<input
											type="number"
											step="0.01"
											style="width: 48px;"
											class="getpaid-item-mobile-quantity-input p-1 m-0 text-center"
											value="%s"
											min="1"
											max="%s"
										>',
										(float) $item->get_quantity() == 0 ? 1 : (float) $item->get_quantity(),
										floatval( null !== $max_qty ? $max_qty : 1000000000000 )
									)
								)
							);
						} else {
							printf(
								'<small class="d-sm-none text-muted form-text">%s</small>',
								sprintf(
									// translators: %s is the item quantity.
									esc_html__( 'Qty %s', 'invoicing' ),
									(float) $item->get_quantity()
								)
							);
						}

						if ( $has_featured_image ) {
							echo '</div>';
							echo '</div>';
						}
					}

					// Item price.
					if ( 'price' === $key ) {

					// Set the currency position.
					$position = wpinv_currency_position();

					if ( 'left_space' === $position ) {
						$position = 'left';
					}

					if ( 'right_space' === $position ) {
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
								// translators: %s is the minimum price.
								esc_attr__( 'The minimum allowed amount is %s', 'invoicing' ),
								wp_strip_all_tags( wpinv_price( $minimum, $currency ) )
							);

							$class = 'getpaid-validate-minimum-amount';

							$data_minimum     = "data-minimum-amount='" . esc_attr( getpaid_unstandardize_amount( $minimum ) ) . "'";
						}

						?>
								<div class="input-group input-group-sm">
									<?php if ( 'left' === $position ) : ?>
										<?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
											<div class="input-group-prepend ">
												<span class="input-group-text">
													<?php echo wp_kses_post( wpinv_currency_symbol( $currency ) ); ?></span>
												</span>
											</div>
										<?php else : ?>
											<span class="input-group-text">
												<?php echo wp_kses_post( wpinv_currency_symbol( $currency ) ); ?></span>
											</span>
										<?php endif; ?>
									<?php endif; ?>

									<input type="number" step="0.01" <?php echo wp_kses_post( $data_minimum ); ?> name="getpaid-items[<?php echo (int) $item->get_id(); ?>][price]" value="<?php echo esc_attr( getpaid_unstandardize_amount( $price ) ); ?>" placeholder="<?php echo esc_attr( getpaid_unstandardize_amount( $item->get_minimum_price() ) ); ?>" class="getpaid-item-price-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border <?php echo esc_attr( $class ); ?>" style="width: 64px; line-height: 1; min-height: 35px;">

								<?php if ( ! empty( $validate_minimum ) ) : ?>
										<div class="invalid-tooltip">
											<?php echo wp_kses_post( $validate_minimum ); ?>
										</div>
									<?php endif; ?>

								<?php if ( 'left' !== $position ) : ?>
									<?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
											<div class="input-group-append ">
												<span class="input-group-text">
													<?php echo wp_kses_post( wpinv_currency_symbol( $currency ) ); ?></span>
												</span>
											</div>
										<?php else : ?>
											<span class="input-group-text">
												<?php echo wp_kses_post( wpinv_currency_symbol( $currency ) ); ?></span>
											</span>
										<?php endif; ?>
									<?php endif; ?>
								</div>

							<?php

						} else {
						?>
							<span class="getpaid-items-<?php echo (int) $item->get_id(); ?>-view-price">
								<?php echo wp_kses_post( wpinv_price( $item->get_price(), $currency ) ); ?>
							</span>
							<input name='getpaid-items[<?php echo (int) $item->get_id(); ?>][price]' type='hidden' class='getpaid-item-price-input' value='<?php echo esc_attr( $item->get_price() ); ?>'>
						<?php
						}

					printf(
                        '<small class="d-sm-none text-muted form-text getpaid-mobile-item-subtotal">%s</small>',
						// translators: %s is the item subtotal.
                        sprintf( esc_html__( 'Subtotal: %s', 'invoicing' ), wp_kses_post( wpinv_price( $item->get_sub_total(), $currency ) ) )
                    );
					}

					// Item quantity.
					if ( 'quantity' === $key ) {

					if ( $item->allows_quantities() ) {
						?>
								<input name='getpaid-items[<?php echo (int) $item->get_id(); ?>][quantity]' type="number" step="any" style='width: 64px; line-height: 1; min-height: 35px;' class='getpaid-item-quantity-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border' value='<?php echo (float) $item->get_quantity() == 0 ? 1 : (float) $item->get_quantity(); ?>' min='1' <?php echo null !== $max_qty ? 'max="' . (float) $max_qty . '"' : ''; ?> required>
						<?php
							} else {
						?>
							<span class="getpaid-items-<?php echo (int) $item->get_id(); ?>-view-quantity">
								<?php echo (float) $item->get_quantity(); ?>
							</span>&nbsp;&nbsp;&nbsp;
							<input type='hidden' name='getpaid-items[<?php echo (int) $item->get_id(); ?>][quantity]' class='getpaid-item-quantity-input' value='<?php echo (float) $item->get_quantity(); ?>'>
						<?php
						}
}

					// Item sub total.
					if ( 'subtotal' === $key ) {
					echo wp_kses_post( wpinv_price( $item->get_sub_total(), $currency ) );
					}

					do_action( "getpaid_payment_form_cart_item_$key", $item, $form );
				?>

			</div>

		<?php endforeach; ?>

	</div>

</div>
<?php
do_action( 'getpaid_payment_form_cart_item', $form, $item );
