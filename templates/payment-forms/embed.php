<?php

	// Is the request set up correctly?
	if ( empty( $_GET['form'] ) && empty( $_GET['item'] ) ) {
		return aui()->alert(
			array(
				'type'    => 'warning',
				'content' => __( 'No payment form or item selected', 'invoicing' ),
			)
		);
		wp_die( __( 'No payment form or item selected', 'invoicing' ), 400 );
	}

	// Payment form or button?
	if ( ! empty( $_GET['form'] ) ) {

		$shortcode = sprintf(
			'[getpaid form=%s]',
			(int) urldecode( $_GET['form'] )
		);

	} else {

		$shortcode = sprintf(
			'[getpaid item=%s]',
			esc_attr( urldecode( $_GET['item'] ) )
		);

	}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="bsui">

	<head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" >

        <meta name="robots" content="noindex,nofollow">

		<link rel="profile" href="https://gmpg.org/xfn/11">

        <title>GetPaid</title>
		<?php
			wp_enqueue_scripts();
			wp_print_styles();
			wp_print_head_scripts();
			wp_custom_css_cb();
			wpinv_get_template( 'frontend-head.php' );
		?>

		<style type="text/css">
			.body{ 
				background: white;
				width: 100%;
				max-width: 100%;
				text-align: left;
				font-weight: 400;
			}

			/* hide all other elements */
			body::before,
			body::after,
			body > *:not(#getpaid-form-embed):not(.flatpickr-calendar) { 
				display:none !important; 
			}

			#getpaid-form-embed {
				display: block !important;
				width: 100%;
				height: 100%;
				padding: 20px;
				border: 0;
				margin: 0 auto;
				max-width: 820px;
			}
		</style>

    </head>

	<body class="body page-template-default page">
		<div id="getpaid-form-embed" class="container my-5 page type-page status-publish hentry post post-content">
			<?php
				do_action( 'getpaid_payment_form_embed_top' );
				echo do_shortcode( $shortcode );
				do_action( 'getpaid_payment_form_embed_bottom' );
				wpinv_get_template( 'frontend-footer.php' );
			?>
		</div>
		<?php wp_footer(); ?>
	</body>

</html>
