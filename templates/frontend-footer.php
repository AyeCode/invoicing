<?php
/**
 * Template that prints additional code in the footer when viewing a blog on the frontend..
 *
 * This template can be overridden by copying it to yourtheme/invoicing/frontend-footer.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>

<div class="bsui">
	<div  id="getpaid-payment-modal" class="modal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="checkout" style="max-width: 650px;">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class=" btn-close p-2 getpaid-payment-modal-close d-sm-none" data-<?php echo $aui_bs5 ? 'bs-' : ''; ?>dismiss="modal" aria-label="<?php esc_attr__( 'Close', 'invoicing' ); ?>">
						<?php if ( empty( $aui_bs5 ) ) : ?>
                            <span aria-hidden="true">×</span>
                        <?php endif; ?>
					</button>
					<div class="modal-body-wrapper"></div>
				</div>
			</div>
		</div>
	</div>
</div>
