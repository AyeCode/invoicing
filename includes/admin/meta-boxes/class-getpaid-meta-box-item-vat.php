<?php

/**
 * Item VAT
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Item_VAT Class.
 */
class GetPaid_Meta_Box_Item_VAT {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the item.
        $item = new WPInv_Item( $post );

        echo "<div class='bsui' style='max-width: 600px;padding-top: 10px;'>";

        do_action( 'wpinv_item_before_vat_metabox', $item );

        // Output the vat rules settings.
        do_action( 'wpinv_item_vat_metabox_before_vat_rules', $item );
        self::output_vat_rules( $item );
        do_action( 'wpinv_item_vat_metabox_vat_rules', $item );

        // Output vat class settings.
        do_action( 'wpinv_item_vat_metabox_before_vat_rules', $item );
        self::output_vat_classes( $item );
        do_action( 'wpinv_item_vat_metabox_vat_class', $item );

        do_action( 'wpinv_item_vat_metabox', $item );

        echo '</div>';
    }

    /**
	 * Output the VAT rules settings.
	 *
	 * @param WPInv_Item $item
	 */
    public static function output_vat_rules( $item ) {
        ?>

            <div class="wpinv_vat_rules">

                <div class="form-group row">
                    <label for="wpinv_vat_rules" class="col-sm-3 col-form-label">
                        <?php _e( 'VAT Rule', 'invoicing' );?>
                    </label>
                    <div class="col-sm-8">
                        <?php
                            echo aui()->select(
                                array(
                                    'id'               => 'wpinv_vat_rules',
                                    'name'             => 'wpinv_vat_rules',
                                    'placeholder'      => __( 'Select VAT rule', 'invoicing' ),
                                    'value'            => $item->get_vat_rule( 'edit' ),
                                    'select2'          => true,
                                    'data-allow-clear' => 'false',
                                    'no_wrap'          => true,
                                    'options'          => getpaid_get_tax_rules(),
                                )
                            );
                        ?>
                    </div>
                    <div class="col-sm-1 pt-2 pl-0">
                        <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'When you select physical product rules, only consumers and businesses in your country will be charged VAT. The VAT rate used will be the rate in your country. <br><br>If you select Digital product rules, VAT will be charged at the rate that applies in the country of the consumer. Only businesses in your country will be charged VAT.', 'invoicing' ); ?>"></span>
                    </div>
                </div>

            </div>

        <?php

    }

    /**
	 * Output the VAT class settings.
	 *
	 * @param WPInv_Item $item
	 */
    public static function output_vat_classes( $item ) {
        ?>

            <div class="wpinv_vat_classes">

                <div class="form-group row">
                    <label for="wpinv_vat_class" class="col-sm-3 col-form-label">
                        <?php _e( 'VAT Class', 'invoicing' );?>
                    </label>
                    <div class="col-sm-8">
                        <?php
                            echo aui()->select(
                                array(
                                    'id'               => 'wpinv_vat_class',
                                    'name'             => 'wpinv_vat_class',
                                    'placeholder'      => __( 'Select VAT class', 'invoicing' ),
                                    'value'            => $item->get_vat_class( 'edit' ),
                                    'select2'          => true,
                                    'data-allow-clear' => 'false',
                                    'no_wrap'          => true,
                                    'options'          => getpaid_get_tax_classes(),
                                )
                            );
                        ?>
                    </div>
                    <div class="col-sm-1 pt-2 pl-0">
                        <span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Select the VAT rate class to use for this invoice item', 'invoicing' ); ?>"></span>
                    </div>
                </div>

            </div>

        <?php

    }

}
