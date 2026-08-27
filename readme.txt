=== Membership & User Roles for WooCommerce (Automatic Role Changer) ===
Contributors: DaveFX
Donate link: https://paypal.me/davefx
Tags: membership, user roles, subscriptions, access control, restrict content
Requires at least: 6.2
Requires PHP: 8.0
Tested up to: 7.1
Stable tag: 20260827
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://davefx.com/en/dfx-automatic-role-changer-for-woocommerce/

Sync user roles with memberships and subscriptions. Grant access on purchase, revoke on expiry, and restrict your store by role.

== Overview ==

The Membership & User Roles for WooCommerce plugin automates the assignment of user roles based on product purchases. This powerful tool streamlines store management by ensuring users are automatically assigned the appropriate roles, enhancing membership sites, subscription-based models, and more.

It also bridges [MemberPress](https://memberpress.com/) to WordPress roles: a membership's state drives the role, continuously, so access granted in MemberPress is reflected wherever your site checks roles.

== Features ==

* Automatically assign roles to users upon product purchase.

* Integrates seamlessly with WooCommerce.

* Syncs [MemberPress](https://memberpress.com/) membership state to WordPress roles, continuously — the direction other integrations don't cover. The free version syncs one membership level.

* *Premium*: Supports the definition of different roles for variations in variable products.

* *Premium*: Allows defining, per product, a role validity period in days after the purchase, so the role granted in the purchase will be automatically removed after that period.

* *Premium*: Supports role management tied to subscription products, supporting [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/), [YITH WooCommerce Subscription](https://wordpress.org/plugins/yith-woocommerce-subscription/) and [WP Swings Subscriptions for WooCommerce](https://wordpress.org/plugins/subscriptions-for-woocommerce/).

* *Premium*: Allows defining multiple roles per product.

* *Premium*: Unlimited MemberPress levels, several roles per membership, and mapping of every membership state (pending, active, suspended, cancelled, expired).

* *Premium*: Restricts your store by role — hides products and categories from the catalog and search, denies direct URL access, blocks add-to-cart, and sets per-role prices, payment gateways and shipping methods.

* *Premium*: Retroactive sync — a batched admin tool that aligns the roles of members who already existed before the mapping was configured. Safe to re-run.

== Installation ==

= Prerequisites =

* WordPress 6.2 or higher.

* WooCommerce 8.0 or higher (tested up to version 11.0).

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

* In WooCommerce > Settings > Role Changer you can configure advanced settings to fine-tune role assignments, such as defining if the new role must be added to the user, or if the new role should just replace the previous one.

* Save changes.

== Premium Features ==

= Define Role Validity Periods =

The premium version allows defining a role validity period in days after the purchase. The role granted in the purchase will be automatically removed after that period.

= Manages roles following the lifecycle of subscription products =

The premium version supports advanced functionality for subscription products, supporting [WooCommerce Subscriptions](https://woocommerce.com/subscriptions/), [YITH WooCommerce Subscription](https://wordpress.org/plugins/yith-woocommerce-subscription/) and [WP Swings Subscriptions for WooCommerce](https://wordpress.org/plugins/subscriptions-for-woocommerce/), assigning roles based on subscription status:

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

= 20260827 =

* *Premium*: restricting a product category now hides the category itself, not only the products in it. Until now a restricted category still appeared in category widgets, menus and any other listing, and its archive URL still opened — to a page with no products, but a real page carrying the category name. Restricted categories are now excluded from category listings and their archive is denied the same way a restricted product URL is, honouring the same 404-or-redirect setting.

* This makes a "members-only catalogue" work as expected: give the category a role, and everyone else stops seeing both the products and the category.

= 20260826 =

**Behaviour change in "Replace Roles" mode. Read this if you use that mode.**

*What changed:* until now, granting a role in Replace Roles mode wiped every role the user had. From this version it replaces only the roles this plugin granted, plus the role the user had when the plugin first acted on them. Any role a *different* plugin gave the user is kept.

*Why:* wiping everything destroyed roles other plugins were actively managing — WooCommerce Subscriptions' subscriber role, Members, MemberPress, anything that assigns roles. The other plugin restored its role on its next event, this one wiped it again on the next grant, and the two fought indefinitely. The user's role ended up depending on which plugin ran last.

*What you will notice:* if this is the only plugin managing roles on your site, nothing changes at all. If another one is, its roles now survive instead of disappearing.

*If you actually want a single role and nothing else*, a site can restore the old behaviour with the new `dfx_woo_role_changer_foreign_roles` filter, returning an empty array.

No provider is detected or named: a role counts as foreign simply because this plugin never granted it.

= 20260824 =

* *Premium*: the retroactive sync now covers subscriptions, not just MemberPress. If you configure a role on a subscription product today, your existing subscribers get it on the next run instead of waiting for their subscription to change status, which on an annual plan can be months away. Works with WooCommerce Subscriptions, YITH WooCommerce Subscription and Subscriptions for WooCommerce.

* *Premium*: the retroactive sync screen no longer requires MemberPress. It lists whichever sources are present and syncs from those.

= 20260822 =

* No functional changes. Tested with WooCommerce 11, so WooCommerce no longer shows its "untested with your version" notice.

* The test suite now runs against WooCommerce 11 as well, across the four supported PHP versions, so the declaration stays backed by CI rather than by a single run.

= 20260817 =

* The settings screen now describes what the premium version adds, including the store restriction by role and the retroactive sync. Those two live in premium-only files, so nothing about them was visible from the free version's admin.

* Spanish (es_ES and es_CL) translations updated for the new strings; both remain complete.

= 20260813 =

* No functional changes. This release only updates the compatibility declarations.

* Tested with WordPress 7.1.

* Raised the declared minimum WordPress version from 5.0 to 6.2. This is not a new restriction: the plugin already required WooCommerce 8.0, and WooCommerce 8.0 itself requires WordPress 6.2, so no install below 6.2 could ever have run it. The old value simply advertised a combination that does not exist.

* Added the `Requires Plugins: woocommerce` header, so WordPress 6.5 and later refuses activation when WooCommerce is missing instead of activating and failing later.

* Presentation fixes in this readme: paragraphs that were wrapped across several lines rendered as broken lines on the plugin page, MemberPress is now linked on first mention, and the MemberPress sync is listed with the free features rather than among the premium ones.

= 20260728 =

* Renamed the plugin to "Membership & User Roles for WooCommerce (Automatic Role Changer)". The slug, text domain and settings are unchanged, so nothing needs reconfiguring on update.

* New: syncs MemberPress membership state to WordPress roles, continuously. This is the direction the existing WooCommerce/MemberPress integrations don't cover. The free version syncs a single membership level while the membership is active.

* *Premium*: unlimited MemberPress levels, several roles per membership, and a mapping for every membership state (pending, active, suspended, cancelled, expired). Roles are granted when a membership enters a state and revoked when it leaves it. A role the user earned through an order is never revoked by the bridge.

* *Premium*: restrict your store by role. Restricted products and categories are hidden from the catalog and from search, their URLs are denied (404 or a redirect to the shop, configurable), add-to-cart is blocked, and prices, payment gateways and shipping methods can each be limited per role. Users who can manage WooCommerce are never restricted.

* *Premium*: retroactive sync. An admin tool under WooCommerce that aligns the roles of members who already existed before the mapping was configured. It runs in batches with a progress bar, resumes where it left off if the page is reloaded, and is safe to run more than once.

* Added Spanish (es_ES and es_CL) translations, complete for every string.

* Fixed a translatable string that carried no text domain, so its translation was never applied.

* Numbered the placeholders in three order-note strings that had unnumbered ones, so translators can reorder them.

* Added an end-to-end test suite driven against a real WordPress with MemberPress installed, alongside the existing unit tests.

= 20260522.3 =

* Widened the supported WooCommerce range: now works on WC 8.0+ (previously implicitly required WC 9.5+). Inlined the `Automattic\WooCommerce\Enums\OrderStatus` constants to plain strings and gated `Automattic\WooCommerce\Utilities\OrderUtil` with `class_exists`, so older WooCommerce installs no longer fatal-error.

* CI now also runs the test suite against WooCommerce 8.0, 9.5, and 10.7 in addition to the four PHP versions.

= 20260522.2 =

* Bumped the declared WooCommerce requirement to match the code: now requires WC 9.4+ (was `3.0`, but the plugin uses the `Automattic\WooCommerce\Enums\OrderStatus` enum which only exists in WC 9.x+). Tested up to WC 10.7.

* *Premium*: Guarded the role-assignment `$_POST` read with `isset()` so saving a product without the role field no longer throws a PHP 8 warning.

* *Premium*: Removed unreachable code in `extract_validity_from_role` (multi-grant aggregation actually happens upstream in `add_expiration_to_roles`).

* Removed the unused `add_settings_tab()` method.

* CI now runs the test suite across PHP 8.0, 8.1, 8.2, and 8.3.

= 20260522.1 =

* *Premium*: Fixed the "Multiple expiration mode" setting that was silently non-functional in the admin UI (the setting array was missing the `id` field, so WooCommerce never persisted it). The min/max option labels were also swapped — they now match the underlying behavior.

* Fixed a race in *replace_roles* mode when a single user has two or more active role-granting orders. The plugin now tracks managed roles separately so subsequent grants don't overwrite the user's original roles, and refunds restore the right state regardless of the order in which they happen.

* Declared a minimum PHP version (8.0) in the plugin header.

* Fixed the `Domain Path` plugin header (was `/lang`, the directory is `/languages`).

* *Premium*: User display names and URLs are now HTML-escaped in order notes.

* Declared previously-dynamic properties on the premium class (avoids the PHP 8.2 deprecation notice).

= 20260522 =

* Fixed a deprecated Action Scheduler API call (`next()` → `get_date()`).

* Defensive iteration over the user subscription list returned by YITH WooCommerce Subscription, preventing rare fatal errors on role unassignment.

* Null-order guard in the internal `dfx_woo_role_changer_role_maybe_assigned` action so subscription paths no longer fatal when the associated order isn't available.

* *Premium*: Fixed the Subscriptions For WooCommerce (WP Swings) integration under HPOS. Subscription status changes weren't triggering role assignments because the plugin was listening on hooks that don't fire under HPOS; switched to `woocommerce_new_order` / `woocommerce_update_order`.

* Added a PHPUnit integration test suite covering the free and premium paths, including all three supported subscription providers.

= 20260521 =

* Upgraded Freemius SDK from 2.11.0 to 2.13.1.

* Marked plugin as tested with WordPress 7.0.

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

* If the plugin is configured to replace roles (not adding them), now we won't ever replace the administrator role after a purchase if the user had this role before the purchase.

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


