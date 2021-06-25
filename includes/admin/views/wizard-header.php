<?php
/**
 * Displays the set-up wizard header.
 *
 */

defined( 'ABSPATH' ) || exit;

$aui_settings = AyeCode_UI_Settings::instance();
$aui_settings->enqueue_scripts();
$aui_settings->enqueue_style();

?>

<!DOCTYPE html>
	<html <?php language_attributes(); ?> class="bsui">
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_html_e( 'GetPaid &rsaquo; Setup Wizard', 'invoicing' ); ?></title>
			<?php
                getpaid_admin()->enqeue_scripts();
				wp_enqueue_style( 'font-awesome', 'https://use.fontawesome.com/releases/v5.13.0/css/all.css', array(), 'v5.13.0' );
				wp_print_styles( 'select2' );
                wp_print_scripts( 'select2' );
				wp_print_scripts( 'wpinv-admin-script' );
                do_action( 'admin_print_styles' );
                do_action( 'admin_head' );
			?>
			<style>
				body, p{
					font-size: 16px;
					font-weight: normal;
				}

                .bsui .settings-label {
                    font-weight: 500;
                    margin-bottom: .1rem;
                }
				<?php echo $aui_settings::css_primary( '#009874', true ); ?>
			</style>
		</head>

        <body class="gp-setup wp-core-ui bg-lightx mx-auto text-dark scrollbars-ios" style="background: #f3f6ff;">

            <?php if ( isset( $_REQUEST['step'] ) ) : ?>
                <ol class="gp-setup-steps mb-0 pb-4 mw-100 list-group list-group-horizontal text-center">
                    <?php foreach ( $steps as $step => $data ) : ?>
                        <li class="list-group-item flex-fill rounded-0 <?php
                            echo $step == $current ? 'active' : 'd-none d-md-block';
                            echo array_search( $current, array_keys( $steps ) ) > array_search( $step, array_keys( $steps ) ) ? ' done' : '';
                        ?>">
                            <i class="far fa-check-circle <?php echo array_search( $current, array_keys( $steps ) ) > array_search( $step, array_keys( $steps ) ) ? 'text-success' : '' ;?>"></i>
                            <?php echo esc_html( $data['name'] ); ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div class='mb-3'>&nbsp;</div>
            <?php endif; ?>

            <div class="text-center pb-3 mt-5">
                <a class=" text-decoration-none" href="https://wpgetpaid.com/">
                    <span class="text-black-50">
                        <img class="ml-n3x" src="<?php echo WPINV_PLUGIN_URL . 'assets/images/getpaid-logo.png';?>" />
                    </span>
                </a>
            </div>