<div id="wpinv-payment-processing">

    <p>
        <?php 
            echo
            wp_sprintf(
                __( 'Your payment is processing. This page will reload automatically in 10 seconds. If it does not, click <a href="%s">here</a>.', 'invoicing' ),
                esc_url( $_SERVER['REQUEST_URI'] )
            );
        ?>
        <i class="fa fa-spin fa-refresh"></i>
    </p>

    <script type="text/javascript">
        setTimeout(
            function(){
                window.location.href = window.location.href;
            },
            10000
        );
    </script>

</div>
