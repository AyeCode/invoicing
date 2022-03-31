<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invoice widget.
 */
class WPInv_Invoice_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {

        $options = array(
            'textdomain'     => 'invoicing',
            'block-icon'     => 'admin-site',
            'block-category' => 'widgets',
            'block-keywords' => "['invoicing','invoice']",
            'class_name'     => __CLASS__,
            'base_id'        => 'getpaid_invoice',
            'name'           => __( 'GetPaid > Single Invoice', 'invoicing' ),
            'widget_ops'     => array(
                'classname'   => 'wpinv-invoice-class bsui',
                'description' => esc_html__( 'Displays a single invoice.', 'invoicing' ),
            ),
            'arguments'      => array(
                'title' => array(
                    'title'    => __( 'Widget title', 'invoicing' ),
                    'desc'     => __( 'Enter widget title.', 'invoicing' ),
                    'type'     => 'text',
                    'desc_tip' => true,
                    'default'  => '',
                    'advanced' => false,
                ),
                'id'    => array(
	                'title'       => __( 'Invoice', 'invoicing' ),
	                'desc'        => __( 'Enter the invoice ID', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => '',
	                'placeholder' => __( '1', 'invoicing' ),
	                'advanced'    => false,
				),
            ),

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

        // Is the shortcode set up correctly?
		if ( empty( $args['id'] ) ) {
			return aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'Missing invoice ID', 'invoicing' ),
				)
			);
		}

        $invoice = wpinv_get_invoice( (int) $args['id'] );

        if ( $invoice ) {
            ob_start();
            getpaid_invoice( $invoice );
            return ob_get_clean();
        }

        return aui()->alert(
            array(
                'type'    => 'danger',
                'content' => __( 'Invoice not found', 'invoicing' ),
            )
        );

    }

}
