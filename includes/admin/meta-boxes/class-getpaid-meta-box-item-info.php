<?php

/**
 * Item Info
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Item_Info Class.
 */
class GetPaid_Meta_Box_Item_Info {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the item.
        $item = new WPInv_Item( $post );

        ?>

        <div class='bsui' style='padding-top: 10px;'>
            <?php do_action( 'wpinv_item_before_info_metabox', $item ); ?>

            <div class="wpinv_item_type form-group row">
                <label for="wpinv_item_type" class="col-sm-12 col-form-label">
                    <?php _e( 'Item Type', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php echo strip_tags( self::get_tooltip( $post ) ); ?>"></span>
                </label>

                <div class="col-sm-12">

                    <?php
                        echo aui()->select(
                            array(
                                'id'               => 'wpinv_item_type',
                                'name'             => 'wpinv_item_type',
                                'placeholder'      => __( 'Select item type', 'invoicing' ),
                                'value'            => $item->get_type( 'edit' ),
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'no_wrap'          => true,
                                'options'          => wpinv_get_item_types(),
                            )
                        );
                    ?>

                </div>
            </div>

            <div class="wpinv_item_shortcode form-group row">
                <label for="wpinv_item_shortcode" class="col-sm-12 col-form-label">
                    <?php _e( 'Payment Form Shortcode', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Displays a payment form', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input  onClick="this.select()" type="text" id="wpinv_item_shortcode" value="[getpaid item=<?php echo esc_attr( $item->get_id() ); ?>]" style="width: 100%;" readonly/>
                </div>
            </div>

            <div class="wpinv_item_buy_shortcode form-group row">
                <label for="wpinv_item_button_shortcode" class="col-sm-12 col-form-label">
                    <?php _e( 'Payment Button Shortcode', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Displays a buy now button', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input onClick="this.select()" type="text" id="wpinv_item_button_shortcode" value="[getpaid item=<?php echo esc_attr( $item->get_id() ); ?> button='Buy Now']" style="width: 100%;" readonly/>
                    <small class="form-text text-muted">
                        <?php _e( 'Or use the following URL in a link:', 'invoicing' );?>
                        <code>#getpaid-item-<?php echo intval( $item->get_id() ); ?>|0</code>
                    </small>
                </div>
            </div>

            <div class="wpinv_item_buy_url form-group row">
                <label for="wpinv_item_buy_url" class="col-sm-12 col-form-label">
                    <?php _e( 'Direct Payment URL', 'invoicing' );?>
                    <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'You can use this in an iFrame to embed the payment form on another website', 'invoicing' ); ?>"></span>
                </label>

                <div class="col-sm-12">
                    <input onClick="this.select()" type="text" id="wpinv_item_buy_url" value="<?php echo esc_url( getpaid_embed_url( false, $item->get_id() . '|0' ) ); ?>" style="width: 100%;" readonly/>
                </div>
            </div>

            <div class="wpinv_item_custom_id form-group">
                <?php _e( 'Custom ID', 'invoicing' );?> &mdash; <?php echo esc_html( $item->get_custom_id() ) ?>
            </div>

            <?php do_action( 'wpinv_meta_values_metabox_before', $post ); ?>
            <?php foreach ( apply_filters( 'wpinv_show_meta_values_for_keys', array() ) as $meta_key ) : ?>
                <div class="wpinv_item_custom_id form-group">
                    <?php echo esc_html( $meta_key );?> &mdash; <?php echo esc_html( get_post_meta( $item->get_id(), '_wpinv_' . $meta_key, true ) ); ?>
                </div>
            <?php endforeach; ?>
            <?php do_action( 'wpinv_meta_values_metabox_after', $post ); ?>
            <?php do_action( 'wpinv_item_info_metabox', $item ); ?>
        </div>
        <?php

    }

    /**
	 * Returns item type tolltip.
	 *
	 */
    public static function get_tooltip( $post ) {

        ob_start();
        ?>

        <?php _e( 'Standard: Standard item type', 'invoicing' );?>
        <?php _e( 'Fee: Like Registration Fee, Sign up Fee etc', 'invoicing' );?>

        <?php
        do_action( 'wpinv_item_info_metabox_after', $post );

        return ob_get_clean();

    }

}
