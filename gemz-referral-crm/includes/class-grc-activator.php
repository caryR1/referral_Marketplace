<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GRC_Activator {

	public static function activate() {
		self::create_tables();
		GRC_Roles::register_roles_and_caps();
		update_option( 'grc_db_version', GRC_DB_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Creates (or updates, via dbDelta) every custom table the plugin needs.
	 * Safe to call multiple times - dbDelta diffs against existing schema.
	 */
	public static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collate = GRC_DB::charset_collate();

		$statements = array();

		// -------------------------------------------------------------
		// PARTNERS - fulfillment partners (roofing/HVAC/solar companies)
		// -------------------------------------------------------------
		$partners = GRC_DB::table( 'partners' );
		$statements[] = "CREATE TABLE {$partners} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL,
			industry VARCHAR(50) NOT NULL,
			contact_name VARCHAR(150) DEFAULT '',
			phone VARCHAR(30) DEFAULT '',
			email VARCHAR(150) DEFAULT '',
			website VARCHAR(255) DEFAULT '',
			service_areas LONGTEXT NULL COMMENT 'JSON: list of {state,city,zip} coverage entries',
			payout_amount DECIMAL(10,2) DEFAULT 0,
			payout_type VARCHAR(30) DEFAULT 'flat' COMMENT 'flat, percentage, tiered',
			customer_cashback_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'flat $ paid directly to the homeowner once their lead with this partner is marked completed - separate pool from payout_amount, which funds agent commissions',
			payout_notes TEXT NULL COMMENT 'payout speed/hassle/terms notes',
			status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, paused, dropped',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY industry (industry),
			KEY status (status)
		) {$collate};";

		// -------------------------------------------------------------
		// AGENTS - referrers (extends wp_users, one row per agent profile)
		// -------------------------------------------------------------
		$agents = GRC_DB::table( 'agents' );
		$statements[] = "CREATE TABLE {$agents} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL COMMENT 'FK to wp_users',
			referral_code VARCHAR(40) NOT NULL,
			sponsor_agent_id BIGINT UNSIGNED NULL COMMENT 'agent who referred this agent into the program (MLM chain), NULL if root',
			payment_method VARCHAR(20) NULL COMMENT 'wise, paypal, bank',
			payment_details LONGTEXT NULL COMMENT 'JSON, agent-editable only, never admin-editable',
			status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, suspended',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY referral_code (referral_code),
			KEY user_id (user_id),
			KEY sponsor_agent_id (sponsor_agent_id)
		) {$collate};";

		// -------------------------------------------------------------
		// CAMPAIGNS - funnel/landing-page campaigns tied to a partner
		// -------------------------------------------------------------
		$campaigns = GRC_DB::table( 'campaigns' );
		$statements[] = "CREATE TABLE {$campaigns} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL,
			partner_id BIGINT UNSIGNED NOT NULL,
			industry VARCHAR(50) NOT NULL,
			landing_page_id BIGINT UNSIGNED NULL COMMENT 'FK to wp_posts, the Gutenberg landing page for this campaign',
			tracking_slug VARCHAR(60) NOT NULL COMMENT 'used in the ready-to-send link, e.g. /go/roofing-tc-storm',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY tracking_slug (tracking_slug),
			KEY partner_id (partner_id)
		) {$collate};";

		// -------------------------------------------------------------
		// LEADS - a customer sent toward a partner via an agent/campaign
		// -------------------------------------------------------------
		$leads = GRC_DB::table( 'leads' );
		$statements[] = "CREATE TABLE {$leads} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			partner_id BIGINT UNSIGNED NOT NULL,
			agent_id BIGINT UNSIGNED NULL COMMENT 'agent credited for this lead, NULL if direct/organic',
			campaign_id BIGINT UNSIGNED NULL,
			customer_name VARCHAR(150) NOT NULL,
			customer_phone VARCHAR(30) DEFAULT '',
			customer_email VARCHAR(150) DEFAULT '',
			customer_zip VARCHAR(15) DEFAULT '',
			preferred_contact VARCHAR(20) DEFAULT 'phone' COMMENT 'phone, text, email',
			appointment_primary DATETIME NULL,
			appointment_backup DATETIME NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'new' COMMENT 'new, sent_to_partner, accepted, in_progress, completed, lost, stale',
			last_partner_update_at DATETIME NULL COMMENT 'used to detect stale leads',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY partner_id (partner_id),
			KEY agent_id (agent_id),
			KEY campaign_id (campaign_id),
			KEY status (status)
		) {$collate};";

		// -------------------------------------------------------------
		// MILESTONES - per-lead progress events (config varies by industry)
		// -------------------------------------------------------------
		$milestones = GRC_DB::table( 'milestones' );
		$statements[] = "CREATE TABLE {$milestones} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			milestone_key VARCHAR(60) NOT NULL COMMENT 'e.g. contract_signed, permit_pulled, ptO_approved',
			milestone_label VARCHAR(150) NOT NULL,
			completed_at DATETIME NULL,
			notify_agent TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id)
		) {$collate};";

		// -------------------------------------------------------------
		// COMMISSIONS - money owed/paid per agent per lead, incl MLM splits
		// -------------------------------------------------------------
		$commissions = GRC_DB::table( 'commissions' );
		$statements[] = "CREATE TABLE {$commissions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			agent_id BIGINT UNSIGNED NOT NULL,
			tier TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = direct referrer, 2/3 = upline sponsors in MLM chain',
			amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'owed' COMMENT 'owed, ready, paid, cancelled',
			paid_at DATETIME NULL,
			payout_reference VARCHAR(100) NULL COMMENT 'Wise/PayPal/bank transaction ref',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id),
			KEY agent_id (agent_id),
			KEY status (status)
		) {$collate};";

		// -------------------------------------------------------------
		// NOTIFICATIONS LOG - audit trail of every notification sent
		// -------------------------------------------------------------
		$notifications = GRC_DB::table( 'notifications_log' );
		$statements[] = "CREATE TABLE {$notifications} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_key VARCHAR(60) NOT NULL COMMENT 'e.g. agent_signup, appointment_booked, milestone_reached',
			recipient_type VARCHAR(20) NOT NULL COMMENT 'agent, customer, partner',
			recipient_ref VARCHAR(150) NOT NULL COMMENT 'email or phone actually used',
			channel VARCHAR(20) NOT NULL COMMENT 'email, whatsapp',
			related_lead_id BIGINT UNSIGNED NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'sent' COMMENT 'sent, failed',
			sent_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY event_key (event_key),
			KEY related_lead_id (related_lead_id)
		) {$collate};";

		// -------------------------------------------------------------
		// AUDIT LOG - generic change tracking across the plugin
		// -------------------------------------------------------------
		$audit = GRC_DB::table( 'audit_log' );
		$statements[] = "CREATE TABLE {$audit} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL COMMENT 'WP user who made the change, NULL if system/automated',
			object_type VARCHAR(40) NOT NULL COMMENT 'partner, lead, agent, commission, campaign',
			object_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(40) NOT NULL COMMENT 'created, updated, status_changed, deleted',
			details LONGTEXT NULL COMMENT 'JSON diff or free text',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY object_type_id (object_type, object_id)
		) {$collate};";

		// -------------------------------------------------------------
		// EMAIL TEMPLATES - editable subject/body per notification event
		// -------------------------------------------------------------
		$templates = GRC_DB::table( 'email_templates' );
		$statements[] = "CREATE TABLE {$templates} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_key VARCHAR(60) NOT NULL COMMENT 'matches the event keys used by GRC_Notifications::send()',
			subject VARCHAR(255) NOT NULL,
			body LONGTEXT NOT NULL COMMENT 'plain text with {{variable}} placeholders',
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_key (event_key)
		) {$collate};";

		// -------------------------------------------------------------
		// CUSTOMER PAYOUTS - homeowner cash-back owed/claimed/paid per
		// lead. Separate from COMMISSIONS (which pays agents) - this is
		// the money promised directly to the homeowner on every landing
		// page. No customer login exists, so claiming happens via a
		// unique unguessable claim_token sent by email, not a session.
		// -------------------------------------------------------------
		$customer_payouts = GRC_DB::table( 'customer_payouts' );
		$statements[] = "CREATE TABLE {$customer_payouts} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'ready' COMMENT 'ready (awaiting claim), claimed (payout method submitted), paid',
			claim_token VARCHAR(64) NOT NULL,
			payout_method VARCHAR(20) NULL COMMENT 'paypal, venmo, bank, check',
			payout_details LONGTEXT NULL COMMENT 'JSON, customer-submitted via the claim page',
			payout_reference VARCHAR(100) NULL,
			claimed_at DATETIME NULL,
			paid_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY lead_id (lead_id),
			UNIQUE KEY claim_token (claim_token),
			KEY status (status)
		) {$collate};";

		foreach ( $statements as $sql ) {
			dbDelta( $sql );
		}
	}
}
