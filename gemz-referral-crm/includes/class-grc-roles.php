<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GRC_Roles {

	const AGENT_ROLE = 'grc_agent';

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
}
