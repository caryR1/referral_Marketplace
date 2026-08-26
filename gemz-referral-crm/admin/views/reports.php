<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$leads_table = GRC_DB::table( 'leads' );
$partners_table = GRC_DB::table( 'partners' );
$commissions_table = GRC_DB::table( 'commissions' );

$partner_outcomes = $wpdb->get_results( "
	SELECT p.name, p.industry,
		COUNT(l.id) AS total_leads,
		SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) AS completed,
		SUM(CASE WHEN l.status = 'lost' THEN 1 ELSE 0 END) AS lost
	FROM {$partners_table} p
	LEFT JOIN {$leads_table} l ON l.partner_id = p.id
	GROUP BY p.id
	ORDER BY total_leads DESC
" );

$commission_summary = $wpdb->get_row( "
	SELECT
		SUM(CASE WHEN status = 'owed' THEN amount ELSE 0 END) AS owed,
		SUM(CASE WHEN status = 'ready' THEN amount ELSE 0 END) AS ready,
		SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid
	FROM {$commissions_table}
" );
?>
<div class="wrap">
	<h1>Reports</h1>

	<h2>Commission Summary</h2>
	<table class="wp-list-table widefat fixed striped" style="max-width:500px;">
		<tbody>
			<tr><th>Owed</th><td>$<?php echo esc_html( number_format( (float) ( $commission_summary->owed ?? 0 ), 2 ) ); ?></td></tr>
			<tr><th>Ready to Pay</th><td>$<?php echo esc_html( number_format( (float) ( $commission_summary->ready ?? 0 ), 2 ) ); ?></td></tr>
			<tr><th>Paid</th><td>$<?php echo esc_html( number_format( (float) ( $commission_summary->paid ?? 0 ), 2 ) ); ?></td></tr>
		</tbody>
	</table>

	<h2 style="margin-top:30px;">Partner Outcomes</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>Partner</th><th>Industry</th><th>Total Leads</th><th>Completed</th><th>Lost</th><th>Close Rate</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $partner_outcomes ) ) : ?>
				<tr><td colspan="6">No data yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $partner_outcomes as $row ) :
				$rate = $row->total_leads > 0 ? round( ( $row->completed / $row->total_leads ) * 100 ) . '%' : '—';
			?>
				<tr>
					<td><?php echo esc_html( $row->name ); ?></td>
					<td><?php echo esc_html( ucfirst( $row->industry ) ); ?></td>
					<td><?php echo esc_html( $row->total_leads ); ?></td>
					<td><?php echo esc_html( $row->completed ); ?></td>
					<td><?php echo esc_html( $row->lost ); ?></td>
					<td><?php echo esc_html( $rate ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
