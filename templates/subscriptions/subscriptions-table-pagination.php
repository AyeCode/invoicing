<?php
/**
 * Render subscriptions pagination content.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/subscriptions/subscriptions-pagination.php.
 *
 * @var WPInv_Subscriptions_Widget $widget
 * @var WPInv_Subscriptions_Query $subscriptions_query
 * @var array $subscriptions
 * @var array $columns
 * @var int $total
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="getpaid-subscriptions-pagination">
	<?php
		$big = 999999;

		echo wp_kses_post(
			getpaid_paginate_links(
				array(
					'base'   => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format' => '?paged=%#%',
					'total'  => (int) ceil( $total / 10 ),
				)
			)
		);
	?>
</div>