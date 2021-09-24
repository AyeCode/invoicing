<?php

/**
 * Payment Form Info
 *
 * Display the Payment Form info meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Payment_Form_Info Class.
 */
class GetPaid_Meta_Box_Payment_Form_Info {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the form.
        $form = new GetPaid_Payment_Form( $post );

        ?>

        <div class='bsui' style='padding-top: 10px;'>
            <?php do_action( 'wpinv_payment_form_before_info_metabox', $form ); ?>

            <div class="wpinv_payment_form_shortcode form-group row">
                <label for="wpinv_payment_form_shortcode" class="col-sm-12 col-form-label">
                    <?php _e( 'Payment Form Shortcode', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Displays a payment form', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input  onClick="this.select()" type="text" id="wpinv_payment_form_shortcode" value="[getpaid form=<?php echo esc_attr( $form->get_id() ); ?>]" style="width: 100%;" />
                </div>
            </div>

            <div class="wpinv_payment_form_buy_shortcode form-group row">
                <label for="wpinv_payment_form_buy_shortcode" class="col-sm-12 col-form-label">
                    <?php _e( 'Payment Button Shortcode', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Displays a buy now button', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input onClick="this.select()" type="text" id="wpinv_payment_form_buy_shortcode" value="[getpaid form=<?php echo esc_attr( $form->get_id() ); ?> button='Buy Now']" style="width: 100%;" />
                    <small class="form-text text-muted">
                        <?php _e( 'Or use the following URL in a link:', 'invoicing' );?>
                        <code>#getpaid-form-<?php echo intval( $form->get_id() ); ?></code>
                    </small>
                </div>
            </div>

            <div class="wpinv_item_buy_url form-group row">
                <label for="wpinv_item_buy_url" class="col-sm-12 col-form-label">
                    <?php _e( 'Direct Payment URL', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'You can use this in an iFrame to embed the payment form on another website', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input onClick="this.select()" type="text" id="wpinv_item_buy_url" value="<?php echo esc_url( getpaid_embed_url( $form->get_id(), false ) ); ?>" style="width: 100%;" readonly/>
                </div>
            </div>

            <?php do_action( 'wpinv_payment_form_info_metabox', $form ); ?>
        </div>
        <?php

    }

}
