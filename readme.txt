=== WooReviso ===
Contributors:      WooReviso
Plugin Name:       WooCommerce Reviso Integration (WooReviso)
Plugin URI:        www.onlineforce.net
Tags:              Accounting, Bookkeeping, invoicing, order sync, customer sync, product sync, Reviso Integration, woocommerce reviso integration, WooReviso, Reviso
Author URI:        www.onlineforce.net
Author:            WooReviso
Requires at least: 3.8
Tested up to:      4.4.2
Stable tag:        1.1
Version:           1.1

WooCommerce Reviso Integration (WooReviso) synchronizes your WooCommerce Orders, Customers and Products to your Reviso account.

== Description ==

WooReviso synchronizes your WooCommerce Orders, Customers and Products to your Reviso accounting system. 
Reviso invoices can be automatically created. This plugin requires the WooCommerce plugin. 
The INVOICE and PRODUCT sync features require a license purchase from http://wooreviso.com. 
WooCommerce Reviso Integration (WooReviso) plugin connects to license server hosted at http://onlineforce.net to check the validity of the license key you type in the settings page.

[vimeo http://vimeo.com/131753163]

= Data export to Reviso: =

*	CUSTOMER:
	*	Billing Company / Last Name
	*	Billing Last Name
	*	Billing First Name
	*	Email
	*	Billing Address 1
	*	Billing Address 2
	*	Billing Country
	*	Billing City
	*	Billing Postcode
	*	Shipping Address 1
	*	Shipping Address 2
	*	Shipping Country
	*	Shipping City
	*	Shipping Postcode
	*   VAT Zone
*	PRODUCT/ARTICLE:
	*	Product name
	*	ArticleNumber (SKU + product prefix)
	*	Regular Price / Sale Price
	*	Description
	*	Inventory stock quantity (updated from Reviso to WooCommerce)
*	INVOICE:
	*	Order ID (as reference)
	*	Customer number
	*	Delivery Address
	*	Delivery City
	*	Delivery Postcode
	*	Delivery Country
	*	Product Title
	*	Product quantity
	*	Product Price
	*	Shipping cost (as orderline - workaround) 
	*	Currency

= Features of WooCommerce Reviso Integration (WooReviso): =

1.	Automatic sync of all Customers from WooCommerce to Reviso invoicing service dashboard.
2.	Automatic sync of all Orders from WooCommerce to Reviso invoicing service dashboard. Sync initiated when order status is changed to 'Completed'.
3.	Automatic sync of all products from WooCommerce to Reviso invoicing service Items. This function also updates products data modified after initial sync. Supports variable products.
4.	Manual sync of all Shipping methods (excluding the additional cost for flat_shipping) from WooCommerce to Reviso invoicing service dashboard.
5.	Sync Order, Products, Customers to Reviso when Order status is changed to 'Completed' at WooCommerce->Orders Management section.
6.  Product stock quantity is imported from Reviso to WooCommerce.
7.  Sync orders created before wooreviso installation using "Activate old orders sync" option.
8.  "Activate product sync" option syncs product information from WooCommerce to Reviso. (Stock information is updated regardless of this setting)
9.	In the plugin settings you will see the option "Run scheduled product stock sync" to select the cron frequence (daily, twice daily and hourly). This cron feature will fetch the stock data from Reviso and update the stock data in woocommerce for a product.
10. Using "Product group" option, new products from the selected group are added at Reviso product group.         
11. Prefix added to the products stored from woocommerce to Reviso using "Product prefix" option.
12. New customers are added at Reviso customer group using "Customer group" option(domestic, european and overseas).
13. Multishop support. Use "Order reference prefix" to add a prefix to the order reference of an Order synced from woocommerce to Reviso.
14. Manual sync of all Products and Customers data from WooCommerce send to Reviso using "WooCommerce to Reviso". Manual sync of all Products and Customers data from Reviso saved at WooCommerce using "Reviso to WooCommerce". Choose this option before using "Manual sync contacts" and "Manual sync products" option, default will be WooCommerce to Reviso.
15. Multishop support. Support for multiple stores with different currency. Option to use base currency setting in Reviso (default setting) or use currency setup in WooCommerce.

= Supported Plugins: =

1. Product Bundles WooCommerce Extension.
2. Weight Based Shipping for WooCommerce.

== Plugin Requirement ==

*	PHP version : 5.3 or higher, tested upto 5.5.X
*	WordPress   : Wordpress 3.8 or higher

== Installation ==

[vimeo http://vimeo.com/131753163]

1.	Install WooCommerce Reviso Integration (WooReviso) either via the WordPress.org plugin directory, or by uploading the files to your server
2.	Activate the plugin in your WordPress Admin and go to the admin panel Setting -> WooCommerce Reviso Integration (WooReviso).
3.	Active the plugin with your License Key that you have received by mail and your Reviso API-USER ID.
4.	Configure your plugin as needed.
5.	That's it. You're ready to focus on sales, marketing and other cool stuff :-)

== Screenshots ==

1.	*General settings*

2.	*Manual Sync function*

3.	*Support*

4.	*Welcome Screen*

Read the FAQ or business hours mail support except weekends and holidays.

== Frequently Asked Questions ==

http://wooreviso.com/category/faq/

== Changelog ==

= 1.1 =
* Bug fixes.

= 1.0 =
* Initial Release