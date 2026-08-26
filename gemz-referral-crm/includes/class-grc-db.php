<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Central place for table names. Nothing else in the plugin should
 * hardcode a table name string — always call GRC_DB::table('leads') etc.
 * so a prefix change or table rename only has to happen in one place.
 */
class GRC_DB {

	/**
	 * @param string $short_name e.g. 'partners', 'leads', 'agents'
	 * @return string full table name with $wpdb prefix
	 */
	public static function table( $short_name ) {
		global $wpdb;
		return $wpdb->prefix . 'grc_' . $short_name;
	}

	public static function charset_collate() {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}
}
