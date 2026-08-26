<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$agents_table = GRC_DB::table( 'agents' );
$leads_table  = GRC_DB::table( 'leads' );

$agents = $wpdb->get_results( "
	SELECT a.*,
		(SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = a.id) AS leads_sent,
		(SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = a.id AND status = 'completed') AS leads_converted
	FROM {$agents_table} a
	ORDER BY a.created_at DESC
" );
?>
<div class="wrap">
	<h1>Agents</h1>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Referral Code</th><th>WP User</th><th>Leads Sent</th><th>Converted</th><th>Conversion Rate</th><th>Status</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $agents ) ) : ?>
				<tr><td colspan="6">No agents yet.</td></tr>
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
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
