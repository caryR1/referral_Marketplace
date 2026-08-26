<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Single source of truth for the industries this platform serves, used by
 * both the admin partner/campaign forms and the public industry browser so
 * adding a new industry (e.g. windows_doors) only means editing this list.
 */
class GRC_Industries {

	public static function all() {
		return array(
			'roofing'       => 'Roofing',
			'hvac'          => 'HVAC',
			'solar'         => 'Solar',
			'plumbing'      => 'Plumbing',
			'remodeling'    => 'Remodeling',
			'windows_doors' => 'Windows & Doors',
		);
	}

	public static function label( $key ) {
		$all = self::all();
		return $all[ $key ] ?? ucfirst( str_replace( '_', ' ', (string) $key ) );
	}
}
