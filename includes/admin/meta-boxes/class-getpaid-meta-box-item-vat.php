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

                <div class="form-group mb-3 row">
                    <label for="wpinv_vat_rules" class="col-sm-3 col-form-label">
                        <?php esc_html_e( 'Tax Rule', 'invoicing' ); ?>
                    </label>
                    <div class="col-sm-8">
                        <?php
                            aui()->select(
                                array(
                                    'id'               => 'wpinv_vat_rules',
                                    'name'             => 'wpinv_vat_rules',
                                    'placeholder'      => __( 'Select tax rule', 'invoicing' ),
                                    'value'            => $item->get_vat_rule( 'edit' ),
                                    'select2'          => true,
                                    'data-allow-clear' => 'false',
                                    'no_wrap'          => true,
                                    'options'          => getpaid_get_tax_rules(),
                                ),
                                true
                            );
                        ?>
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

                <div class="form-group mb-3 row">
                    <label for="wpinv_vat_class" class="col-sm-3 col-form-label">
                        <?php esc_html_e( 'Tax Class', 'invoicing' ); ?>
                    </label>
                    <div class="col-sm-8">
                        <?php
                            aui()->select(
                                array(
                                    'id'               => 'wpinv_vat_class',
                                    'name'             => 'wpinv_vat_class',
                                    'placeholder'      => __( 'Select tax class', 'invoicing' ),
                                    'value'            => $item->get_vat_class( 'edit' ),
                                    'select2'          => true,
                                    'data-allow-clear' => 'false',
                                    'no_wrap'          => true,
                                    'options'          => getpaid_get_tax_classes(),
                                ),
                                true
                            );
                        ?>
                    </div>
                </div>

            </div>

        <?php

    }

}
