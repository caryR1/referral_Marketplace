<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$payouts_table     = GRC_DB::table( 'customer_payouts' );
$leads_table       = GRC_DB::table( 'leads' );
$commissions_table = GRC_DB::table( 'commissions' );
$agents_table      = GRC_DB::table( 'agents' );

$customer_payouts = $wpdb->get_results( "
	SELECT cp.*, l.customer_name, l.customer_email
	FROM {$payouts_table} cp
	LEFT JOIN {$leads_table} l ON l.id = cp.lead_id
	ORDER BY FIELD(cp.status, 'claimed', 'ready', 'paid'), cp.updated_at DESC
	LIMIT 200
" );

$commissions = $wpdb->get_results( "
	SELECT c.*, l.customer_name, a.referral_code, u.display_name AS agent_name
	FROM {$commissions_table} c
	LEFT JOIN {$leads_table} l ON l.id = c.lead_id
	LEFT JOIN {$agents_table} a ON a.id = c.agent_id
	LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
	WHERE c.status != 'cancelled'
	ORDER BY FIELD(c.status, 'ready', 'owed', 'paid'), c.updated_at DESC
	LIMIT 200
" );
?>
<div class="wrap">
	<h1>Payouts</h1>

	<?php if ( ! empty( $_GET['paid'] ) ) : ?>
		<div class="notice notice-success"><p>Marked as paid.</p></div>
	<?php endif; ?>

	<h2>Homeowner Cash-Back</h2>
	<p class="description">A homeowner's reward is created automatically when their lead is marked completed (if the partner has a Customer Cash-Back Amount set). "Ready" means we're waiting on them to submit a payout method via their claim link; only "Claimed" rows can be marked paid.</p>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>Customer</th><th>Amount</th><th>Status</th><th>Payout Method</th><th>Claim Link</th><th>Updated</th><th>Action</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $customer_payouts ) ) : ?>
				<tr><td colspan="7">No homeowner cash-back rewards yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $customer_payouts as $cp ) : ?>
				<tr>
					<td><?php echo esc_html( $cp->customer_name ?: '—' ); ?></td>
					<td>$<?php echo esc_html( number_format( (float) $cp->amount, 2 ) ); ?></td>
					<td><?php echo esc_html( ucfirst( $cp->status ) ); ?></td>
					<td><?php echo esc_html( $cp->payout_method ? ucfirst( $cp->payout_method ) : '—' ); ?></td>
					<td>
						<?php if ( 'ready' === $cp->status ) : ?>
							<?php $claim_url = add_query_arg( 'token', $cp->claim_token, home_url( '/claim-cashback/' ) ); ?>
							<input type="text" readonly value="<?php echo esc_url( $claim_url ); ?>" style="width:100%; font-size:11px;" onclick="this.select();">
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $cp->updated_at ); ?></td>
					<td>
						<?php if ( 'claimed' === $cp->status ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:6px; align-items:center;">
								<?php wp_nonce_field( 'grc_mark_customer_payout_paid' ); ?>
								<input type="hidden" name="action" value="grc_mark_customer_payout_paid">
								<input type="hidden" name="payout_id" value="<?php echo esc_attr( $cp->id ); ?>">
								<input type="text" name="payout_reference" placeholder="Reference (optional)" style="width:140px;">
								<button type="submit" class="button">Mark Paid</button>
							</form>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2 style="margin-top:40px;">Agent Commissions</h2>
	<p class="description">Only "Ready" commissions can be marked paid.</p>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr><th>Agent</th><th>Customer</th><th>Tier</th><th>Amount</th><th>Status</th><th>Updated</th><th>Action</th></tr>
		</thead>
		<tbody>
			<?php if ( empty( $commissions ) ) : ?>
				<tr><td colspan="7">No commissions yet.</td></tr>
			<?php endif; ?>
			<?php foreach ( $commissions as $c ) : ?>
				<tr>
					<td><?php echo esc_html( $c->agent_name ?: $c->referral_code ?: '—' ); ?></td>
					<td><?php echo esc_html( $c->customer_name ?: '—' ); ?></td>
					<td><?php echo esc_html( $c->tier ); ?></td>
					<td>$<?php echo esc_html( number_format( (float) $c->amount, 2 ) ); ?></td>
					<td><?php echo esc_html( ucfirst( $c->status ) ); ?></td>
					<td><?php echo esc_html( $c->updated_at ); ?></td>
					<td>
						<?php if ( 'ready' === $c->status ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:6px; align-items:center;">
								<?php wp_nonce_field( 'grc_mark_commission_paid' ); ?>
								<input type="hidden" name="action" value="grc_mark_commission_paid">
								<input type="hidden" name="commission_id" value="<?php echo esc_attr( $c->id ); ?>">
								<input type="text" name="payout_reference" placeholder="Reference (optional)" style="width:140px;">
								<button type="submit" class="button">Mark Paid</button>
							</form>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
