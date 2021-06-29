<?php
/**
 * Displays the set-up wizard bussiness settings.
 *
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="card shadow-sm my-5">
	<div class="text-center card-body">
		<h1 class="h3"><?php esc_html_e( 'Awesome, you are ready to Get Paid', 'invoicing' ); ?></h1>

		<div class="geodirectory-message geodirectory-tracker">
			<p><?php _e( 'Thank you for choosing GetPaid!', 'invoicing' ); ?> <i class="far fa-smile-beam"></i></p>
		</div>

		<div class="gp-setup-next-steps">
			<div class="gp-setup-next-steps-first mb-4">
				<div>
					<h2 class="h3"><i class="fas fa-arrow-down"></i> <?php esc_html_e( 'Watch This', 'invoicing' ); ?> <i class="fas fa-arrow-down"></i></h2>
					<div class="embed-responsive embed-responsive-16by9 mb-4">
						<iframe class="embed-responsive-item" src="https://www.youtube.com/embed/TXZuPXHjt9E?rel=0" allowfullscreen></iframe>
					</div>
				</div>
				<h2 class="h3"><?php esc_html_e( 'Next steps', 'invoicing' ); ?></h2>

				<div class="d-flex justify-content-between">
					<a
						class="btn btn-outline-primary btn-sm"
						href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_item' ) ); ?>">
						<span class="h1 d-block"><i class="fas fa-box-open"></i></span>
						<?php esc_html_e( 'Create Item', 'invoicing' ); ?>
					</a>
					<a
						class="btn btn-outline-primary btn-sm"
						href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_payment_form' ) ); ?>">
						<span class="h1 d-block"><i class="fas fa-align-justify"></i></span>
						<?php esc_html_e( 'Create Payment Form', 'invoicing' ); ?>
					</a>
					<a
						class="btn btn-outline-primary btn-sm"
						href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_invoice' ) ); ?>">
						<span class="h1 d-block"><i class="fas fa-file-alt"></i></span>
						<?php esc_html_e( 'Create Invoice', 'invoicing' ); ?>
					</a>
				</div>



						<h2 class="h3 mt-4"><?php _e( 'Learn more', 'invoicing' ); ?></h2>
				<div class="gp-setup-next-steps-last mt-2 d-flex justify-content-between">
						<a
							class="btn btn-outline-primary btn-sm" href="https://docs.wpgetpaid.com/collection/114-getting-started?utm_source=setupwizard&utm_medium=product&utm_content=getting-started&utm_campaign=invoicingplugin"
							target="_blank"><?php esc_html_e( 'Getting Started', 'invoicing' ); ?></a>
						<a
							class="btn btn-outline-primary btn-sm"
							href="https://docs.wpgetpaid.com/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=invoicingplugin"
							target="_blank"><?php esc_html_e( 'Documentation', 'invoicing' ); ?></a>
						<a
							class="btn btn-outline-primary btn-sm"
							href="https://wpgetpaid.com/support/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=invoicingyplugin"
							target="_blank"><?php esc_html_e( 'Support', 'invoicing' ); ?></a>
						<a
							class="btn btn-outline-primary btn-sm"
							href="https://demos.ayecode.io/getpaid/?utm_source=setupwizard&utm_medium=product&utm_content=demos&utm_campaign=invoicingyplugin"
							target="_blank"><?php esc_html_e( 'Demos', 'invoicing' ); ?></a>
					</div>
			</div>
		</div>

	</div>
</div>
