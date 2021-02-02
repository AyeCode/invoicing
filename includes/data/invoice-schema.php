<?php
/**
 * The invoice schema.
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
		'description' => __( 'Unique identifier for the invoice.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'parent_id'       => array(
		'description' => __( 'Parent invoice ID.', 'invoicing' ),
		'type'        => 'integer',
		'minimum'     => 0,
		'default'     => 0,
		'context'     => array( 'view', 'edit' ),
	),

	'key'			  => array(
		'description' => __( 'A unique key for the invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit' ),
		'readonly'    => true,
	),

	'number'		  => array(
		'description' => __( 'A unique number for the invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'type'			  => array(
		'description' => __( 'Get the invoice type (e.g invoice, quote etc).', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'post_type'		  => array(
		'description' => __( 'Get the invoice post type (e.g wpi_invoice, wpi_quote etc).', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'version'         => array(
		'description' => __( 'Version of GetPaid/Invoicing which last updated the invoice.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit' ),
		'readonly'    => true,
	),

	'template'        => array(
		'description' => __( 'The invoice template.', 'invoicing' ),
		'type'        => 'string',
		'default'     => 'quantity',
		'enum'        => array( 'quantity', 'hours', 'amount' ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'status'          => array(
		'description' => __( 'Invoice status.', 'invoicing' ),
		'type'        => 'string',
		'default'     => 'wpi-pending',
		'enum'        => array_keys( wpinv_get_invoice_statuses( true ) ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'status_nicename' => array(
		'description' => __( 'A human readable name for the invoice status.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'currency'        => array(
		'description' => __( 'The invoice currency in ISO format.', 'invoicing' ),
		'type'        => 'string',
		'default'     => wpinv_get_currency(),
		'enum'        => array_keys( wpinv_get_currencies() ),
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'date_created'    => array(
		'description' => __( "The date the invoice was created, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'date_created_gmt'    => array(
		'description' => __( 'The GMT date the invoice was created.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified'   => array(
		'description' => __( "The date the invoice was last modified, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'date_modified_gmt'    => array(
		'description' => __( 'The GMT date the invoice was last modified.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'due_date'        => array(
		'description' => __( "The invoice's due date, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'due_date_gmt'    => array(
		'description' => __( 'The GMT date the invoice is/was due.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'completed_date'  => array(
		'description' => __( "The date the invoice was paid, in the site's timezone.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'completed_date_gmt'    => array(
		'description' => __( 'The GMT date the invoice was paid.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'total_discount'   => array(
		'description' => __( 'Total discount amount for the invoice.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'total_tax'       => array(
		'description' => __( 'Total tax amount for the invoice.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'total_fees'      => array(
		'description' => __( 'Total fees amount for the invoice.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'subtotal'        => array(
		'description' => __( 'Invoice subtotal.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'total'           => array(
		'description' => __( 'Grand total.', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'initial_total'   => array(
		'description' => __( 'Initial total (for recurring invoices).', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'recurring_total'  => array(
		'description' => __( 'Recurring total (for recurring invoices).', 'invoicing' ),
		'type'        => 'number',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'totals'          => array(
		'description' => __( 'Invoice totals.', 'invoicing' ),
		'type'        => 'object',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'fees'            => array(
		'description' => __( 'Invoice fees (Name => properties).', 'invoicing' ),
		'type'        => 'object',
		'context'     => array( 'view', 'edit', 'embed' ),
		'items'       => array(
			'type'                => 'object',
			'required'            => array( 'amount' ),
			'properties'          => array(
				'amount'          => array(
					'description' => __( 'Fee amount.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'recurring'       => array(
					'description' => __( 'Whether this is a recurring or one-time fee.', 'invoicing' ),
					'type'        => array( 'boolean', 'integer' ),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		),
	),

	'discounts'       => array(
		'description' => __( 'Invoice discounts (Name => properties).', 'invoicing' ),
		'type'        => 'object',
		'context'     => array( 'view', 'edit', 'embed' ),
		'items'       => array(
			'type'                => 'object',
			'required'            => array( 'amount' ),
			'properties'          => array(
				'amount'          => array(
					'description' => __( 'Fee amount.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'recurring'       => array(
					'description' => __( 'Whether this is a recurring or one-time discount.', 'invoicing' ),
					'type'        => array( 'boolean', 'integer' ),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		),
	),

	'taxes'           => array(
		'description' => __( 'Invoice taxes (Name => properties).', 'invoicing' ),
		'type'        => 'object',
		'context'     => array( 'view', 'edit', 'embed' ),
		'items'       => array(
			'type'                => 'object',
			'required'            => array( 'amount' ),
			'properties'          => array(
				'amount'          => array(
					'description' => __( 'Fee amount.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'recurring'       => array(
					'description' => __( 'Whether this is a recurring or one-time tax.', 'invoicing' ),
					'type'        => array( 'boolean', 'integer' ),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		),
	),

	'items'           => array(
		'description' => __( 'Invoice items.', 'invoicing' ),
		'type'        => 'array',
		'context'     => array( 'view', 'edit', 'embed' ),
		'items'       => array(
			'type'                => 'object',
			'required'            => array( 'item_id' ),
			'properties'          => array(
				'item_id'         => array(
					'description' => __( 'Item ID.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'item_name'       => array(
					'description' => __( 'Item Name.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'item_description' => array(
					'description'  => __( 'Item Description.', 'invoicing' ),
					'type'         => 'string',
					'context'      => array( 'view', 'edit', 'embed' ),
				),
				'item_price'      => array(
					'description' => __( 'Item Price.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'quantity'        => array(
					'description' => __( 'Item Quantity.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'subtotal'        => array(
					'description' => __( 'Item Subtotal.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'meta'            => array(
					'description' => __( 'Item Meta.', 'invoicing' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		),
	),

	'mode'			  => array(
		'description' => __( 'The invoice transaction mode.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'enum'        => array( 'live', 'test' ),
		'readonly'    => true,
	),
	
	'discount_code'   => array(
		'description' => __( 'The discount code used on this invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'gateway'         => array(
		'description' => __( 'The gateway used to pay this invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'gateway_title'   => array(
		'description' => __( 'The title of the gateway used to pay this invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'transaction_id'  => array(
		'description' => __( 'The transaction id for this invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),
	
	'disable_taxes'   => array(
		'description' => __( 'Whether or not taxes should be disabled for this invoice.', 'invoicing' ),
		'type'        => 'boolean ',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'is_viewed'       => array(
		'description' => __( 'Whether or not this invoice has been viewed by the user.', 'invoicing' ),
		'type'        => 'boolean ',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'email_cc'        => array(
		'description' => __( 'A comma separated list of other emails that should receive communications for this invoice.', 'invoicing' ),
		'type'        => 'string ',
		'context'     => array( 'view', 'edit' ),
	),

	'subscription_id' => array(
		'description' => __( 'The ID of the subscription associated with this invoice.', 'invoicing' ),
		'type'        => 'string ',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'subscription_name' => array(
		'description' => __( 'The name of the subscription associated with this invoice.', 'invoicing' ),
		'type'        => 'string ',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'subscription_name' => array(
		'description' => __( 'The name of the subscription associated with this invoice.', 'invoicing' ),
		'type'        => 'string ',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_parent'		  => array(
		'description' => __( 'Whether or not this is a parent invoice.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_renewal'      => array(
		'description' => __( 'Whether or not this is a renewal invoice.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_recurring'    => array(
		'description' => __( 'Whether or not this is a recurring invoice.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_free'         => array(
		'description' => __( 'Whether or not this invoice is free.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_paid'         => array(
		'description' => __( 'Whether or not this invoice has been paid.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'needs_payment'   => array(
		'description' => __( 'Whether or not this invoice needs payment.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_refunded'     => array(
		'description' => __( 'Whether or not this invoice was refunded.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_due'          => array(
		'description' => __( 'Whether or not this invoice is due.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_held'         => array(
		'description' => __( 'Whether or not this invoice has been held for payment confirmation.', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'is_draft'        => array(
		'description' => __( 'Whether or not this invoice is marked as draft (cannot be viewed on the frontend).', 'invoicing' ),
		'type'        => 'boolean',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'path'			  => array(
		'description' => __( 'The invoice path/slug/name.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'description'     => array(
		'description' => __( 'The invoice description.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'payment_form'    => array(
		'description' => __( 'The id of the payment form used to pay for this invoice.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit' ),
		'readonly'    => true,
	),

	'submission_id'   => array(
		'description' => __( 'A uniques ID of the submission details used to pay for this invoice.', 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit' ),
		'readonly'    => true,
	),

	'customer_id'     => array(
		'description' => __( 'The customer id.', 'invoicing' ),
		'type'        => 'integer',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'customer_ip'     => array(
		'description' => __( "The customer's ip address.", 'invoicing' ),
		'type'        => 'string',
		'format'      => 'ip',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'first_name'     => array(
		'description' => __( "The customer's first name.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'last_name'       => array(
		'description' => __( "The customer's last name.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),
	
	'full_name'       => array(
		'description' => __( "The customer's full name.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'phone_number'    => array(
		'description' => __( "The customer's phone number.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
	),

	'email_address'   => array(
		'description' => __( "The customer's email address.", 'invoicing' ),
		'type'        => 'string',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'customer_country'   => array(
		'description'    => __( "The customer's country.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
		'default'        => wpinv_get_default_country(),
	),

	'customer_state'     => array(
		'description'    => __( "The customer's state.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'customer_city'      => array(
		'description'    => __( "The customer's city.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'customer_zip'       => array(
		'description'    => __( "The customer's zip/postal code.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'customer_company'   => array(
		'description'    => __( "The customer's company name.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'vat_number'         => array(
		'description'    => __( "The customer's VAT number.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'vat_rate'           => array(
		'description'    => __( "The customer's VAT rate.", 'invoicing' ),
		'type'           => 'number',
		'context'        => array( 'view', 'edit', 'embed' ),
		'readonly'       => true,
	),

	'customer_address'   => array(
		'description'    => __( "The customer's address.", 'invoicing' ),
		'type'           => 'string',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'address_confirmed'  => array(
		'description'    => __( "Whether or not the customer's address is confirmed.", 'invoicing' ),
		'type'           => 'boolean',
		'context'        => array( 'view', 'edit', 'embed' ),
	),

	'meta_data'       => array(
		'description' => __( 'Invoice meta data.', 'invoicing' ),
		'type'        => 'array',
		'context'     => array( 'view', 'edit', 'embed' ),
		'items'       => array(
			'type'                => 'object',
			'properties'          => array(
				'id'              => array(
					'description' => __( 'Meta ID.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'key'             => array(
					'description' => __( 'Meta key.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'value'           => array(
					'description' => __( 'Meta Value.', 'invoicing' ),
					'type'        => array( 'string', 'array', 'object', 'integer', 'null' ),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		),
	),

	'view_url'        => array(
		'description' => __( 'URL to the invoice.', 'invoicing' ),
		'type'        => 'string',
		'format'      => 'uri',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'checkout_payment_url'         => array(
		'description' => __( 'URL to the invoice checkout page.', 'invoicing' ),
		'type'        => 'string',
		'format'      => 'uri',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

	'receipt_url'     => array(
		'description' => __( 'URL to the invoice receipt page.', 'invoicing' ),
		'type'        => 'string',
		'format'      => 'uri',
		'context'     => array( 'view', 'edit', 'embed' ),
		'readonly'    => true,
	),

);
