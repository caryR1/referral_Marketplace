<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$leads_table    = GRC_DB::table( 'leads' );
$partners_table = GRC_DB::table( 'partners' );
$agents_table   = GRC_DB::table( 'agents' );

$leads = $wpdb->get_results( "
	SELECT l.*, p.name AS partner_name, a.referral_code
	FROM {$leads_table} l
	LEFT JOIN {$partners_table} p ON p.id = l.partner_id
	LEFT JOIN {$agents_table} a ON a.id = l.agent_id
	ORDER BY l.created_at DESC
	LIMIT 200
" );

$statuses = array( 'new', 'sent_to_partner', 'accepted', 'in_progress', 'completed', 'lost', 'stale' );
?>
<div class="wrap">
	<h1>Leads</h1>

	<?php if ( ! empty( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success"><p>Lead status updated.</p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Customer</th><th>Partner</th><th>Agent</th><th>Status</th><th>Appointment</th><th>Created</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $leads ) ) : ?>
				<tr><td colspan="6">No leads yet. Leads will appear here as they come in through campaign landing pages.</td></tr>
			<?php endif; ?>
			<?php foreach ( $leads as $l ) : ?>
				<tr>
					<td><?php echo esc_html( $l->customer_name ); ?><br><small><?php echo esc_html( $l->customer_phone ); ?></small></td>
					<td><?php echo esc_html( $l->partner_name ?: '—' ); ?></td>
					<td><?php echo esc_html( $l->referral_code ?: 'Direct' ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:6px; align-items:center;">
							<?php wp_nonce_field( 'grc_update_lead_status' ); ?>
							<input type="hidden" name="action" value="grc_update_lead_status">
							<input type="hidden" name="lead_id" value="<?php echo esc_attr( $l->id ); ?>">
							<select name="status" onchange="this.form.submit()">
								<?php foreach ( $statuses as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $l->status, $s ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $s ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
							<noscript><button type="submit" class="button">Update</button></noscript>
						</form>
					</td>
					<td><?php echo esc_html( $l->appointment_primary ?: '—' ); ?></td>
					<td><?php echo esc_html( $l->created_at ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
