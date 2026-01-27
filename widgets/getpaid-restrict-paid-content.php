<?php

class WPInv_Restrict_Paid_Content_Widget extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'       => 'invoicing',
			'output_types'     => array( 'block', 'shortcode' ),
			'nested-block'     => true,
			'block-icon'       => 'lock',
			'block-category'   => 'layout',
			'block-keywords'   => "['getpaid','restrict','paid','content','container']",
			'block-label'      => "'" . __( 'GP > Restrict Paid Content', 'invoicing' ) . "'",
			'block-supports'   => array(
				'customClassName' => false,
			),
			'block-output'     => array(
				array(
					'element'          => 'innerBlocksProps',
					'blockProps'       => array(
						'if_className' => ' ( typeof  props.attributes.styleid !== "undefined" )  ?  props.attributes.styleid + " " [%WrapClass%] : ""  [%WrapClass%]',
						'style'        => '{[%WrapStyle%]}',
						'if_id'        => 'props.attributes.anchor ? props.attributes.anchor : props.clientId',
					),
					'innerBlocksProps' => array(
						'orientation' => 'vertical',
					),
				),
			),
			'block-wrap'       => '',
			'class_name'       => __CLASS__,
			'base_id'          => 'getpaid_restrict_paid_content',
			'name'             => __( 'GP > Restrict Paid Content', 'invoicing' ),
			'widget_ops'       => array(
				'classname'   => 'getpaid-restrict-paid-content',
				'description' => esc_html__( 'A container block that restricts content based on user purchases or total spend.', 'invoicing' ),
			),
			'example'          => array(
				'viewportWidth' => 300,
				'innerBlocks'   => array(
					array(
						'name'       => 'core/paragraph',
						'attributes' => array(
							'content' => esc_html__( 'This content is restricted based on purchase criteria.', 'invoicing' ),
						),
					),
				),
			),
			'no_wrap'          => true,
			'block_group_tabs' => array(
				'content'  => array(
					'groups' => array( __( 'Restriction', 'invoicing' ), __( 'Container', 'invoicing' ) ),
					'tab'    => array(
						'title'     => __( 'Content', 'invoicing' ),
						'key'       => 'bs_tab_content',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'styles'   => array(
					'groups' => array( __( 'Background', 'invoicing' ), __( 'Typography', 'invoicing' ) ),
					'tab'    => array(
						'title'     => __( 'Styles', 'invoicing' ),
						'key'       => 'bs_tab_styles',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'advanced' => array(
					'groups' => array(
						__( 'Wrapper Styles', 'invoicing' ),
						__( 'Advanced', 'invoicing' ),
					),
					'tab'    => array(
						'title'     => __( 'Advanced', 'invoicing' ),
						'key'       => 'bs_tab_advanced',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
			),
		);

		parent::__construct( $options );
	}

	/**
	 * Set the arguments later.
	 *
	 * @return array
	 */
	public function set_arguments() {

		$arguments = array();

		// Restriction settings
		$arguments['restriction_type'] = array(
			'title'    => __( 'Restriction Type', 'invoicing' ),
			'desc'     => __( 'Select the type of restriction to apply', 'invoicing' ),
			'type'     => 'select',
			'options'  => array(
				'bought_item'     => __( 'User has bought item(s)', 'invoicing' ),
				'not_bought_item' => __( 'User has NOT bought item(s)', 'invoicing' ),
				'total_spend'     => __( 'User total spend greater than', 'invoicing' ),
			),
			'default'  => 'bought_item',
			'desc_tip' => true,
			'group'    => __( 'Restriction', 'invoicing' ),
		);

		$arguments['item_ids'] = array(
			'title'           => __( 'Item IDs', 'invoicing' ),
			'desc'            => __( 'Enter comma-separated item IDs (e.g., 101,102,103)', 'invoicing' ),
			'type'            => 'text',
			'placeholder'     => __( '101,102,103', 'invoicing' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => __( 'Restriction', 'invoicing' ),
			'element_require' => '[%restriction_type%]=="bought_item" || [%restriction_type%]=="not_bought_item"',
		);

		$arguments['match_type'] = array(
			'title'           => __( 'Match Type', 'invoicing' ),
			'desc'            => __( 'For multiple IDs, should the user have purchased any or all of them?', 'invoicing' ),
			'type'            => 'select',
			'options'         => array(
				'any' => __( 'Any (OR) - User has purchased at least one', 'invoicing' ),
				'all' => __( 'All (AND) - User has purchased all items', 'invoicing' ),
			),
			'default'         => 'any',
			'desc_tip'        => true,
			'group'           => __( 'Restriction', 'invoicing' ),
			'element_require' => '[%restriction_type%]=="bought_item" || [%restriction_type%]=="not_bought_item"',
		);

		$arguments['min_amount'] = array(
			'title'           => __( 'Minimum Amount', 'invoicing' ),
			'desc'            => __( 'Minimum total spend amount required', 'invoicing' ),
			'type'            => 'number',
			'placeholder'     => __( '100', 'invoicing' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => __( 'Restriction', 'invoicing' ),
			'element_require' => '[%restriction_type%]=="total_spend"',
		);

		$arguments['fallback_message'] = array(
			'title'    => __( 'Fallback Message', 'invoicing' ),
			'desc'     => __( 'Message to display when content is hidden (leave empty to show nothing)', 'invoicing' ),
			'type'     => 'textarea',
			'placeholder' => __( 'This content is only available to customers who have purchased specific items.', 'invoicing' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => __( 'Restriction', 'invoicing' ),
		);

		// Container class
		$arguments['container'] = sd_get_container_class_input( 'container', array( 'group' => __( 'Container', 'invoicing' ) ) );

		$arguments['h100'] = array(
			'type'            => 'select',
			'title'           => __( 'Card equal heights', 'invoicing' ),
			'default'         => '',
			'options'         => array(
				''      => __( 'No', 'invoicing' ),
				'h-100' => __( 'Yes', 'invoicing' ),
			),
			'desc_tip'        => false,
			'group'           => __( 'Container', 'invoicing' ),
			'element_require' => '[%container%]=="card"',
		);

		// row-cols
		$arguments['row_cols']    = sd_get_row_cols_input( 'row_cols', array( 'device_type' => 'Mobile', 'group' => __( 'Container', 'invoicing' ) ) );
		$arguments['row_cols_md'] = sd_get_row_cols_input( 'row_cols', array( 'device_type' => 'Tablet', 'group' => __( 'Container', 'invoicing' ) ) );
		$arguments['row_cols_lg'] = sd_get_row_cols_input( 'row_cols', array( 'device_type' => 'Desktop', 'group' => __( 'Container', 'invoicing' ) ) );

		// columns
		$arguments['col']    = sd_get_col_input( 'col', array( 'device_type' => 'Mobile', 'group' => __( 'Container', 'invoicing' ) ) );
		$arguments['col_md'] = sd_get_col_input( 'col', array( 'device_type' => 'Tablet', 'group' => __( 'Container', 'invoicing' ) ) );
		$arguments['col_lg'] = sd_get_col_input( 'col', array( 'device_type' => 'Desktop', 'group' => __( 'Container', 'invoicing' ) ) );

		// Background
		$arguments = $arguments + sd_get_background_inputs( 'bg', array( 'group' => __( 'Background', 'invoicing' ) ) );

		$arguments['bg_on_text'] = array(
			'type'            => 'checkbox',
			'title'           => __( 'Background on text', 'invoicing' ),
			'default'         => '',
			'value'           => '1',
			'desc_tip'        => false,
			'desc'            => __( 'This will show the background on the text.', 'invoicing' ),
			'group'           => __( 'Background', 'invoicing' ),
			'element_require' => '[%bg%]=="custom-gradient"',
		);

		// text color
		$arguments['text_color'] = sd_get_text_color_input( 'text_color', array( 'group' => __( 'Typography', 'invoicing' ) ) );

		// Text justify
		$arguments['text_justify'] = sd_get_text_justify_input( 'text_justify', array( 'group' => __( 'Typography', 'invoicing' ) ) );

		// text align
		$arguments['text_align']    = sd_get_text_align_input(
			'text_align',
			array(
				'device_type'     => 'Mobile',
				'element_require' => '[%text_justify%]==""',
				'group'           => __( 'Typography', 'invoicing' ),
			)
		);
		$arguments['text_align_md'] = sd_get_text_align_input(
			'text_align',
			array(
				'device_type'     => 'Tablet',
				'element_require' => '[%text_justify%]==""',
				'group'           => __( 'Typography', 'invoicing' ),
			)
		);
		$arguments['text_align_lg'] = sd_get_text_align_input(
			'text_align',
			array(
				'device_type'     => 'Desktop',
				'element_require' => '[%text_justify%]==""',
				'group'           => __( 'Typography', 'invoicing' ),
			)
		);

		// margins mobile
		$arguments['mt'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mr'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mb'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['ml'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// margins tablet
		$arguments['mt_md'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mr_md'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mb_md'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['ml_md'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// margins desktop
		$arguments['mt_lg'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mr_lg'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['mb_lg'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Desktop', 'default' => 3, 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['ml_lg'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// padding
		$arguments['pt'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pr'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pb'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pl'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// padding tablet
		$arguments['pt_md'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pr_md'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pb_md'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pl_md'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// padding desktop
		$arguments['pt_lg'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pr_lg'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pb_lg'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['pl_lg'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// border
		$arguments['border']         = sd_get_border_input( 'border', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['border_type']    = sd_get_border_input( 'type', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['border_width']   = sd_get_border_input( 'width', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['border_opacity'] = sd_get_border_input( 'opacity', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['rounded']        = sd_get_border_input( 'rounded', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['rounded_size']   = sd_get_border_input( 'rounded_size', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// shadow
		$arguments['shadow'] = sd_get_shadow_input( 'shadow', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// position
		$arguments['position'] = sd_get_position_class_input( 'position', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		$arguments['sticky_offset_top']    = sd_get_sticky_offset_input( 'top', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['sticky_offset_bottom'] = sd_get_sticky_offset_input( 'bottom', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		$arguments['display']    = sd_get_display_input( 'd', array( 'device_type' => 'Mobile', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['display_md'] = sd_get_display_input( 'd', array( 'device_type' => 'Tablet', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments['display_lg'] = sd_get_display_input( 'd', array( 'device_type' => 'Desktop', 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// flex align items
		$arguments = $arguments + sd_get_flex_align_items_input_group( 'flex_align_items', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments = $arguments + sd_get_flex_justify_content_input_group( 'flex_justify_content', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments = $arguments + sd_get_flex_align_self_input_group( 'flex_align_self', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );
		$arguments = $arguments + sd_get_flex_order_input_group( 'flex_order', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// overflow
		$arguments['overflow'] = sd_get_overflow_input( 'overflow', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// Max height
		$arguments['max_height'] = sd_get_max_height_input( 'max_height', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// scrollbars
		$arguments['scrollbars'] = sd_get_scrollbars_input( 'scrollbars', array( 'group' => __( 'Wrapper Styles', 'invoicing' ) ) );

		// block visibility conditions
//		$arguments['visibility_conditions'] = sd_get_visibility_conditions_input( 'visibility_conditions', array( 'group' => __( 'Advanced', 'invoicing' ) ) );

		// advanced
		$arguments['anchor'] = sd_get_anchor_input( 'anchor', array( 'group' => __( 'Advanced', 'invoicing' ) ) );

		$arguments['css_class'] = sd_get_class_input( 'css_class', array( 'group' => __( 'Advanced', 'invoicing' ) ) );

		if ( function_exists( 'sd_get_custom_name_input' ) ) {
			$arguments['metadata_name'] = sd_get_custom_name_input( 'metadata_name', array( 'group' => __( 'Advanced', 'invoicing' ) ) );
		}

		return $arguments;
	}

	/**
	 * This is the output function for the widget, shortcode and block (front end).
	 *
	 * @param array $args The arguments values.
	 * @param array $widget_args The widget arguments when used.
	 * @param string $content The shortcode content argument
	 *
	 * @return string
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {

		// If content is empty, return early
		if ( empty( $content ) ) {
			return '';
		}

		// Get current user ID
		$user_id = get_current_user_id();

		// Check if user meets the restriction criteria
		$condition_met = $this->check_restriction( $user_id, $args );

		// Determine if we should show the content
		$show_content = $condition_met;

		// If restriction type is "not_bought_item", invert the logic
		if ( isset( $args['restriction_type'] ) && 'not_bought_item' === $args['restriction_type'] ) {
			$show_content = ! $condition_met;
		}

		// If content should not be shown, return fallback message
		if ( ! $show_content ) {
			if ( ! empty( $args['fallback_message'] ) ) {
				return '<div class="getpaid-restricted-content-message alert alert-info">' . wp_kses_post( wpautop( $args['fallback_message'] ) ) . '</div>';
			}
			return '';
		}

		// Check if this is a block output (contains wp-block- classes)
		if ( strpos( $content, 'class="wp-block-' ) !== false ) {
			// Block output - content already has proper wrapper
			return $content;
		} else {
			// Shortcode output - need to wrap with section
			$wrap_class = sd_build_aui_class( $args );

			$styles = sd_build_aui_styles( $args );
			$style  = $styles ? ' style="' . $styles . '"' : '';

			return '<section class="' . $wrap_class . '"' . $style . '>' . $content . '</section>';
		}
	}

	/**
	 * Check if user meets the restriction criteria.
	 *
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function check_restriction( $user_id, $args ) {

		// If user is not logged in, they don't meet any restriction
		if ( empty( $user_id ) ) {
			return false;
		}

		$restriction_type = isset( $args['restriction_type'] ) ? $args['restriction_type'] : 'bought_item';

		// Handle "bought_item" and "not_bought_item" restrictions
		if ( in_array( $restriction_type, array( 'bought_item', 'not_bought_item' ), true ) ) {
			return $this->check_purchased_items( $user_id, $args );
		}

		// Handle "total_spend" restriction
		if ( 'total_spend' === $restriction_type ) {
			return $this->check_total_spend( $user_id, $args );
		}

		return false;
	}

	/**
	 * Check if user has purchased the specified items.
	 *
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function check_purchased_items( $user_id, $args ) {

		if ( empty( $args['item_ids'] ) ) {
			return false;
		}

		// Parse comma-separated item IDs
		$item_ids = array_map( 'trim', explode( ',', $args['item_ids'] ) );
		$item_ids = array_filter( $item_ids, 'is_numeric' );

		if ( empty( $item_ids ) ) {
			return false;
		}

		$match_type = isset( $args['match_type'] ) ? $args['match_type'] : 'any';
		$purchased_count = 0;

		// Check each item
		foreach ( $item_ids as $item_id ) {
			if ( function_exists( 'getpaid_has_user_purchased_item' ) ) {
				$has_purchased = getpaid_has_user_purchased_item( $user_id, absint( $item_id ) );

				if ( $has_purchased ) {
					$purchased_count++;

					// If match type is "any" and user has purchased at least one, return true
					if ( 'any' === $match_type ) {
						return true;
					}
				} else {
					// If match type is "all" and user hasn't purchased one item, return false
					if ( 'all' === $match_type ) {
						return false;
					}
				}
			}
		}

		// If we reach here with "all" match type, user has purchased all items
		if ( 'all' === $match_type && $purchased_count === count( $item_ids ) ) {
			return true;
		}

		// If we reach here with "any" match type, user hasn't purchased any items
		return false;
	}

	/**
	 * Check if user's total spend meets the minimum amount.
	 *
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function check_total_spend( $user_id, $args ) {

		if ( empty( $args['min_amount'] ) || ! is_numeric( $args['min_amount'] ) ) {
			return false;
		}

		$min_amount = floatval( $args['min_amount'] );

		if ( function_exists( 'getpaid_get_user_total_spend' ) ) {
			$total_spend = getpaid_get_user_total_spend( $user_id );

			return $total_spend >= $min_amount;
		}

		return false;
	}

}
