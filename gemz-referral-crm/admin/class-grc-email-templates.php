<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin screen for editing the subject/body of every notification event.
 * Saved rows override the built-in defaults in GRC_Notifications; an
 * event with no saved row just uses the default until someone edits it.
 */
class GRC_Email_Templates {

	/**
	 * Which {{variables}} are actually available for each event, shown
	 * as a reference on the edit screen so admins aren't guessing.
	 * This documents what data each call site passes to
	 * GRC_Notifications::send() - keep in sync if a call site's $data
	 * array changes.
	 */
	public static function variables_for( $event_key ) {
		$common = array( 'customer_name', 'agent_name', 'partner_name' );
		$map = array(
			'agent_signup'             => array( 'agent_name', 'referral_code' ),
			'appointment_booked'       => array( 'customer_name', 'appointment_date' ),
			'appointment_changed'      => array( 'customer_name', 'appointment_date' ),
			'lead_accepted_by_partner' => array( 'agent_name', 'customer_name' ),
			'milestone_reached'        => array( 'agent_name', 'customer_name', 'milestone_label' ),
			'project_completed'       => array( 'agent_name', 'customer_name' ),
			'payout_ready'             => array( 'agent_name', 'customer_name', 'amount', 'tier' ),
			'payout_sent'              => array( 'agent_name', 'amount', 'payment_method' ),
			'lead_stale'               => array( 'customer_name', 'partner_name' ),
			'new_lead_for_partner'     => array( 'customer_name', 'preferred_contact', 'appointment_date' ),
			'customer_cashback_ready'  => array( 'customer_name', 'amount', 'claim_url' ),
			'customer_payout_sent'     => array( 'customer_name', 'amount', 'payment_method' ),
		);
		return $map[ $event_key ] ?? $common;
	}

	public static function event_labels() {
		return array(
			'agent_signup'             => 'Agent signup (welcome email)',
			'appointment_booked'       => 'Customer books an appointment',
			'appointment_changed'      => 'Appointment rescheduled',
			'lead_accepted_by_partner' => 'Lead accepted by partner',
			'milestone_reached'        => 'Project milestone reached',
			'project_completed'       => 'Project completed',
			'payout_ready'             => 'Commission ready to pay',
			'payout_sent'              => 'Payout sent',
			'lead_stale'               => 'Lead gone stale',
			'new_lead_for_partner'     => 'New lead notification to partner',
			'customer_cashback_ready'  => 'Customer cash-back ready to claim',
			'customer_payout_sent'     => 'Customer cash-back sent',
		);
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_save_email_template' );

		global $wpdb;
		$event_key = sanitize_key( $_POST['event_key'] ?? '' );
		$labels    = self::event_labels();
		if ( ! isset( $labels[ $event_key ] ) ) {
			wp_die( 'Unknown event.' );
		}

		$subject = sanitize_text_field( $_POST['subject'] ?? '' );
		$body    = sanitize_textarea_field( $_POST['body'] ?? '' );
		$table   = GRC_DB::table( 'email_templates' );
		$now     = current_time( 'mysql' );

		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE event_key = %s", $event_key ) );
		if ( $existing_id ) {
			$wpdb->update( $table, array( 'subject' => $subject, 'body' => $body, 'updated_at' => $now ), array( 'id' => $existing_id ) );
		} else {
			$wpdb->insert( $table, array( 'event_key' => $event_key, 'subject' => $subject, 'body' => $body, 'updated_at' => $now ) );
		}

		GRC_Admin::audit_log( 'email_template', $existing_id ?: $wpdb->insert_id, 'updated', array( 'event_key' => $event_key ) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-email-templates&saved=' . $event_key ) );
		exit;
	}

	/**
	 * Deletes the saved override, reverting that event back to the
	 * built-in default copy.
	 */
	public static function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_reset_email_template' );

		global $wpdb;
		$event_key = sanitize_key( $_POST['event_key'] ?? '' );
		$table = GRC_DB::table( 'email_templates' );
		$wpdb->delete( $table, array( 'event_key' => $event_key ) );

		GRC_Admin::audit_log( 'email_template', 0, 'reset_to_default', array( 'event_key' => $event_key ) );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-email-templates&reset=' . $event_key ) );
		exit;
	}

	/**
	 * Sends a real test email using the CURRENTLY EDITED (unsaved) subject/body
	 * from the form, with placeholder sample values for every variable that
	 * event supports - so an admin can see exactly what a recipient will get
	 * before saving.
	 */
	public static function handle_send_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'grc_send_test_email' );

		$event_key = sanitize_key( $_POST['event_key'] ?? '' );
		$labels    = self::event_labels();
		if ( ! isset( $labels[ $event_key ] ) ) {
			wp_die( 'Unknown event.' );
		}

		$subject = sanitize_text_field( $_POST['subject'] ?? '' );
		$body    = sanitize_textarea_field( $_POST['body'] ?? '' );

		$sample_data = self::sample_data_for( $event_key );

		$rendered_subject = self::render_preview( $subject, $sample_data );
		$rendered_body    = self::render_preview( $body, $sample_data );

		$current_user = wp_get_current_user();
		$sent = wp_mail( $current_user->user_email, '[TEST] ' . $rendered_subject, $rendered_body );

		wp_safe_redirect( admin_url( 'admin.php?page=grc-email-templates&edit=' . $event_key . '&test=' . ( $sent ? '1' : '0' ) ) );
		exit;
	}

	/**
	 * Same {{variable}} substitution GRC_Notifications uses, duplicated
	 * here (rather than calling the private method there) since this is
	 * a preview of UNSAVED form content, not a real saved template.
	 */
	public static function render_preview( $text, $data ) {
		return preg_replace_callback( '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ( $matches ) use ( $data ) {
			$key = $matches[1];
			return array_key_exists( $key, $data ) ? (string) $data[ $key ] : $matches[0];
		}, $text );
	}

	/**
	 * Realistic placeholder values for every variable an event supports,
	 * used for both the live on-screen preview and the test-email send.
	 */
	public static function sample_data_for( $event_key ) {
		$all_samples = array(
			'customer_name'      => 'Jane Smith',
			'agent_name'         => 'Alex Rivera',
			'partner_name'       => 'Roofs By Rhino',
			'referral_code'      => 'AG-7F3K2Q',
			'appointment_date'   => 'Tuesday, Sept 2 at 10:00 AM',
			'milestone_label'    => 'Permit approved',
			'amount'             => '175.00',
			'tier'               => '1',
			'payment_method'     => 'Wise',
			'preferred_contact'  => 'Phone',
			'claim_url'          => home_url( '/claim-cashback/?token=sample' ),
		);
		$keys = self::variables_for( $event_key );
		$sample = array();
		foreach ( $keys as $k ) {
			$sample[ $k ] = $all_samples[ $k ] ?? '[sample ' . $k . ']';
		}
		return $sample;
	}
}

add_action( 'admin_post_grc_save_email_template', array( 'GRC_Email_Templates', 'handle_save' ) );
add_action( 'admin_post_grc_reset_email_template', array( 'GRC_Email_Templates', 'handle_reset' ) );
add_action( 'admin_post_grc_send_test_email', array( 'GRC_Email_Templates', 'handle_send_test' ) );
