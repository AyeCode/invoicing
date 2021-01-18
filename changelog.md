= 2.0.2-beta =
* Fix "minimum value" error with number settings fields - FIXED
* Add the settings REST API endpoint - ADDED
* Add sales REST API endpoint - ADDED
* Add top-sellers REST API endpoint - ADDED
* Add top-earners REST API endpoint - ADDED
* Add invoice counts REST API endpoint - ADDED

# 2.0.1-beta
* Add the raw_html settings field type - ADDED

# 2.0.0-beta
* Exclude invoicing pages from Yoast XML page sitemap - CHANGED
* Bump tested upto WP version - CHANGED
* Send BCC email to admin for Payment Reminder - ADDED
* Ability to create payment forms - ADDED
* If VAT is enabled, you can now disable taxes on a per item basis - ADDED
* If VAT is enabled, you can now disable taxes on a per invoice basis - ADDED
* Inovice action buttons not working on the 2019 theme - FIXED
* Ability to change the invoice date - ADDED
* CTA for free checkouts changed from "Complete" to "Continue" - CHANGED
* Unable to checkout when required fields are hidden via CSS - FIXED
* Buy now buttons shortcode now visible - ADDED
* Admin menus re-ordered to provide better hierachy - CHANGED
* Revenue reports - ADDED
* Yoast SEO 14.1 style conflicts - FIXED
* Show subscription details on receipt - ADDED
* GeoIP2 database not downloading - FIXED
* Pay now link working for logged out users - FIXED
* Upcoming subscription renewal emails - ADDED
* Ability to send invoice notifications to other emails (CC) other than the one currently being billed - ADDED
* VAT Reverse charge notice - ADDED
* Ability to add descriptions/excerpts to invoices - ADDED
* Ability to view and edit customers - ADDED
* Error shows if all discounts are expired - FIXED
* Some discounts do not have a delete link - FIXED
* Remove Astra and RankMath metaboxes from the Invoice edit pages - ADDED
* Change item prices on a per invoice basis - ADDED
* Improve UI to change invoice item quantities - ADDED
* Ability to charge hours, quantities or amounts only - ADDED
* Worldpay - You can now specify an MD5 secret and callback password.
* Worldpay - invoices are now automatically marked as paid or failed.
* Authorize.NET - Get rid of the Authorize.NET SDK to improve on speed, size and reduce the required minimum PHP version.
* Authorize.NET - Allow customers to save their payment profiles for quicker checkouts.
* Authorize.NET - Replaced the deprecated md5 secret with a [signature key](https://support.authorize.net/s/article/MD5-Hash-End-of-Life-Signature-Key-Replacement)
* Authorize.NET - Improve the subscriptions feature.
* Admin can now manually renew a subscription - ADDED
* Ability to enter prices with tax - ADDED
* All matching tax rates are now applied - CHANGED
* Ability to calculate tax based on base address instead of shop address - ADDED
* Invoice exports now include the item ids - ADDED
* Users can now set a tax rate for all countries - ADDED
* Users can now set a tax rate for multiple states - ADDED

# 1.0.18
* Display customer notes on the invoice print page - ADDED
* Use Responsive tables for invoice history invoice items table - CHANGED
* Ability to add item description on the quick add form - ADDED
* Manual payments now support subscriptions - CHANGED
* Pass invoice object to `wpinv_invoice_is_free_trial` filter - 
* PHP 7.4 compatibility - ADDED

# 1.0.17
* Show confirmation message when cancelling subscriptions - CHANGED
* Ability to set the receiver email for admin invoice notifications - ADDED
* Discount use reports - ADDED
* Ability to set currency per invoice - ADDED

# 1.0.16
* Conflict with Pricing Manager Addon - FIXED

# 1.0.15
* Send email notifications for successful renewals - ADDED
* Remove invoice items (or reduce the number) - Added
* `WPInv_Invoice->setup_status_nicename()` now supports quotes - CHANGED
* Created `WPInv_Invoice->is_quote()` method - ADDED
* `wpinv_create_invoice()` and `wpinv_insert_invoice()` functions now support creating quotes - CHANGED
* Invoices api now supports querying items by meta fields and dates - ADDED
* Return canceled PayPal transactions to the checkout page instead of the payment failed page - CHANGED
* Discount Object - ADDED
* AyeCode Connect notice now shows on extensions pages - ADDED

# 1.0.14
* Support for group_description for privacy exporters (thanks @garretthyder) - ADDED
* Default buy now button text - ADDED
* Users with a manage_invoicing capability can view subscriptions - ADDED
* Missing "Add New" button on item overview pages - FIXED
* Change invoice address format based on the customer's billing country - ADDED
* More country states - ADDED
* Rearrange address data into a data folder - CHANGED
* [wpinv_buy] shortcode now uses label instead of title for the button label - BREAKING CHANGE

# 1.0.13
* Extensions page Gateways not able to be installed via single key - FIXED
* Ability to create, read, update and delete an invoice via REST API - ADDED
* Ability to create, read, update and delete invoice items via REST API - ADDED
* Ability to create, read, update and delete discounts via REST API - ADDED
* Filter invoice address format - ADDED
* Shortcodes converted to Super Duper widgets - CHANGED
* Oxygen plugin page builder breaks invoice template - FIXED
* Error: Call to undefined function `wpinv_month_num_to_name` - FIXED
* Users with a `manage_invoicing` capability can now manage all aspects of the plugin - ADDED
* Super Duper updated to 1.0.16 - CHANGED
* Added alternative IP location service for servers with allow_url_fopen disabled - ADDED

# 1.0.12
* Super Duper updated to v1.0.15 - CHANGED

# 1.0.11
* BuddyPress profile my invoice tab showing count with paid only should show all - FIXED
* Remove use of WP_Session library and use transient instead - CHANGED
* Mark invoice viewed when a user view it from invoice history - FIXED
* 100% discount with first time payment for recurring payment should not redirect to gateway - FIXED
* Fix 503 error while visiting checkout page if w3 total cache is active - FIXED
* Problem in submitting the checkout form with full price discount - FIXED
* Remove Yoast SEO metabox from edit invoice screen - FIXED
* Allow users to pay what they want - ADDED
* Display gateway in status column on admin side if invoice paid by offline payment gateways - ADDED
* BuddyPress profile my invoice tab showing count with paid only should show all - FIXED
* View invoice link now uses exit instead of wp_die() function - CHANGED

# 1.0.10
Invalid invoice user id error sometimes when require login to checkout disabled - FIXED
Extensions screen containing all available add ons for UsersWP and recommended plugins - ADDED
Updated Font Awesome version to 1.0.11 - CHANGED
Setting to allow to enable renewal payment email notification which is disabled by default. - ADDED
Export items to CSV export in reports page - ADDED

# 1.0.9
Invoice history menu item should redirect to my invoices in BuddyPress profile if BuddyPress active - FIXED
Use select2 for dropdown. - CHANGED
Invoice set to paid due to conflict with duplicate page plugin - FIXED
Discount should be calculated based on old item price if the item price changed after invoice created - FIXED
Super Duper updated to v1.0.12 - CHANGED

# 1.0.8
Checkout fields mandatory is option not working - FIXED
Buddypress profile page invoices tab is not responsive - FIXED
Add classes to invoice page buttons - ADDED
Add invoicing pages to admin menu items metabox - ADDED
filter added to wpinv_get_invoices query params - ADDED
Authorize.net recurring payment only authorize the payment allow capture as well - FIXED
Super Duper updated to v1.0.10 - CHANGED
load vat js files when required - FIXED

# 1.0.7
Recurring invoice treated as renewal payment for first payment due to delay in IPN - FIXED
Use font awesome library - CHANGED
Option to allow reset invoice sequence - ADDED
Item editable meta value not working after update item - FIXED
Item summary displays warning if not filled - FIXED


# 1.0.6
Invoice print table design issue on mobile - FIXED
Option to add custom style for invoice print page - ADDED
Setting for transaction type in Authorize.net - ADDED
Invoice not saving due to conflicts with ACFPro - FIXED
Translation typos and consistency - FIXED
Search invoice by user email in admin side invoice listing - ADDED
Column displaying invoice count in admin side users listing - ADDED
Updated to use Font Awesome 5 JS version - CHANGED
Updated invoicing menu icon - CHANGED

# 1.0.5
Update authorize.net SSL certificates - FIXED
Upgrade from older version < 1.0.3 can sometimes activate sandbox mode for active payment gateways - FIXED
Show warning if test mode active for payment gateways - ADDED

# 1.0.4
Invoice notes should not display in RSS feeds - FIXED

# 1.0.3
Invoice created date should not updated on invoice published - CHANGED
Show recurring supported gateways in backend item page - CHANGED
Fix front end style conflict - FIXED
Subscription functionality improved - CHANGED
Option to force show company name - ADDED
Avada colorpicker conflicts - FIXED
It is hard to link paypal payment to invoice at paypal site - CHANGED
Fix the color picker conflict with Avada theme - FIXED
GDPR Compliance - ADDED

# 1.0.2
Paying old recurring invoice treated as renewal payment and creates new invoice - FIXED
VAT fields not displayed on checkout page for invoice with free trial - FIXED
Payment button text confusing when invoice is non recurring & total is zero - FIXED
wpinv_get_template_part() does not locate the template from the themes - FIXED

# 1.0.1
New currencies added - ADDED
Option added to remove data on uninstall - CHANGED
Show last invoice's sequential number - ADDED
Set PayPal landing page to Billing page - CHANGED
GD Listing does not renewed on renewal payment - FIXED
Changing currency should not reflected in existing invoices until invoices resaved - FIXED
Negative total if discount is greater than item price - FIXED
Checkout/history/success pages should not be cached - FIXED

# 1.0.0

initial wp.org release - YAY
Option added to make phone mandatory/optional in address fields - CHANGED
wp-admin url param escaping bug - FIXED
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
Ajax buy button shortcode added - ADDED

# 0.0.4
First public beta release - RELEASE

# 0.0.1
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