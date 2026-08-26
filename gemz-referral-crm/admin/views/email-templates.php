<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = GRC_DB::table( 'email_templates' );
$saved_rows = $wpdb->get_results( "SELECT * FROM {$table}", OBJECT_K );
// re-key by event_key for lookup
$saved = array();
foreach ( $saved_rows as $row ) {
	$saved[ $row->event_key ] = $row;
}

$labels    = GRC_Email_Templates::event_labels();
$defaults  = GRC_Notifications::default_templates();
$editing   = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : ( array_key_first( $labels ) );
?>
<div class="wrap">
	<h1>Email Templates</h1>
	<p class="description">Customize the subject and body sent for each notification event. Use <code>{{variable}}</code> placeholders - they're filled in automatically when the email actually sends. Any event you haven't edited uses the built-in default copy shown below.</p>

	<?php if ( ! empty( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p>Template saved for "<?php echo esc_html( $labels[ sanitize_key( $_GET['saved'] ) ] ?? '' ); ?>".</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['reset'] ) ) : ?>
		<div class="notice notice-success"><p>Reverted "<?php echo esc_html( $labels[ sanitize_key( $_GET['reset'] ) ] ?? '' ); ?>" to the default template.</p></div>
	<?php endif; ?>

<?php if ( ! empty( $_GET['test'] ) ) : ?>
		<div class="notice <?php echo '1' === $_GET['test'] ? 'notice-success' : 'notice-error'; ?>">
			<p><?php echo '1' === $_GET['test'] ? 'Test email sent to your account email.' : "Test email failed to send \xe2\x80\x94 check your site's mail configuration."; ?></p>
		</div>
	<?php endif; ?>

	<div style="display:flex; gap:24px; margin-top:20px; align-items:flex-start;">
		<div style="min-width:260px;">
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Event</th><th>Status</th></tr></thead>
				<tbody>
					<?php foreach ( $labels as $key => $label ) : ?>
						<tr <?php echo $key === $editing ? 'style="background:#eef3ff;"' : ''; ?>>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-email-templates&edit=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a></td>
							<td><?php echo isset( $saved[ $key ] ) ? '<strong>Customized</strong>' : '<span style="color:#888;">Default</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div style="flex:1; min-width:320px;">
			<?php
			$current    = $saved[ $editing ] ?? null;
			$subject    = $current->subject ?? $defaults[ $editing ]['subject'] ?? '';
			$body       = $current->body ?? $defaults[ $editing ]['body'] ?? '';
			$variables  = GRC_Email_Templates::variables_for( $editing );
			$sample     = GRC_Email_Templates::sample_data_for( $editing );
			$preview_subject = GRC_Email_Templates::render_preview( $subject, $sample );
			$preview_body    = GRC_Email_Templates::render_preview( $body, $sample );
			?>
			<h2><?php echo esc_html( $labels[ $editing ] ?? $editing ); ?></h2>

			<p class="description">Available variables for this event:
				<?php foreach ( $variables as $v ) : ?>
					<code style="margin-right:6px;">{{<?php echo esc_html( $v ); ?>}}</code>
				<?php endforeach; ?>
			</p>

			<div style="display:flex; gap:24px; flex-wrap:wrap;">
				<div style="flex:1; min-width:280px;">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="grc-template-form">
						<?php wp_nonce_field( 'grc_save_email_template' ); ?>
						<input type="hidden" name="action" value="grc_save_email_template">
						<input type="hidden" name="event_key" value="<?php echo esc_attr( $editing ); ?>">

						<table class="form-table">
							<tr>
								<th><label for="subject">Subject</label></th>
								<td><input type="text" id="subject" name="subject" class="large-text" value="<?php echo esc_attr( $subject ); ?>"></td>
							</tr>
							<tr>
								<th><label for="body">Body</label></th>
								<td><textarea id="body" name="body" class="large-text" rows="10"><?php echo esc_textarea( $body ); ?></textarea></td>
							</tr>
						</table>
						<?php submit_button( 'Save Template', 'primary', 'submit', false ); ?>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-left:8px;" id="grc-test-form">
						<?php wp_nonce_field( 'grc_send_test_email' ); ?>
						<input type="hidden" name="action" value="grc_send_test_email">
						<input type="hidden" name="event_key" value="<?php echo esc_attr( $editing ); ?>">
						<input type="hidden" name="subject" id="grc-test-subject" value="<?php echo esc_attr( $subject ); ?>">
						<input type="hidden" name="body" id="grc-test-body" value="<?php echo esc_attr( $body ); ?>">
						<button type="submit" class="button">Send Test Email to Me</button>
					</form>

					<?php if ( $current ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Revert this event to the default template?');" style="margin-top:16px;">
							<?php wp_nonce_field( 'grc_reset_email_template' ); ?>
							<input type="hidden" name="action" value="grc_reset_email_template">
							<input type="hidden" name="event_key" value="<?php echo esc_attr( $editing ); ?>">
							<button type="submit" class="button button-secondary">Reset to Default</button>
						</form>
					<?php endif; ?>
				</div>

				<div style="flex:1; min-width:280px;">
					<h3 style="margin-top:0;">Live Preview</h3>
					<p class="description">Updates as you type, using sample data.</p>
					<div style="border:1px solid #dcdcde; border-radius:6px; background:#fff; overflow:hidden;">
						<div style="background:#f6f7f7; border-bottom:1px solid #dcdcde; padding:10px 14px; font-weight:600;" id="grc-preview-subject"><?php echo esc_html( $preview_subject ); ?></div>
						<div style="padding:14px; white-space:pre-wrap; font-size:13px; line-height:1.6;" id="grc-preview-body"><?php echo esc_html( $preview_body ); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
	(function() {
		var sampleData = <?php echo wp_json_encode( $sample ); ?>;
		function render(text) {
			return text.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function(match, key) {
				return sampleData.hasOwnProperty(key) ? sampleData[key] : match;
			});
		}
		var subjectInput = document.getElementById('subject');
		var bodyInput = document.getElementById('body');
		var previewSubject = document.getElementById('grc-preview-subject');
		var previewBody = document.getElementById('grc-preview-body');
		var testSubject = document.getElementById('grc-test-subject');
		var testBody = document.getElementById('grc-test-body');

		function update() {
			previewSubject.textContent = render(subjectInput.value);
			previewBody.textContent = render(bodyInput.value);
			testSubject.value = subjectInput.value;
			testBody.value = bodyInput.value;
		}
		subjectInput.addEventListener('input', update);
		bodyInput.addEventListener('input', update);
	})();
	</script>
</div>
