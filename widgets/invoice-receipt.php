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
            'name'          => __('GetPaid > Invoice Receipt','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-receipt-class bsui',
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
        return wpinv_payment_receipt();
    }

}
