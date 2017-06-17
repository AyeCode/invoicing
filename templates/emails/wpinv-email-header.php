<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

if ( !isset( $email_heading ) ) {
    global $email_heading;
}
?>
<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title><?php echo wpinv_get_blogname(); ?></title>
    </head>
    <body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
            <table border="0" cellpadding="0" cellspacing="0" height="100%" class="wrapper-table">
                <tr>
                    <td align="center" valign="top">
                        <div id="template_header_image">
                        <?php
                            if ( $img = wpinv_get_option( 'email_header_image' ) ) {
                                echo '<p style="margin-top:0;"><img style="max-width:100%" src="' . esc_url( $img ) . '" alt="' . esc_attr( wpinv_get_blogname() ) . '" /></p>';
                            }
                        ?>
                        </div>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container">
                            <?php if ( !empty( $email_heading ) ) { ?>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Header -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
                                        <tr>
                                            <td id="header_wrapper">
                                                <h1><?php echo $email_heading; ?></h1>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Body -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                        <tr>
                                            <td valign="top" id="body_content">
                                                <!-- Content -->
                                                <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top">
                                                            <div id="body_content_inner">