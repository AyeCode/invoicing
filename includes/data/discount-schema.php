<?php
/**
 * The discount schema.
 *
 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
 * @link http://json-schema.org/draft-04/schema#
 *
 * @package Invoicing/data
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

return array(

	'id'              => array(
		'description' => __( 'Unique identifier for the discount.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'status'          => array(
		'description' => __( 'A named status for the discount.', 'invoicing' ),
		'type'        => 'string',
		'enum'        => array( 'publish', 'pending', 'draft', 'expired' ),
		'default'     => 'draft',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'version'         => array(
		'description' => __( 'Plugin version when the discount was created.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_created'    => array(
		'description' => __( "The date the discount was created, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'date_created_gmt'    => array(
		'description' => __( 'The GMT date the discount was created.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified'   => array(
		'description' => __( "The date the discount was last modified, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified_gmt'    => array(
		'description' => __( 'The GMT date the discount was last modified.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'name'			  => array(
		'description' => __( 'The discount name.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'description'     => array(
		'description' => __( 'A description of what the discount is all about.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'code'            => array(
		'description' => __( 'The discount code.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'required'	  => true,
	),

	'type'            => array(
		'description' => __( 'The type of discount.', 'invoicing' ),
		'type'        => 'string',
		'enum'        => array_keys( wpinv_get_discount_types() ),
		'context'     => array( 'view', 'edit', 'embed' ),
		'default'	  => 'percent',
	),

	'amount'        => array(
		'description' => __( 'The discount value.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'required'	  => true,
	),

	'formatted_amount'        => array(
		'description' => __( 'The formatted discount value.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'uses'            => array(
		'description' => __( 'The number of times the discount has been used.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'embed' ),
		'readonly'    => true,
	),

	'max_uses'        => array(
		'description' => __( 'The maximum number of times the discount can be used.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit' ),
	),

	'usage'           => array(
		'description' => __( "The discount's usage, i.e uses / max uses.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'embed' ),
		'readonly'    => true,
	),

	'is_single_use'   => array(
		'description' => __( 'Whether or not the discount can only be used once per user.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit' ),
	),

	'is_recurring'   => array(
		'description' => __( 'Whether or not the discount applies to the initial payment only or all recurring payments.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit' ),
	),

	'start_date'      => array(
		'description' => __( 'The start date for the discount in the format of yyyy-mm-dd hh:mm:ss. If provided, the discount can only be used after or on this date.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit' ),
	),

	'end_date'        => array(
		'description' => __( 'The expiration date for the discount.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit' ),
	),

	'allowed_items'   => array(
		'description' => __( 'Items which are allowed to use this discount. Leave blank to enable for all items.', 'invoicing' ),
		'type'        => 'array',
		'context'     => array( 'view', 'edit' ),
		'items'       => array(
			'type'    => 'integer'
		)
	),

	'excluded_items'  => array(
		'description' => __( 'Items which are NOT allowed to use this discount.', 'invoicing' ),
		'type'        => 'array',
		'context'     => array( 'view', 'edit' ),
		'items'       => array(
			'type'    => 'integer'
		)
	),
	
	'minimum_total'   => array(
		'description' => __( 'The minimum total needed to use this invoice.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit' ),
	),

	'maximum_total'   => array(
		'description' => __( 'The maximum total needed to use this invoice.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit' ),
	),

);
