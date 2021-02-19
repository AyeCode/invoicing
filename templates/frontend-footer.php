<?php
/**
 * Template that prints additional code in the footer when viewing a blog on the frontend..
 *
 * This template can be overridden by copying it to yourtheme/invoicing/frontend-footer.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="bsui">
	<div  id="getpaid-payment-modal" class="modal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="checkout" style="max-width: 650px;">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close p-2 getpaid-payment-modal-close d-sm-none" data-dismiss="modal" aria-label="<?php esc_attr__( 'Close', 'invoicing' ); ?>">
						<i class="fa fa-times" aria-hidden="true"></i>
					</button>
					<div class="modal-body-wrapper"></div>
				</div>
			</div>
		</div>
	</div>
</div>
