<?php
/**
 * Displays the set-up wizard bussiness settings.
 *
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="card shadow-sm my-5">'

    <form method="post" class="text-center card-body">
        <div class="gp-wizard-payments">
            <h2 class="gd-settings-title h3 "><?php esc_html_e( 'Gateway Setup', 'invoicing' ); ?></h2>
            <p><?php esc_html_e( 'Below are a few gateways that can be setup in a few seconds.', 'invoicing' ); ?>
                <br>
                <?php esc_html_e( 'We have 20+ Gateways that can be setup later.', 'invoicing' ); ?>
            </p>

            <ul class="list-group">

				<li class="list-group-item d-flex justify-content-between align-items-center">
				    <span class="mr-auto"><img src="<?php echo esc_url( WPINV_PLUGIN_URL . 'assets/images/stripe-verified.svg' ); ?>" class="<?php echo( $aui_bs5 ? 'ms-n2' : 'ml-n2' ); ?>" alt="Stripe"></span>
				    <?php if ( false === wpinv_get_option( 'stripe_live_connect_account_id' ) ) : ?>
                        <a href="<?php
                        echo esc_url( wp_nonce_url(
                            add_query_arg(
                                array(
                                    'getpaid-admin-action' => 'connect_gateway',
                                    'plugin'               => 'stripe',
                                    'redirect'             => urlencode( add_query_arg( 'step', 'payments' ) ),
                                ),
                                admin_url()
                            ),
                            'getpaid-nonce',
                            'getpaid-nonce'
                        ));
                        ?>"
                        class="btn btn-sm btn-outline-primary"><?php esc_html_e( 'Connect', 'invoicing' ); ?></a>
                    <?php else : ?>
                        <span class="btn btn-sm btn-success"><?php esc_html_e( 'Connected', 'invoicing' ); ?></span>
                    <?php endif; ?>
				</li>

				<li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="<?php echo( $aui_bs5 ? 'me-auto' : 'mr-auto' ); ?>">
                            <img src="<?php echo esc_url( WPINV_PLUGIN_URL . 'assets/images/pp-logo-150px.webp' ); ?>" class="" alt="PayPal" height="25">
                        </span>
                        <a
                            href="#"
                            onclick="jQuery('.getpaid-setup-paypal-input').toggleClass('d-none'); return false;"
                            class="getpaid-setup-paypal btn btn-sm btn-outline-primary"><?php esc_html_e( 'Set-up', 'invoicing' ); ?></a>
                    </div>
                    <div class="mt-4 getpaid-setup-paypal-input d-none">
                        <input type="text" placeholder="<?php esc_attr_e( 'PayPal Email', 'invoicing' ); ?>" name="paypal-email" class="form-control" value="<?php echo esc_attr( wpinv_get_option( 'paypal_email' ) ); ?>">
                    </div>
                </li>

				<li class="list-group-item d-flex justify-content-between align-items-center">
					<span class="<?php echo( $aui_bs5 ? 'me-auto' : 'mr-auto' ); ?>"><?php esc_html_e( 'Test Gateway', 'invoicing' ); ?></span>
					<div class="<?php echo ( $aui_bs5 ? 'form-check form-switch' : 'custom-control custom-switch' ); ?>">
						<input type="checkbox" name="enable-manual-gateway" id="enable-manual-gateway" value="1" class="<?php echo ( $aui_bs5 ? 'form-check-input' : 'custom-control-input' ); ?>" <?php checked( wpinv_is_gateway_active( 'manual' ) ); ?>>
						<label class="<?php echo ( $aui_bs5 ? 'form-check-label' : 'custom-control-label' ); ?>" for="enable-manual-gateway"></label>
					</div>
				</li>
			</ul>
        </div>

        <p class="gp-setup-actions step text-center mt-4">
			<input type="submit" class="btn btn-primary" value="<?php esc_attr_e( 'Continue', 'invoicing' ); ?>" />
		</p>
        
        <?php getpaid_hidden_field( 'save_step', 1 ); ?>
        <?php wp_nonce_field( 'getpaid-setup-wizard', 'getpaid-setup-wizard' ); ?>
    </form>
</div>
