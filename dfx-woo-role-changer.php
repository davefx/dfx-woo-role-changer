<?php

/**
 * Plugin Name: DFX Automatic Role Changer for WooCommerce
 * Description: Allows the automatic assignation of roles to users on product purchases in WooCommerce
 * Version:     20250121
 * Author:      David Marín Carreño
 * Author URI:  https://davefx.com
 * Text Domain: dfx-woo-role-changer
 * Domain Path: /lang
 *
 * WC requires at least: 3.0
 * WC tested up to: 8.4.0
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 3 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not,
 * write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package   DFX-Woo-Role-Changer
 * @version   20250121
 * @author    David Marín Carreño <davefx@davefx.com>
 * @copyright Copyright (c) 2020-2025 David Marín Carreño
 * @link      https://davefx.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if ( function_exists( 'dfx_woo_role_changer_fs' ) ) {
    dfx_woo_role_changer_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'dfx_woo_role_changer_fs' ) ) {
        // Create a helper function for easy SDK access.
        function dfx_woo_role_changer_fs() {
            global $dfx_woo_role_changer_fs;
            if ( !isset( $dfx_woo_role_changer_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $dfx_woo_role_changer_fs = fs_dynamic_init( array(
                    'id'             => '17666',
                    'slug'           => 'dfx-woo-role-changer',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_723cc0fd3221fd990e5aeda69cab4',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'first-path' => 'plugins.php',
                        'contact'    => false,
                        'support'    => false,
                    ),
                    'is_live'        => true,
                ) );
            }
            return $dfx_woo_role_changer_fs;
        }

        // Init Freemius.
        dfx_woo_role_changer_fs();
        // Signal that SDK was initiated.
        do_action( 'dfx_woo_role_changer_fs_loaded' );
    }
}
if ( !class_exists( 'DfxWooRoleChanger' ) ) {
    /**
     * Singleton class for setting up the plugin.
     *
     * @since  1.0.0
     */
    final class DfxWooRoleChanger {
        public $plugin_name = '';

        /**
         * Returns the instance.
         *
         * @return DfxWooRoleChanger
         */
        public static function get_instance() {
            static $instance = null;
            if ( is_null( $instance ) ) {
                $instance = new self();
            }
            return $instance;
        }

        /**
         * Constructor method.
         *
         * @return void
         * @since  1.0.0
         */
        private function __construct() {
            // Set plugin file, removing the need to use __FILE__
            if ( strpos( __FILE__, WP_PLUGIN_DIR ) !== false ) {
                $this->plugin_name = str_replace( WP_PLUGIN_DIR . '/', '', wp_normalize_path( realpath( __FILE__ ) ) );
            } else {
                $this->plugin_name = $this->get_plugin_path_with_symlinks();
            }
            $this->registerHooks();
        }

        function get_plugin_path_with_symlinks() {
            // Resolve the real path of the current file (__FILE__)
            $current_file_realpath = wp_normalize_path( realpath( __FILE__ ) );
            // Get the list of all plugins
            $all_plugins = get_plugins();
            foreach ( $all_plugins as $plugin_file => $plugin_data ) {
                // Get the path of the plugin's main file
                $plugin_main_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                // Resolve the real path of the plugin's main file
                $plugin_main_file_realpath = wp_normalize_path( realpath( $plugin_main_file ) );
                // Compare the real paths of the current file and the plugin directory
                if ( strpos( $current_file_realpath, wp_normalize_path( dirname( $plugin_main_file_realpath ) ) ) === 0 ) {
                    return $plugin_file;
                    // Return the plugin name
                }
            }
            return null;
        }

        /**
         * Register hooks
         *
         * @return void
         * @since 1.0.0
         */
        private function registerHooks() {
            add_action( 'plugins_loaded', [$this, 'load_i18n'] );
            $grant_moments = $this->get_current_grant_moment();
            if ( in_array( 'on_payment', $grant_moments, true ) ) {
                add_action(
                    'woocommerce_payment_complete',
                    [$this, 'role_assignment'],
                    0,
                    1
                );
                add_filter(
                    'woocommerce_order_refunded',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
            }
            if ( in_array( 'on_processing', $grant_moments, true ) ) {
                add_action(
                    'woocommerce_order_status_processing',
                    [$this, 'role_assignment'],
                    0,
                    1
                );
                add_action(
                    'woocommerce_order_status_processing_to_refunded',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
                add_action(
                    'woocommerce_order_status_processing_to_cancelled',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
                add_action(
                    'woocommerce_order_status_processing_to_on-hold',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
            }
            if ( in_array( 'on_completed', $grant_moments, true ) ) {
                add_action(
                    'woocommerce_order_status_completed',
                    [$this, 'role_assignment'],
                    0,
                    1
                );
            }
            if ( in_array( 'on_completed', $grant_moments, true ) || in_array( 'on_processing', $grant_moments, true ) ) {
                add_action(
                    'woocommerce_order_status_completed_to_refunded',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
                add_action(
                    'woocommerce_order_status_completed_to_cancelled',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
                add_action(
                    'woocommerce_order_status_completed_to_on-hold',
                    [$this, 'role_unassignment'],
                    0,
                    2
                );
            }
            // If it's a call to the backend and not an ajax call
            if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
                add_action( 'woocommerce_product_options_general_product_data', [$this, 'add_role_assignment_option'] );
                add_action(
                    'woocommerce_process_product_meta',
                    [$this, 'save_role_assignment_option'],
                    10,
                    2
                );
                // Show notice in plugins.php
                add_filter( 'plugin_action_links_' . $this->plugin_name, [$this, 'add_settings_link'] );
                add_action( 'woocommerce_settings_tabs_products', [$this, 'render_settings_tab'] );
                add_action( 'woocommerce_update_options_products', [$this, 'update_settings'] );
            }
            add_action( 'before_woocommerce_init', function () {
                if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
                }
            } );
        }

        public function load_i18n() {
            $plugin_rel_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            load_plugin_textdomain( 'dfx-woo-role-changer', false, $plugin_rel_path );
        }

        public function add_role_assignment_option() {
            $options = wp_roles()->get_names();
            $options = array_merge( [
                'none' => __( 'None', 'dfx-woo-role-changer' ),
            ], $options );
            echo '<div class="options_group">';
            $description = __( 'Role to be assigned to people purchasing this product', 'dfx-woo-role-changer' );
            // If WooCommerce Subscriptions is installed, and this is a subscription product promote the Pro version
            if ( class_exists( 'WC_Subscriptions' ) && WC_Subscriptions_Product::is_subscription( get_the_ID() ) ) {
                if ( dfx_woo_role_changer_fs()->is_not_paying() ) {
                    $description .= __( ' The premium version of the plugin supports assigning/deassigning roles based on product subscription status. ', 'dfx-woo-role-changer' );
                    $description .= sprintf( __( '<a href="%s">Upgrade to Pro</a> to get this feature.', 'dfx-woo-role-changer' ), dfx_woo_role_changer_fs()->checkout_url() );
                }
            }
            woocommerce_wp_select( array(
                'id'          => 'dfxwcrc_role_assignment',
                'value'       => get_post_meta( get_the_ID(), '_dfxwcrc_role_assignment', true ) ?? 'none',
                'label'       => 'Assigned Role',
                'desc_tip'    => true,
                'description' => __( 'Role to be assigned to people purchasing this product', 'dfx-woo-role-changer' ),
                'options'     => $options,
            ) );
            echo '</div>';
        }

        public function save_role_assignment_option( $id, $post ) {
            if ( !isset( $_POST['dfxwcrc_role_assignment'] ) ) {
                return;
            }
            $available_roles = wp_roles()->get_names();
            $assignment = sanitize_text_field( $_POST['dfxwcrc_role_assignment'] );
            if ( $assignment !== 'none' ) {
                if ( array_key_exists( $assignment, $available_roles ) ) {
                    update_post_meta( $id, '_dfxwcrc_role_assignment', $assignment );
                }
            } else {
                delete_post_meta( $id, '_dfxwcrc_role_assignment' );
            }
        }

        public function role_assignment( $order_id ) {
            $roles = [];
            $order = wc_get_order( $order_id );
            $product_items = $order->get_items();
            $user_id = $order->get_user_id();
            if ( !$user_id ) {
                return;
            }
            $user = new WP_User($user_id);
            foreach ( $product_items as $product_item ) {
                $role = get_post_meta( $product_item->get_product_id(), '_dfxwcrc_role_assignment', true );
                if ( empty( $role ) || empty( trim( $role ) ) ) {
                    continue;
                }
                $roles_for_product = explode( ',', $role );
                foreach ( $roles_for_product as $role_for_product ) {
                    $role_for_product = trim( $role_for_product );
                    if ( $role_for_product !== 'none' && !empty( $role_for_product ) && !in_array( $role_for_product, $roles ) ) {
                        $roles[] = $role_for_product;
                    }
                }
            }
            if ( count( $roles ) == 0 ) {
                return;
            }
            foreach ( $roles as $new_role ) {
                //Apply appropriate role
                if ( $new_role && $new_role !== 'none' ) {
                    $this->maybe_add_role_to_user( $user, $new_role, $order );
                }
            }
        }

        public function role_unassignment( $order_id ) {
            $order = wc_get_order( $order_id );
            $product_items = $order->get_items();
            $user_id = $order->get_user_id();
            if ( !$user_id ) {
                return;
            }
            $user = new WP_User($user_id);
            foreach ( $product_items as $product_item ) {
                $role = get_post_meta( $product_item->get_product_id(), '_dfxwcrc_role_assignment', true );
                if ( empty( $role ) || empty( trim( $role ) ) ) {
                    continue;
                }
                $roles_for_product = explode( ',', $role );
                foreach ( $roles_for_product as $role_for_product ) {
                    $role_for_product = trim( $role_for_product );
                    if ( $role_for_product !== 'none' && !empty( $role_for_product ) ) {
                        $this->maybe_remove_role_from_user( $user, $role_for_product, $order );
                    }
                }
            }
        }

        /**
         * @param WP_User $user
         * @param string $new_role
         * @param WC_Order | null $order
         * @param string $note
         *
         * @return void
         */
        public function maybe_add_role_to_user(
            $user,
            $new_role,
            $order = null,
            $note = ''
        ) {
            if ( !in_array( $new_role, $user->roles ) ) {
                if ( $this->get_current_mode() === 'replace_roles' ) {
                    update_user_meta( $user->ID, 'dfxwcrc_old_roles', $user->roles );
                    $user->set_role( $new_role );
                } else {
                    $user->add_role( $new_role );
                }
                if ( $order ) {
                    if ( !empty( $note ) ) {
                        $note .= ' ';
                    }
                    $note .= sprintf( 
                        /* translators: 1: role name, 2: user login */
                        __( 'Added role "%1$s" to user "%2$s"', 'dfx-woo-role-changer' ),
                        $new_role,
                        $user->user_login
                     );
                    $order->add_order_note( $note );
                }
            }
        }

        /**
         * @param WP_User $user
         * @param string $new_role
         * @param WC_Order | null $order
         *
         * @return void
         */
        public function maybe_remove_role_from_user(
            $user,
            $role,
            $order = null,
            $note = ''
        ) {
            if ( in_array( $role, $user->roles ) ) {
                if ( $this->get_current_mode() === 'replace_roles' ) {
                    $old_roles = get_user_meta( $user->ID, 'dfxwcrc_old_roles', true );
                    if ( $old_roles ) {
                        $user->set_role( '' );
                        foreach ( $old_roles as $old_role ) {
                            $user->add_role( $old_role );
                        }
                        delete_user_meta( $user->ID, 'dfxwcrc_old_roles' );
                    }
                } else {
                    $user->remove_role( $role );
                }
                if ( $order ) {
                    if ( !empty( $note ) ) {
                        $note .= ' ';
                    }
                    $note .= sprintf( 
                        /* translators: 1: role name, 2: user login */
                        __( 'Removed role "%1$s" from user "%2$s".', 'dfx-woo-role-changer' ),
                        $role,
                        $user->user_login
                     );
                    $order->add_order_note( $note );
                }
            }
        }

        public function add_settings_tab( $settings_tabs ) {
            $settings_tabs['dfx_role_changer'] = 'DFX Role Changer';
            return $settings_tabs;
        }

        public function add_settings_link( $links ) {
            $settings_link = '<a href="admin.php?page=wc-settings&tab=products#dfx_woo_role_changer_settings">' . __( 'Settings', 'dfx-woo-role-changer' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }

        public function render_settings_tab() {
            // Show only if the current section is the general default section
            if ( isset( $_GET['section'] ) && $_GET['section'] !== '' ) {
                return;
            }
            echo '<a id="dfx_woo_role_changer_settings"></a>';
            woocommerce_admin_fields( $this->get_settings() );
        }

        public function update_settings() {
            woocommerce_update_options( $this->get_settings() );
        }

        private function get_current_mode() {
            $mode = get_option( 'dfx_woo_role_changer_mode_selection', 'add_remove_roles' );
            return $mode;
        }

        private function get_current_grant_moment() {
            $mode = get_option( 'dfx_woo_role_changer_grant_moment', ['on_payment'] );
            return $mode;
        }

        private function get_settings() {
            $settings = [];
            $settings['dfx_woo_role_changer_title'] = [
                'name' => __( 'DFX Role Changer Settings', 'dfx-woo-role-changer' ),
                'type' => 'title',
                'desc' => '',
                'id'   => 'dfx_woo_role_changer_settings',
            ];
            $settings['dfx_woo_role_changer_section_start'] = [
                'type' => 'sectionstart',
                'id'   => 'dfx_woo_role_changer_section_start',
            ];
            $settings['dfx_woo_role_changer_mode_selection'] = [
                'name'    => __( 'Role assignment mode', 'dfx-woo-role-changer' ),
                'type'    => 'select',
                'default' => 'add_remove_roles',
                'options' => [
                    'add_remove_roles' => __( 'Add/Remove Roles', 'dfx-woo-role-changer' ),
                    'replace_roles'    => __( 'Replace Roles', 'dfx-woo-role-changer' ),
                ],
                'desc'    => __( 'Choose the mode of role assignment. If you are using a plugin that allows managing multiple roles for a given user, you can choose to add or remove roles. If not, you should choose to replace the role.', 'dfx-woo-role-changer' ),
                'id'      => 'dfx_woo_role_changer_mode_selection',
            ];
            $settings['dfx_woo_role_changer_grant_moment'] = [
                'name'    => __( 'When to grant roles', 'dfx-woo-role-changer' ),
                'type'    => 'multiselect',
                'default' => ['on_payment'],
                'options' => [
                    'on_payment'    => __( 'On order payment', 'dfx-woo-role-changer' ),
                    'on_processing' => __( 'When order gets to Processing state', 'dfx-woo-role-changer' ),
                    'on_completed'  => __( 'When order gets to Completed state', 'dfx-woo-role-changer' ),
                ],
                'class'   => 'wc-enhanced-select',
                'desc'    => __( 'When in the order lifecycle the role should be granted. If you choose more than one option, the role will be granted at all the selected moments.', 'dfx-woo-role-changer' ),
                'id'      => 'dfx_woo_role_changer_grant_moment',
            ];
            // Message for non-premium users
            if ( !dfx_woo_role_changer_fs()->is_not_paying() ) {
                // Adds a direct checkout link in the free version.
                $settings['dfx_woo_role_changer_premium_message'] = [
                    'name'     => __( 'Unlock Premium Features', 'dfx-woo-role-changer' ),
                    'type'     => 'title',
                    'desc_tip' => false,
                    'desc'     => sprintf( __( '<a href="%s"><small>Unlock Pro</small></a>' ), dfx_woo_role_changer_fs()->checkout_url() ),
                    'id'       => 'dfx_woo_role_changer_premium_message',
                ];
            }
            $settings['dfx_woo_sole_changer_section_end'] = array(
                'type' => 'sectionend',
                'id'   => 'dfx_woo_role_changer_section_end',
            );
            return apply_filters( 'dfx_woo_role_changer_settings', $settings );
        }

    }

}
/**
 * Gets the instance of the `DfxFeedbackPlugin` class.  This function is useful for quickly grabbing data
 * used throughout the plugin.
 *
 * @since  1.0.0
 * @return object
 */
function dfx_woo_role_changer_plugin() {
    return DfxWooRoleChanger::get_instance();
}

// Let's roll!
dfx_woo_role_changer_plugin();