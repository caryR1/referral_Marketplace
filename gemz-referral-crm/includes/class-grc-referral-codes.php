<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generates and resolves unique agent referral codes.
 * A code looks like "AG-7F3K2Q" - short enough to fit in a text message,
 * unambiguous enough (no 0/O/1/I) to read off a flyer.
 */
class GRC_Referral_Codes {

	private static $safe_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0,O,1,I

	public static function generate_unique_code() {
		global $wpdb;
		$table = GRC_DB::table( 'agents' );
		do {
			$code = 'AG-' . self::random_string( 6 );
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE referral_code = %s", $code
			) );
		} while ( $exists );
		return $code;
	}

	private static function random_string( $length ) {
		$out = '';
		$max = strlen( self::$safe_chars ) - 1;
		for ( $i = 0; $i < $length; $i++ ) {
			$out .= self::$safe_chars[ random_int( 0, $max ) ];
		}
		return $out;
	}

	/**
	 * Look up the agent row for a given referral code, or null.
	 */
	public static function get_agent_by_code( $code ) {
		global $wpdb;
		$table = GRC_DB::table( 'agents' );
		$code  = sanitize_text_field( $code );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE referral_code = %s", $code
		) );
	}

	/**
	 * Builds the ready-to-send tracking link for a campaign + agent.
	 * e.g. https://refer.gemzonline.com/go/roofing-tc-storm?ref=AG-7F3K2Q
	 */
	public static function build_campaign_link( $tracking_slug, $referral_code ) {
		$base = home_url( '/go/' . $tracking_slug );
		return add_query_arg( 'ref', $referral_code, $base );
	}
}
