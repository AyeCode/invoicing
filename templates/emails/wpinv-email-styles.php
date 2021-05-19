<?php
/**
 * Template that generates the email styles.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-styles.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$bg              = wpinv_get_option( 'email_background_color', '#f5f5f5' );
$body            = wpinv_get_option( 'email_body_background_color', '#fdfdfd' );
$base            = wpinv_get_option( 'email_base_color', '#557da2' );
$base_text       = wpinv_light_or_dark( $base, '#202020', '#ffffff' );
$text            = wpinv_get_option( 'email_text_color', '#505050' );

$bg_darker_10    = wpinv_hex_darker( $bg, 10 );
$body_darker_10  = wpinv_hex_darker( $body, 10 );
$base_lighter_20 = wpinv_hex_lighter( $base, 20 );
$base_lighter_40 = wpinv_hex_lighter( $base, 40 );
$text_lighter_20 = wpinv_hex_lighter( $text, 20 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
?>
#wrapper {
    background-color: <?php echo esc_attr( $bg ); ?>;
    margin: 0;
    -webkit-text-size-adjust: none !important;
    padding: 3%;
    width: 94%;
}

#wrapper > p {
    height: 0;
    margin: 0;
    padding: 0;
}

#wrapper .wrapper-table {
    margin: auto;
    max-width: 900px;
    width: 100%;
}

#template_container {
    box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
    background-color: <?php echo esc_attr( $body ); ?>;
    border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
    border-radius: 3px !important;
}

#template_header {
    background-color: <?php echo esc_attr( $base ); ?>;
    border-radius: 3px 3px 0 0 !important;
    color: <?php echo esc_attr( $base_text ); ?>;
    border-bottom: 0;
    font-weight: bold;
    line-height: 100%;
    vertical-align: middle;
    font-family: Arial,Helvetica,sans-serif;
}

#template_header_image {
    width: 100%;
}

#template_header h1 {
    color: <?php echo esc_attr( $base_text ); ?>;
}

#template_footer td {
    padding: 0;
    -webkit-border-radius: 6px;
    font-size: 14px;
}

#template_footer #credit {
    border:0;
    color: <?php echo esc_attr( $base_lighter_40 ); ?>;
    font-family: Arial;
    font-size:12px;
    line-height:125%;
    text-align:center;
    padding: 0 36px 36px 36px;
}

#body_content {
    background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
    padding: 27px;
}

#body_content table td td {
    padding: 10px;
}

#body_content table td th {
    padding: 10px;
}

#body_content p {
    margin: 0 0 16px;
}

#body_content_inner {
    color: <?php echo esc_attr( $text_lighter_20 ); ?>;
    font-family: Arial,Helvetica,sans-serif;
    font-size: 14px;
    line-height: 150%;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
    color: <?php echo esc_attr( $text_lighter_20 ); ?>;
    border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
}

.text {
    color: <?php echo esc_attr( $text ); ?>;
    font-family: Arial,Helvetica,sans-serif;
}

.link {
    color: <?php echo esc_attr( $base ); ?>;
}

#header_wrapper {
    padding: 22px 24px;
    display: block;
}

h1 {
    color: <?php echo esc_attr( $base ); ?>;
    font-family: Arial,Helvetica,sans-serif;
    font-size: 30px;
    font-weight: 300;
    line-height: 150%;
    margin: 0;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
    text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
    -webkit-font-smoothing: antialiased;
}

h2 {
    color: <?php echo esc_attr( $base ); ?>;
    display: block;
    font-family: Arial,Helvetica,sans-serif;
    font-size: 18px;
    font-weight: bold;
    line-height: 130%;
    margin: 16px 0 8px;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
    color: <?php echo esc_attr( $base ); ?>;
    display: block;
    font-family: Arial,Helvetica,sans-serif;
    font-size: 16px;
    font-weight: bold;
    line-height: 130%;
    margin: 16px 0 8px;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
    color: <?php echo esc_attr( $base ); ?>;
    font-weight: normal;
    text-decoration: underline;
}

img {
    border: none;
    display: inline;
    font-size: 14px;
    font-weight: bold;
    height: auto;
    line-height: 100%;
    outline: none;
    text-decoration: none;
    text-transform: capitalize;
}

.table-bordered {
    border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
    color: <?php echo esc_attr( $text_lighter_20 ); ?>;
    font-size: 14px;
}
.small {
    font-size: 85%;
}
.bold {
    font-weight: bold;
}
.normal {
    font-weight: normal;
}
.text-left {
  text-align: left;
}
.text-right {
  text-align: right;
}
.text-center {
  text-align: center;
}
.text-justify {
  text-align: justify;
}
.text-nowrap {
  white-space: nowrap;
}
.text-lowercase {
  text-transform: lowercase;
}
.text-uppercase {
  text-transform: uppercase;
}
.text-capitalize {
  text-transform: capitalize;
}
#body_content #wpinv-email-billing .wpi-receipt-address p {
    margin: 0 0 5px;
}
#body_content .wpinv_cart_item_name p.small {
    margin: 0;
}

.wpi-email-row,
#wpinv-email-details,
#wpinv-email-items,
#wpinv-email-billing {
    margin-bottom: 20px;
}
.wpinv-cart-sub-desc {
    display: inline-block;
    max-width: 85%;
    float: left;
    text-align: left;
    font-size: 90%;
}
.wpinv-cart-sub-desc .label-primary {
    font-weight: bold;
}
.wpinv_cart_footer_row .wpinv_cart_total {
    vertical-align: top;
}
#body_content .wpinv-note {
    background-color: #f7f7f7;
    border: 1px solid #f3f3f3;
    margin: -8px 0 15px 0;
    padding: 10px 20px;
}
#body_content .wpinv-note p {
    margin: 0;
}
#body_content .wpinv-note {
    padding: 7.5px 12.5px;
}
#body_content .wpinv-note-date {
    font-size: 95%
}
#body_content .wpinv-note .description {
    line-height: 137.5%;
    margin-top: 3px;
}
.btn {
  display: inline-block;
  padding: 0.2rem .6rem;
  font-size: 95%;
  font-weight: normal;
  line-height: 1.5;
  text-align: center;
  white-space: nowrap;
  vertical-align: middle;
  cursor: pointer;
  -webkit-user-select: none;
     -moz-user-select: none;
      -ms-user-select: none;
          user-select: none;
  border: 1px solid transparent;
  border-radius: .25rem;
  text-decoration: none;
}
.btn-default {
    color: <?php echo esc_attr( $base_text ); ?>;
    background-color: <?php echo esc_attr( $base ); ?>;
    border-color: <?php echo esc_attr( $base ); ?>;
}
.btn-primary {
  color: #fff;
  background-color: #0275d8;
  border-color: #0275d8;
}
.btn-success {
  color: #fff;
  background-color: #5cb85c;
  border-color: #5cb85c;
}
.p-2 {
    padding: 10px !important;
}