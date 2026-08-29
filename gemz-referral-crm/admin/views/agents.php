<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$agents_table = GRC_DB::table( 'agents' );
$leads_table  = GRC_DB::table( 'leads' );
$segments_table = GRC_DB::table( 'agent_segments' );

$agents = $wpdb->get_results( "
	SELECT a.*,
		(SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = a.id) AS leads_sent,
		(SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = a.id AND status = 'completed') AS leads_converted
	FROM {$agents_table} a
	ORDER BY a.created_at DESC
" );
$segments = $wpdb->get_results( "SELECT * FROM {$segments_table} ORDER BY name" );
?>
<div class="wrap">
	<h1>Agents</h1>

	<?php if ( ! empty( $_GET['segment_updated'] ) ) : ?>
		<div class="notice notice-success"><p>Segment assignment updated.</p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['status_updated'] ) ) : ?>
		<div class="notice notice-success"><p>Agent status updated.</p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Referral Code</th><th>WP User</th><th>Leads Sent</th><th>Converted</th><th>Conversion Rate</th><th>Status</th><th>Segment</th><th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $agents ) ) : ?>
				<tr><td colspan="8">No agents yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $agents as $a ) :
				$user = get_userdata( $a->user_id );
				$rate = $a->leads_sent > 0 ? round( ( $a->leads_converted / $a->leads_sent ) * 100 ) . '%' : '—';
			?>
				<tr>
					<td><?php echo esc_html( $a->referral_code ); ?></td>
					<td><?php echo esc_html( $user ? $user->display_name : 'Unknown' ); ?></td>
					<td><?php echo esc_html( $a->leads_sent ); ?></td>
					<td><?php echo esc_html( $a->leads_converted ); ?></td>
					<td><?php echo esc_html( $rate ); ?></td>
					<td><?php echo esc_html( ucfirst( $a->status ) ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'grc_assign_agent_segment' ); ?>
							<input type="hidden" name="action" value="grc_assign_agent_segment">
							<input type="hidden" name="agent_id" value="<?php echo esc_attr( $a->id ); ?>">
							<select name="segment_id" onchange="this.form.submit()">
								<option value="">— None —</option>
								<?php foreach ( $segments as $s ) : ?>
									<option value="<?php echo esc_attr( $s->id ); ?>" <?php selected( (int) $a->segment_id, $s->id ); ?>><?php echo esc_html( $s->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<noscript><button type="submit" class="button">Update</button></noscript>
						</form>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo 'active' === $a->status ? 'Suspend' : 'Reactivate'; ?> this agent?');">
							<?php wp_nonce_field( 'grc_toggle_agent_status' ); ?>
							<input type="hidden" name="action" value="grc_toggle_agent_status">
							<input type="hidden" name="agent_id" value="<?php echo esc_attr( $a->id ); ?>">
							<button type="submit" class="button"><?php echo 'active' === $a->status ? 'Suspend' : 'Reactivate'; ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=grc-segments' ) ); ?>">Manage Segments &rarr;</a></p>
</div>
