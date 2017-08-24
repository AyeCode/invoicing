=== Invoicing - Invoice & Payments plugin ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpinvoicing.com
Tags:  invoice, invoicing, recurring payments, paypal, quote, VAT MOSS, HTML invoice, HTML quote, estimate, HTML estimate, billing, bills, bill clients, invoice clients, email invoice, invoice online, recurring invoice, recurring billing, invoice generator, invoice system, accounting, ecommerce, check out, shopping cart, stripe, 2check out, authorize.met, paypal pro, sagepay, payfast
Requires at least: 3.1
Tested up to: 4.8.1
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPress Invoicing & payments plugin that allows to create Invoices, send them to clients and accept single or recurring payments. Invoicing can be easily used as check out system for 3rd party plugins.

== Description ==

= Lightweight, extensible and very easy to use Invoicing plugin to create Invoices that you can send to your customers and they can pay online. =

It can be used as payment system for 3rd party plugins too.

= Features =

* Create quotes and estimates to send to your clients (requires WPI quotes add-ons) - coming soon.
* Quotes can be turned into invoices and client can pay online - coming soon.
* Create and send invoices directly to receive single or recurring payments.
* Accept payments via Paypal Standard, Authorize.net, WorldPay and Pre Bank Transfer (other payment gateways available as premium add-ons).
* Export payments and any other data through the reporting system.
* Manage taxes.
* VAT MOSS complaint (European Union VAT management system).
* Easily create invoices programatically through other plugins.

= A payment system for oher plugins and themes =

Invoicing can be used as payment system with check out page by any plugin.

[Here you find instrutions on how to integrate any plugin with Invoicing.](https://wpinvoicing.com/docs/adding-a-custom-item-type/)

= Plugins currently using Invoicing as their Payment system are =

* [GeoDirectory](https://wpgeodirectory.com/) - GeoDirectory uses Invoicing to manage Invoices, taxes, VAT and payments.
* [Paid Members](https://wordpress.org/plugins/members/) - Coming soon - An add-on for Members to create paid membership with custom roles and permissions.

= Premium Add-ons and Payment Gateways =

* [Stripe](https://wpinvoicing.com/downloads/stripe-payment-gateway/) - Accept credit card payments (single or recurring) direcly on your website via Stripe.
* [PayPal Pro](https://wpinvoicing.com/downloads/paypal-pro-payment-gateway/) - Accept Paypal and credit card payments (single or recurring) directly on your website.
* [2CheckOut](https://wpinvoicing.com/downloads/2checkout-payment-gateway/) - Accept payments (single or recurring) via 2CO.
* [PayFast](https://wpinvoicing.com/downloads/payfast-payment-gateway/) - Accept payments via PayFast.
* [Sage Pay](https://wpinvoicing.com/downloads/sage-pay-payment-gateway/)  - Accept payments via Sage Pay
* [Checkque Payment](https://wpinvoicing.com/downloads/category/gateways/)  - Accept payments via Cheques
* [Cash on Delivery](https://wpinvoicing.com/downloads/category/gateways/) - Accept payments via Cash on Delivery
* [AffiliateWP Integration](https://wpinvoicing.com/downloads/affiliatewp-integration/) - Integrate with the [AffiliateWP plugin](https://affiliatewp.com/)

New Payment Gateways and Add-ons will be created regularly. If there is a Payment Gateway that you need urgently or a feature missing that you think we must add, [get in touch with us](https://wpinvoicing.com/contact-form/). we will consider it.

= Support =

Get timely and friendly support for both Core Plugin and add-ons at our official website, [Invoicing Support](https://wpinvoicing.com/support)

== Installation ==

= Minimum Requirements =

* WordPress 3.1 or greater
* PHP version 5.3 or greater
* MySQL version 5.0 or greater

= Automatic installation =

Automatic installation is the easiest option. To do an automatic install of GeoDirectory, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type GeoDirectory and click Search Plugins. Once you've found our Direcotry plugin you install it by simply clicking Install Now. [Invoicing  basic installation](http://wpinvoicing.com/docs/basic-installation/)

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