<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Invoicing payment form widget.
 *
 */
class WPInv_Payment_Form_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {

		$forms = get_posts(
			array(
				'post_type'      => 'wpi_payment_form',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish' ),
			)
		);

		$options = array(
			'' => __('Select a Form','invoicing')
		);

		foreach( $forms as $form ) {
			$options[ $form->ID ] = $form->post_title;
		}

        $options = array(
            'textdomain'    => 'invoicing',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','buy', 'buy item', 'pay', 'payment form']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_payment_form',
            'name'          => __('Invoicing > Payment Form','invoicing'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-payment-form-class bsui',
                'description' => esc_html__('Displays a payment form.','invoicing'),
            ),
            'arguments'           => array(
                'form'            => array(
                    'title'       => __( 'Payment Form', 'invoicing' ),
                    'desc'        => __( 'Select your payment form.', 'invoicing' ),
					'type'        => 'select',
					'options'     =>  $options,
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
	 * @return string
	 */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $invoicing;

		// Do we have a payment form?
		if ( empty( $args['form'] ) ) {
			return aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'No payment form selected', 'invoicing' ),
				)
			);

		}

		// If yes, ensure that it is published.
		if ( 'publish' != get_post_status( $args['form'] ) ) {
			return aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'This payment form is no longer active', 'invoicing' ),
				)
			);
		}

		// Get the form elements and items.
		$elements = $invoicing->form_elements->get_form_elements( $args['form'] );
		$items    = $invoicing->form_elements->get_form_items( $args['form'] );

		ob_start();
		echo "<form class='wpinv_payment_form'>";
		echo "<input type='hidden' name='form_id' value='{$args['form']}'/>";
		wp_nonce_field( 'wpinv_payment_form', 'wpinv_payment_form' );
		wp_nonce_field( 'vat_validation', '_wpi_nonce' );

		foreach ( $elements as $element ) {
			do_action( 'wpinv_frontend_render_payment_form_element', $element, $items, $args['form'] );
			do_action( "wpinv_frontend_render_payment_form_{$element['type']}", $element, $items, $args['form'] );
		}

		echo "<div class='wpinv_payment_form_errors alert alert-danger d-none'></div>";
		echo '</form>';

		$content = ob_get_clean();

		return str_replace( 'sr-only', '', $content );

    }

}
