<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$table = GRC_DB::table( 'audit_log' );
$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
?>
<div class="wrap">
	<h1>Audit Log</h1>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>When</th><th>User</th><th>Object</th><th>Action</th><th>Details</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="5">No changes logged yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $r ) :
				$user = $r->user_id ? get_userdata( $r->user_id ) : null;
			?>
				<tr>
					<td><?php echo esc_html( $r->created_at ); ?></td>
					<td><?php echo esc_html( $user ? $user->display_name : 'System' ); ?></td>
					<td><?php echo esc_html( ucfirst( $r->object_type ) . ' #' . $r->object_id ); ?></td>
					<td><?php echo esc_html( ucfirst( $r->action ) ); ?></td>
					<td><code style="font-size:11px;"><?php echo esc_html( wp_trim_words( $r->details, 12 ) ); ?></code></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
