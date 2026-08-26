<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$table = GRC_DB::table( 'notifications_log' );
$labels = GRC_Email_Templates::event_labels();

$filter_status = sanitize_key( $_GET['status'] ?? '' );
$where = '';
if ( in_array( $filter_status, array( 'sent', 'failed' ), true ) ) {
	$where = $wpdb->prepare( ' WHERE status = %s', $filter_status );
}

$rows = $wpdb->get_results( "SELECT * FROM {$table}{$where} ORDER BY sent_at DESC LIMIT 200" );

$sent_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'sent'" );
$failed_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" );
?>
<div class="wrap">
	<h1>Notification Log</h1>
	<p class="description">Every email (and, once connected, WhatsApp message) the plugin has attempted to send, whether it succeeded or not.</p>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-notification-log' ) ); ?>" class="button <?php echo '' === $filter_status ? 'button-primary' : ''; ?>">All</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-notification-log&status=sent' ) ); ?>" class="button <?php echo 'sent' === $filter_status ? 'button-primary' : ''; ?>">Sent (<?php echo esc_html( $sent_count ); ?>)</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-notification-log&status=failed' ) ); ?>" class="button <?php echo 'failed' === $filter_status ? 'button-primary' : ''; ?>">Failed (<?php echo esc_html( $failed_count ); ?>)</a>
	</p>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>When</th><th>Event</th><th>Recipient</th><th>Channel</th><th>Related Lead</th><th>Status</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="6">No notifications logged yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r->sent_at ); ?></td>
					<td><?php echo esc_html( $labels[ $r->event_key ] ?? $r->event_key ); ?></td>
					<td><?php echo esc_html( $r->recipient_ref ); ?> <span style="color:#888;">(<?php echo esc_html( $r->recipient_type ); ?>)</span></td>
					<td><?php echo esc_html( ucfirst( $r->channel ) ); ?></td>
					<td><?php echo $r->related_lead_id ? esc_html( '#' . $r->related_lead_id ) : '&mdash;'; ?></td>
					<td>
						<?php if ( 'sent' === $r->status ) : ?>
							<span style="color:#1a7a4c; font-weight:600;">Sent</span>
						<?php else : ?>
							<span style="color:#c0392b; font-weight:600;">Failed</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
