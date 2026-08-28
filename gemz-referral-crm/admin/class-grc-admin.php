<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GRC_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_grc_save_partner', array( __CLASS__, 'handle_save_partner' ) );
		add_action( 'admin_post_grc_reset_test_data', array( __CLASS__, 'handle_reset_database' ) );
		add_action( 'admin_post_grc_update_lead_status', array( __CLASS__, 'handle_update_lead_status' ) );
		add_action( 'admin_post_grc_save_campaign', array( __CLASS__, 'handle_save_campaign' ) );
		add_action( 'admin_post_grc_save_commission_split', array( __CLASS__, 'handle_save_commission_split' ) );
		add_action( 'admin_post_grc_save_whatsapp_settings', array( __CLASS__, 'handle_save_whatsapp_settings' ) );
		add_action( 'admin_post_grc_mark_customer_payout_paid', array( __CLASS__, 'handle_mark_customer_payout_paid' ) );
		add_action( 'admin_post_grc_mark_commission_paid', array( __CLASS__, 'handle_mark_commission_paid' ) );
	}

	public static function register_menu() {
		add_menu_page(
			'Gemz Referral CRM',
			'Referral CRM',
			'grc_manage_partners',
			'grc-dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-networking',
			26
		);

		add_submenu_page( 'grc-dashboard', 'Dashboard', 'Dashboard', 'grc_manage_partners', 'grc-dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'grc-dashboard', 'Partners', 'Partners', 'grc_manage_partners', 'grc-partners', array( __CLASS__, 'render_partners' ) );
		add_submenu_page( 'grc-dashboard', 'Campaigns', 'Campaigns', 'grc_manage_campaigns', 'grc-campaigns', array( __CLASS__, 'render_campaigns' ) );
		add_submenu_page( 'grc-dashboard', 'Leads', 'Leads', 'grc_manage_leads', 'grc-leads', array( __CLASS__, 'render_leads' ) );
		add_submenu_page( 'grc-dashboard', 'Agents', 'Agents', 'grc_manage_agents', 'grc-agents', array( __CLASS__, 'render_agents' ) );
		add_submenu_page( 'grc-dashboard', 'Payouts', 'Payouts', 'grc_manage_commissions', 'grc-payouts', array( __CLASS__, 'render_payouts' ) );
		add_submenu_page( 'grc-dashboard', 'Reports', 'Reports', 'grc_view_reports', 'grc-reports', array( __CLASS__, 'render_reports' ) );
		add_submenu_page( 'grc-dashboard', 'Email Templates', 'Email Templates', 'manage_options', 'grc-email-templates', array( __CLASS__, 'render_email_templates' ) );
		add_submenu_page( 'grc-dashboard', 'Notification Log', 'Notification Log', 'grc_view_audit_log', 'grc-notification-log', array( __CLASS__, 'render_notification_log' ) );
		add_submenu_page( 'grc-dashboard', 'Audit Log', 'Audit Log', 'grc_view_audit_log', 'grc-audit', array( __CLASS__, 'render_audit_log' ) );
		add_submenu_page( 'grc-dashboard', 'Settings', 'Settings', 'grc_manage_partners', 'grc-settings', array( __CLASS__, 'render_settings' ) );
	}

	public static function render_dashboard() {
		include GRC_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public static function render_partners() {
		include GRC_PLUGIN_DIR . 'admin/views/partners.php';
	}

	public static function render_campaigns() {
		include GRC_PLUGIN_DIR . 'admin/views/campaigns.php';
	}

	public static function render_leads() {
		include GRC_PLUGIN_DIR . 'admin/views/leads.php';
	}

	public static function render_agents() {
		include GRC_PLUGIN_DIR . 'admin/views/agents.php';
	}

	public static function render_reports() {
		include GRC_PLUGIN_DIR . 'admin/views/reports.php';
	}

	public static function render_payouts() {
		include GRC_PLUGIN_DIR . 'admin/views/payouts.php';
	}

	public static function render_email_templates() {
		include GRC_PLUGIN_DIR . 'admin/views/email-templates.php';
	}

	public static function render_notification_log() {
		include GRC_PLUGIN_DIR . 'admin/views/notification-log.php';
	}

	public static function render_audit_log() {
		include GRC_PLUGIN_DIR . 'admin/views/audit-log.php';
	}

	public static function render_settings() {
		include GRC_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Handles the partner add/edit form submission.
	 */
	public static function handle_save_partner() {
		if ( ! current_user_can( 'grc_manage_partners' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_save_partner' );

		global $wpdb;
		$now = current_time( 'mysql' );

		$data = array(
			'name'          => sanitize_text_field( $_POST['name'] ?? '' ),
			'industry'      => sanitize_text_field( $_POST['industry'] ?? '' ),
			'contact_name'  => sanitize_text_field( $_POST['contact_name'] ?? '' ),
			'phone'         => sanitize_text_field( $_POST['phone'] ?? '' ),
			'email'         => sanitize_email( $_POST['email'] ?? '' ),
			'website'       => esc_url_raw( $_POST['website'] ?? '' ),
			'service_areas' => self::sanitize_service_areas( $_POST['service_areas'] ?? '' ),
			'payout_amount' => floatval( $_POST['payout_amount'] ?? 0 ),
			'payout_type'   => sanitize_text_field( $_POST['payout_type'] ?? 'flat' ),
			'customer_cashback_amount' => floatval( $_POST['customer_cashback_amount'] ?? 0 ),
			'payout_notes'  => sanitize_textarea_field( $_POST['payout_notes'] ?? '' ),
			'status'        => sanitize_text_field( $_POST['status'] ?? 'active' ),
			'updated_at'    => $now,
		);

		$partner_id = absint( $_POST['partner_id'] ?? 0 );
		$table       = GRC_DB::table( 'partners' );

		if ( $partner_id ) {
			$result = $wpdb->update( $table, $data, array( 'id' => $partner_id ) );
			if ( false === $result ) {
				wp_die( 'Database error saving partner: ' . esc_html( $wpdb->last_error ) );
			}
			self::audit_log( 'partner', $partner_id, 'updated', $data );
		} else {
			$data['created_at'] = $now;
			$result = $wpdb->insert( $table, $data );
			if ( false === $result ) {
				wp_die( 'Database error saving partner: ' . esc_html( $wpdb->last_error ) );
			}
			self::audit_log( 'partner', $wpdb->insert_id, 'created', $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=grc-partners&saved=1' ) );
		exit;
	}

	/**
	 * Sanitizes the JSON posted by the service-areas repeatable-row UI on
	 * the Partners screen into a clean {state,city,zip} array, dropping
	 * any row with no state/city/zip and any state that isn't a 2-letter
	 * code. Never trust JS-built JSON from a form as already clean.
	 */
	private static function sanitize_service_areas( $raw_json ) {
		$decoded = json_decode( wp_unslash( (string) $raw_json ), true );
		if ( empty( $decoded ) || ! is_array( $decoded ) ) {
			return wp_json_encode( array() );
		}

		$clean = array();
		foreach ( $decoded as $area ) {
			if ( ! is_array( $area ) ) {
				continue;
			}
			$state = strtoupper( sanitize_text_field( $area['state'] ?? '' ) );
			$city  = sanitize_text_field( $area['city'] ?? '' );
			$zip   = sanitize_text_field( $area['zip'] ?? '' );

			if ( '' === $state && '' === $city && '' === $zip ) {
				continue;
			}
			if ( '' !== $state && ! preg_match( '/^[A-Z]{2}$/', $state ) ) {
				continue; // not a valid 2-letter state code, drop the row rather than store garbage
			}

			$clean[] = array( 'state' => $state, 'city' => $city, 'zip' => $zip );
		}

		return wp_json_encode( $clean );
	}

	/**
	 * Saves a campaign (name, partner, industry, landing page, tracking slug).
	 * The tracking slug is what makes the ready-to-send link -
	 * refer.gemzonline.com/go/{slug} - so it must be unique and URL-safe.
	 */
	public static function handle_save_campaign() {
		if ( ! current_user_can( 'grc_manage_campaigns' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_save_campaign' );

		global $wpdb;
		$now = current_time( 'mysql' );
		$table = GRC_DB::table( 'campaigns' );

		$slug = sanitize_title( $_POST['tracking_slug'] ?? '' );
		if ( empty( $slug ) ) {
			wp_die( 'Tracking slug is required and must be URL-safe.' );
		}

		$campaign_id = absint( $_POST['campaign_id'] ?? 0 );

		// Enforce uniqueness of the slug (excluding this campaign's own row when editing).
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE tracking_slug = %s AND id != %d", $slug, $campaign_id
		) );
		if ( $existing ) {
			wp_die( 'That tracking slug is already used by another campaign. Choose a different one.' );
		}

		$data = array(
			'name'            => sanitize_text_field( $_POST['name'] ?? '' ),
			'partner_id'      => absint( $_POST['partner_id'] ?? 0 ),
			'industry'        => sanitize_text_field( $_POST['industry'] ?? '' ),
			'landing_page_id' => absint( $_POST['landing_page_id'] ?? 0 ) ?: null,
			'tracking_slug'   => $slug,
			'status'          => sanitize_text_field( $_POST['status'] ?? 'active' ),
			'updated_at'      => $now,
		);

		if ( $campaign_id ) {
			$wpdb->update( $table, $data, array( 'id' => $campaign_id ) );
			self::audit_log( 'campaign', $campaign_id, 'updated', $data );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $table, $data );
			self::audit_log( 'campaign', $wpdb->insert_id, 'created', $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=grc-campaigns&saved=1' ) );
		exit;
	}

	/**
	 * Saves the configurable commission split (Settings screen). The three
	 * tier percentages must be non-negative and sum to 100 - a split that
	 * doesn't add up to the whole payout is a silent bug waiting to
	 * shortchange or overpay agents, so it's rejected outright rather than
	 * saved and left for someone to notice later.
	 */
	public static function handle_save_commission_split() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_save_commission_split' );

		$tier1 = max( 0, (float) ( $_POST['tier1_pct'] ?? 0 ) );
		$tier2 = max( 0, (float) ( $_POST['tier2_pct'] ?? 0 ) );
		$tier3 = max( 0, (float) ( $_POST['tier3_pct'] ?? 0 ) );
		$total = $tier1 + $tier2 + $tier3;

		if ( abs( $total - 100.0 ) > 0.01 ) {
			wp_die( 'Tier percentages must add up to 100. You entered ' . esc_html( $total ) . '. Go back and try again.' );
		}

		update_option( 'grc_commission_split', array(
			1 => round( $tier1 / 100, 4 ),
			2 => round( $tier2 / 100, 4 ),
			3 => round( $tier3 / 100, 4 ),
		) );

		self::audit_log( 'settings', 0, 'commission_split_updated', array(
			'tier1_pct' => $tier1, 'tier2_pct' => $tier2, 'tier3_pct' => $tier3,
		) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-settings&split_saved=1' ) );
		exit;
	}

	/**
	 * Saves WhatsApp provider settings (Twilio). Credentials are stored
	 * as plugin options - same trust level as any other WP plugin secret
	 * (e.g. an SMTP password), not a dedicated secrets store. Once these
	 * are filled in, GRC_Notifications::maybe_send_whatsapp_via_twilio() (hooked
	 * to the grc_send_whatsapp filter) starts actually sending instead of
	 * just logging intent.
	 */
	public static function handle_save_whatsapp_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_save_whatsapp_settings' );

		update_option( 'grc_whatsapp_provider', sanitize_text_field( $_POST['whatsapp_provider'] ?? 'none' ) );
		update_option( 'grc_whatsapp_twilio_sid', sanitize_text_field( $_POST['twilio_sid'] ?? '' ) );

		// Only overwrite the stored auth token if a new one was actually typed -
		// the field is rendered blank for security, so an empty submit means
		// "leave it as-is", not "clear it".
		if ( ! empty( $_POST['twilio_auth_token'] ) ) {
			update_option( 'grc_whatsapp_twilio_auth_token', sanitize_text_field( wp_unslash( $_POST['twilio_auth_token'] ) ) );
		}
		update_option( 'grc_whatsapp_twilio_from', sanitize_text_field( $_POST['twilio_from'] ?? '' ) );

		self::audit_log( 'settings', 0, 'whatsapp_settings_updated', array(
			'provider' => sanitize_text_field( $_POST['whatsapp_provider'] ?? 'none' ),
		) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-settings&whatsapp_saved=1' ) );
		exit;
	}

	/**
	 * Explicit, confirmed reset of all plugin tables. Only for dev/testing.
	 * Requires a checked confirmation checkbox in the settings screen form,
	 * on top of the capability check and nonce - three separate guards
	 * against ever nuking production data by accident.
	 */
	public static function handle_reset_database() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_reset_test_data' );

		if ( empty( $_POST['confirm_reset'] ) || 'yes' !== $_POST['confirm_reset'] ) {
			wp_die( 'Reset not confirmed.' );
		}

		GRC_Deactivator::drop_all_tables();
		GRC_Activator::create_tables();

		wp_safe_redirect( admin_url( 'admin.php?page=grc-settings&reset=1' ) );
		exit;
	}

	/**
	 * Updates a lead's status. When moved to 'completed', fires
	 * grc_lead_marked_completed so GRC_Commissions can calculate the
	 * configured tier split - kept as a hook rather than a direct call so other
	 * things (e.g. a future "project completed" customer notification)
	 * can also listen without this method needing to know about them.
	 */
	public static function handle_update_lead_status() {
		if ( ! current_user_can( 'grc_manage_leads' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_update_lead_status' );

		global $wpdb;
		$lead_id = absint( $_POST['lead_id'] ?? 0 );
		$new_status = sanitize_text_field( $_POST['status'] ?? '' );
		$allowed = array( 'new', 'sent_to_partner', 'accepted', 'in_progress', 'completed', 'lost', 'stale' );

		if ( ! $lead_id || ! in_array( $new_status, $allowed, true ) ) {
			wp_die( 'Invalid request.' );
		}

		$table = GRC_DB::table( 'leads' );
		$wpdb->update( $table, array(
			'status'                 => $new_status,
			'last_partner_update_at' => current_time( 'mysql' ),
			'updated_at'             => current_time( 'mysql' ),
		), array( 'id' => $lead_id ) );

		self::audit_log( 'lead', $lead_id, 'status_changed', array( 'new_status' => $new_status ) );

		if ( 'completed' === $new_status ) {
			do_action( 'grc_lead_marked_completed', $lead_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=grc-leads&updated=1' ) );
		exit;
	}

	/**
	 * Marks a homeowner cash-back payout as actually sent. Only a row in
	 * 'claimed' status can be marked paid - the homeowner has to have
	 * submitted a payout method first, otherwise there's nowhere to have
	 * sent money to. payout_reference is optional free text (e.g. a
	 * PayPal transaction ID) for the admin's own record-keeping.
	 */
	public static function handle_mark_customer_payout_paid() {
		if ( ! current_user_can( 'grc_manage_commissions' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_mark_customer_payout_paid' );

		global $wpdb;
		$payout_id = absint( $_POST['payout_id'] ?? 0 );
		$table     = GRC_DB::table( 'customer_payouts' );

		$payout = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $payout_id ) );
		if ( ! $payout || 'claimed' !== $payout->status ) {
			wp_die( 'This payout can\'t be marked paid yet - the homeowner hasn\'t submitted a payout method.' );
		}

		$reference = sanitize_text_field( $_POST['payout_reference'] ?? '' );
		$wpdb->update( $table, array(
			'status'           => 'paid',
			'payout_reference' => $reference,
			'paid_at'          => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		), array( 'id' => $payout_id ) );

		$leads_table = GRC_DB::table( 'leads' );
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$leads_table} WHERE id = %d", $payout->lead_id ) );
		if ( $lead && ! empty( $lead->customer_email ) ) {
			GRC_Notifications::send( 'customer_payout_sent', 'customer', $lead->customer_email, 'email', array(
				'customer_name'  => $lead->customer_name,
				'amount'         => number_format( (float) $payout->amount, 2 ),
				'payment_method' => ucfirst( $payout->payout_method ?: 'the method you provided' ),
			), $payout->lead_id );
		}

		self::audit_log( 'customer_payout', $payout_id, 'marked_paid', array( 'reference' => $reference ) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-payouts&paid=1' ) );
		exit;
	}

	/**
	 * Marks an agent commission as paid - this is the one piece of the
	 * agent payout flow that had no admin action at all before (the
	 * commissions table's 'paid' status was write-only from nowhere).
	 */
	public static function handle_mark_commission_paid() {
		if ( ! current_user_can( 'grc_manage_commissions' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_mark_commission_paid' );

		global $wpdb;
		$commission_id = absint( $_POST['commission_id'] ?? 0 );
		$table         = GRC_DB::table( 'commissions' );

		$commission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $commission_id ) );
		if ( ! $commission || 'ready' !== $commission->status ) {
			wp_die( 'This commission isn\'t ready to be marked paid.' );
		}

		$reference = sanitize_text_field( $_POST['payout_reference'] ?? '' );
		$wpdb->update( $table, array(
			'status'           => 'paid',
			'payout_reference' => $reference,
			'paid_at'          => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		), array( 'id' => $commission_id ) );

		$agents_table = GRC_DB::table( 'agents' );
		$agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$agents_table} WHERE id = %d", $commission->agent_id ) );
		if ( $agent ) {
			$agent_user = get_userdata( $agent->user_id );
			if ( $agent_user ) {
				GRC_Notifications::send( 'payout_sent', 'agent', $agent_user->user_email, 'email', array(
					'agent_name'     => $agent_user->display_name,
					'amount'         => number_format( (float) $commission->amount, 2 ),
					'payment_method' => ucfirst( $agent->payment_method ?: 'the method on file' ),
				), $commission->lead_id );
			}
		}

		self::audit_log( 'commission', $commission_id, 'marked_paid', array( 'reference' => $reference ) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-payouts&paid=1' ) );
		exit;
	}

	public static function audit_log( $object_type, $object_id, $action, $details = array() ) {
		global $wpdb;
		$wpdb->insert(
			GRC_DB::table( 'audit_log' ),
			array(
				'user_id'    => get_current_user_id(),
				'object_type'=> $object_type,
				'object_id'  => $object_id,
				'action'     => $action,
				'details'    => wp_json_encode( $details ),
				'created_at' => current_time( 'mysql' ),
			)
		);
	}
}

GRC_Admin::init();
