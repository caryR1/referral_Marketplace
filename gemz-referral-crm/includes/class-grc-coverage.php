<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Searches active partners/campaigns by industry + location, without
 * ever exposing which company fulfills the offer (front-end principle
 * from the concept phase: offers are shown by industry/area, partner
 * identity stays internal).
 *
 * partners.service_areas is stored as JSON: an array of
 * {"state":"FL","city":"Fort Pierce","zip":"34950"} entries. A partner
 * can list multiple entries to cover multiple areas. Matching is
 * deliberately loose: a zip match wins outright; otherwise city+state
 * or state-only entries also count, so a partner who only bothered to
 * enter "FL" still shows up for any FL zip.
 */
class GRC_Coverage {

	public static function search( $industry, $zip = '', $city = '', $state = '' ) {
		global $wpdb;
		$partners_table  = GRC_DB::table( 'partners' );
		$campaigns_table = GRC_DB::table( 'campaigns' );

		$partners = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$partners_table} WHERE industry = %s AND status = 'active'", $industry
		) );

		$matching_partner_ids = array();
		foreach ( $partners as $partner ) {
			$areas = json_decode( $partner->service_areas, true );
			if ( empty( $areas ) || ! is_array( $areas ) ) {
				continue;
			}
			foreach ( $areas as $area ) {
				$area_zip   = isset( $area['zip'] ) ? trim( $area['zip'] ) : '';
				$area_city  = isset( $area['city'] ) ? strtolower( trim( $area['city'] ) ) : '';
				$area_state = isset( $area['state'] ) ? strtolower( trim( $area['state'] ) ) : '';

				$zip_match   = $zip && $area_zip && $area_zip === $zip;
				$city_match  = $city && $area_city && $area_city === strtolower( $city ) && ( ! $state || ! $area_state || $area_state === strtolower( $state ) );
				$state_only  = $state && $area_state && $area_state === strtolower( $state ) && empty( $area_city ) && empty( $area_zip );

				if ( $zip_match || $city_match || $state_only ) {
					$matching_partner_ids[] = $partner->id;
					break;
				}
			}
		}

		if ( empty( $matching_partner_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $matching_partner_ids ), '%d' ) );
		$campaigns = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, name, tracking_slug, partner_id FROM {$campaigns_table}
			 WHERE status = 'active' AND partner_id IN ({$placeholders})",
			...$matching_partner_ids
		) );

		$offers = array();
		foreach ( $campaigns as $c ) {
			$offers[] = array(
				'campaign_id'    => (int) $c->id,
				'name'           => $c->name,
				'tracking_slug'  => $c->tracking_slug,
				'link'           => home_url( '/go/' . $c->tracking_slug ),
			);
		}
		return $offers;
	}
}
