<?php
/**
 * Invoice Shipping Address
 *
 * Display the invoice shipping address meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Shipping_Address Class.
 */
class GetPaid_Meta_Box_Invoice_Shipping_Address {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {

		// Retrieve shipping address.
		$shipping_address = get_post_meta( $post->ID, 'shipping_address', true );

		// Abort if it is invalid.
		if ( ! is_array( $shipping_address ) ) {
			return;
		}

		?>

		<div class="bsui">

			<?php foreach ( getpaid_user_address_fields() as $key => $label ) : ?>

					<?php if ( ! empty( $shipping_address[ $key ] ) ) : ?>

						<div class="form-group form-row">
							<div class="col">
								<span style="font-weight: 600"><?php echo esc_html( $label ); ?>:</span>
							</div>
							<div class="col">
								<?php echo self::prepare_for_display( $shipping_address, $key ); ?>
							</div>
						</div>

					<?php endif; ?>

			<?php endforeach; ?>

		</div>

		<?php

	}

	/**
	 * Prepares a value.
	 *
	 * @param array $address
	 * @param string $key
	 * @return string
	 */
	public static function prepare_for_display( $address, $key ) {

		// Prepare the value.
		$value = $address[ $key ];

		if ( $key == 'country' ) {
			$value = wpinv_country_name( $value );
		}

		if ( $key == 'state' ) {
			$country = isset( $address[ 'country' ] ) ? $address[ 'country' ] : wpinv_get_default_country();
			$value = wpinv_state_name( $value, $country );
		}

		return esc_html( $value );

	}

}
