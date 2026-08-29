<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GRC_Roles {

	const AGENT_ROLE = 'grc_agent';
	const PARTNER_ROLE = 'grc_partner';

	public static function register_roles_and_caps() {
		// Agent role: logs in to see their own leads, commissions, referral link, payout settings.
		if ( ! get_role( self::AGENT_ROLE ) ) {
			add_role( self::AGENT_ROLE, 'Referral Agent', array(
				'read' => true,
				'grc_view_own_leads' => true,
				'grc_view_own_commissions' => true,
				'grc_edit_own_payment_info' => true,
			) );
		}

		// Partner role: logs in to see their own deal pipeline. A user can
		// hold this role AND grc_agent at the same time (WP supports
		// multiple roles per user natively) - that's what makes "one
		// person is both an agent and a partner" actually work.
		if ( ! get_role( self::PARTNER_ROLE ) ) {
			add_role( self::PARTNER_ROLE, 'Fulfillment Partner', array(
				'read' => true,
				'grc_view_own_deals' => true,
			) );
		}

		// Give administrators full plugin management capabilities.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin_caps = array(
				'grc_manage_partners',
				'grc_manage_leads',
				'grc_manage_agents',
				'grc_manage_campaigns',
				'grc_manage_commissions',
				'grc_view_reports',
				'grc_view_audit_log',
			);
			foreach ( $admin_caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Where should "your dashboard" actually point for whoever's looking
	 * at the page right now? Used by the nav menu item, and by the
	 * agent login/signup pages' "you're already logged in" messages -
	 * both were previously dead ends (plain text, no link at all).
	 */
	public static function get_portal_link_for_current_user() {
		if ( ! is_user_logged_in() ) {
			return array( 'label' => 'Agent Login', 'url' => home_url( '/agent-login/' ) );
		}

		$roles = (array) wp_get_current_user()->roles;

		if ( in_array( self::AGENT_ROLE, $roles, true ) ) {
			return array( 'label' => 'Agent Portal', 'url' => home_url( '/agent-portal/' ) );
		}
		if ( in_array( self::PARTNER_ROLE, $roles, true ) ) {
			return array( 'label' => 'Partner Portal', 'url' => home_url( '/partner-portal/' ) );
		}

		// Logged in (e.g. as an admin) but neither role - nothing to send
		// them to, so don't imply a dashboard that doesn't exist for them.
		return array( 'label' => null, 'url' => null );
	}

	/**
	 * Auto-creates (or links) a WP user account for a partner so they can
	 * log in to their own dashboard - called whenever a partner is saved
	 * with an email and doesn't have one yet. If that email already
	 * belongs to a WP user (e.g. an existing agent), the partner role is
	 * ADDED to that account rather than creating a duplicate - this is
	 * what makes "one person is both an agent and a partner" real rather
	 * than two disconnected logins. Lives here (not GRC_Admin) because
	 * this needs to run from REST contexts too, where is_admin() is
	 * false and admin/ classes aren't loaded.
	 */
	public static function provision_partner_account( $partner_id ) {
		global $wpdb;
		$table = GRC_DB::table( 'partners' );
		$partner = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $partner_id ) );

		if ( ! $partner || empty( $partner->email ) || ! empty( $partner->user_id ) ) {
			return; // no email to invite, or already has an account
		}

		$existing_user = get_user_by( 'email', $partner->email );

		if ( $existing_user ) {
			$existing_user->add_role( self::PARTNER_ROLE );
			$wpdb->update( $table, array( 'user_id' => $existing_user->ID ), array( 'id' => $partner_id ) );
			return;
		}

		$base_username = sanitize_user( current( explode( '@', $partner->email ) ), true );
		$username = $base_username;
		$suffix = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . $suffix;
			$suffix++;
		}

		$user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $partner->email,
			'user_pass'    => wp_generate_password( 20 ),
			'display_name' => $partner->name,
			'role'         => self::PARTNER_ROLE,
		) );

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		$wpdb->update( $table, array( 'user_id' => $user_id ), array( 'id' => $partner_id ) );

		// Sends WP core's standard "set your new password" email - same
		// mechanism as the "forgot password" flow, just triggered
		// proactively instead of by the user requesting it.
		retrieve_password( $partner->email );
	}
}
