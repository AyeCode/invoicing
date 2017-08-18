=== Invoicing ===
Contributors: stiofansisland, ayecode, paoltaia
Donate link: http://wpgeodirectory.com/
Tags: invoice, invoicing, invoice plugin, invoices, invoices plugin, invoicing plugin
Requires at least: 3.1
Tested up to: 4.8
Stable tag: 0.0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Invoicing plugin.

== Description ==

Invoicing plugin.

== Requirements ==

* 

== Changelog ==

= 0.0.5 =
Option added to make phone mandatory/optional in address fields - CHANGED
Security vulnerability fixed - FIXED
_wpinv_payment_meta should always return an array even if empty - FIXED
No way to remove VAT Details input from frontend - FIXED
Function added to create item from array of data - CHANGED
Fix invoice status conflict with other plugin - CHANGED
Shows incorrect trial end date - FIXED
Invoice notes should not counted in WP standard comments count - CHANGED
Payment gateways should be hidden if invoice total is zero(except invoices with free trial) - FIXED
Allow for sequential invoice numbers - CHANGED
Limit the discount to a single use per user sometimes not working - FIXED
Invoice status "pending" changed to "wpi-pending" - CHANGED

= 0.0.4 =
First public beta release - RELEASE

= 0.0.1 =
ajaxurl sometimes not defined on frontend - FIXED
Not working if GD not installed - FIXED
In print invoice view use the company name if available instead of the website name - CHANGED
Invoice link added in email to view invoice print page - ADDED
In backend button added to send invoice to user via email - ADDED
Backend the initial vat value should be assigned via the user country - CHANGED
In backend button added to recalculate totals for invoice - ADDED
Able to add new user from edit invoice page - ADDED
Tools added to merge GD packages & invoices - ADDED
Discount can be enable/disable for relevant item - ADDED
