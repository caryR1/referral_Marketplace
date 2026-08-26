<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Central notification dispatcher. Every other part of the plugin should
 * call GRC_Notifications::send() rather than wp_mail() directly, so every
 * notification is logged and channel routing (email vs WhatsApp) lives
 * in one place.
 *
 * Subject/body come from the email_templates table when an admin has
 * customized that event (see GRC_Email_Templates), otherwise from the
 * built-in defaults below. {{variable}} placeholders in either source
 * are replaced from the $data array passed to send().
 *
 * Event keys currently wired:
 *   agent_signup, appointment_booked, appointment_changed,
 *   lead_accepted_by_partner, milestone_reached, project_completed,
 *   payout_ready, payout_sent, lead_stale, new_lead_for_partner
 */
class GRC_Notifications {

	public static function send( $event_key, $recipient_type, $recipient_ref, $channel, $data = array(), $related_lead_id = null ) {
		$sent = false;

		if ( 'email' === $channel ) {
			$template = self::resolve_template( $event_key );
			$subject  = self::render( $template['subject'], $data );
			$body     = self::render( $template['body'], $data );
			$sent     = wp_mail( $recipient_ref, $subject, $body );
		} elseif ( 'whatsapp' === $channel ) {
			// TODO: wire to WhatsApp Business API / provider (e.g. Twilio, Meta Cloud API)
			// once the account is set up. For now this is a stub that logs intent
			// so the workflow is provably complete end-to-end before the real
			// integration is dropped in.
			$sent = apply_filters( 'grc_send_whatsapp', false, $recipient_ref, $event_key, $data );
		}

		self::log( $event_key, $recipient_type, $recipient_ref, $channel, $related_lead_id, $sent ? 'sent' : 'failed' );
		return $sent;
	}

	/**
	 * Looks up a saved template for this event; falls back to the
	 * built-in default subject/body if the admin hasn't customized it.
	 */
	public static function resolve_template( $event_key ) {
		global $wpdb;
		$table = GRC_DB::table( 'email_templates' );
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT subject, body FROM {$table} WHERE event_key = %s", $event_key
		), ARRAY_A );

		if ( $row ) {
			return $row;
		}

		$defaults = self::default_templates();
		return $defaults[ $event_key ] ?? array(
			'subject' => 'Gemz Referral Update',
			'body'    => '{{message}}',
		);
	}

	/**
	 * Built-in fallback copy for every wired event, used until an admin
	 * customizes it via Referral CRM -> Email Templates. Also the source
	 * of truth for "reset to default" and for seeding the templates
	 * screen the first time it's opened.
	 */
	public static function default_templates() {
		return array(
			'agent_signup' => array(
				'subject' => 'Welcome to the Gemz Referral Program',
				'body'    => "Hi {{agent_name}},\n\nWelcome aboard! Your referral code is {{referral_code}}.\n\nShare your campaign links and start earning.\n\n- The Gemz Team",
			),
			'appointment_booked' => array(
				'subject' => 'Your appointment is set',
				'body'    => "Hi {{customer_name}},\n\nThanks for booking with us. We'll be in touch to confirm your appointment on {{appointment_date}}.\n\n- The Gemz Team",
			),
			'appointment_changed' => array(
				'subject' => 'Your appointment was updated',
				'body'    => "Hi {{customer_name}},\n\nYour appointment has been updated to {{appointment_date}}.\n\n- The Gemz Team",
			),
			'lead_accepted_by_partner' => array(
				'subject' => 'Your referral was accepted',
				'body'    => "Hi {{agent_name}},\n\nGood news - {{customer_name}}'s referral has been accepted and is moving forward.\n\n- The Gemz Team",
			),
			'milestone_reached' => array(
				'subject' => 'Update on your referral',
				'body'    => "Hi {{agent_name}},\n\n{{customer_name}}'s project has reached a new milestone: {{milestone_label}}.\n\n- The Gemz Team",
			),
			'project_completed' => array(
				'subject' => 'Project complete',
				'body'    => "Hi {{agent_name}},\n\n{{customer_name}}'s project is complete. Your commission is being calculated now.\n\n- The Gemz Team",
			),
			'payout_ready' => array(
				'subject' => 'Your commission is ready',
				'body'    => "Hi {{agent_name}},\n\nYour commission of \${{amount}} (tier {{tier}}) for {{customer_name}}'s referral is ready for payout.\n\n- The Gemz Team",
			),
			'payout_sent' => array(
				'subject' => 'Your payout is on its way',
				'body'    => "Hi {{agent_name}},\n\nYour payout of \${{amount}} has been sent via {{payment_method}}.\n\n- The Gemz Team",
			),
			'lead_stale' => array(
				'subject' => 'A lead needs attention',
				'body'    => "Heads up - {{customer_name}}'s lead (with {{partner_name}}) hasn't been updated in a while and may need a follow-up call.",
			),
			'new_lead_for_partner' => array(
				'subject' => 'New referral received',
				'body'    => "You have a new referral: {{customer_name}}, preferred contact {{preferred_contact}}, requested appointment {{appointment_date}}.",
			),
		);
	}

	/**
	 * Replaces {{variable}} placeholders in $text using $data. Any
	 * placeholder with no matching key is left as-is rather than
	 * silently blanked, so a missing variable is obvious in the sent
	 * email instead of invisible.
	 */
	private static function render( $text, $data ) {
		return preg_replace_callback( '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ( $matches ) use ( $data ) {
			$key = $matches[1];
			return array_key_exists( $key, $data ) ? (string) $data[ $key ] : $matches[0];
		}, $text );
	}

	private static function log( $event_key, $recipient_type, $recipient_ref, $channel, $related_lead_id, $status ) {
		global $wpdb;
		$wpdb->insert(
			GRC_DB::table( 'notifications_log' ),
			array(
				'event_key'        => $event_key,
				'recipient_type'   => $recipient_type,
				'recipient_ref'    => $recipient_ref,
				'channel'          => $channel,
				'related_lead_id'  => $related_lead_id,
				'status'           => $status,
				'sent_at'          => current_time( 'mysql' ),
			)
		);
	}
}
