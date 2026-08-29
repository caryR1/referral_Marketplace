<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public agent self-registration. Mirrors the "instant self-signup"
 * flow from the rportal spec: fill in name/email/password, optionally
 * arrive via another agent's link (?ref=), and become an active agent
 * immediately - no admin approval step, matching what was decided
 * for campaign membership generally ("joining is instant").
 */
class GRC_Agent_Signup {

	public static function init() {
		add_shortcode( 'gemz_agent_signup', array( __CLASS__, 'render_form' ) );
	}

	public static function render_form( $atts ) {
		if ( is_user_logged_in() ) {
			$portal = GRC_Roles::get_portal_link_for_current_user();
			if ( $portal['url'] ) {
				return '<p class="gemz-portal-notice">You\'re already logged in. <a href="' . esc_url( $portal['url'] ) . '">Go to your ' . esc_html( $portal['label'] ) . '</a>.</p>';
			}
			return '<p class="gemz-portal-notice">You\'re already logged in, but this account isn\'t set up as an agent or partner.</p>';
		}

		ob_start();
		?>
		<div class="gemz-portal">
			<form id="gemz-agent-signup-form" class="gemz-payment-form">
				<label for="gemz_signup_name">Full Name</label>
				<input type="text" id="gemz_signup_name" name="full_name" required>

				<label for="gemz_signup_email">Email</label>
				<input type="email" id="gemz_signup_email" name="email" required>

				<label for="gemz_signup_password">Password</label>
				<input type="password" id="gemz_signup_password" name="password" required minlength="8">

				<button type="submit" class="gemz-btn">Become an Agent</button>
				<p class="gemz-form-message" role="status" aria-live="polite"></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}

GRC_Agent_Signup::init();
