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

$smtp_enabled     = get_option( 'grc_smtp_enabled', '' );
$smtp_host        = get_option( 'grc_smtp_host', '' );
$smtp_port        = get_option( 'grc_smtp_port', '587' );
$smtp_encryption  = get_option( 'grc_smtp_encryption', 'tls' );
$smtp_username    = get_option( 'grc_smtp_username', '' );
$has_smtp_password = (bool) get_option( 'grc_smtp_password', '' );
$smtp_from_email  = get_option( 'grc_smtp_from_email', '' );
$smtp_from_name   = get_option( 'grc_smtp_from_name', 'Gemz' );

$research_industries      = get_option( 'grc_research_industries', array( 'roofing', 'hvac', 'solar', 'windows_doors' ) );
$research_states          = get_option( 'grc_research_states', '' );
$research_min_commission  = get_option( 'grc_research_min_commission', '500' );
$research_radius          = get_option( 'grc_research_radius', '' );
$research_date_range      = get_option( 'grc_research_date_range', '' );
$research_count           = get_option( 'grc_research_count', '1' );
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
	<?php if ( ! empty( $_GET['smtp_saved'] ) ) : ?>
		<div class="notice notice-success"><p>Email (SMTP) settings saved.</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['research_saved'] ) ) : ?>
		<div class="notice notice-success"><p>Partner research defaults saved.</p></div>
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

	<h2>Partner Research</h2>
	<p class="description">
		Finding, verifying, and sourcing real candidate companies requires actual judgment (reading their pages, confirming a real commission program exists) - it isn't something a scheduled PHP job can do on its own, so a research pass is run on request rather than automatically. These are just the default parameters that pass uses; they don't trigger a search by themselves.
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_research_defaults' ); ?>
		<input type="hidden" name="action" value="grc_save_research_defaults">
		<table class="form-table">
			<tr>
				<th>Industries</th>
				<td>
					<?php foreach ( GRC_Industries::all() as $ind => $label ) : ?>
						<label style="display:inline-block; margin:0 14px 6px 0;">
							<input type="checkbox" name="research_industries[]" value="<?php echo esc_attr( $ind ); ?>" <?php checked( in_array( $ind, (array) $research_industries, true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label for="research_states">Target States</label></th>
				<td><input type="text" id="research_states" name="research_states" class="regular-text" value="<?php echo esc_attr( $research_states ); ?>" placeholder="e.g. TX, FL, AZ (blank = nationally, prioritizing strong markets)"></td>
			</tr>
			<tr>
				<th><label for="research_min_commission">Minimum Commission ($)</label></th>
				<td><input type="number" step="1" id="research_min_commission" name="research_min_commission" value="<?php echo esc_attr( $research_min_commission ); ?>"></td>
			</tr>
			<tr>
				<th><label for="research_radius">City/Zip Radius</label></th>
				<td><input type="text" id="research_radius" name="research_radius" value="<?php echo esc_attr( $research_radius ); ?>" placeholder="e.g. 50mi around Dallas, TX (optional)"></td>
			</tr>
			<tr>
				<th><label for="research_date_range">Date Range</label></th>
				<td><input type="text" id="research_date_range" name="research_date_range" value="<?php echo esc_attr( $research_date_range ); ?>" placeholder="e.g. programs updated/verified in the last 12 months (optional)"></td>
			</tr>
			<tr>
				<th><label for="research_count">Companies to Search For (per industry)</label></th>
				<td><input type="number" step="1" min="1" id="research_count" name="research_count" value="<?php echo esc_attr( $research_count ); ?>"></td>
			</tr>
		</table>
		<?php submit_button( 'Save Research Defaults' ); ?>
	</form>

	<hr>

	<h2>Email (SMTP)</h2>
	<p class="description">
		By default, all plugin emails go through PHP's built-in <code>mail()</code>, which on shared hosting is frequently spam-filtered or silently dropped by the receiving mail server (no SPF/DKIM, shared IP reputation). Configuring real SMTP credentials here routes every email through an authenticated mail account instead - this is the fix if customers report never getting their booking confirmation or cash-back claim email.
		Use a mailbox created in your hosting panel (e.g. <code>noreply@refer.gemzonline.com</code>), or credentials from a transactional email provider (SendGrid, Postmark, etc.) if you switch to one later.
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'grc_save_smtp_settings' ); ?>
		<input type="hidden" name="action" value="grc_save_smtp_settings">
		<table class="form-table">
			<tr>
				<th><label for="smtp_enabled">Use SMTP</label></th>
				<td>
					<label><input type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?php checked( $smtp_enabled, '1' ); ?>> Route plugin emails through the SMTP settings below instead of PHP's default mail()</label>
				</td>
			</tr>
			<tr>
				<th><label for="smtp_host">SMTP Host</label></th>
				<td><input type="text" id="smtp_host" name="smtp_host" class="regular-text" value="<?php echo esc_attr( $smtp_host ); ?>" placeholder="smtp.hostinger.com"></td>
			</tr>
			<tr>
				<th><label for="smtp_port">SMTP Port</label></th>
				<td><input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr( $smtp_port ); ?>" placeholder="587"></td>
			</tr>
			<tr>
				<th><label for="smtp_encryption">Encryption</label></th>
				<td>
					<select id="smtp_encryption" name="smtp_encryption">
						<option value="tls" <?php selected( $smtp_encryption, 'tls' ); ?>>TLS (usually port 587)</option>
						<option value="ssl" <?php selected( $smtp_encryption, 'ssl' ); ?>>SSL (usually port 465)</option>
						<option value="none" <?php selected( $smtp_encryption, 'none' ); ?>>None</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="smtp_username">SMTP Username</label></th>
				<td><input type="text" id="smtp_username" name="smtp_username" class="regular-text" value="<?php echo esc_attr( $smtp_username ); ?>" placeholder="noreply@refer.gemzonline.com"></td>
			</tr>
			<tr>
				<th><label for="smtp_password">SMTP Password</label></th>
				<td><input type="password" id="smtp_password" name="smtp_password" class="regular-text" placeholder="<?php echo $has_smtp_password ? esc_attr( '•••••••• (saved — leave blank to keep)' ) : ''; ?>"></td>
			</tr>
			<tr>
				<th><label for="smtp_from_email">From Email</label></th>
				<td><input type="email" id="smtp_from_email" name="smtp_from_email" class="regular-text" value="<?php echo esc_attr( $smtp_from_email ); ?>" placeholder="Usually the same as SMTP Username"></td>
			</tr>
			<tr>
				<th><label for="smtp_from_name">From Name</label></th>
				<td><input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr( $smtp_from_name ); ?>"></td>
			</tr>
		</table>
		<?php submit_button( 'Save Email Settings' ); ?>
	</form>
	<p class="description">After saving, send yourself a test from <a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-email-templates' ) ); ?>">Email Templates</a> to confirm delivery actually works.</p>

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
