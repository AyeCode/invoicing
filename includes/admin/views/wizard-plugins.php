<?php
/**
 * Displays the set-up wizard bussiness settings.
 *
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="card shadow-sm my-5">
	<form method="post" class="text-center card-body" action="<?php echo esc_url( admin_url() ); ?>">
		<?php getpaid_hidden_field( 'getpaid-admin-action', 'install_plugin' ); ?>
		<?php wp_nonce_field( 'getpaid-nonce', 'getpaid-nonce' ); ?>
		<?php getpaid_hidden_field( 'redirect', $next_url ); ?>
		<div class="gd-wizard-recommend">
			<h2 class="gd-settings-title h3"><?php esc_html_e( 'Recommended Plugins', 'invoicing' ); ?></h2>
			<p><?php esc_html_e( 'Below are a few of our own plugins that may help you.', 'invoicing' ); ?></p>
			<ul class="list-group">
				<?php foreach ( $recommended_plugins as $key => $plugin ) : ?>
				<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap <?php echo( $aui_bs5 ? 'text-start' : 'text-left' ); ?>">
					<span class="mr-auto"><?php echo esc_html( $plugin['name'] ); ?></span>
					<div class="<?php echo( $aui_bs5 ? 'form-check form-switch me-n2' : 'custom-control custom-switch mr-n2 getpaid-install-plugin-siwtch-div' ); ?>">
						<input type="checkbox" name="plugins[<?php echo esc_attr( $plugin['slug'] ); ?>]" value="<?php echo esc_attr( $plugin['file'] ); ?>" class="<?php echo ( $aui_bs5 ? 'form-check-input' : 'custom-control-input' ); ?>" <?php if ( is_plugin_active( $plugin['file'] ) ) { echo 'checked';}?> id="ac-setting-updates<?php echo esc_attr( $key ); ?>">
						<label class="<?php echo ( $aui_bs5 ? 'form-check-label' : 'custom-control-label' ); ?>" for="ac-setting-updates<?php echo esc_attr( $key ); ?>"></label>
					</div>
					<small class="w-100"><?php echo esc_attr( $plugin['desc'] ); ?></small>
				</li>
				<?php endforeach; ?>
			</ul>
			<p class="gp-setup-actions step text-center mt-4"><input type="submit" class="btn btn-primary button-next" value="<?php esc_attr_e( 'Continue', 'invoicing' ); ?>" name="save_step"/></p>
		</div>
	</form>
</div>