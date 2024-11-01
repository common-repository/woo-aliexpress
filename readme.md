=== AliExpress for WooCommerce ===
Contributors: wecommsolutions
Tags: aliexpress, woocommerce, orders, seller
Requires at least: 5.1
Tested up to: 6.4.2
Requires PHP: 5.6
Stable tag: 1.8.0

== Description ==

Official AliExpress Plugin for WooCommerce, publish any products you have in your WooCommerce store on AliExpress.

AliExpress is an online retailer based in China, owned by the Alibaba group, one of the best online markets in the world.

Located in USA, Russia, Spain, Italy, among others. It offers stores for companies and individuals to sell their products on its own platform.

**AliExpress WooCommerce is the official plugin for WooCommerce**. This plugin allows sellers to synchronize their product catalog, does not matter if they are simple or variable products. You will be able to sell your products easily with a single click, and synchronize AliExpress categories with your WooCommerce categories.

When receiving an order through AliExpress, this plugin will download this order into WooCommerce and you will be able to manage your shipment, assigning a tracking number to the order so that it is notified to AliExpress.

You previously must have a seller account on AliExpress, [click here to create an account](https://login.aliexpress.com/join/seller/unifiedJoin.htm?_regbizsource=ES_Woo_Wecomm)


[Online Documentation](https://wecomm.es/en/documentation)


# Characteristics

* Product Synchronization.
* AliExpress Categories Synchronization.
* Attribute Synchronization.
* Characteristics Synchronization.
* Order Synchronization.


# Product Functions

* Product Title
* Description
* SKU
* Stock
* Category
* Price
* Variations
* Characteristics
* Images
* Images of Variations
* Weight
* Dimensions

# Order Functions

Synchronize all orders automatically from AliExpress in your WooCommerce, manage the shipping process directly from WooCommerce, when shipping the product, mark the order as 'Finished' and enter the tracking number so that AliExpress notifies the customer.

# Categories

AliExpress WooCommerce allows you to synchronize AliExpress categories with the local WooCommerce categories, and easily set a percentage added to the total of product when sending to AliExpress. 
You will be able to set the default measurements and weight of the entire category, avoiding modifying the required parameters product by product.

# Attributes

The attributes allow to create variations of the products, different colors, etc... This is also synchronized with the product and with the AliExpress Attributes, also allows to select an alias to rename the attributes of AliExpress.

# Characteristics

Just as attributes are used to create variations in WooCommerce, they also allow you to define the product with its characteristics. For instance the finish or the material which the product is made. This is very important because AliExpress uses this data to position the product when the customer is looking for something concrete.

## Upgrade Notice ##

### 1.4.0 ###
Warning: Orders now use the default WooCommerce statuses
To COMPLETE an AliExpress order you must enter each order, since if you complete it from the order list (bulk action) the tracking number will NOT be sent to AliExpress and therefore the order will be canceled. Previous orders will not be shown in the WooCommerce panel, they will only have to modify their status if necessary.


== Changelog ==

= 1.8.0 =
* New system of Order by AliExpress
* Minor fixes

= 1.7.13 = 
* Fix order

= 1.7.12 =
* Minor stock prices fix

= 1.7.10 =
* Minor order fixes

= 1.7.9 =
* Bank of Images
* Support to change Chunk Jobs
* Minor Fixes

= 1.7.8 =
* Add support for more plugins
* Minor Fixes

= 1.7.7 =
* Change chunk job to 200

= 1.7.6 =
* Discount setting (Advanced) Please if you use discount active this option
* Fix Shipping cost with Tax Class
* Fix stock quantity with plugins stock manager
* Other minor fixes

= 1.7.4 =
* Add order discount from AliExpress
* Address with two lines, now import correctly
* Other Minor Fixes

= 1.7.3 =
* Minor Fixes

= 1.7.2 =
* Fix Admin Emails on new order from AliExpress
* Fix UI
* Unable to map groups if there is only one
* Other minor fixes

= 1.7.1 =
* Add support to size images
* Fix combinations fix amount
* Improvement in product mapping
* Minor fixes

= 1.6.18 =
* Add more holded compatibility
* Minor fixes

= 1.6.17 =
* Fix tax shipping order
* Fix minor

= 1.6.16 =
* Holded Compatibility

= 1.6.15 =
* Fix Fixed price on Combinations
* Product Mapping show all products
* Show orphans products (Status Tab)
* Show records on tables (Status Tab)
* Translate Spanish

= 1.6.14 =
* Fix EAN-code reverse
* Force send all products in "Manage categories"
* Minor Fixes

= 1.6.12 =
* Rand Product Suggest Category
* Add image to suggest category

= 1.6.11 =
* Category Suggest
* Fix System status
* Change AliExpress Category Bulk
* Minor Fixes

= 1.6.10 =
* Fix

= 1.6.9 =
* New Images System
* Fix Title 218 limit
* Warnings Fix
* Minor fixes

= 1.6.8 =
* Fix mapping categories

= 1.6.7 =
* Active, disable or delete products from edit product admin page
* Fix link CRON JOB
* Validate EAN
* Change admin links
* Language changed when send product to AliExpress
* Change view mapping categories
* minor fixes

= 1.6.6 =
* Minor fixes

= 1.6.5 =
* Fix Order Tax
* Stock < 0 send 0 to AliExpress
* Fix error on Combination
* Optimize
* Fix EAN on variations products

= 1.6.2 =
* Fix Upload Explosion Products

= 1.6.1 =
* Close session when user is not seller
* Avanced Options
* Link to cron jobs free service
* Change errors with products
* System Status add options (Clear, Disconect, etc)
* When download product from AliExpress and product exist by sku, connect.
* Minor Fixes

= 1.6.0 =
* Taxes Support (IVA)
* Amount Fixed Support to category
* Fix Error CRON Stock and Price
* Fix Error Category set when upload product to AliExpress
* Minor fixes


= 1.5.14 =
* EAN Code Support
* Add tags by category to upload products
* Add Brand to upload products

= 1.5.12 =
* Download products from AliExpress to WooCommerce
* Set Group category products on configuration category or product detail
* Other minor fixes

= 1.5.11 =
* Add Shipping templates for category and products
* Add option to only update stock and prices

= 1.5.10 =
* Add support prices by country
* Minor fixes

= 1.5.9 =
* Fix

= 1.5.8 =
* Enable / Disable products by bulk
* Min and Max range price to upload products AliExpress
* Minor Fixes

= 1.5.7 =
* Delete products by bulk
* Using explosion with general option for products variations.
* Set preparations days for each category
* Minor fixes

= 1.5.6 =
* Store manager Rol

= 1.5.5 =
* Fix PHP Errors
* Add support category and product description (replace, before or after)

= 1.5.4 =
* Option: Use ID by SKU

= 1.5.3 =
* Fix AliExpress Description style

= 1.5.1 =
* Add support to change default status order
* Minor fixes
* Apply hook content description category

= 1.5.0 =
* Add override description products by category.
* Change Support URL

= 1.4.8 =
* Fix form save order

= 1.4.7 =
* Register carrier for orders

= 1.4.6 =
* [FOR DEVELOPERS] Sent to AliExpress tracking number

= 1.4.5 =
* Set Tracker Order Fix

= 1.4.4 =
* Add support Order Debug

= 1.4.3 =
* Minor fixes

= 1.4.2 =
* Fix Order Empty Items

= 1.4.1 =
* Fix Items Orders.
* Fix Undeclared category for explosion products.
* Others Fix

= 1.4.0 =
* Warning: Orders now use the default WooCommerce statuses
To COMPLETE an AliExpress order you must enter each order, since if you complete it from the order list (bulk action) the tracking number will NOT be sent to AliExpress and therefore the order will be canceled.
* Fix Upload Category products
* Others minor fixs

= 1.3.2 =
* Fix Order List Pagination

= 1.3.1 =
* Add Support dev operations

= 1.3.0 =
* Variation explosion support
* Image products replace with medium size
* Minor Fixes


= 1.2.12 =
* Fix upload products when correlative variations
* Fix upload products with title is more than 128 characters
* Minor fixes

= 1.2.11 =
* Added support for Shipping Cost get from AliExpress
* Autoconvert weight product to kg when upload product to AliExpress
* Minor fixes

= 1.2.10 =

* Now all products uploaded to AliExpress without category default, is set the last category of product.
* Fix - Stock: When a order is downloaded from AliExpress, stock decresases, before version 1.2.10 this happened when the order was finalized.
* Do not allow change of status from the order table, orders can´t be completed batch

== Support ==

When your support license and upgrades are active you can request support for any problem the plugin may causes or doubt you may have.
Problems or doubts external to the plugin are not included. [click here for more information](https://wecomm.es/en/documentation)
