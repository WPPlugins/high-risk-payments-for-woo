=== High Risk Payment Gateway for WooCommerce ===
Contributors: cardpaysolutions
Tags: woocommerce, payment gateway, high risk, woocommerce subscription payment, woocommerc pre order payment, adult products, e-cigarettes, firearms, tech support
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 2.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Cardpay Solutions plugin allows merchants that fall into high risk categories to securely accept credit cards through their WooCommerce store.

== Description ==

The High Risk Payment Gateway for WooCommerce plugin adds Cardpay Solutions as a payment method to your WooCommerce store. Cardpay Solutions makes accepting 
credit cards simple. Accept all major credit cards including Visa, MasterCard, American Express, Discover, JCB, and Diners Club. The plugin allows your logged 
in customers to securely store and re-use credit card profiles to speed up the checkout process. We also support all Subscription and Pre-Order features. 
We accept merchants that fall into the following high risk categories:

* Adult Websites/Products
* e-Cigarettes & Vaporizers
* Paraphernalia
* Supplements & Nutraceuticals
* Diet Programs
* PC Technical Support
* Tobacco Products
* Multi-Level Marketing
* Travel Agencies
* Furniture Stores
* Firearm Sales
* and many more...

= Features =

* Supports both "Authorize Only" and "Authorize & Capture" transaction types
* Optional automatic capture of "Authorize Only" transactions when order status is changed to "Completed"
* Supports WooCommerce 2.2+ automatic refunds
* Supports WooCommerce 2.6+ Tokenization features
* Customers can save credit card information to use for future orders
* Supports all WooCommerce Subscriptions 2.x features
* Supports WooCommerce Pre-Orders
* Uses the WooCommerce built in checkout so the customer never leaves your website
* AVS and CVC responses are shown on Order Detail page to assist with fraud prevention

= Requirements =

A Cardpay Solutions Gateway and Merchant Account is required. Your Cardpay Solutions merchant account must have Customer Vault
enabled for the stored credit card, subscription, and pre-orders features to work.

You can apply for a Cardpay Solutions merchant account through the link below. There are not set-up fees and we
offer several flexible pricing plans..

[Click Here to Sign Up!](http://www.cardpaymerchant.com/woocommerce?pid=317d5f0aa67f1638)

== Installation ==

= Minimum Requirements =

* WordPress 3.8 or greater
* WooCommerce 2.2 or greater
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install,
follow these directions:

1. Log in to your WordPress dashboard
1. Navigate to the Plugins menu and click Add New
1. Search for "High Risk Payment Gateway for WooCommerce" and click "Install Now"
1. Activate "High Risk Payment Gateway for WooCommerce" from the Plugins page
1. Complete the configuration by navigating to WooCommmerce -> Settings -> Checkout -> Cardpay Solutions

= Manual installation =

1. Download and unzip the High Risk Payment Gateway for WooCommerce plugin
1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate "High Risk Payment Gateway for WooCommerce" from the Plugins page
1. Complete the configuration by navigating to WooCommmerce -> Settings -> Checkout -> Cardpay Solutions

== Frequently Asked Questions ==

= How do I obtain a Cardpay Solutions gateway and merchant account? =

[Click Here](http://www.cardpaymerchant.com/woocommerce?pid=317d5f0aa67f1638) to register for a low cost account.

= How do I get my Username and Password? =

Call sales support at (866) 588-0503 and we can assist you.

= How do I test the plugin before going live? =

The plugin has a built-in test mode. Navigate to the configuration page at WooCommerce -> Settings -> Checkout -> Cardpay Solutions
and check the "Use Sandbox" box and then click the "Save Changes" button. The Username and Password fields are
not required in sandbox mode and can be left blank.

The following test cards can be used in Sandbox Mode with any future expiration date:

* Visa 4111111111111111
* MasterCard 5431111111111111
* American Express 341111111111111
* Discover 6011601160116611

= Can I use the plugin without the stored credit card features? =

Yes. Navigate to the configuration page at WooCommerce -> Settings -> Checkout -> Cardpay Solutions and uncheck the "Allow Stored Cards"
box and save your changes. Customers will then not see the option to save cards for future use in the checkout and will not see any stored credit
card information on the My Account page.

= What is the difference between the "Authorize Only" and "Authorize & Capture" transaction types? =

The Authorize Only transaction type reserves the amount of the transaction on the customer's credit card but does not start the process of
transferring the funds to your bank account until a separate "Capture" request is sent to the gateway. The capture request can be 
automatically sent when the order status is changed to "Completed" by enabling the "Auto Capture" feature in the configuration or by
logging into your Cardpay Solutions gateway account and manually requesting the capture from there.

The Authorize & Capture transaction type authorizes the transaction and then automatically captures it at your designated batch cut-off
time each day. This starts the process of moving the funds to your bank account.

== Screenshots ==

1. Settings
2. Checkout
3. Customer credit card management
4. Admin order management

== Changelog ==

= 2.0.0 =

* Added support for WC 2.6+ Tokenization
* Updated for WC 3.0+ CRUD
* Other minor bug fixes

= 1.0.1 =

* Fixed formatting of saved card dropdown on payment form
* Tested compatibility with Wordpress 4.7
* Tested compatibility with WooCommerce 3.0.7

= 1.0.0 =

* Initial release

== Upgrade Notice ==

= 2.0.0 =
Increases compatibility with WooCommerce 3.0
