<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dedicated front-end agent login (separate from wp-login.php so the
 * whole flow can stay on-brand and the error message can stay generic -
 * see the REST handler in GRC_REST_API::login_agent(), which never
 * reveals whether a given email has an account).
 */
class GRC_Agent_Login {

	public static function init() {
		add_shortcode( 'gemz_agent_login', array( __CLASS__, 'render_form' ) );
	}

	public static function render_form( $atts ) {
		if ( is_user_logged_in() ) {
			return '<p class="gemz-portal-notice">You\'re already logged in. Visit your agent dashboard to see your referral code.</p>';
		}

		ob_start();
		?>
		<div class="gemz-portal">
			<form id="gemz-agent-login-form" class="gemz-payment-form">
				<label for="gemz_login_email">Email</label>
				<input type="email" id="gemz_login_email" name="email" required>

				<label for="gemz_login_password">Password</label>
				<input type="password" id="gemz_login_password" name="password" required>

				<button type="submit" class="gemz-btn">Sign In</button>
				<p class="gemz-form-message" role="status" aria-live="polite"></p>
				<p class="gemz-portal-hint"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot your password?</a></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}

GRC_Agent_Login::init();
