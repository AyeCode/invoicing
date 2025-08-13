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
			'textdomain'     => 'invoicing',
			'block-icon'     => 'admin-site',
			'block-category' => 'widgets',
			'block-keywords' => "['invoicing','checkout']",
			'class_name'     => __CLASS__,
			'base_id'        => 'wpinv_checkout',
			'name'           => __( 'GetPaid > Checkout', 'invoicing' ),
			'widget_ops'     => array(
				'classname'   => 'getpaid-checkout bsui',
				'description' => esc_html__( 'Displays a checkout form.', 'invoicing' ),
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
		if ( $this->is_preview() ) {
			return $this->get_dummy_preview( $args );
		}

		return wpinv_checkout_form();
	}

	public function get_dummy_preview( $args ) {
		$output = '<form><div class="col-12">';

		$output .= aui()->alert(
			array(
				'type'=> 'info',
				'content' => __( 'This is a simple preview for a checkout form.', 'invoicing' )
			)
		);

		$output .= aui()->input(
			array(
				'name'        => 'mmdwqzpox',
				'required'    => true,
				'label'       => __( 'Billing Email', 'invoicing' ),
				'label_type'  => 'vertical',
				'type'        => 'text',
				'placeholder' => 'jon@snow.com',
				'class'       => '',
				'value'       => ''
			)
		);

		$output .= aui()->input(
			array(
				'name'        => 'mmdwqzpoy',
				'required'    => true,
				'label'       => __( 'First Name', 'invoicing' ),
				'label_type'  => 'vertical',
				'type'        => 'text',
				'placeholder' => 'Jon',
				'class'       => '',
				'value'       => ''
			)
		);

		$output .= aui()->input(
			array(
				'name'        => 'mmdwqzpoz',
				'required'    => true,
				'label'       => __( 'Last Name', 'invoicing' ),
				'label_type'  => 'vertical',
				'type'        => 'text',
				'placeholder' => 'Snow',
				'class'       => '',
				'value'       => ''
			)
		);

		$output .= aui()->button(
			array(
				'type'        => 'button',
				'class'       => 'btn btn-primary w-100',
				'content'     => __( 'Pay Now »', 'invoicing' ),
				'description' => __( 'By continuing with your payment, you are agreeing to our privacy policy and terms of service.', 'invoicing' )
			)
		);

		$output .= '</div></form>';

		return $output;
	}
}
