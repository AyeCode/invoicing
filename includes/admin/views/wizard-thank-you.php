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
				<h2 class="h3"><?php esc_html_e( 'Next steps', 'invoicing' ); ?></h2>
					<a
						class="btn btn-primary btn-sm"
						href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpi_item' ) ); ?>"><?php esc_html_e( 'Create your first Item!', 'invoicing' ); ?></a>
					<div class="gp-setup-next-steps-first mb-4">
						<h2 class="h3"><?php esc_html_e( 'Examples', 'invoicing' ); ?></h2>
						<a
							class="btn btn-primary btn-sm"
							target="_blank"
							href="https://demos.ayecode.io/getpaid/"><?php esc_html_e( "View What's Possible", 'invoicing' ); ?></a>

						<a
							class="btn btn-outline-primary btn-sm"
							target="_blank"
							href="https://demos.ayecode.io/getpaid/"><?php esc_html_e( "View What's Possible", 'invoicing' ); ?></a>
					</div>
					<div class="gp-setup-next-steps-last">
						<h2 class="h3"><?php _e( 'Learn more', 'invoicing' ); ?></h2>
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
