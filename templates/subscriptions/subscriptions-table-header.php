<?php
/**
 * Render subscriptions table header content.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscriptions-table-header.php.
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
<table class="table table-bordered table-striped">
	<thead>
		<tr>
			<?php foreach ( $columns as $key => $label ) { ?>
				<th scope="col" class="font-weight-bold getpaid-subscriptions-table-<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</th>
			<?php } ?>
		</tr>
	</thead>
<?php } ?>