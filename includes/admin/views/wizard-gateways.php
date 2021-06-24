<?php
/**
 * Displays the set-up wizard bussiness settings.
 *
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="card shadow-sm my-5">'

    <form method="post" class="text-center card-body">
        <div class="gp-wizard-payments">
            <h2 class="gd-settings-title h3 "><?php _e( 'Gateway Setup', 'invoicing' ); ?></h2>
            <p><?php _e( 'Below are a few gateways that can be setup in a few seconds.', 'invoicing' ); ?>
                <br>
                <?php _e( 'We have 20+ Gateways that can be setup later.', 'invoicing' ); ?>
            </p>

            <ul class="list-group">

				<li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto"><img src="<?php echo esc_url( WPINV_PLUGIN_URL . 'assets/images/stripe-verified.svg' );?>" class="ml-n2" alt="Stripe"></span>
				    <a href="<?php echo wp_nonce_url(
                            add_query_arg(
                                array(
                                    'getpaid-admin-action' => 'connect_plugin',
                                    'plugin'               => 'stripe',
                                    'redirect'             => urlencode( $next_url ),
                                )
                            ),
                            'getpaid-nonce',
                            'getpaid-nonce'
                        ); ?>"
                        class="btn btn-sm btn-outline-primary"><?php _e( 'Connect', 'invoicing' ); ?></a>
				</li>

				<li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto"><img src="<?php echo esc_url( WPINV_PLUGIN_URL . 'assets/images/pp-logo-150px.webp' );?>" class="" alt="PayPal" height="25"></span>
				    <a href="<?php echo wp_nonce_url(
                            add_query_arg(
                                array(
                                    'getpaid-admin-action' => 'connect_plugin',
                                    'plugin'               => 'paypal',
                                    'redirect'             => urlencode( $next_url ),
                                )
                            ),
                            'getpaid-nonce',
                            'getpaid-nonce'
                        ); ?>"
                        class="btn btn-sm btn-outline-primary"><?php _e( 'Connect', 'invoicing' ); ?></a>
				</li>

				<li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto">Test Gateway</span>
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="ac-setting-updates" checked="" onclick="if(jQuery(this).is(':checked')){}else{}">
						<label class="custom-control-label" for="ac-setting-updates"></label>
					</div>
				</li>

			</ul>
        </div>

        <p class="gp-setup-actions step text-center mt-4">
			<a href="<?php echo esc_url( $next_url ); ?>" class="btn btn-primary"><?php esc_attr_e( 'Continue', 'invoicing' ); ?></a>
		</p>

    </form>
    <p class="gd-return-to-dashboard-wrap">
		<a href="<?php echo esc_url( $next_url ); ?>" class="gd-return-to-dashboard btn btn-link d-block text-muted"><?php _e( 'Skip this step', 'invoicing' ); ?></a>
	</p>
</div>
