<?php
/**
 * Renders an embedded payment form, invoice, or buy-item button.
 *
 * By default the page is a bare standalone HTML skeleton, suitable for iframes
 * or isolated checkouts. Appending ?wrap=true (or ?wrap=1) opts into the active
 * theme's header/footer chrome so the page matches the rest of the site.
 *
 * This template can be overridden by copying it to
 * yourtheme/invoicing/payment-forms/embed.php.
 *
 * @version 2.8.48
 */

defined( 'ABSPATH' ) || exit;

// Is the request set up correctly?
if ( empty( $_GET['form'] ) && empty( $_GET['item'] ) && empty( $_GET['invoice'] ) ) {
	return aui()->alert(
		array(
			'type'    => 'warning',
			'content' => __( 'No payment form or item selected', 'invoicing' ),
		)
	);
}

// Payment form, invoice, or buy-item button?
if ( ! empty( $_GET['form'] ) ) {

	$shortcode = sprintf(
		'[getpaid form=%s]',
		(int) $_GET['form']
	);

} elseif ( ! empty( $_GET['invoice'] ) ) {

	$shortcode = sprintf(
		'[getpaid_invoice id=%s]',
		(int) $_GET['invoice']
	);

} else {

	$shortcode = sprintf(
		'[getpaid item=%s]',
		esc_attr( urldecode( $_GET['item'] ) )
	);

}

// Keep embed URLs out of search indexes. The bare skeleton hard-codes a <meta robots>;
// wrapped mode delegates to the theme's wp_head().
add_filter(
	'wp_robots',
	static function ( $robots ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'] );
		return $robots;
	}
);

// Opt-in theme chrome: ?wrap=true or ?wrap=1.
$getpaid_embed_wrap = isset( $_GET['wrap'] )
	&& in_array( strtolower( (string) $_GET['wrap'] ), array( 'true', '1' ), true );

if ( $getpaid_embed_wrap ) {

	// Runtime-measured offset for .navbar.fixed-top: height varies by viewport and theme.
	// scroll-padding-top keeps anchor links from landing behind the bar.
	$render_offset_assets = static function () {
		?>
		<style id="getpaid-embed-fixed-top-offset-css">
			:root { --getpaid-embed-fixed-top-offset: 0px; }
			html { scroll-padding-top: var( --getpaid-embed-fixed-top-offset ); }
			.getpaid-embed-main { padding-top: var( --getpaid-embed-fixed-top-offset ); }
		</style>
		<script id="getpaid-embed-fixed-top-offset-js">
			( function () {
				var applyOffset = function () {
					var nav    = document.querySelector( '.navbar.fixed-top' );
					var offset = nav ? nav.offsetHeight : 0;
					document.documentElement.style.setProperty(
						'--getpaid-embed-fixed-top-offset',
						offset + 'px'
					);
				};
				if ( document.readyState === 'loading' ) {
					document.addEventListener( 'DOMContentLoaded', applyOffset );
				} else {
					applyOffset();
				}
				window.addEventListener( 'load', applyOffset );
				window.addEventListener( 'resize', applyOffset );
			} )();
		</script>
		<?php
	};

	// Form body, reused by both theme branches below.
	$render_payment_form = static function () use ( $shortcode ) {
		?>
		<main class="getpaid-embed-main">
			<div id="getpaid-form-embed" class="container my-5 bsui">
				<?php
					do_action( 'getpaid_payment_form_embed_top' );
					echo do_shortcode( $shortcode );
					do_action( 'getpaid_payment_form_embed_bottom' );
					wpinv_get_template( 'frontend-footer.php' );
				?>
			</div>
		</main>
		<?php
	};

	// Block themes ship no header.php/footer.php, so get_header()/get_footer() trigger
	// WP's deprecation fallback. Emit the skeleton ourselves and render the header and
	// footer via block_template_part().
	if ( wp_is_block_theme() ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<?php
				wp_head();
				$render_offset_assets();
				?>
			</head>
			<body <?php body_class(); ?>>
				<?php
				wp_body_open();
				block_template_part( 'header' );
				$render_payment_form();
				block_template_part( 'footer' );
				wp_footer();
				?>
			</body>
		</html>
		<?php
		return;
	}

	// Classic theme: wp_head() fires inside get_header(), so hook the offset assets there.
	add_action( 'wp_head', $render_offset_assets );

	get_header();
	$render_payment_form();
	get_footer();
	return;
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="bsui">

	<head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" >

        <meta name="robots" content="noindex,nofollow">

		<link rel="profile" href="https://gmpg.org/xfn/11">

        <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<?php
			wp_enqueue_scripts();
			wp_print_styles();
			wp_print_head_scripts();
			wp_custom_css_cb();
			wpinv_get_template( 'frontend-head.php' );
			wp_site_icon();
		?>

		<style type="text/css">
			.body{
				background: white;
				width: 100%;
				max-width: 100%;
				text-align: left;
				font-weight: 400;
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
