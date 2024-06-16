<?php
/**
 * Plugin Name: DFX Automatic Role Changer for WooCommerce
 * Description: Allows the automatic assignation of roles to users on product purchases in WooCommerce
 * Version:     20240616
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
 * @version   20240616
 * @author    David Marín Carreño <davefx@davefx.com>
 * @copyright Copyright (c) 2020-2024 David Marín Carreño
 * @link      https://davefx.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  1.0.0
 */
final class DfxWooRoleChanger {


	/**
	 * Returns the instance.
	 *
	 * @return DfxWooRoleChanger
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
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

		$this->registerHooks();

	}


	/**
	 * Register hooks
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function registerHooks() {
		add_action( 'plugins_loaded', [ $this, 'load_i18n' ] );

		$grant_moments = $this->get_current_grant_moment();

		if ( in_array( 'on_payment', $grant_moments, true ) ) {
			add_action( 'woocommerce_payment_complete', [ $this, 'role_assignment' ], 0, 1 );
			add_filter( 'woocommerce_order_refunded', [ $this, 'role_unassignment' ], 0, 2 );
		}

		if ( in_array( 'on_processing', $grant_moments, true  ) ) {
			add_action( 'woocommerce_order_status_processing', [ $this, 'role_assignment' ], 0, 1 );
			add_action( 'woocommerce_order_status_processing_to_refunded', [ $this, 'role_unassignment' ], 0, 2 );
			add_action( 'woocommerce_order_status_processing_to_cancelled', [ $this, 'role_unassignment' ], 0, 2 );
			add_action( 'woocommerce_order_status_processing_to_on-hold', [ $this, 'role_unassignment' ], 0, 2 );
		}

		if ( in_array( 'on_completed', $grant_moments, true ) ) {
			add_action( 'woocommerce_order_status_completed', [ $this, 'role_assignment' ], 0, 1 );
		}

		if ( in_array( 'on_completed', $grant_moments, true ) || in_array( 'on_processing', $grant_moments, true ) ) {
			add_action( 'woocommerce_order_status_completed_to_refunded', [ $this, 'role_unassignment' ], 0, 2 );
			add_action( 'woocommerce_order_status_completed_to_cancelled', [ $this, 'role_unassignment' ], 0, 2 );
			add_action( 'woocommerce_order_status_completed_to_on-hold', [ $this, 'role_unassignment' ], 0, 2 );
		}

		// If it's a call to the backend and not an ajax call
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_role_assignment_option' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_role_assignment_option' ], 10, 2 );

			add_filter( 'plugin_action_links_dfx-woo-role-changer/dfx-woo-role-changer.php', [ $this, 'add_settings_link' ] );
			add_action( 'woocommerce_settings_tabs_products', [ $this, 'render_settings_tab' ] );
			add_action( 'woocommerce_update_options_products', [ $this, 'update_settings' ] );
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

		$options = array_merge( [ 'none' => __( 'None', 'dfx-woo-role-changer' ) ], $options );

		echo '<div class="options_group">';

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

		if ( ! isset( $_POST['dfxwcrc_role_assignment'] ) ) {
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

		$roles         = [];
		$order         = wc_get_order( $order_id );
		$product_items = $order->get_items();

		$user_id = $order->get_user_id();

		if ( ! $user_id ) {
			return;
		}

		$user = new WP_User( $user_id );

		foreach ( $product_items as $product_item ) {

			$role = get_post_meta( $product_item->get_product_id(), '_dfxwcrc_role_assignment', true );

			if ( empty( $role ) || empty( trim( $role ) ) ) {
				continue;
			}

			$roles_for_product = explode( ',', $role );
			foreach ( $roles_for_product as $role_for_product ) {
				$role_for_product = trim( $role_for_product );
				if ( $role_for_product !== 'none' && ! empty( $role_for_product ) && ! in_array( $role_for_product, $roles ) ) {
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

		$order         = wc_get_order( $order_id );
		$product_items = $order->get_items();

		$user_id = $order->get_user_id();

		if ( ! $user_id ) {
			return;
		}

		$user = new WP_User( $user_id );

		foreach ( $product_items as $product_item ) {

			$role = get_post_meta( $product_item->get_product_id(), '_dfxwcrc_role_assignment', true );

			if ( empty( $role ) || empty( trim( $role ) ) ) {
				continue;
			}

			$roles_for_product = explode( ',', $role );
			foreach ( $roles_for_product as $role_for_product ) {
				$role_for_product = trim( $role_for_product );
				if ( $role_for_product !== 'none' && ! empty( $role_for_product ) ) {
					$this->maybe_remove_role_from_user( $user, $role_for_product, $order );
				}
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
	private function maybe_add_role_to_user( $user, $new_role, $order = null ) {

		if ( ! in_array( $new_role, $user->roles ) ) {

			if ( $this->get_current_mode() === 'replace_roles' ) {
				update_user_meta( $user->ID, 'dfxwcrc_old_roles', $user->roles );
				$user->set_role( $new_role );
			} else {
				$user->add_role( $new_role );
			}

			if ($order ) {
				$order->add_order_note( sprintf (
					/* translators: 1: role name, 2: user login */
					__('Added role "%1$s" to user "%2$s"', 'dfx-woo-role-changer' ),
					$new_role, $user->user_login ) );
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
	private function maybe_remove_role_from_user( $user, $role, $order = null ) {

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

			if ($order) {
				$order->add_order_note( sprintf(
					/* translators: 1: role name, 2: user login */
					__( 'Removed role "%1$s" from user "%2$s".', 'dfx-woo-role-changer' ),
					$role, $user->user_login ) );
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
			'name' => __( 'Role assignment mode', 'dfx-woo-role-changer' ),
			'type' => 'select',
			'default' => 'add_remove_roles',
			'options' => [
				'add_remove_roles' => __('Add/Remove Roles', 'dfx-woo-role-changer' ),
				'replace_roles' => __('Replace Roles', 'dfx-woo-role-changer' ),
			],
			'desc' => __( 'Choose the mode of role assignment. If you are using a plugin that allows managing multiple roles for a given user, you can choose to add or remove roles. If not, you should choose to replace the role.', 'dfx-woo-role-changer' ),
			'id' => 'dfx_woo_role_changer_mode_selection',
		];

		$settings['dfx_woo_role_changer_grant_moment'] = [
			'name' => __( 'When to grant roles', 'dfx-woo-role-changer' ),
			'type' => 'multiselect',
			'default' => ['on_payment'],
			'options' => [
				'on_payment' => __( 'On order payment', 'dfx-woo-role-changer' ),
				'on_processing'=> __( 'When order gets to Processing state', 'dfx-woo-role-changer' ),
				'on_completed' => __( 'When order gets to Completed state', 'dfx-woo-role-changer' ),
			],
			'class' => 'wc-enhanced-select',
			'desc' => __( 'When in the order lifecycle the role should be granted. If you choose more than one option, the role will be granted at all the selected moments.', 'dfx-woo-role-changer' ),
			'id' => 'dfx_woo_role_changer_grant_moment',
		];


		$settings['dfx_woo_sole_changer_section_end']   = array(
			'type' => 'sectionend',
			'id'   => 'dfx_woo_role_changer_section_end'
		);

		return apply_filters( 'dfx_woo_role_changer_settings', $settings );
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
