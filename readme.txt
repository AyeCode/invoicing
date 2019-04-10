=== Invoicing - Invoice & Payments Plugin ===
Contributors: stiofansisland, paoltaia, ayecode, Ismiaini
Donate link: https://wpinvoicing.com
Tags:  invoice, invoicing, recurring payments, paypal, quote, VAT MOSS, HTML invoice, HTML quote, estimate, HTML estimate, billing, bills, bill clients, invoice clients, email invoice, invoice online, recurring invoice, recurring billing, invoice generator, invoice system, accounting, ecommerce, check out, shopping cart, stripe, 2check out, authorize.met, paypal pro, sagepay, payfast
Requires at least: 4.9
Tested up to: 5.1
Stable tag: 1.0.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPress Invoicing & payments plugin that allows to create Invoices, send them to clients and accept single or recurring payments. Invoicing can be easily used as check out system for 3rd party plugins.

== Description ==

= Lightweight, extensible and very easy to use Invoicing plugin to create Invoices that you can send to your customers and they can pay online. =

It can be used as payment system for 3rd party plugins too.

= Features =

* Create quotes and estimates to send to your clients [requires WPI quotes add-on](https://wordpress.org/plugins/invoicing-quotes/)
* Quotes can be turned into invoices and client can pay online.
* Create and send invoices directly to receive single or recurring payments.
* Accept payments via Paypal Standard, Authorize.net, WorldPay and Pre Bank Transfer (other payment gateways available as premium add-ons).
* Export payments and any other data through the reporting system.
* Manage taxes.
* VAT MOSS complaint (European Union VAT management system).
* Easily create invoices programmatically through other plugins.

= A payment system for other plugins and themes =

Invoicing can be used as payment system with check out page by any plugin.

[Here you find instructions on how to integrate any plugin with Invoicing.](https://wpinvoicing.com/docs/adding-a-custom-item-type/)

= Plugins currently using Invoicing as their Payment system are =

* [GeoDirectory](https://wpgeodirectory.com/) - GeoDirectory uses Invoicing to manage Invoices, taxes, VAT and payments.
* [Paid Members](https://wordpress.org/plugins/members/) - Coming soon - An add-on for Members to create paid membership with custom roles and permissions.

= Payment Gateways =

* PayPal Standard (built in) - Accept Paypal and credit card payments (single or recurring) via paypal.
* Authorize.Net (built in) - Accept credit card payments (single or recurring) via Authorize.Net.
* Worldpay (built in) - Accept credit card payments (single or recurring) via Worldpay.
* Pre Bank Transfer (built in) - Instruct users how to send you a bank transfer which you can then later mark as paid.
* [Stripe](https://wpinvoicing.com/downloads/stripe-payment-gateway/) - Accept credit card payments (single or recurring) directly on your website via Stripe.
* [PayPal Pro](https://wpinvoicing.com/downloads/paypal-pro-payment-gateway/) - Accept Paypal and credit card payments (single or recurring) directly on your website.
* [PayFast](https://wpinvoicing.com/downloads/payfast-payment-gateway/) - Accept payments via PayFast.
* [Cheque Payment](https://wpinvoicing.com/downloads/cheque-payment-gateway/)  - Accept payments via Cheques
* [Mollie](https://wpinvoicing.com/downloads/mollie-payment-gateway/)  - Accept payment via Mollie (EUR only)
* [GoCardless](https://wpinvoicing.com/downloads/gocardless-payment-gateway/)  - Accept payments via GoCardless (direct debits)
* [Sage Pay](https://wpinvoicing.com/downloads/sage-pay-payment-gateway/)  - Accept payments via Sage Pay
* [2CheckOut](https://wpinvoicing.com/downloads/2checkout-payment-gateway/) - Accept payments (single or recurring) via 2CO.
* [Cash on Delivery](https://wpinvoicing.com/downloads/cash-on-delivery-payment-gateway/) - Accept payments via Cash on Delivery
* [PayUmoney](https://wpinvoicing.com/downloads/payumoney-payment-gateway/) - Accept payments via PayUmoney
* [WebPay](https://wpinvoicing.com/downloads/payumoney-payment-gateway/) - Accept payments via WebPay


= Add-ons =

* [Quotes](https://wordpress.org/plugins/invoicing-quotes/) - Create quotes, send them to clients and convert them to Invoices when accepted by the customer
* [PDF Invoices](https://wpinvoicing.com/downloads/pdf-invoices/) - Send PDF invoices via email or let users download them
* [AffiliateWP Integration](https://wpinvoicing.com/downloads/affiliatewp-integration/) - Integrate with the [AffiliateWP plugin](https://affiliatewp.com/)
* [Contact form 7](https://wpinvoicing.com/downloads/contact-form-7/) - Send a invoice/quote when a user fills out a form
* [Gravity Forms](https://wpinvoicing.com/downloads/gravity-forms/) - Send a invoice/quote when a user fills out a form


New Payment Gateways and Add-ons will be created regularly. If there is a Payment Gateway that you need urgently or a feature missing that you think we must add, [get in touch with us](https://wpinvoicing.com/contact-form/). we will consider it.

= Support =

Get timely and friendly support for both Core Plugin and add-ons at our official website, [Invoicing Support](https://wpinvoicing.com/support/)

= Origin =

Work on Invoicing started in April 2016.
We are proud the original base of the plugin was a fork of [EDD](https://wordpress.org/plugins/easy-digital-downloads/) with permission from Pippin.
Additionally we are proud some code from [Sliced Invoices](https://wordpress.org/plugins/sliced-invoices/) by David Grant was used in places, mostly for his beautiful invoice layout.
We worked on the plugin for over a year before it got its first public release, we stripped down the code to make it a simple lightweight payment and invoicing plugin with many additional features to fit the needs of our customers.

== Installation ==

= Minimum Requirements =

* WordPress 3.1 or greater
* PHP version 5.3 or greater
* MySQL version 5.0 or greater

= Automatic installation =

Automatic installation is the easiest option. To do an automatic install of WP Invoicing, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type WP Invoicing and click Search Plugins. Once you've found WP Invoicing plugin you install it by simply clicking Install Now. [Invoicing  basic installation](http://wpinvoicing.com/docs/basic-installation/)

= Manual installation =

The manual installation method involves downloading our Directory plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex will tell you more [here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation). [Invoicing  basic installation](http://wpinvoicing.com/docs/basic-installation/)

= Updating =

Automatic updates should seamlessly work. We always suggest you backup up your website before performing any automated update to avoid unforeseen problems.

== Frequently Asked Questions ==

[Invoicing FAQ](https://wpinvoicing.com/faq/).

== Screenshots ==

1. General Settings.
2. Payment Gateways.
3. Taxes and VAT MOSS Settings.
4. Email Settings.
5. Miscellaneous.
6. Add new Invoice.
7. Invoice Details.
8. Invoice Items.
9. Invoice Notes.
10. Client Invoice

== Changelog ==

= 1.0.8 =
Checkout fields mandatory is option not working - FIXED
Buddypress profile page invoices tab is not responsive - FIXED
Add classes to invoice page buttons - ADDED
Add invoicing pages to admin menu items metabox - ADDED
filter added to wpinv_get_invoices query params - ADDED
Authorize.net recurring payment only authorize the payment allow capture as well - FIXED
Super Duper updated to v1.0.12 - CHANGED
load vat js files when required - FIXED
Invoice history menu item should redirect to my invoices in BuddyPress profile if BuddyPress active - FIXED
Use select2 for dropdown. - CHANGED
Invoice set to paid due to conflict with duplicate page plugin - FIXED
Discount should be calculated based on old item price if the item price changed after invoice created - FIXED

= 1.0.7 =
Recurring invoice treated as renewal payment for first payment due to delay in IPN - FIXED
Use font awesome library - CHANGED
Option to allow reset invoice sequence - ADDED
Item editable meta value not working after update item - FIXED
Item summary displays warning if not filled - FIXED


= 1.0.6 =
Invoice print table design issue on mobile - FIXED
Option to add custom style for invoice print page - ADDED
Setting for transaction type in Authorize.net - ADDED
Invoice not saving due to conflicts with ACFPro - FIXED
Translation typos and consistency - FIXED
Search invoice by user email in admin side invoice listing - ADDED
Column displaying invoice count in admin side users listing - ADDED
Updated to use Font Awesome 5 JS version - CHANGED
Updated invoicing menu icon - CHANGED

= 1.0.5 =
Update authorize.net SSL certificates - FIXED
Upgrade from older version < 1.0.3 can sometimes activate sandbox mode for active payment gateways - FIXED
Show warning if test mode active for payment gateways - ADDED

= 1.0.4 =
Invoice notes should not display in RSS feeds - FIXED

= 1.0.3 =
Invoice created date should not updated on invoice published - CHANGED
Show recurring supported gateways in backend item page - CHANGED
Fix front end style conflict - FIXED
Subscription functionality improved - CHANGED
Option to force show company name - ADDED
Avada colorpicker conflicts - FIXED
It is hard to link paypal payment to invoice at paypal site - CHANGED
Fix the color picker conflict with Avada theme - FIXED
GDPR Compliance - ADDED

= 1.0.2 =
Paying old recurring invoice treated as renewal payment and creates new invoice - FIXED
VAT fields not displayed on checkout page for invoice with free trial - FIXED
Payment button text confusing when invoice is non recurring & total is zero - FIXED
wpinv_get_template_part() does not locate the template from the themes - FIXED

= 1.0.1 =
New currencies added - ADDED
Option added to remove data on uninstall - CHANGED
Show last invoice's sequential number - ADDED
Set PayPal landing page to Billing page - CHANGED
GD Listing does not renewed on renewal payment - FIXED
Changing currency should not reflected in existing invoices until invoices resaved - FIXED
Negative total if discount is greater than item price - FIXED
Checkout/history/success pages should not be cached - FIXED

= 1.0.0 =

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

== Upgrade Notice ==

none yet.