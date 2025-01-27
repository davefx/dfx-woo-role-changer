=== DFX Automatic Role Changer for WooCommerce ===
Contributors: DaveFX
Donate link: https://paypal.me/davefx
Tags: woocommerce, role
Requires at least: 3.1
Tested up to: 6.6
Stable tag: 20250122.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/

This plugin allows the association of a role to a WooCommerce product so the role is assigned to a registered user when the product is purchased.

== Overview ==

The DFX Automatic Role Changer for WooCommerce plugin automates the assignment of user roles based on product purchases. This powerful tool streamlines store management by ensuring users are automatically assigned the appropriate roles, enhancing membership sites, subscription-based models, and more.

== Features ==

* Automatically assign roles to users upon product purchase.

* Integrates seamlessly with WooCommerce.

* Supports role management tied to WooCommerce Subscriptions (Premium).

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

* Go to WooCommerce > Settings > Role Changer.

* Select a product from your catalog.

* Choose the role to assign when the product is purchased.

* (Optional) Configure advanced settings to fine-tune role assignments.

* Save changes.

== Premium Features ==

= WooCommerce Subscriptions Integration =

The premium version supports advanced functionality for WooCommerce Subscriptions:

* Assign roles based on subscription status:

** Active: Assigns a role when a subscription is active.

** Suspended: Updates the user role if a subscription is suspended.

** Cancelled: Removes or changes roles when a subscription ends.

= How to Enable =

* Purchase the premium version of the plugin via the official site https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/

* Upload and activate the premium plugin.

* Navigate to WooCommerce > Settings > Role Changer.

* Enable subscription-based role assignments.

== Frequently Asked Questions (FAQs) ==

= 1. What happens if a user purchases multiple products with different role assignments? =

The plugin applies the highest-priority role based on your settings. You can configure priority rules in the settings.

= 2. Can I assign multiple roles to a single product? =

Yes, you can assign multiple roles. Users will gain all assigned roles upon purchase.

= 3. How do I upgrade to the premium version? =

Visit the official plugin page to purchase the premium version. After purchasing, install and activate it like the free version.

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

Website: https://davefx.com

== Changelog ==

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


