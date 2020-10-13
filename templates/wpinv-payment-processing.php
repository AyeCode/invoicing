<?php 
global $wpi_invoice;

// Backwards compatibility.
if ( empty( $invoice ) ) {
    $invoice = $wpi_invoice;
}

$success_page_uri = wpinv_get_success_page_uri();
if ( ! empty( $invoice ) ) {
    $success_page_uri = $invoice->get_receipt_url();
}
?>
<div id="wpinv-payment-processing">

    <p>
        <?php 
            echo
            wp_sprintf(
                __( 'Your payment is processing. This page will reload automatically in 10 seconds. If it does not, click <a href="%s">here</a>.', 'invoicing' ),
                esc_url( $success_page_uri )
            );
        ?>
        <i class="fa fa-spin fa-refresh"></i>
    </p>
    
    <script type="text/javascript">
        setTimeout(
            function(){
                window.location = '<?php echo esc_url( $success_page_uri ); ?>';
            },
            10000
        );
    </script>

</div>