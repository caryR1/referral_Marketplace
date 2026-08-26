<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$split = GRC_Commissions::get_split();
$tier1_pct = round( ( $split[1] ?? 0 ) * 100, 2 );
$tier2_pct = round( ( $split[2] ?? 0 ) * 100, 2 );
$tier3_pct = round( ( $split[3] ?? 0 ) * 100, 2 );

$whatsapp_provider = get_option( 'grc_whatsapp_provider', 'none' );
$twilio_sid        = get_option( 'grc_whatsapp_twilio_sid', '' );
$twilio_from       = get_option( 'grc_whatsapp_twilio_from', '' );
$has_twilio_token  = (bool) get_option( 'grc_whatsapp_twilio_auth_token', '' );
?>
<div class="wrap">
	<h1>Settings</h1>

	<?php if ( ! empty( $_GET['reset'] ) ) : ?>
		<div class="notice notice-success"><p>All plugin tables were reset. Fresh empty schema is in place.</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['split_saved'] ) ) : ?>
		<div class="notice notice-success"><p>Commission split saved.</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['whatsapp_saved'] ) ) : ?>
		<div class="notice notice-success"><p>WhatsApp settings saved.</p></div>
	<?php endif; ?>

	<h2>Plugin Info</h2>
	<p>Version: <?php echo esc_html( GRC_VERSION ); ?> &middot; DB schema version: <?php echo esc_html( get_option( 'grc_db_version', 'unknown' ) ); ?></p>

	<hr>

	<h2>Commission Split</h2>
	<p class="description">Percent of a lead's payout each tier of the MLM chain earns when a lead is marked completed. Tier 1 is the agent who referred the lead directly; tiers 2 and 3 are their upline sponsors. Must add up to 100.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_commission_split' ); ?>
		<input type="hidden" name="action" value="grc_save_commission_split">
		<table class="form-table">
			<tr>
				<th><label for="tier1_pct">Tier 1 (direct agent) %</label></th>
				<td><input type="number" step="0.01" min="0" max="100" id="tier1_pct" name="tier1_pct" value="<?php echo esc_attr( $tier1_pct ); ?>" required> %</td>
			</tr>
			<tr>
				<th><label for="tier2_pct">Tier 2 (sponsor) %</label></th>
				<td><input type="number" step="0.01" min="0" max="100" id="tier2_pct" name="tier2_pct" value="<?php echo esc_attr( $tier2_pct ); ?>" required> %</td>
			</tr>
			<tr>
				<th><label for="tier3_pct">Tier 3 (sponsor's sponsor) %</label></th>
				<td><input type="number" step="0.01" min="0" max="100" id="tier3_pct" name="tier3_pct" value="<?php echo esc_attr( $tier3_pct ); ?>" required> %</td>
			</tr>
		</table>
		<?php submit_button( 'Save Commission Split' ); ?>
	</form>

	<hr>

	<h2>WhatsApp Notifications</h2>
	<p class="description">Until a provider is selected and configured here, WhatsApp sends are logged in the Notification Log as "failed" but never actually delivered (email notifications are unaffected).</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_whatsapp_settings' ); ?>
		<input type="hidden" name="action" value="grc_save_whatsapp_settings">
		<table class="form-table">
			<tr>
				<th><label for="whatsapp_provider">Provider</label></th>
				<td>
					<select id="whatsapp_provider" name="whatsapp_provider">
						<option value="none" <?php selected( $whatsapp_provider, 'none' ); ?>>None (stub only)</option>
						<option value="twilio" <?php selected( $whatsapp_provider, 'twilio' ); ?>>Twilio</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="twilio_sid">Twilio Account SID</label></th>
				<td><input type="text" id="twilio_sid" name="twilio_sid" class="regular-text" value="<?php echo esc_attr( $twilio_sid ); ?>"></td>
			</tr>
			<tr>
				<th><label for="twilio_auth_token">Twilio Auth Token</label></th>
				<td>
					<input type="password" id="twilio_auth_token" name="twilio_auth_token" class="regular-text" placeholder="<?php echo $has_twilio_token ? esc_attr( '•••••••• (saved — leave blank to keep)' ) : ''; ?>">
				</td>
			</tr>
			<tr>
				<th><label for="twilio_from">Twilio WhatsApp From Number</label></th>
				<td><input type="text" id="twilio_from" name="twilio_from" value="<?php echo esc_attr( $twilio_from ); ?>" placeholder="+14155238886"></td>
			</tr>
		</table>
		<?php submit_button( 'Save WhatsApp Settings' ); ?>
	</form>

	<hr>

	<h2 style="color:#a00;">Danger Zone — Reset Test Data</h2>
	<p>This permanently deletes ALL partners, leads, agents, commissions, campaigns, and notification/audit history, then rebuilds empty tables. Use only while testing, never in production.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('This will permanently delete ALL plugin data. Are you absolutely sure?');">
		<?php wp_nonce_field( 'grc_reset_test_data' ); ?>
		<input type="hidden" name="action" value="grc_reset_test_data">
		<label>
			<input type="checkbox" name="confirm_reset" value="yes" required>
			I understand this deletes all data and cannot be undone.
		</label>
		<p><button type="submit" class="button button-secondary" style="color:#a00; border-color:#a00;">Reset All Plugin Data</button></p>
	</form>
</div>
