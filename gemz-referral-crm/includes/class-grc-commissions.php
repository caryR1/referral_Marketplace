<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Multi-level commission split: tier 1 = direct agent, tier 2 = their
 * sponsor, tier 3 = sponsor's sponsor. Percentages are configurable from
 * Referral CRM -> Settings (stored in the grc_commission_split option);
 * DEFAULT_SPLIT below is only the fallback for a fresh install.
 *
 * If a tier has no agent above it in the chain (e.g. a tier-1 agent
 * with no sponsor at all), that tier's share is simply not created as
 * a commission row - it is not reassigned or paid to anyone else. This
 * is a deliberate default, not a stated requirement - flag it to the
 * user if it ever matters, since "what happens to an orphaned tier's
 * share" is a business-logic choice, not something they've specified.
 */
class GRC_Commissions {

	const DEFAULT_SPLIT = array(
		1 => 0.70,
		2 => 0.20,
		3 => 0.10,
	);

	public static function init() {
		add_action( 'grc_lead_marked_completed', array( __CLASS__, 'calculate_for_lead' ), 10, 1 );
	}

	/**
	 * Reads the configured split from options, falling back to
	 * DEFAULT_SPLIT if it's missing or malformed (e.g. before Settings
	 * has ever been saved).
	 */
	public static function get_split() {
		$saved = get_option( 'grc_commission_split', array() );
		if ( ! is_array( $saved ) || empty( $saved[1] ) ) {
			return self::DEFAULT_SPLIT;
		}

		$split = array();
		foreach ( array( 1, 2, 3 ) as $tier ) {
			$split[ $tier ] = isset( $saved[ $tier ] ) ? (float) $saved[ $tier ] : 0.0;
		}
		return $split;
	}

	/**
	 * Walks the sponsor chain for the lead's agent and creates commission
	 * rows (status 'owed') for each tier that has an agent present.
	 * Amount basis is the partner's flat payout_amount for that lead.
	 * (Percentage/tiered partner payout types aren't handled yet - flat
	 * only for now; flag before relying on this for a percentage-type
	 * partner.)
	 */
	public static function calculate_for_lead( $lead_id ) {
		global $wpdb;

		$leads_table    = GRC_DB::table( 'leads' );
		$partners_table = GRC_DB::table( 'partners' );
		$agents_table   = GRC_DB::table( 'agents' );
		$commissions_table = GRC_DB::table( 'commissions' );

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$leads_table} WHERE id = %d", $lead_id ) );
		if ( ! $lead || empty( $lead->agent_id ) ) {
			return; // no agent credited - nothing to split
		}

		// Don't double-create commissions if this lead was already processed.
		$already = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$commissions_table} WHERE lead_id = %d", $lead_id
		) );
		if ( $already > 0 ) {
			return;
		}

		$partner = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$partners_table} WHERE id = %d", $lead->partner_id ) );
		if ( ! $partner ) {
			return;
		}
		$base_amount = (float) $partner->payout_amount;

		// Walk the chain: tier 1 = lead's agent, tier 2 = their sponsor, tier 3 = sponsor's sponsor.
		$chain = array();
		$current_agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$agents_table} WHERE id = %d", $lead->agent_id ) );
		$tier = 1;
		while ( $current_agent && $tier <= 3 ) {
			$chain[ $tier ] = $current_agent;
			if ( empty( $current_agent->sponsor_agent_id ) ) {
				break;
			}
			$current_agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$agents_table} WHERE id = %d", $current_agent->sponsor_agent_id ) );
			$tier++;
		}

		$direct_agent = $chain[1] ?? null;
		if ( $direct_agent ) {
			$direct_agent_user = get_userdata( $direct_agent->user_id );
			if ( $direct_agent_user ) {
				GRC_Notifications::send( 'project_completed', 'agent', $direct_agent_user->user_email, 'email', array(
					'agent_name'    => $direct_agent_user->display_name,
					'customer_name' => $lead->customer_name,
				), $lead_id );
			}
		}

		$split = self::get_split();
		$now   = current_time( 'mysql' );
		foreach ( $chain as $tier_num => $agent ) {
			$pct    = $split[ $tier_num ] ?? 0.0;
			$amount = round( $base_amount * $pct, 2 );

			$wpdb->insert( $commissions_table, array(
				'lead_id'    => $lead_id,
				'agent_id'   => $agent->id,
				'tier'       => $tier_num,
				'amount'     => $amount,
				'status'     => 'ready', // ready to pay as soon as lead completed
				'created_at' => $now,
				'updated_at' => $now,
			) );

			$agent_user = get_userdata( $agent->user_id );
			if ( $agent_user ) {
				GRC_Notifications::send( 'payout_ready', 'agent', $agent_user->user_email, 'email', array(
					'agent_name'    => $agent_user->display_name,
					'customer_name' => $lead->customer_name,
					'amount'        => number_format( $amount, 2 ),
					'tier'          => $tier_num,
				), $lead_id );
			}
		}

		GRC_Admin::audit_log( 'lead', $lead_id, 'commissions_calculated', array(
			'base_amount' => $base_amount,
			'tiers'       => array_keys( $chain ),
		) );
	}
}

GRC_Commissions::init();
