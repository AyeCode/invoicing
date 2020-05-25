<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * getpaid button widget.
 *
 */
class WPInv_GetPaid_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {

        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','buy', 'buy item', 'form']",
            'class_name'     => __CLASS__,
            'base_id'       => 'getpaid',
            'name'          => __('Invoicing > GetPaid Button','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-getpaid-class wpi-g bsui',
                'description' => esc_html__('Displays a button that loads a payment form in a popup.','invoicing'),
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

                'form'  => array(
	                'title'       => __( 'Form', 'invoicing' ),
	                'desc'        => __( 'Enter a form id in case you want to display a specific payment form', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => '',
	                'placeholder' => __('1','invoicing'),
	                'advanced'    => false
				),

				'item'  => array(
	                'title'       => __( 'Item', 'invoicing' ),
	                'desc'        => __( 'Enter an item id in case you want to sell an item', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => '',
	                'placeholder' => __('1','invoicing'),
	                'advanced'    => false
				),

                'button'  => array(
	                'title'       => __( 'Button Label', 'invoicing' ),
	                'desc'        => __( 'Enter button label. Default "Buy Now".', 'invoicing' ),
	                'type'        => 'text',
	                'desc_tip'    => true,
	                'default'     => __( 'Buy Now', 'invoicing' ),
	                'advanced'    => false
				)

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

	    // Do we have a payment form?
		if ( empty( $args['form'] ) && empty( $args['item'] ) ) {
			return aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'No payment form or item selected', 'invoicing' ),
				)
			);

		}

	    $defaults = array(
		    'item'    => '',
		    'button'  => __( 'Buy Now', 'invoicing' ),
		    'form'    => '',
	    );

	    /**
	     * Parse incoming $args into an array and merge it with $defaults
	     */
		$args = wp_parse_args( $args, $defaults );
		
		// Payment form?
		if ( ! empty( $args['form'] ) ) {

			// Ensure that it is published.
			if ( 'publish' != get_post_status( $args['form'] ) ) {
				return aui()->alert(
					array(
						'type'    => 'warning',
						'content' => __( 'This payment form is no longer active', 'invoicing' ),
					)
				);
			}

			$attrs = array(
				'form' => $args['form']
			);

		} else {

			// Ensure that it is published.
			if ( 'publish' != get_post_status( $args['item'] ) ) {
				return aui()->alert(
					array(
						'type'    => 'warning',
						'content' => __( 'This item is no longer active', 'invoicing' ),
					)
				);
			}

			$attrs = array(
				'item' => $args['item']
			);

		}

		$label = ! empty( $args['button'] ) ? sanitize_text_field( $args['button'] ) : __( 'Buy Now', 'invoicing' );
		$attr  = '';

		foreach( $attrs as $key => $val ) {
			$key  = sanitize_text_field( $key );
			$val  = esc_attr( $val );
			$attr .= " $key='$val' ";
		}

		return "<button class='btn btn-primary getpaid-payment-button' type='button' $attr>$label</button>";

    }

}
