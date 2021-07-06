<?php
/**
 * Displays the set-up wizard header.
 *
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="card shadow-sm my-5">

    <h1 class="h4 card-header bg-white border-bottom-0 pt-4 pb-1">
        <?php esc_html_e( 'Welcome to the GetPaid Setup Wizard!', 'invoicing' ); ?>
    </h1>

    <div class="card-body text-muted ">
        <p><?php _e( 'Thank you for choosing GetPaid - The most Powerful Payments Plugin for WordPress', 'invoicing' ); ?></p>
        <hr class="mt-4 pt-3 pb-0" />
        <p class="small"><?php _e( 'This quick setup wizard will help you <b>configure the basic settings</b>. It’s <b>completely optional</b> and shouldn’t take longer than <b>five minutes</b>.', 'invoicing' ); ?></p>
    </div>

    <div class="card-footer mb-0 bg-white gp-setup-actions step border-top-0">
        <a
            href="<?php echo esc_url( $next_url ); ?>"
            class="btn btn-primary button-next"><?php esc_html_e( "Let's go!", 'invoicing' ); ?></a>
        <a
            href="<?php echo esc_url( admin_url() ); ?>"
            class="btn btn-link d-block mt-2 "><?php esc_html_e( 'Not right now', 'invoicing' ); ?></a>
    </div>
</div>

<div class="card shadow-sm my-5 overflow-hidden">
    <h1 class="h4 card-header bg-white border-bottom-0  pt-4 pb-1">
        <?php esc_html_e( 'GetPaid Features & Addons!', 'invoicing' ); ?>
    </h1>

    <div class="card-body text-muted overflow-hidden">
		<p><?php _e( 'Collect one time & recurring payments online within minutes. No complex setup required.', 'invoicing' ); ?></p>
		<hr>

		<div class="row row-cols-2 text-left">
			<div class="col mt-3">
				<div class="media">
                    <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/buy.svg';?>" class="mr-3" alt="...">
                    <div class="media-body">
                        <h6 class="mt-0 font-weight-bold"><?php _e( 'GetPaid via Buy Now Buttons', 'invoicing' );?></h6>
                        <small><?php _e( 'Sell via buy now buttons anywhere on your site', 'invoicing' );?></small>
                    </div>
                </div>
			</div>

            <div class="col mt-3">
                <div class="media">
                    <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/report.svg';?>" class="mr-3" alt="...">
                        <div class="media-body">
                        <h6 class="mt-0 font-weight-bold"><?php _e( 'GetPaid via payment form', 'invoicing' );?></h6>
                        <small><?php _e( 'Payment forms are conversion-optimized checkout forms', 'invoicing' );?></small>
                    </div>
                </div>
		    </div>

            <div class="col mt-3">
                <div class="media">
                    <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/invoices.svg';?>" class="mr-3" alt="...">
                    <div class="media-body">
                        <h6 class="mt-0 font-weight-bold"><?php _e('GetPaid via Invoice','invoicing');?></h6>
                        <small><?php _e('Create and send invoices for just about anything from the WordPress dashboard','invoicing');?></small>
                    </div>
                </div>
		    </div>

            <div class="col mt-3">
                <div class="media">
                    <img src="<?php echo WPINV_PLUGIN_URL . 'assets/images/payment.svg';?>" class="mr-3" alt="...">
                    <div class="media-body">
                        <h6 class="mt-0 font-weight-bold"><?php _e('Affordable payment gateways','invoicing');?></h6>
                        <small><?php _e('On average our gateways are over 66% cheaper than our competition','invoicing');?></small>
                    </div>
                </div>
		    </div>
		</div>

	</div>

	<div class="mt-5">
		<a
            href="https://wpgetpaid.com/features-list/"
			class="btn btn-primary"><?php esc_html_e( 'View All Features!', 'invoicing' ); ?></a>
	</div>

	<div class="mt-5 mx-n4 py-4" style="background:#eafaf6;">
		<h4 class="mt-0 font-weight-bold text-dark mb-4"><?php _e( 'More with Membership!' , 'invoicing' );?></h4>
		<div class="row row-cols-2 text-left px-5">

			<div class="col">
				<ul class="list-unstyled">
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'PDF Invoices' , 'invoicing' );?></li>
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'Gravity Forms' , 'invoicing' );?></li>
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'Contact form 7' , 'invoicing' );?></li>
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'AffiliateWP Integration' , 'invoicing' );?></li>
				</ul>
			</div>

			<div class="col">
				<ul class="list-unstyled">
			    	<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'Ninja forms' , 'invoicing' );?></li>
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'Digital Downloads' , 'invoicing' );?></li>
					<li class="my-2"><i class="far fa-check-circle text-success"></i> <?php _e( 'Wallet' , 'invoicing' );?></li>
				</ul>
			</div>
		</div>

		<h5 class="mt-4 font-weight-bold text-dark mb-3"><?php _e('Membership Starts From','invoicing');?></h5>
		<h1 class="mt-0 font-weight-bold text-dark mb-4 display-3"><?php esc_html_e( '$49', 'invoicing' ); ?></h1>

		<div class="mt-2">
			<a
                href="https://wpgetpaid.com/downloads/membership/"
				class="btn btn-primary"><?php esc_html_e( 'Buy Membership Now!', 'invoicing' ); ?></a>
		</div>

	</div>

    <div class="card-footer mb-0 bg-white gp-setup-actions step border-top-0">
        <a
            href="<?php echo esc_url( $next_url ); ?>"
            class="btn btn-outline-primary button-next"><?php esc_html_e( 'Launch the Setup Wizard!', 'invoicing' ); ?></a>
        <a
            href="https://docs.wpgetpaid.com/"
            class="btn btn-outline-primary ml-4"><?php esc_html_e( 'Documentation', 'invoicing' ); ?></a>
        <a
            href="<?php echo esc_url( admin_url() ); ?>"
            class="btn btn-link d-block mt-2 "><?php esc_html_e( 'Not right now', 'invoicing' ); ?></a>
    </div>
</div>
