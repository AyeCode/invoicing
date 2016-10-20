<?php 
global $wpi_invoice;

$success_page_uri = wpinv_get_success_page_uri();
if ( !empty( $wpi_invoice ) ) {
    $success_page_uri = add_query_arg( 'invoice_key', $wpi_invoice->get_key(), $success_page_uri );
}
?>
<div id="wpinv-payment-processing">
    <p><?php echo wp_sprintf( __( 'Your payment is processing. This page will reload automatically in 10 seconds. If it does not, click <a href="%s">here</a>.', 'invoicing' ), $success_page_uri ); ?> <i class="fa fa-spin fa-refresh"></i></p>
    <script type="text/javascript">setTimeout(function(){ window.location = '<?php echo $success_page_uri; ?>'; }, 10000);</script>
</div>