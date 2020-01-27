<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoicing checkout form widget.
 *
 */
class WPInv_Checkout_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','checkout']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_checkout',
            'name'          => __('Invoicing > Checkout','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-checkout-class wpi-g',
                'description' => esc_html__('Displays checkout form.','invoicing'),
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
	 * @return mixed|string|bool
	 */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {

	    return wpinv_checkout_form();

    }

}