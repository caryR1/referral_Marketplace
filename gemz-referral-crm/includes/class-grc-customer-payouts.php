<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Homeowner cash-back: separate from GRC_Commissions (which pays agents).
 * Every landing page promises the customer cash back once their project
 * is complete - this is what actually tracks and pays that out. Fires
 * off the same grc_lead_marked_completed hook GRC_Commissions uses, so
 * both run whenever a lead is marked completed, independent of whether
 * an agent was involved at all.
 */
class GRC_Customer_Payouts {

	public static function init() {
		add_action( 'grc_lead_marked_completed', array( __CLASS__, 'calculate_for_lead' ), 10, 1 );
	}

	/**
	 * Creates the payout row (status 'ready') and emails the homeowner
	 * their claim link. Skipped entirely if the partner has no configured
	 * customer_cashback_amount (no row, nothing to claim) - mirrors how
	 * GRC_Commissions skips leads with no agent credited.
	 */
	public static function calculate_for_lead( $lead_id ) {
		global $wpdb;

		$leads_table    = GRC_DB::table( 'leads' );
		$partners_table = GRC_DB::table( 'partners' );
		$payouts_table  = GRC_DB::table( 'customer_payouts' );

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$leads_table} WHERE id = %d", $lead_id ) );
		if ( ! $lead || empty( $lead->customer_email ) ) {
			return; // no way to reach the customer with a claim link
		}

		// Don't double-create if this lead was already processed.
		$already = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$payouts_table} WHERE lead_id = %d", $lead_id
		) );
		if ( $already > 0 ) {
			return;
		}

		$partner = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$partners_table} WHERE id = %d", $lead->partner_id ) );
		$amount  = $partner ? (float) $partner->customer_cashback_amount : 0.0;
		if ( $amount <= 0 ) {
			return; // this partner doesn't fund a homeowner cash-back pool
		}

		$token = self::generate_unique_token();
		$now   = current_time( 'mysql' );

		$wpdb->insert( $payouts_table, array(
			'lead_id'      => $lead_id,
			'amount'       => $amount,
			'status'       => 'ready',
			'claim_token'  => $token,
			'created_at'   => $now,
			'updated_at'   => $now,
		) );

		$claim_url = add_query_arg( 'token', $token, home_url( '/claim-cashback/' ) );

		GRC_Notifications::send( 'customer_cashback_ready', 'customer', $lead->customer_email, 'email', array(
			'customer_name' => $lead->customer_name,
			'amount'        => number_format( $amount, 2 ),
			'claim_url'     => $claim_url,
		), $lead_id );

		if ( class_exists( 'GRC_Admin' ) ) {
			GRC_Admin::audit_log( 'lead', $lead_id, 'customer_cashback_created', array( 'amount' => $amount ) );
		}
	}

	private static function generate_unique_token() {
		global $wpdb;
		$table = GRC_DB::table( 'customer_payouts' );
		do {
			$token = bin2hex( random_bytes( 24 ) );
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE claim_token = %s", $token ) );
		} while ( $exists );
		return $token;
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		$table = GRC_DB::table( 'customer_payouts' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE claim_token = %s", $token ) );
	}
}

GRC_Customer_Payouts::init();
