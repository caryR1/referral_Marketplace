<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GRC_Deactivator {

	/**
	 * Deactivation intentionally does NOT touch the database.
	 * Deactivating (e.g. during a plugin code update) should never risk
	 * live lead/commission data. Table drops only happen through the
	 * explicit "Reset test data" tool in the admin, guarded separately.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
		wp_clear_scheduled_hook( 'grc_daily_stale_lead_check' );
	}

	/**
	 * Drops every custom table. NEVER call this automatically.
	 * Wired only to an explicit, confirmed admin action (see
	 * admin/class-grc-admin.php -> handle_reset_database()) so testing
	 * resets are one click but production data can't be nuked by accident.
	 */
	public static function drop_all_tables() {
		global $wpdb;
		$tables = array( 'partners', 'agents', 'agent_segments', 'campaigns', 'leads', 'milestones', 'commissions', 'customer_payouts', 'notifications_log', 'audit_log', 'email_templates' );
		foreach ( $tables as $t ) {
			$full = GRC_DB::table( $t );
			$wpdb->query( "DROP TABLE IF EXISTS {$full}" ); // phpcs:ignore -- table name is from our own fixed whitelist above, not user input
		}
		delete_option( 'grc_db_version' );
	}
}
