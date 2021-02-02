<?php
/**
 * Displays a cart item in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/cart-item.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_before_payment_form_cart_item', $form, $item );

$currency = $form->get_currency();

?>
<div class='getpaid-payment-form-items-cart-item getpaid-<?php echo $item->is_required() ? 'required'  : 'selectable'; ?> item-<?php echo $item->get_id(); ?> border-bottom py-2 px-3'>

	<div class="form-row">

		<?php foreach ( array_keys( $columns ) as $key ) : ?>

			<div class="<?php echo 'name' == $key ? 'col-12 col-sm-6' : 'col-12 col-sm' ?> getpaid-form-cart-item-<?php echo sanitize_html_class( $key ); ?> getpaid-form-cart-item-<?php echo sanitize_html_class( $key ); ?>-<?php echo $item->get_id(); ?>">

				<?php

					// Fires before printing a line item column.
					do_action( "getpaid_form_cart_item_before_$key", $item, $form );

					// Item name.
					if ( 'name' == $key ) {

						// Display the name.
						echo '<div class="mb-1">' . sanitize_text_field( $item->get_name() ) . '</div>';

						// And an optional description.
                        $description = $item->get_description();

                        if ( ! empty( $description ) ) {
                            $description = wp_kses_post( $description );
                            echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";
						}

						// Price help text.
                        $description = getpaid_item_recurring_price_help_text( $item, $currency );
                        if ( $description ) {
                            echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";
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
							$price = max( (float) $item->get_price(), (float) $item->get_minimum_price() );
							?>
								<div class="input-group input-group-sm">
									<?php if( 'left' == $position ) : ?>
										<div class="input-group-prepend">
											<span class="input-group-text"><?php echo wpinv_currency_symbol( $currency ); ?></span>
										</div>
									<?php endif; ?>
									<input type="text" name="getpaid-items[<?php echo (int) $item->get_id(); ?>][price]" value="<?php echo $price; ?>" placeholder="<?php echo esc_attr( $item->get_minimum_price() ); ?>" class="getpaid-item-price-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border border">

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
					}

					// Item quantity.
					if ( 'quantity' == $key ) {

						if ( $item->allows_quantities() ) {
							?>
								<input name='getpaid-items[<?php echo (int) $item->get_id(); ?>][quantity]' type='text' class='getpaid-item-quantity-input p-1 align-middle font-weight-normal shadow-none m-0 rounded-0 text-center border' value='<?php echo (float) $item->get_quantity(); ?>' min='1' required>
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
