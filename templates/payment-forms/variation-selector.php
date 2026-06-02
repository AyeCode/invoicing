<?php
/**
 * Variation Selector Template.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/variation-selector.php.
 *
 * @package GetPaid
 * @version 2.8.47
 *
 * @var int                  $item_id              Item post ID.
 * @var array                $variations           Array of variation data.
 * @var string               $selected_id          Currently selected variation ID.
 * @var string               $currency             Currency code.
 * @var GetPaid_Form_Item    $item                 Form item object.
 * @var GetPaid_Payment_Form $form                 Payment form object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="getpaid-variation-selector mb-3" data-item-id="<?php echo esc_attr( $item_id ); ?>">

	<?php foreach ( $variations as $variation ) :

		$is_selected     = ( $variation['id'] === $selected_id );
		$formatted_price = wpinv_price( $variation['price'], $currency );

		$billing_label = '';

		if ( ! empty( $variation['is_recurring'] ) ) {
			$periods = array(
				'D' => __( 'day', 'invoicing' ),
				'W' => __( 'week', 'invoicing' ),
				'M' => __( 'month', 'invoicing' ),
				'Y' => __( 'year', 'invoicing' ),
			);

			$period_label = isset( $periods[ $variation['recurring_period'] ] ) ? $periods[ $variation['recurring_period'] ] : $variation['recurring_period'];

			if ( (int) $variation['recurring_interval'] > 1 ) {
				/* translators: 1: Interval number, 2: Period label. */
				$billing_label = sprintf( __( ' / %1$d %2$ss', 'invoicing' ), $variation['recurring_interval'], $period_label );
			} else {
				/* translators: %s: Period label. */
				$billing_label = sprintf( __( ' / %s', 'invoicing' ), $period_label );
			}
		}

		$features = array();

		if ( isset( $variation['max_activations'] ) && '' !== $variation['max_activations'] ) {
			$max = absint( $variation['max_activations'] );

			if ( 0 === $max ) {
				$features[] = __( 'Unlimited activations', 'invoicing' );
			} elseif ( 1 === $max ) {
				$features[] = __( '1 activation', 'invoicing' );
			} else {
				/* translators: %d: Number of activations. */
				$features[] = sprintf( __( '%d activations', 'invoicing' ), $max );
			}
		}

		if ( ! empty( $variation['license_interval'] ) ) {
			$dur_periods = array(
				'days'   => __( 'day', 'invoicing' ),
				'weeks'  => __( 'week', 'invoicing' ),
				'months' => __( 'month', 'invoicing' ),
				'years'  => __( 'year', 'invoicing' ),
			);

			$dur_label = isset( $dur_periods[ $variation['license_period'] ] ) ? $dur_periods[ $variation['license_period'] ] : '';

			if ( $dur_label ) {
				$interval = absint( $variation['license_interval'] );

				if ( $interval > 1 ) {
					/* translators: 1: Interval number, 2: Period label. */
					$features[] = sprintf( __( '%1$d %2$s license', 'invoicing' ), $interval, $dur_label );
				} else {
					/* translators: 1: Period label (e.g. "year"). */
					$features[] = sprintf( __( '1 %s license', 'invoicing' ), $dur_label );
				}
			}
		} elseif ( empty( $variation['is_recurring'] ) && isset( $variation['max_activations'] ) ) {
			$features[] = __( 'Lifetime license', 'invoicing' );
		}

		/**
		 * Filters the features displayed for a variation in the frontend selector.
		 *
		 * @since 2.8.47
		 *
		 * @param array $features List of feature strings.
		 * @param array $variation Variation data.
		 * @param int   $item_id  Item post ID.
		 */
		$features = apply_filters( 'getpaid_variation_selector_features', $features, $variation, $item_id );

		$features_text = implode( ' · ', $features );
		?>

		<label class="getpaid-variation-option mb-2 <?php echo $is_selected ? 'getpaid-variation-active' : ''; ?>">

			<input
				type="radio"
				class="getpaid-variation-radio"
				name="getpaid-variation[<?php echo esc_attr( $item_id ); ?>]"
				value="<?php echo esc_attr( $variation['id'] ); ?>"
				data-item-id="<?php echo esc_attr( $item_id ); ?>"
				data-price="<?php echo esc_attr( $variation['price'] ); ?>"
				<?php checked( $is_selected ); ?>
			>

			<span class="getpaid-variation-option-inner">
				<span class="getpaid-variation-option-left">
					<span class="fw-bold d-inline-flex align-items-center fs-lg gap-1">
						<?php echo esc_html( $variation['name'] ); ?>
						<?php if ( ! empty( $variation['is_default'] ) ) : ?>
							<span class="getpaid-variation-badge"><?php echo esc_html( apply_filters( 'getpaid_default_variation_badge_text', __( 'Recommended', 'invoicing' ), $variation, $item_id ) ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( ! empty( $features_text ) ) : ?>
						<span class="text-muted d-block fs-base"><?php echo esc_html( $features_text ); ?></span>
					<?php endif; ?>
				</span>
				<span class="getpaid-variation-option-right fw-bold">
					<?php echo wp_kses_post( $formatted_price . $billing_label ); ?>
				</span>
			</span>

		</label>

	<?php endforeach; ?>

</div>
