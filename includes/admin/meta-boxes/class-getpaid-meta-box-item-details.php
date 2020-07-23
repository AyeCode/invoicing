<?php

/**
 * Item Details
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Item_Details Class.
 */
class GetPaid_Meta_Box_Item_Details {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the item.
        $item = new WPInv_Item( $post );

        echo "<div class='bsui' style='max-width: 700px;'>";

        // Nonce field.
        wp_nonce_field( 'wpinv_item_meta_box_save', 'wpinv_vat_meta_box_nonce' );

        // Item price.
        do_action( 'wpinv_item_details_metabox_before_price', $item );

        $position       = wpinv_currency_position();
        echo aui()->input(
            array(
                'id'          => 'wpinv_item_price',
                'name'        => 'wpinv_item_price',
                'label'       => __( 'Item Price', 'invoicing' ),
                'placeholder' => wpinv_sanitize_amount( 0 ),
                'help_text'   => __( 'Enter the item price without the currency symbol.', 'invoicing' ),
                'value'       => $item->get_price( 'edit' ),
                'label_type'  => 'horizontal',
                'input_group_right' => 'left' == $position ? '' : wpinv_currency_symbol(),
                'input_group_left'  => 'left' == $position ? wpinv_currency_symbol() : '',
            )
        );

        do_action( 'wpinv_prices_metabox_price', $item );

        // Subscription toggle.
        echo aui()->input(
			array(
                'id'          => 'wpinv_is_recurring',
                'name'        => 'wpinv_is_recurring',
                'type'        => 'checkbox',
				'label'       => apply_filters( 'wpinv_is_recurring_toggle_text', __( 'Is Recurring Item?', 'invoicing' ) ),
                'value'       => '1',
                'checked'     => $item->is_recurring(),
                'label_type'  => 'horizontal',
			)
        );

        do_action( 'wpinv_prices_metabox_is_recurring_field', $item );

        // Recurring details.
        do_action( 'wpinv_item_details_metabox_before_recurring_section', $item );

        echo "<div class='wpinv-item-recurring-details'>";

        echo "</div>";
        do_action( 'wpinv_item_details_metabox_recurring_section', $item );

        echo "</div>";

        $symbol         = wpinv_currency_symbol();
        
        $item           = new WPInv_Item( $post->ID );

        $price                = $item->get_price();
        $is_dynamic_pricing   = $item->get_is_dynamic_pricing();
        $minimum_price        = $item->get_minimum_price();
        $is_recurring         = $item->is_recurring();
        $period               = $item->get_recurring_period();
        $interval             = absint( $item->get_recurring_interval() );
        $times                = absint( $item->get_recurring_limit() );
        $free_trial           = $item->has_free_trial();
        $trial_interval       = $item->get_trial_interval();
        $trial_period         = $item->get_trial_period();

        $intervals            = array();
        for ( $i = 1; $i <= 90; $i++ ) {
            $intervals[$i] = $i;
        }

        $interval       = $interval > 0 ? $interval : 1;

        $class = $is_recurring ? 'wpinv-recurring-y' : 'wpinv-recurring-n';

        $minimum_price_style = 'margin-left: 24px;';
        if(! $is_dynamic_pricing ) {
            $minimum_price_style .= 'display: none;';
        }

        ?>
        <p class="wpinv-row-prices"><?php echo ( $position != 'right' ? $symbol . '&nbsp;' : '' );?><input type="text" maxlength="12" placeholder="<?php echo wpinv_sanitize_amount( 0 ); ?>" value="<?php echo $price;?>" id="wpinv_item_price" name="wpinv_item_price" class="medium-text wpi-field-price wpi-price" <?php disabled( $item->is_editable(), false ); ?> /><?php echo ( $position == 'right' ? '&nbsp;' . $symbol : '' );?><input type="hidden" name="wpinv_vat_meta_box_nonce" value="<?php echo wp_create_nonce( 'wpinv_item_meta_box_save' ) ;?>" />
        <?php do_action( 'wpinv_prices_metabox_price', $item ); ?>
        </p>

    <?php if( $item->supports_dynamic_pricing() ) { ?>

        <p class="wpinv-row-name-your-price">
            <label>
                <input type="checkbox" name="wpinv_name_your_price" id="wpinv_name_your_price" value="1" <?php checked( 1, $is_dynamic_pricing ); ?> />
                <?php echo apply_filters( 'wpinv_name_your_price_toggle_text', __( 'User can set a custom price', 'invoicing' ) ); ?>
            </label>
            <?php do_action( 'wpinv_prices_metabox_name_your_price_field', $item ); ?>
        </p>

        <p class="wpinv-row-minimum-price" style="<?php echo $minimum_price_style; ?>">
            <label>
                <?php _e( 'Minimum Price', 'invoicing' ); ?>
                <?php echo ( $position != 'right' ? $symbol . '&nbsp;' : '' );?><input type="text" maxlength="12" placeholder="<?php echo wpinv_sanitize_amount( 0 ); ?>" value="<?php echo $minimum_price;?>" id="wpinv_minimum_price" name="wpinv_minimum_price" class="medium-text wpi-field-price" <?php disabled( $item->is_editable(), false ); ?> /><?php echo ( $position == 'right' ? '&nbsp;' . $symbol : '' );?>
            </label>

            <?php do_action( 'wpinv_prices_metabox_minimum_price_field', $item ); ?>
        </p>

    <?php } ?>

        
        <p class="wpinv-row-recurring-fields <?php echo $class;?>">
            <label class="wpinv-period" for="wpinv_recurring_period"><?php _e( 'Recurring', 'invoicing' );?> <select class="wpinv-select wpi_select2" id="wpinv_recurring_period" name="wpinv_recurring_period"><option value="D" data-text="<?php esc_attr_e( 'day(s)', 'invoicing' ); ?>" <?php selected( 'D', $period );?>><?php _e( 'Daily', 'invoicing' ); ?></option><option value="W" data-text="<?php esc_attr_e( 'week(s)', 'invoicing' ); ?>" <?php selected( 'W', $period );?>><?php _e( 'Weekly', 'invoicing' ); ?></option><option value="M" data-text="<?php esc_attr_e( 'month(s)', 'invoicing' ); ?>" <?php selected( 'M', $period );?>><?php _e( 'Monthly', 'invoicing' ); ?></option><option value="Y" data-text="<?php esc_attr_e( 'year(s)', 'invoicing' ); ?>" <?php selected( 'Y', $period );?>><?php _e( 'Yearly', 'invoicing' ); ?></option></select></label>
            <label class="wpinv-interval" for="wpinv_recurring_interval"> <?php _e( 'at every', 'invoicing' );?> <?php echo wpinv_html_select( array(
                'options'          => $intervals,
                'name'             => 'wpinv_recurring_interval',
                'id'               => 'wpinv_recurring_interval',
                'selected'         => $interval,
                'show_option_all'  => false,
                'show_option_none' => false,
                'class'            => 'wpi_select2',
            ) ); ?> <span id="wpinv_interval_text"><?php _e( 'day(s)', 'invoicing' );?></span></label>
            <label class="wpinv-times" for="wpinv_recurring_limit"> <?php _e( 'for', 'invoicing' );?> <input class="small-text" type="number" value="<?php echo $times;?>" size="4" id="wpinv_recurring_limit" name="wpinv_recurring_limit" step="1" min="0"> <?php _e( 'time(s) <i>(select 0 for recurring forever until cancelled</i>)', 'invoicing' );?></label>
            <span class="clear wpi-trial-clr"></span>
            <label class="wpinv-free-trial" for="wpinv_free_trial">
                <input type="checkbox" name="wpinv_free_trial" id="wpinv_free_trial" value="1" <?php checked( true, (bool)$free_trial ); ?> /> 
                <?php echo __( 'Offer free trial for', 'invoicing' ); ?>
            </label>
            <label class="wpinv-trial-interval" for="wpinv_trial_interval">
                <input class="small-text" type="number" value="<?php echo $trial_interval;?>" size="4" id="wpinv_trial_interval" name="wpinv_trial_interval" step="1" min="1"> <select class="wpinv-select wpi_select2" id="wpinv_trial_period" name="wpinv_trial_period"><option value="D" <?php selected( 'D', $trial_period );?>><?php _e( 'day(s)', 'invoicing' ); ?></option><option value="W" <?php selected( 'W', $trial_period );?>><?php _e( 'week(s)', 'invoicing' ); ?></option><option value="M" <?php selected( 'M', $trial_period );?>><?php _e( 'month(s)', 'invoicing' ); ?></option><option value="Y" <?php selected( 'Y', $trial_period );?>><?php _e( 'year(s)', 'invoicing' ); ?></option></select>
            </label>
            <?php do_action( 'wpinv_prices_metabox_recurring_fields', $item ); ?>
        </p>
        <input type="hidden" id="_wpi_current_type" value="<?php echo wpinv_get_item_type( $post->ID ); ?>" />
        <?php do_action( 'wpinv_item_price_field', $post->ID ); ?>
        <?php
    }

    /**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {

        // verify nonce
        if ( ! isset( $_POST['wpinv_vat_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wpinv_vat_meta_box_nonce'], 'wpinv_item_meta_box_save' ) ) {
            return;
        }

        // Prepare the item.
        $item = new WPInv_Item( $post_id );

        // Load new data.
        $item->set_props(
			array(
				'price'                => isset( $_POST['wpinv_item_price'] ) ? (float) $_POST['wpinv_item_price'] : null,
				'vat_rule'             => isset( $_POST['wpinv_vat_rules'] ) ? $_POST['wpinv_vat_rules'] : null,
				'vat_class'            => isset( $_POST['wpinv_vat_class'] ) ? $_POST['wpinv_vat_class'] : null,
				'type'                 => isset( $_POST['wpinv_item_type'] ) ? (int) $_POST['wpinv_item_type'] : null,
				'is_dynamic_pricing'   => isset( $_POST['wpinv_name_your_price'] ),
				'minimum_price'        => isset( $_POST['wpinv_minimum_price'] ) ? (float) $_POST['wpinv_minimum_price'] : null,
				'is_recurring'         => isset( $_POST['wpinv_is_recurring'] ),
				'recurring_period'     => isset( $_POST['wpinv_recurring_period'] ) ? wpinv_clean( $_POST['wpinv_recurring_period'] ) : null,
				'recurring_interval'   => isset( $_POST['wpinv_recurring_interval'] ) ? (int) $_POST['wpinv_recurring_interval'] : null,
				'recurring_limit'      => isset( $_POST['wpinv_recurring_limit'] ) ? (int) $_POST['wpinv_recurring_limit'] : null,
				'is_free_trial'        => isset( $_POST['wpinv_free_trial'] ) ,
				'trial_period'         => isset( $_POST['wpinv_trial_period'] ) ? wpinv_clean( $_POST['wpinv_trial_period'] ) : null,
				'trial_interval'       => isset( $_POST['wpinv_trial_interval'] ) ? (int) $_POST['wpinv_trial_interval'] : null,
			)
        );

		$item->save();
		do_action( 'getpaid_item_metabox_save', $post_id, $item );
	}
}
