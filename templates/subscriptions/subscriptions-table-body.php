<?php
/**
 * Render subscriptions table body content.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscriptions-table-body.php.
 *
 * @var WPInv_Subscriptions_Widget $widget
 * @var WPInv_Subscriptions_Query $subscriptions_query
 * @var array $subscriptions
 * @var array $columns
 */

defined( 'ABSPATH' ) || exit;
?>
	<?php if ( ! empty( $subscriptions ) ) { ?>
	<tbody>
		<?php foreach ( $subscriptions as $subscription ) { ?>
		<tr class="getpaid-subscriptions-table-row subscription-<?php echo (int) $subscription->get_id(); ?>">
			<?php
				wpinv_get_template(
					'subscriptions/subscriptions-table-row.php',
					array(
						'subscription' => $subscription,
						'widget'       => $widget,
					)
				);
			?>
		</tr>
		<?php } ?>
	</tbody>
	<?php } else {
		aui()->alert(
			array(
				'content' => wp_kses_post( __( 'No subscriptions found.', 'invoicing' ) ),
				'type'    => 'warning',
			),
			true
		);
	} ?>