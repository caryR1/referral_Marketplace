<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Front-end fulfillment partner self-service dashboard. Mirrors
 * GRC_Agent_Portal's pattern exactly: looks up the partner record by
 * the CURRENT logged-in user's ID, never by a posted partner_id, so a
 * partner can only ever see their own deals.
 */
class GRC_Partner_Dashboard {

	public static function init() {
		add_shortcode( 'gemz_partner_dashboard', array( __CLASS__, 'render_dashboard' ) );
	}

	private static function get_current_partner() {
		global $wpdb;
		if ( ! is_user_logged_in() ) {
			return null;
		}
		$table = GRC_DB::table( 'partners' );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d", get_current_user_id()
		) );
	}

	/**
	 * Collapses the internal 7-status lead lifecycle down to the simple
	 * sent/pending/done view a partner sees - the lead table itself
	 * keeps the full detail, this is display-only.
	 */
	private static function deal_stage( $lead_status ) {
		if ( in_array( $lead_status, array( 'new', 'sent_to_partner' ), true ) ) {
			return 'Sent';
		}
		if ( in_array( $lead_status, array( 'accepted', 'in_progress' ), true ) ) {
			return 'Pending';
		}
		return 'Done'; // completed, lost, or stale
	}

	public static function render_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="gemz-portal-notice">Please <a href="' . esc_url( home_url( '/agent-login/' ) ) . '">log in</a> to view your partner dashboard.</p>';
		}

		$partner = self::get_current_partner();
		if ( ! $partner ) {
			return '<p class="gemz-portal-notice">No fulfillment partner profile is linked to your account yet. Contact the site admin.</p>';
		}

		global $wpdb;
		$leads_table = GRC_DB::table( 'leads' );
		$leads = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, customer_name, status, created_at FROM {$leads_table} WHERE partner_id = %d ORDER BY created_at DESC LIMIT 100",
			$partner->id
		) );

		$total_deals = count( $leads );
		$closed_deals = 0;
		foreach ( $leads as $l ) {
			if ( 'completed' === $l->status ) {
				$closed_deals++;
			}
		}
		$success_rate = $total_deals > 0 ? round( ( $closed_deals / $total_deals ) * 100 ) . '%' : '—';

		ob_start();
		?>
		<div class="gemz-portal">
			<?php if ( current_user_can( 'grc_view_own_leads' ) ) : ?>
				<p class="gemz-portal-hint"><a href="<?php echo esc_url( home_url( '/agent-portal/' ) ); ?>">Switch to your Agent Portal &rarr;</a></p>
			<?php endif; ?>

			<div class="gemz-portal-stats">
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Total deals</span>
					<span class="gemz-stat-value"><?php echo esc_html( $total_deals ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Closed deals</span>
					<span class="gemz-stat-value"><?php echo esc_html( $closed_deals ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Success rate</span>
					<span class="gemz-stat-value"><?php echo esc_html( $success_rate ); ?></span>
				</div>
			</div>

			<h3>Your Deal Pipeline</h3>
			<table class="gemz-portal-table">
				<thead>
					<tr><th>Customer</th><th>Stage</th><th>Received</th></tr>
				</thead>
				<tbody>
					<?php if ( empty( $leads ) ) : ?>
						<tr><td colspan="3">No deals yet - they'll show up here as leads come in.</td></tr>
					<?php endif; ?>
					<?php foreach ( $leads as $l ) : ?>
						<tr>
							<td><?php echo esc_html( $l->customer_name ); ?></td>
							<td><?php echo esc_html( self::deal_stage( $l->status ) ); ?></td>
							<td><?php echo esc_html( $l->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}
}

GRC_Partner_Dashboard::init();
