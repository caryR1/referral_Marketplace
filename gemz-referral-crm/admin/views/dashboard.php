<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$counts = array(
	'partners' => $wpdb->get_var( "SELECT COUNT(*) FROM " . GRC_DB::table('partners') . " WHERE status = 'active'" ),
	'agents'   => $wpdb->get_var( "SELECT COUNT(*) FROM " . GRC_DB::table('agents') . " WHERE status = 'active'" ),
	'leads_new' => $wpdb->get_var( "SELECT COUNT(*) FROM " . GRC_DB::table('leads') . " WHERE status = 'new'" ),
	'leads_open' => $wpdb->get_var( "SELECT COUNT(*) FROM " . GRC_DB::table('leads') . " WHERE status IN ('new','sent_to_partner','accepted','in_progress')" ),
	'commission_owed' => $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM " . GRC_DB::table('commissions') . " WHERE status IN ('owed','ready')" ),
	'cashback_pending' => $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM " . GRC_DB::table('customer_payouts') . " WHERE status IN ('ready','claimed')" ),
);
?>
<div class="wrap">
	<h1>Gemz Referral CRM</h1>
	<div class="grc-stat-cards" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>Active Partners</h3>
			<p style="font-size:28px; margin:0;"><?php echo esc_html( $counts['partners'] ?? 0 ); ?></p>
		</div>
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>Active Agents</h3>
			<p style="font-size:28px; margin:0;"><?php echo esc_html( $counts['agents'] ?? 0 ); ?></p>
		</div>
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>New Leads</h3>
			<p style="font-size:28px; margin:0;"><?php echo esc_html( $counts['leads_new'] ?? 0 ); ?></p>
		</div>
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>Open Leads</h3>
			<p style="font-size:28px; margin:0;"><?php echo esc_html( $counts['leads_open'] ?? 0 ); ?></p>
		</div>
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>Commission Owed</h3>
			<p style="font-size:28px; margin:0;">$<?php echo esc_html( number_format( (float) ( $counts['commission_owed'] ?? 0 ), 2 ) ); ?></p>
		</div>
		<div class="card" style="padding:20px; min-width:180px;">
			<h3>Cash-Back Pending</h3>
			<p style="font-size:28px; margin:0;">$<?php echo esc_html( number_format( (float) ( $counts['cashback_pending'] ?? 0 ), 2 ) ); ?></p>
		</div>
	</div>
	<p style="margin-top:30px;">
		<a href="<?php echo esc_url( admin_url('admin.php?page=grc-partners') ); ?>" class="button button-primary">Manage Partners</a>
		<a href="<?php echo esc_url( admin_url('admin.php?page=grc-leads') ); ?>" class="button">View Leads</a>
		<a href="<?php echo esc_url( admin_url('admin.php?page=grc-reports') ); ?>" class="button">Reports</a>
		<a href="<?php echo esc_url( admin_url('admin.php?page=grc-payouts') ); ?>" class="button">Payouts</a>
	</p>
</div>
