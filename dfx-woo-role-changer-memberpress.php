<?php

/**
 * MemberPress bridge: membership state -> WordPress role.
 *
 * This file is intentionally NOT marked @fs_premium_only: the free build ships the
 * bridge for a single membership level. The premium-only capabilities (unlimited
 * levels, several roles per membership, and the granular per-state mapping) are
 * wrapped in can_use_premium_code__premium_only() blocks so Freemius strips them.
 *
 * Verified against MemberPress 1.12.15:
 *  - mepr_account_is_active / mepr_account_is_inactive  (MeprActiveInactiveHooksCtrl,
 *    MemberPress's own central "is this account entitled?" dispatcher, added in 1.7.3)
 *  - mepr_subscription_transition_status                (MeprSubscription::store)
 *  - mepr_transaction_expired                           (MeprTransactionsCtrl, cron driven;
 *                                                        mepr_txn_expired is DEPRECATED)
 *
 * @package   DFX-Woo-Role-Changer
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if ( !class_exists( 'DfxWooRoleChangerMemberPress' ) ) {
    /**
     * Singleton class bridging MemberPress membership state to WordPress roles.
     */
    final class DfxWooRoleChangerMemberPress {
        /**
         * MemberPress membership custom post type (MeprProduct::$cpt).
         */
        public const MEPR_CPT = 'memberpressproduct';

        /**
         * Post meta prefix holding the role mapping on each membership.
         * Value convention matches the rest of the plugin: a comma-separated
         * list of role slugs (a single slug in the free build).
         */
        public const META_PREFIX = '_dfxwcrc_mepr_role_';

        /**
         * User meta recording which roles this bridge granted, keyed by
         * membership id. Lets us remove only what we granted, and makes a
         * re-run of the sync idempotent.
         */
        public const GRANTED_META = 'dfxwcrc_mepr_granted';

        /**
         * The single state the free build maps.
         */
        public const FREE_STATE = 'active';

        /**
         * Returns the instance.
         *
         * @return DfxWooRoleChangerMemberPress
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
         */
        private function __construct() {
            $this->registerHooks();
        }

        /**
         * Is MemberPress loaded?
         *
         * MEPR_VERSION is defined by memberpress.php at load time, so this is
         * reliable from `init` onwards.
         *
         * @return bool
         */
        public static function is_memberpress_active() {
            return defined( 'MEPR_VERSION' );
        }

        /**
         * The membership states that can be mapped to a role.
         *
         * `pending`, `active`, `suspended` and `cancelled` are the real
         * MeprSubscription statuses. `expired` is not a subscription status in
         * MemberPress — it is a transaction-level concept — but it is surfaced
         * here as a sibling because that is how store admins think about it.
         *
         * @return string[]
         */
        public static function get_states() {
            $states = [self::FREE_STATE];
            return $states;
        }

        /**
         * Human readable labels for the mappable states.
         *
         * @return array<string, string>
         */
        public static function get_state_labels() {
            return [
                'pending'   => __( 'Pending', 'dfx-woo-role-changer' ),
                'active'    => __( 'Active', 'dfx-woo-role-changer' ),
                'suspended' => __( 'Suspended', 'dfx-woo-role-changer' ),
                'cancelled' => __( 'Cancelled', 'dfx-woo-role-changer' ),
                'expired'   => __( 'Expired', 'dfx-woo-role-changer' ),
            ];
        }

        /**
         * Register hooks
         *
         * @return void
         */
        private function registerHooks() {
            add_action( 'init', [$this, 'registerDelayedHooks'] );
        }

        /**
         * Register the hooks that depend on MemberPress being loaded.
         *
         * @return void
         */
        public function registerDelayedHooks() {
            if ( !self::is_memberpress_active() ) {
                return;
            }
            add_filter( 'dfx_woo_role_changer_settings', [$this, 'add_memberpress_settings'] );
            add_action( 'add_meta_boxes', [$this, 'add_role_assignment_metabox'] );
            add_action(
                'save_post_' . self::MEPR_CPT,
                [$this, 'save_role_assignment_metabox'],
                10,
                1
            );
            // MemberPress's own entitlement dispatcher. This is the whole of the
            // free bridge: entitled -> grant, not entitled -> revoke.
            add_action(
                'mepr_account_is_active',
                [$this, 'handle_account_is_active'],
                10,
                1
            );
            add_action(
                'mepr_account_is_inactive',
                [$this, 'handle_account_is_inactive'],
                10,
                1
            );
        }

        /* ---------------------------------------------------------------------
         * Mapping storage
         * ------------------------------------------------------------------ */
        /**
         * Roles mapped to a given state of a given membership.
         *
         * @param int    $membership_id Membership post id.
         * @param string $state         One of get_states().
         *
         * @return string[]
         */
        public function get_roles_for_state( $membership_id, $state ) {
            if ( !in_array( $state, self::get_states(), true ) ) {
                return [];
            }
            $stored = get_post_meta( $membership_id, self::META_PREFIX . $state, true );
            if ( empty( $stored ) || $stored === 'none' ) {
                return [];
            }
            $roles = array_filter( array_map( 'trim', explode( ',', $stored ) ) );
            $available = wp_roles()->get_names();
            return array_values( array_filter( $roles, function ( $role ) use($available) {
                return array_key_exists( $role, $available );
            } ) );
        }

        /**
         * Membership ids that have at least one role mapped.
         *
         * @return int[]
         */
        public function get_mapped_memberships() {
            global $wpdb;
            // Deliberately not a meta_query. One OR'd EXISTS clause per state
            // makes WP_Meta_Query emit one LEFT JOIN on postmeta per state, and
            // five self-joins on a real site's postmeta table blow up
            // combinatorially — it hung for minutes on a catalogue of 22
            // products. A single prefix match on the indexed meta_key column
            // answers the same question immediately.
            $ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID\n\t\t\t\t   FROM {$wpdb->posts} p\n\t\t\t\t   INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID\n\t\t\t\t  WHERE p.post_type = %s\n\t\t\t\t    AND pm.meta_key LIKE %s\n\t\t\t\t    AND pm.meta_value <> ''", self::MEPR_CPT, $wpdb->esc_like( self::META_PREFIX ) . '%' ) );
            if ( !is_array( $ids ) ) {
                return [];
            }
            // The meta prefix is shared by every state, so restrict to the ones
            // this build actually maps.
            $mapped = [];
            foreach ( array_map( 'intval', $ids ) as $membership_id ) {
                foreach ( self::get_states() as $state ) {
                    if ( $this->get_roles_for_state( $membership_id, $state ) ) {
                        $mapped[] = $membership_id;
                        break;
                    }
                }
            }
            return $mapped;
        }

        /**
         * Can this membership still be mapped?
         *
         * The free build syncs a single membership level; once one is mapped the
         * rest show an upsell instead of the selector.
         *
         * @param int $membership_id Membership post id.
         *
         * @return bool
         */
        public function membership_is_mappable( $membership_id ) {
            $mapped = $this->get_mapped_memberships();
            return empty( $mapped ) || in_array( (int) $membership_id, $mapped, true );
        }

        /* ---------------------------------------------------------------------
         * Admin UI
         * ------------------------------------------------------------------ */
        /**
         * Register the role mapping metabox on the membership edit screen.
         *
         * @return void
         */
        public function add_role_assignment_metabox() {
            add_meta_box(
                'dfxwcrc_mepr_role_assignment',
                __( 'Role assignment', 'dfx-woo-role-changer' ),
                [$this, 'render_role_assignment_metabox'],
                self::MEPR_CPT,
                'side'
            );
        }

        /**
         * Render the role mapping metabox.
         *
         * @param WP_Post $post Membership being edited.
         *
         * @return void
         */
        public function render_role_assignment_metabox( $post ) {
            wp_nonce_field( 'dfxwcrc_mepr_save', 'dfxwcrc_mepr_nonce' );
            if ( !$this->membership_is_mappable( $post->ID ) ) {
                echo '<p>' . esc_html__( 'The free version syncs a single membership level, and another membership is already mapped.', 'dfx-woo-role-changer' ) . '</p>';
                if ( dfx_woo_role_changer_fs()->is_not_paying() ) {
                    printf( '<p><a href="%s">%s</a></p>', esc_url( dfx_woo_role_changer_fs()->checkout_url() ), esc_html__( 'Upgrade to Pro to map unlimited membership levels.', 'dfx-woo-role-changer' ) );
                }
                return;
            }
            $options = array_merge( [
                'none' => __( 'None', 'dfx-woo-role-changer' ),
            ], wp_roles()->get_names() );
            $labels = self::get_state_labels();
            // Written as a positive premium block so the free build is left with
            // the single-select default when Freemius strips it.
            $multiple = false;
            foreach ( self::get_states() as $state ) {
                $selected = $this->get_roles_for_state( $post->ID, $state );
                printf( '<p><label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr( 'dfxwcrc_mepr_role_' . $state ), esc_html( $labels[$state] ?? $state ) );
                printf(
                    '<select id="%1$s" name="%1$s%2$s" style="width:100%%"%3$s>',
                    esc_attr( 'dfxwcrc_mepr_role_' . $state ),
                    ( $multiple ? '[]' : '' ),
                    ( $multiple ? ' multiple="multiple"' : '' )
                );
                foreach ( $options as $slug => $label ) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr( $slug ),
                        selected( in_array( $slug, $selected, true ) || $slug === 'none' && empty( $selected ), true, false ),
                        esc_html( $label )
                    );
                }
                echo '</select></p>';
            }
            echo '<p class="description">' . esc_html__( 'Roles are granted when the membership enters the state and revoked when it leaves it.', 'dfx-woo-role-changer' ) . '</p>';
            if ( dfx_woo_role_changer_fs()->is_not_paying() ) {
                printf( '<p class="description"><a href="%s">%s</a></p>', esc_url( dfx_woo_role_changer_fs()->checkout_url() ), esc_html__( 'Upgrade to Pro for unlimited levels, several roles per membership, and mapping of every membership state.', 'dfx-woo-role-changer' ) );
            }
        }

        /**
         * Persist the role mapping.
         *
         * @param int $post_id Membership post id.
         *
         * @return void
         */
        public function save_role_assignment_metabox( $post_id ) {
            if ( !isset( $_POST['dfxwcrc_mepr_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dfxwcrc_mepr_nonce'] ) ), 'dfxwcrc_mepr_save' ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
            if ( !current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
            if ( !$this->membership_is_mappable( $post_id ) ) {
                return;
            }
            $available = wp_roles()->get_names();
            foreach ( self::get_states() as $state ) {
                $field = 'dfxwcrc_mepr_role_' . $state;
                if ( !isset( $_POST[$field] ) ) {
                    continue;
                }
                $submitted = wp_unslash( $_POST[$field] );
                $submitted = ( is_array( $submitted ) ? $submitted : [$submitted] );
                $roles = [];
                foreach ( $submitted as $role ) {
                    $role = sanitize_text_field( $role );
                    if ( $role !== 'none' && array_key_exists( $role, $available ) ) {
                        $roles[] = $role;
                    }
                }
                if ( empty( $roles ) ) {
                    delete_post_meta( $post_id, self::META_PREFIX . $state );
                } else {
                    update_post_meta( $post_id, self::META_PREFIX . $state, implode( ',', $roles ) );
                }
            }
        }

        /**
         * Report MemberPress detection in the WooCommerce settings screen.
         *
         * @param array $settings Settings array.
         *
         * @return array
         */
        public function add_memberpress_settings( $settings ) {
            $end = $settings['dfx_woo_role_changer_section_end'];
            unset($settings['dfx_woo_role_changer_section_end']);
            $intro = __( 'MemberPress detected. Map membership states to roles from each membership\'s edit screen.', 'dfx-woo-role-changer' );
            // Free wording is the default so it survives Freemius stripping; the
            // premium block replaces it rather than the other way round.
            $desc = $intro . '<br>' . __( 'The free version syncs a single membership level, granting a role while the membership is active.', 'dfx-woo-role-changer' );
            $settings['dfx_woo_role_changer_memberpress_message'] = [
                'name'     => __( 'MemberPress integration', 'dfx-woo-role-changer' ),
                'type'     => 'title',
                'desc_tip' => false,
                'desc'     => $desc,
                'id'       => 'dfx_woo_role_changer_memberpress_message',
            ];
            $settings['dfx_woo_role_changer_section_end'] = $end;
            return $settings;
        }

        /* ---------------------------------------------------------------------
         * MemberPress event handlers
         * ------------------------------------------------------------------ */
        /**
         * MemberPress considers the account entitled to this membership.
         *
         * @param MeprTransaction $txn Transaction backing the entitlement.
         *
         * @return void
         */
        public function handle_account_is_active( $txn ) {
            if ( $this->subscription_owns( $txn ) ) {
                return;
            }
            $this->sync_user( $txn->user_id, $txn->product_id, 'active' );
        }

        /**
         * MemberPress considers the account no longer entitled to this membership.
         *
         * @param MeprTransaction $txn Transaction backing the entitlement.
         *
         * @return void
         */
        public function handle_account_is_inactive( $txn ) {
            if ( $this->subscription_owns( $txn ) ) {
                return;
            }
            // No state mapped: every role this bridge granted for the membership
            // is revoked.
            $this->sync_user( $txn->user_id, $txn->product_id, '' );
        }

        /**
         * Should the granular subscription handlers own this transaction?
         *
         * In the premium build a subscription-backed membership is driven by
         * mepr_subscription_transition_status, so the coarse active/inactive
         * dispatcher must keep its hands off it — otherwise the two race and the
         * last one to run wins.
         *
         * @param MeprTransaction $txn Transaction.
         *
         * @return bool
         */
        private function subscription_owns( $txn ) {
            // Positive premium block: the free build strips it and keeps `false`,
            // so the free bridge always handles active/inactive itself.
            $owns = false;
            return $owns;
        }

        /* ---------------------------------------------------------------------
         * Sync engine
         * ------------------------------------------------------------------ */
        /**
         * Align a user's roles with the current state of one membership.
         *
         * Idempotent: running it again with the same state is a no-op, which is
         * what makes the retroactive sync safe to re-run.
         *
         * This does not implement role assignment itself — it feeds the existing
         * engine in DfxWooRoleChanger, so assignment mode, the administrator
         * guard and the premium expiration scheduling all still apply.
         *
         * @param int    $user_id       User id.
         * @param int    $membership_id Membership post id.
         * @param string $state         Current membership state ('' for none).
         *
         * @return array{added: string[], removed: string[]} What actually changed.
         */
        public function sync_user( $user_id, $membership_id, $state ) {
            $result = [
                'added'   => [],
                'removed' => [],
            ];
            $user_id = (int) $user_id;
            $membership_id = (int) $membership_id;
            if ( $user_id <= 0 || $membership_id <= 0 ) {
                return $result;
            }
            $user = get_user_by( 'id', $user_id );
            if ( !$user ) {
                return $result;
            }
            $target = ( $state === '' ? [] : $this->get_roles_for_state( $membership_id, $state ) );
            $granted = $this->get_granted_roles( $user_id, $membership_id );
            if ( empty( $target ) && empty( $granted ) ) {
                return $result;
            }
            $plugin = DfxWooRoleChanger::get_instance();
            // Revoke only what this bridge granted for this membership, so a role
            // the user also earned through a WooCommerce order is left alone.
            foreach ( array_diff( $granted, $target ) as $role ) {
                $plugin->maybe_remove_role_from_user( $user, $role );
                $result['removed'][] = $role;
            }
            foreach ( array_diff( $target, $granted ) as $role ) {
                $plugin->maybe_add_role_to_user( $user, $role );
                $result['added'][] = $role;
            }
            $this->set_granted_roles( $user_id, $membership_id, $target );
            return $result;
        }

        /**
         * Roles this bridge granted to a user for a membership.
         *
         * @param int $user_id       User id.
         * @param int $membership_id Membership post id.
         *
         * @return string[]
         */
        private function get_granted_roles( $user_id, $membership_id ) {
            $granted = get_user_meta( $user_id, self::GRANTED_META, true );
            if ( !is_array( $granted ) || !isset( $granted[$membership_id] ) ) {
                return [];
            }
            return ( is_array( $granted[$membership_id] ) ? $granted[$membership_id] : [] );
        }

        /**
         * Record the roles this bridge granted to a user for a membership.
         *
         * @param int      $user_id       User id.
         * @param int      $membership_id Membership post id.
         * @param string[] $roles         Roles currently granted.
         *
         * @return void
         */
        private function set_granted_roles( $user_id, $membership_id, $roles ) {
            $granted = get_user_meta( $user_id, self::GRANTED_META, true );
            $granted = ( is_array( $granted ) ? $granted : [] );
            if ( empty( $roles ) ) {
                unset($granted[$membership_id]);
            } else {
                $granted[$membership_id] = array_values( $roles );
            }
            if ( empty( $granted ) ) {
                delete_user_meta( $user_id, self::GRANTED_META );
            } else {
                update_user_meta( $user_id, self::GRANTED_META, $granted );
            }
        }

    }

    /**
     * Gets the instance of the `DfxWooRoleChangerMemberPress` class.
     *
     * @return DfxWooRoleChangerMemberPress
     */
    function dfx_woo_role_changer_memberpress() {
        return DfxWooRoleChangerMemberPress::get_instance();
    }

    dfx_woo_role_changer_memberpress();
}