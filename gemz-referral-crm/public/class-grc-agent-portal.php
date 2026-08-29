<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Front-end agent self-service portal.
 *
 * Deliberately looks up the agent record by the CURRENT logged-in
 * user's ID, never by a posted agent_id - this is what makes "agents
 * edit their own payment info, admin cannot" actually true rather
 * than just a UI convention. Even a tampered form submission can only
 * ever touch the submitting user's own row.
 */
class GRC_Agent_Portal {

	public static function init() {
		add_shortcode( 'gemz_agent_dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_action( 'admin_post_grc_save_payment_info', array( __CLASS__, 'handle_save_payment_info' ) );
	}

	private static function get_current_agent() {
		global $wpdb;
		if ( ! is_user_logged_in() ) {
			return null;
		}
		$table = GRC_DB::table( 'agents' );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d", get_current_user_id()
		) );
	}

	public static function render_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="gemz-portal-notice">Please <a href="' . esc_url( home_url( '/agent-login/' ) ) . '">log in</a> to view your agent dashboard. New here? <a href="' . esc_url( home_url( '/become-an-agent/' ) ) . '">Create a free agent account</a>.</p>';
		}

		$agent = self::get_current_agent();
		if ( ! $agent ) {
			return '<p class="gemz-portal-notice">No agent profile is linked to your account yet. <a href="' . esc_url( home_url( '/become-an-agent/' ) ) . '">Become an agent</a> or contact the site admin.</p>';
		}

		global $wpdb;
		$leads_table = GRC_DB::table( 'leads' );
		$commissions_table = GRC_DB::table( 'commissions' );

		$leads_sent = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = %d", $agent->id ) );
		$leads_converted = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$leads_table} WHERE agent_id = %d AND status = 'completed'", $agent->id ) );
		$owed = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$commissions_table} WHERE agent_id = %d AND status IN ('owed','ready')", $agent->id ) );
		$paid = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$commissions_table} WHERE agent_id = %d AND status = 'paid'", $agent->id ) );

		$payment = ! empty( $agent->payment_details ) ? json_decode( $agent->payment_details, true ) : array();
		$method  = $agent->payment_method ?: '';
		$saved   = ! empty( $_GET['payment_saved'] );

		ob_start();
		?>
		<div class="gemz-portal">
			<?php if ( $saved ) : ?>
				<p class="gemz-portal-success">Your payment info was saved.</p>
			<?php endif; ?>

			<?php if ( current_user_can( 'grc_view_own_deals' ) ) : ?>
				<p class="gemz-portal-hint"><a href="<?php echo esc_url( home_url( '/partner-portal/' ) ); ?>">Switch to your Partner Dashboard &rarr;</a></p>
			<?php endif; ?>

			<div class="gemz-portal-stats">
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Your referral code</span>
					<span class="gemz-stat-value gemz-code"><?php echo esc_html( $agent->referral_code ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Leads sent</span>
					<span class="gemz-stat-value"><?php echo esc_html( $leads_sent ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Converted</span>
					<span class="gemz-stat-value"><?php echo esc_html( $leads_converted ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Owed / ready</span>
					<span class="gemz-stat-value">$<?php echo esc_html( number_format( $owed, 2 ) ); ?></span>
				</div>
				<div class="gemz-stat-card">
					<span class="gemz-stat-label">Paid to date</span>
					<span class="gemz-stat-value">$<?php echo esc_html( number_format( $paid, 2 ) ); ?></span>
				</div>
			</div>

			<h3>Payout method</h3>
			<p class="gemz-portal-hint">Only you can see or change this - the site admin cannot edit it for you.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gemz-payment-form" id="gemz-payment-form">
				<?php wp_nonce_field( 'grc_save_payment_info' ); ?>
				<input type="hidden" name="action" value="grc_save_payment_info">

				<label for="gemz_payment_method">Payout method</label>
				<select id="gemz_payment_method" name="payment_method">
					<option value="">— Select —</option>
					<option value="wise" <?php selected( $method, 'wise' ); ?>>Wise</option>
					<option value="paypal" <?php selected( $method, 'paypal' ); ?>>PayPal</option>
					<option value="bank" <?php selected( $method, 'bank' ); ?>>Bank transfer</option>
				</select>

				<div class="gemz-payment-fields" data-method="wise" style="<?php echo 'wise' === $method ? '' : 'display:none;'; ?>">
					<label for="gemz_wise_email">Wise email</label>
					<input type="email" id="gemz_wise_email" name="wise_email" value="<?php echo esc_attr( $payment['wise_email'] ?? '' ); ?>">
				</div>

				<div class="gemz-payment-fields" data-method="paypal" style="<?php echo 'paypal' === $method ? '' : 'display:none;'; ?>">
					<label for="gemz_paypal_email">PayPal email</label>
					<input type="email" id="gemz_paypal_email" name="paypal_email" value="<?php echo esc_attr( $payment['paypal_email'] ?? '' ); ?>">
				</div>

				<div class="gemz-payment-fields" data-method="bank" style="<?php echo 'bank' === $method ? '' : 'display:none;'; ?>">
					<label for="gemz_bank_country">Bank country</label>
					<input type="text" id="gemz_bank_country" name="bank_country" placeholder="e.g. United States" value="<?php echo esc_attr( $payment['bank_country'] ?? '' ); ?>">

					<label for="gemz_account_holder">Account holder name</label>
					<input type="text" id="gemz_account_holder" name="bank_account_holder" value="<?php echo esc_attr( $payment['bank_account_holder'] ?? '' ); ?>">

					<label for="gemz_account_number">Account number / IBAN</label>
					<input type="text" id="gemz_account_number" name="bank_account_number" value="<?php echo esc_attr( $payment['bank_account_number'] ?? '' ); ?>">

					<label for="gemz_routing">Routing number / SWIFT / BIC</label>
					<input type="text" id="gemz_routing" name="bank_routing" value="<?php echo esc_attr( $payment['bank_routing'] ?? '' ); ?>">

					<label for="gemz_bank_name">Bank name</label>
					<input type="text" id="gemz_bank_name" name="bank_name" value="<?php echo esc_attr( $payment['bank_name'] ?? '' ); ?>">
				</div>

				<button type="submit" class="gemz-btn">Save payout info</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Saves payment info for the CURRENT user only - agent_id is derived
	 * server-side from get_current_user_id(), never trusted from POST.
	 */
	public static function handle_save_payment_info() {
		if ( ! is_user_logged_in() ) {
			wp_die( 'You must be logged in.' );
		}
		check_admin_referer( 'grc_save_payment_info' );

		global $wpdb;
		$agents_table = GRC_DB::table( 'agents' );
		$agent = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$agents_table} WHERE user_id = %d", get_current_user_id()
		) );

		if ( ! $agent ) {
			wp_die( 'No agent profile found for your account.' );
		}

		$method = sanitize_text_field( $_POST['payment_method'] ?? '' );
		$allowed_methods = array( 'wise', 'paypal', 'bank' );
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			wp_die( 'Invalid payout method.' );
		}

		$details = array();
		if ( 'wise' === $method ) {
			$details['wise_email'] = sanitize_email( $_POST['wise_email'] ?? '' );
		} elseif ( 'paypal' === $method ) {
			$details['paypal_email'] = sanitize_email( $_POST['paypal_email'] ?? '' );
		} elseif ( 'bank' === $method ) {
			$details['bank_country']         = sanitize_text_field( $_POST['bank_country'] ?? '' );
			$details['bank_account_holder']  = sanitize_text_field( $_POST['bank_account_holder'] ?? '' );
			$details['bank_account_number']  = sanitize_text_field( $_POST['bank_account_number'] ?? '' );
			$details['bank_routing']         = sanitize_text_field( $_POST['bank_routing'] ?? '' );
			$details['bank_name']            = sanitize_text_field( $_POST['bank_name'] ?? '' );
		}

		$wpdb->update(
			$agents_table,
			array(
				'payment_method'  => $method,
				'payment_details' => wp_json_encode( $details ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $agent->id )
		);

		// Audit-logged as the agent's own action (not attributable to admin),
		// since admin never touches this data.
		GRC_Admin::audit_log( 'agent', $agent->id, 'payment_info_updated', array( 'method' => $method ) );

		wp_safe_redirect( add_query_arg( 'payment_saved', '1', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}
}

GRC_Agent_Portal::init();
