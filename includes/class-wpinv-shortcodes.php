<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Shortcodes {
    /**
     * Init shortcodes.
     */
    public static function init() {
        $shortcodes = array(
            'wpinv_checkout'  => __CLASS__ . '::checkout',
            'wpinv_history'  => __CLASS__ . '::history',
            'wpinv_receipt'  => __CLASS__ . '::receipt',
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
        }
        
        add_shortcode( 'wpinv_messages', __CLASS__ . '::messages' );
    }

    public static function shortcode_wrapper(
        $function,
        $atts    = array(),
        $wrapper = array(
            'class'  => 'wpinv-invoices',
            'before' => null,
            'after'  => null
        )
    ) {
        ob_start();

        echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
        call_user_func( $function, $atts );
        echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

        return ob_get_clean();
    }

    public static function checkout( $atts = array(), $content = null ) {
        return wpinv_checkout_form( $atts, $content );
    }

    public static function messages( $atts, $content = null ) {
        ob_start();
        wpinv_print_errors();
        return '<div class="wpinv">' . ob_get_clean() . '</div>';
    }
    
    public static function history( $atts, $content = null ) {
        return self::shortcode_wrapper( array( __CLASS__, 'history_output' ), $atts );
    }

    /**
     * Output the shortcode.
     *
     * @param array $atts
     */
    public static function history_output( $atts ) {
        do_action( 'wpinv_before_user_invoice_history' );
        wpinv_get_template_part( 'wpinv-invoice-history', $atts );
        do_action( 'wpinv_after_user_invoice_history' );
    }
    
    public static function receipt( $atts, $content = null ) {
        return wpinv_payment_receipt( $atts, $content );
    }
}
