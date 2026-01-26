<?php
/**
 * Render subscriptions table footer content.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscriptions-table-footer.php.
 *
 * @version 2.8.41
 *
 * @var WPInv_Subscriptions_Widget $widget
 * @var WPInv_Subscriptions_Query $subscriptions_query
 * @var array $subscriptions
 * @var array $columns
 */

defined( 'ABSPATH' ) || exit;

if ( ! empty( $subscriptions ) ) { ?>
	<tfoot>
		<tr>
			<?php foreach ( $columns as $key => $label ) : ?>
				<th class="font-weight-bold getpaid-subscriptions-<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</th>
			<?php endforeach; ?>
		</tr>
	</tfoot>
</table>
<?php } ?>
