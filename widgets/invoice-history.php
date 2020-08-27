<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoicing history widget.
 */
class WPInv_History_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','history']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_history',
            'name'          => __('GetPaid > Invoice History','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-history-class bsui',
                'description' => esc_html__('Displays invoice history.','invoicing'),
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
        return getpaid_invoice_history();
    }

}