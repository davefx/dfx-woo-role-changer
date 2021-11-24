<?php
/**
 * Plugin Name: DFX Automatic Role Changer for WooCommerce
 * Description: Allows the automatic assignation of roles to users on product purchases in WooCommerce
 * Version:     20201217
 * Author:      David Marín Carreño
 * Author URI:  https://davefx.com
 * Text Domain: dfx-woo-role-changer
 * Domain Path: /lang
 *
 * WC requires at least: 3.0
 * WC tested up to: 5.9
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
 * @version   20201115
 * @author    David Marín Carreño <davefx@davefx.com>
 * @copyright Copyright (c) 2020 David Marín Carreño
 * @link      https://davefx.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die('No script kiddies please!');

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

		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_role_assignment_option' ] );

		add_action( 'woocommerce_process_product_meta', [ $this, 'save_role_assignment_option' ], 10, 2 );

		add_filter( 'woocommerce_payment_complete', [ $this, 'role_assignment' ], 0, 1 );
		add_filter( 'woocommerce_order_refunded', [ $this, 'role_unassignment' ], 0, 2 );
	}

	public function load_i18n() {
		$plugin_rel_path =  dirname( plugin_basename(__FILE__) ) . '/languages/';
		load_plugin_textdomain( 'dfx-woo-role-changer', false, $plugin_rel_path );
	}

	public function add_role_assignment_option() {

		$options = wp_roles()->get_names();

		$options = array_merge( [ 'none' => __( 'None', 'dfx-woo-role-changer' ) ], $options );

		echo '<div class="options_group">';

		woocommerce_wp_select( array(
			'id'      => 'dfxwcrc_role_assignment',
			'value'   => get_post_meta( get_the_ID(), '_dfxwcrc_role_assignment', true ) ?? 'none',
			'label'   => 'Assigned Role',
			'desc_tip' => true,
			'description' => __( 'Role to be assigned to people purchasing this product', 'dfx-woo-role-changer' ),
			'options' => $options,
		) );

		echo '</div>';
	}

	public function save_role_assignment_option( $id, $post ){

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

		$roles    = [];
		$order    = wc_get_order( $order_id );
		$product_items = $order->get_items();

		$user_id = $order->get_user_id();

		if ( ! $user_id ) {
			return;
		}

		$user    = new WP_User( $user_id );

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

				if ( ! in_array( $new_role, $user->roles ) ) {
					$user->add_role( $new_role );

					$order->add_order_note( "Added role '" . $new_role . "' to user '" . $user->user_login );

				}

			}
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
