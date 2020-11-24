<?php
/**
 * The item schema.
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
		'description' => __( 'Unique identifier for the item.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'parent_id'       => array(
		'description' => __( 'Parent item ID.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
		'default'     => 0,
	),

	'status'          => array(
		'description' => __( 'A named status for the item.', 'invoicing' ),
		'type'        => 'string',
		'enum'        => array( 'draft', 'pending', 'publish' ),
		'context'     => array( 'view', 'edit', 'embed' ),
		'default'     => 'draft',
	),

	'version'         => array(
		'description' => __( 'Plugin version when the item was created.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit' ),
		'readonly'    => true,
	),

	'date_created'    => array(
		'description' => __( "The date the item was created, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'date_created_gmt'    => array(
		'description' => __( 'The GMT date the item was created.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified'   => array(
		'description' => __( "The date the item was last modified, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified_gmt'    => array(
		'description' => __( 'The GMT date the item was last modified.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'name'			  => array(
		'description' => __( "The item's name.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'required'    => true,
	),

	'description'     => array(
		'description' => __( "The item's description.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'owner'           => array(
		'description' => __( 'The owner of the item (user id).', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'price'           => array(
		'description' => __( 'The price of the item.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'required'    => true,
	),

	'the_price'       => array(
		'description' => __( 'The formatted price of the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'type'       => array(
		'description' => __( 'The item type.', 'invoicing' ),
		'type'        => 'string',
		'enum'        => wpinv_item_types(),
		'default'     => 'custom',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'vat_rule'       => array(
		'description' => __( 'VAT rule applied to the item.', 'invoicing' ),
		'type'        => 'string',
		'enum'        => array_keys( getpaid_get_tax_rules() ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'vat_class'       => array(
		'description' => __( 'VAT class for the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'enum'        => array_keys( getpaid_get_tax_classes() ),
	),

	'custom_id'       => array(
		'description' => __( 'Custom id for the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),
	
	'custom_name'       => array(
		'description' => __( 'Custom name for the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'custom_singular_name'       => array(
		'description' => __( 'Custom singular name for the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'is_dynamic_pricing'     => array(
		'description' => __( 'Whether or not customers can enter their own prices when checking out.', 'invoicing' ),
		'type'        => 'integer',
		'enum'        => array( 0, 1 ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'minimum_price'   => array(
		'description' => __( 'For dynamic prices, this is the minimum price that a user can set.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'is_recurring'        => array(
		'description' => __( 'Whether or not this is a subscription item.', 'invoicing' ),
		'type'        => 'integer',
		'enum'        => array( 0, 1 ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'initial_price'   => array(
		'description' => __( 'The initial price of the item.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'the_initial_price'       => array(
		'description' => __( 'The formatted initial price of the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'recurring_price' => array(
		'description' => __( 'The recurring price of the item.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'the_recurring_price'       => array(
		'description' => __( 'The formatted recurring price of the item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'recurring_period'        => array(
		'description' => __( 'The recurring period for a recurring item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'enum'        => array( 'D', 'W', 'M', 'Y' ),
	),

	'recurring_interval'        => array(
		'description' => __( 'The recurring interval for a subscription item.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'recurring_limit' => array(
		'description' => __( 'The maximum number of renewals for a subscription item.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'is_free_trial'   => array(
		'description' => __( 'Whether the item has a free trial period.', 'invoicing' ),
		'type'        => 'integer',
		'enum'        => array( 0, 1 ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'trial_period'    => array(
		'description' => __( 'The trial period.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'enum'        => array( 'D', 'W', 'M', 'Y' ),
	),

	'trial_interval'  => array(
		'description' => __( 'The trial interval.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'first_renewal_date'       => array(
		'description' => __( 'The first renewal date in case the item was to be bought today.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'edit_url'        => array(
		'description' => __( 'The URL to edit an item.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),
);
