<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
	<h1>Settings</h1>

	<?php if ( ! empty( $_GET['reset'] ) ) : ?>
		<div class="notice notice-success"><p>All plugin tables were reset. Fresh empty schema is in place.</p></div>
	<?php endif; ?>

	<h2>Plugin Info</h2>
	<p>Version: <?php echo esc_html( GRC_VERSION ); ?> &middot; DB schema version: <?php echo esc_html( get_option( 'grc_db_version', 'unknown' ) ); ?></p>

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
