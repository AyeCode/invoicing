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
			'textdomain'     => 'invoicing',
			'block-icon'     => 'admin-site',
			'block-category' => 'widgets',
			'block-keywords' => "['invoicing','receipt']",
			'class_name'     => __CLASS__,
			'base_id'        => 'wpinv_receipt',
			'name'           => __( 'GetPaid > Invoice Receipt', 'invoicing' ),
			'widget_ops'     => array(
				'classname'   => 'wpinv-receipt-class bsui',
				'description' => esc_html__( 'Displays invoice receipt after checkout.', 'invoicing' ),
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

		return wpinv_payment_receipt();
	}

	public function get_dummy_preview( $args ) {
		global $wpdb;

		$output = aui()->alert( array(
				'type'=> 'info',
				'content' => __( 'This is a simple preview for a invoice receipt.', 'invoicing' )
			)
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			return $output;
		}

		$invoice_id = $wpdb->get_var( $wpdb->prepare( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` = %s AND `post_status` IN( 'wpi-pending', 'publish', 'wpi-processing', 'wpi-onhold' ) ORDER BY `post_status` ASC, `ID` ASC LIMIT 1;", 'wpi_invoice' ) );

		if ( ! $invoice_id ) {
			return $output;
		}

		$invoice = new WPInv_Invoice( $invoice_id );

		if ( empty( $invoice ) ) {
			return $output;
		}

		$output .= wpinv_get_template_html( 'invoice-receipt.php', array( 'invoice' => $invoice ) );

		$output = preg_replace( '/<a([^>]*)href=(["\'])(.*?)\2([^>]*)>/is', '<a\\1href="javascript:void(0)"\\4>', $output );

		return $output;
	}
}
