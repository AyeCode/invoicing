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

        <?php do_action( 'getpaid_payment_form_embed_head' ); ?>

		<?php wpinv_get_template( 'frontend-head.php' ); ?>

    </head>

	<body class="body" style="font-weight: 400;">
	<div class="container my-5" style="max-width: 820px;">
			<?php
				do_action( 'getpaid_payment_form_embed_top' );
				echo do_shortcode( $shortcode );
				do_action( 'getpaid_payment_form_embed_bottom' );
				wpinv_get_template( 'frontend-footer.php' );
			?>
		</div>
	</body>
</html>
