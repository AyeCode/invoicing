<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoicing buy item widget.
 *
 */
class WPInv_Buy_Item_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','buy', 'buy item']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_buy',
            'name'          => __('Invoicing > Buy Item Button','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-buy-item-class  wpi-g',
                'description' => esc_html__('Displays buy invoicing item button.','invoicing'),
            ),
            'arguments'     => array(
                'title'  => array(
                    'title'       => __( 'Widget title', 'invoicing' ),
                    'desc'        => __( 'Enter widget title.', 'invoicing' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'default'     => '',
                    'advanced'    => false
                ),
                'buy_items'  => array(
	                'title'       => __( 'Items to buy', 'invoicing' ),
	                'desc'        => __( 'Enter comma separated list of invoicing item id and quantity (item_id|quantity). Ex. 101|2 ', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => '',
	                'placeholder' => __('Items to buy','invoicing'),
	                'advanced'    => false
                ),
                'button_label'  => array(
	                'title'       => __( 'Button Label', 'invoicing' ),
	                'desc'        => __( 'Enter button label. Default "Buy Now".', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => '',
	                'advanced'    => true
                ),
                'post_id'  => array(
	                'title'       => __( 'Post ID', 'invoicing' ),
	                'desc'        => __( 'Enter related post ID. This is for 3rd party add ons and not mandatory field.', 'invoicing' ),
	                'type'        => 'number',
	                'desc_tip'    => true,
	                'default'     => '',
	                'advanced'    => true
                ),
            )

        );


        parent::__construct( $options );
    }

	/**
	 * The Super block output function.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return string
	 */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {

	    $defaults = array(
		    'buy_items'     => '', // should be used like: item_id|quantity,item_id|quantity,item_id|quantity
		    'button_label'  => __( 'Buy Now', 'invoicing' ), // the button title
		    'post_id'   => '', // any related post_id
	    );

	    /**
	     * Parse incoming $args into an array and merge it with $defaults
	     */
	    $args = wp_parse_args( $args, $defaults );

	    $post_id = isset( $args['post_id'] ) ? (int)$args['post_id'] : 0;

	    $html = '<div class="wpi-buy-button-wrapper wpi-g">';
	    $html .= '<button class="button button-primary wpi-buy-button" type="button" onclick="wpi_buy(this,\'' . $args['buy_items'] . '\',' . $post_id . ');">' . $args['button_label'] . '</button>';
	    $html .= wp_nonce_field( 'wpinv_buy_items', 'wpinv_buy_nonce', true, false );
	    $html .= '</div>';

	    return $html;

    }

}