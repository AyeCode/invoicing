<?php
// MUST have WordPress.
if ( ! defined( 'WPINC' ) ) {
	exit;
}

class WPInv_Meta_Box_Payment_Form {

	/**
	 * Output payment form details.
	 *
	 * @param WP_Post $post
	 */
	public static function output_details( $post ) {
		$details = get_post_meta( $post->ID, 'payment_form_data', true );

		if ( ! is_array( $details ) ) {
			return;
		}

		echo '<div class="bsui"><div class="form-row row">';

		foreach ( $details as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( $value, 'is_scalar' ) );
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			printf(
				'<div class="col-12"><strong>%s:</strong></div><div class="col-12 form-group mb-3">%s</div>',
				esc_html( $key ),
				wp_kses_post( getpaid_remove_unsafe_attrs( (string) $value ) )
			);
		}

		echo '</div></div>';
	}

	/**
	 * Output fields.
	 *
	 * @param WP_Post $post
	 */
	public static function output_shortcode( $post ) {
		if ( ! is_numeric( $post ) ) {
			$post = $post->ID;
		}

		if ( $post == wpinv_get_default_payment_form() ) {
			echo '&mdash;';
			return;
		}

		echo "<input type='text' style='min-width: 220px;' value='[getpaid form=" . absint( $post ) . "]' disabled>";
	}
}
