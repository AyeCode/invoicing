<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoicing messages widget.
 */
class WPInv_Messages_Widget extends WP_Super_Duper {

    /**
     * Register the checkout form widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','history']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_messages',
            'name'          => __('GetPaid > Invoice Messages','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-messages-class  wpi-g',
                'description' => esc_html__('Displays invoice error and warning messages on checkout page.','invoicing'),
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

	    wpinv_print_errors();

	    return '<div class="wpinv">' . ob_get_clean() . '</div>';

    }

}