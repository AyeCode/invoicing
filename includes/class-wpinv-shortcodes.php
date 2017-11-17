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
            'wpinv_receipt'  => __CLASS__ . '::success',
            'wpinv_buy'  => __CLASS__ . '::buy',
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
        }
        
        add_shortcode( 'wpinv_messages', __CLASS__ . '::messages' );
    }

    public static function shortcode_wrapper( $function, $atts = array(), $content = null, $wrapper = array( 'class' => 'wpi-g', 'before' => null, 'after' => null ) ) {
        ob_start();

        echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
        call_user_func( $function, $atts, $content );
        echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

        return ob_get_clean();
    }

    public static function checkout( $atts = array(), $content = null ) {
        return self::shortcode_wrapper( array( __CLASS__, 'checkout_output' ), $atts, $content );
    }

    public static function checkout_output( $atts = array(), $content = null ) {
        do_action( 'wpinv_checkout_content_before' );
        echo wpinv_checkout_form( $atts, $content );
        do_action( 'wpinv_checkout_content_after' );
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
    
    public static function success( $atts, $content = null ) {
        return self::shortcode_wrapper( array( __CLASS__, 'success_output' ), $atts, $content );
    }
    
    /**
     * Output the shortcode.
     *
     * @param array $atts
     */
    public static function success_output( $atts, $content = null ) {
        do_action( 'wpinv_success_content_before' );
        echo wpinv_payment_receipt( $atts, $content );
        do_action( 'wpinv_success_content_after' );
    }

    public static function buy( $atts, $content = null ) {
        $a = shortcode_atts( array(
            'items'     => '', // should be used like: item_id|quantity,item_id|quantity,item_id|quantity
            'title'     => __( 'Buy Now', 'invoicing' ), // the button title
            'post_id'   => '', // any related post_id
        ), $atts );

        $post_id = isset( $a['post_id'] ) ? (int)$a['post_id'] : '';

        $html = '<div class="wpi-buy-button-wrapper wpi-g">';
        $html .= '<button class="button button-primary wpi-buy-button" type="button" onclick="wpi_buy(this,\'' . $a['items'] . '\',' . $post_id . ');">' . $a['title'] . '</button>';
        $html .= wp_nonce_field( 'wpinv_buy_items', 'wpinv_buy_nonce', true, false );
        $html .= '</div>';

        return $html;
    }
}
