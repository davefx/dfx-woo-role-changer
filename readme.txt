=== DFX Automatic Role Changer for WooCommerce ===
Contributors: DaveFX
Donate link: https://paypal.me/davefx
Tags: woocommerce, role
Requires at least: 3.1
Tested up to: 6.6
Stable tag: 20250325
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/

This plugin allows the association of a role to a WooCommerce product so the role is assigned to a registered user when the product is purchased.

== Overview ==

The DFX Automatic Role Changer for WooCommerce plugin automates the assignment of user roles based on product purchases. This powerful tool streamlines store management by ensuring users are automatically assigned the appropriate roles, enhancing membership sites, subscription-based models, and more.

== Features ==

* Automatically assign roles to users upon product purchase.

* Integrates seamlessly with WooCommerce.

* *Premium*: Supports the definition of different roles for variations in variable products.

* *Premium*: Allows defining, per product, a role validity period in days after the purchase, so the role granted in the purchase will be automatically removed after that period.

* *Premium*: Supports role management tied to subscription products, supporting [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/),
  [YITH WooCommerce Subscription](https://wordpress.org/plugins/yith-woocommerce-subscription/) and
  [WP Swings Subscriptions for WooCommerce](https://wordpress.org/plugins/subscriptions-for-woocommerce/).

* *Premium*: Allows defining multiple roles per product.

== Installation ==

= Prerequisites =

* WordPress 5.0 or higher.

* WooCommerce 3.0 or higher (tested up to version 8.4.0).

= Steps =

* Download the plugin from the WordPress Plugin Directory.

* Log in to your WordPress admin dashboard.

* Navigate to Plugins > Add New.

* Click Upload Plugin, then select the plugin file.

* Click Install Now, then Activate Plugin.

== Configuration ==

= Setting Up Role Assignments =

* Edit a product from your catalog.

* Choose the role (or several roles *PREMIUM*) to assign when the product is purchased.

* *Premium*: Define a role validity period in days after the purchase.

* In WooCommerce > Settings > Role Changer you can configure advanced settings to fine-tune role assignments, such as defining if the new role must be added to the user,
  or if the new role should just replace the previous one.

* Save changes.

== Premium Features ==

= Define Role Validity Periods =

The premium version allows defining a role validity period in days after the purchase. The role granted in the purchase will be automatically removed after that period.

= Manages roles following the lifecycle of subscription products =

The premium version supports advanced functionality for subscription products,
supporting [WooCommerce Subscriptions](https://woocommerce.com/subscriptions/),
[YITH WooCommerce Subscription](https://wordpress.org/plugins/yith-woocommerce-subscription/) and
[WP Swings Subscriptions for WooCommerce](https://wordpress.org/plugins/subscriptions-for-woocommerce/),
assigning roles based on subscription status:

* Active: Assigns a role when a subscription is activated.

* Suspended: Updates the user role if a subscription is suspended, or is waiting for renewal.

* Cancelled: Removes or changes roles when a subscription ends.

= How to Enable =

* Purchase the premium version of the plugin via the [official site](https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/)

* Upload and activate the premium plugin.

* Navigate to WooCommerce > Settings > Role Changer.

* Enable subscription-based role assignments.

== Frequently Asked Questions (FAQs) ==

= 1. What happens if a user purchases multiple products with different role assignments? =

The plugin can be configured to add roles, or to replace roles.

* If the plugin is configured to replace roles, the user will remain with the last-assigned role.
* If the plugin is configured to add roles, all the roles will be assigned to the user. We recommend using a plugin like "Members" to manage multiple roles per user.

= 2. What happens if a user purchases a product granting a role several times? =

In the Premium version, the validity period for a purchase is added to the previously existing one.

= 3. Can I assign multiple roles to a single product? =

In the Premium version you can assign multiple roles. Users will gain all assigned roles upon purchase.

= 4. How do I upgrade to the premium version? =

Visit the [official plugin page](https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/) to purchase the premium version. After purchasing, install and activate it like the free version.

== Troubleshooting ==

= Common Issues =

*Issue: Role changes are not applied after purchase.*

Solution: Ensure the plugin is activated and configured correctly under WooCommerce > Settings > Role Changer.

*Issue: Subscription-based role changes are not working.*

Solution: Verify that the premium version is installed and active.

*Issue: Conflicts with other plugins.*

Solution: Disable other role management plugins to check for compatibility issues.

== Support ==

For support, visit the plugin support forum.

== License ==

This plugin is distributed under the GNU General Public License v3. For details, refer to the license file included with the plugin.

== Credits ==

Author: David Marín Carreño

Website: [https://davefx.com/en/wordpress-plugins](https://davefx.com/en/wordpress-plugins)

== Changelog ==

= 20250325 =

* *Premium*: Added support for definition of roles in variations of variable products.

* *Premium*: In case of having one order with several products granting the same role, we can now select how to calculate the validity period (if defined).

= 20250204 =

* The role selector is now rendered as a Select2 dropdown, allowing an easier selection of roles.

* *Premium*: Added support for roles with a validity period. The role granted in the purchase will be automatically removed after that period.

* *Premium*: Added support for multiple roles per product.

= 20250203 =

* *Premium*: Added support for manage roles for [YITH Subscription for WooCommerce](https://wordpress.org/plugins/yith-woocommerce-subscription/) and [WP Swings Subscriptions for WooCommerce](https://wordpress.org/plugins/subscriptions-for-woocommerce/).

= 20250130 =

* If the plugin is configured to replace roles (not adding them), now we won't ever replace the administrator role after
  a purchase if the user had this role before the purchase.

= 20250127 =

* Upgrading Freemius SDK

= 20250122 = 

* Changed Freemius installation path.

= 20250121 =

* *Premium Feature* Added new premium features to allow the role to be assigned/deassigned according a WooCommerce subscription lifecycle.
* Added messages to promote the premium features.

= 20240616 =

* Added new settings page into WooCommerce Products admin page.
* Adding new feature to select when the role must be assigned to the user.
* Adding new mode selection, to determine if the new role must be added to the user or if the new role just should replace any previous role.
* Added code to be executed if the order gets cancelled or refunded, to remove the role from the user.

= 20240319 =

* Marking compatibility with HPOS.

= 20201115 =

* Initial release


