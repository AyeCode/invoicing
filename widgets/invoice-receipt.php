<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoice receipt widget.
 */
class WPInv_Receipt_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','receipt']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_receipt',
            'name'          => __('Invoicing > Invoice Receipt','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-receipt-class  wpi-g',
                'description' => esc_html__('Displays invoice receipt after checkout.','invoicing'),
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

	    do_action( 'wpinv_success_content_before' );
	    echo wpinv_payment_receipt( $args );
	    do_action( 'wpinv_success_content_after' );

	    $output = ob_get_clean();
	    return trim($output);

    }

}