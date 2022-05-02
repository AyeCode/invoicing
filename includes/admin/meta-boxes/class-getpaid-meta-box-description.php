<?php
/**
 * Description metabox.
 *
 * Replaces the standard excerpt box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Meta_Box_Description Class.
 */
class GetPaid_Meta_Box_Description {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {

		$settings = array(
			'textarea_name' => 'excerpt',
			'quicktags'     => array( 'buttons' => 'em,strong,link' ),
			'teeny'         => true,
			'media_buttons' => false,
			'tinymce'       => array(
				'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
				'theme_advanced_buttons2' => '',
			),
			'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
		);

		wp_editor( htmlspecialchars_decode( $post->post_excerpt, ENT_QUOTES ), 'excerpt', apply_filters( 'getpaid_description_editor_settings', $settings ) );
	}
}
