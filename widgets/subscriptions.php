<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoicing subscriptions history widget.
 */
class WPInv_Subscriptions_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','subscriptions']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_subscriptions',
            'name'          => __('Invoicing > Subscriptions History','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-checkout-class  wpi-g',
                'description' => esc_html__('Displays subscriptions history.','invoicing'),
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

	    ob_start();

	    do_action( 'wpinv_before_user_subscriptions' );
	    wpinv_get_template_part( 'wpinv-subscriptions-history' );
	    do_action( 'wpinv_after_user_subscriptions' );

	    $output = ob_get_clean();
	    return trim($output);

    }

}