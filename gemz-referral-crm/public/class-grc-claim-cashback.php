<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public, no-login claim page for homeowner cash-back. The claim_token
 * in the URL (from the customer_cashback_ready email) is the sole
 * credential - there's no customer account system, same pattern as a
 * password-reset link.
 */
class GRC_Claim_Cashback {

	public static function init() {
		add_shortcode( 'gemz_claim_cashback', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( empty( $token ) ) {
			return '<p class="gemz-portal-notice">This page needs a valid claim link - check the email we sent you when your project was marked complete.</p>';
		}

		$payout = GRC_Customer_Payouts::get_by_token( $token );
		if ( ! $payout ) {
			return '<p class="gemz-portal-notice">That claim link isn\'t valid. Double check the link from your email, or contact us if you think this is a mistake.</p>';
		}

		if ( 'paid' === $payout->status ) {
			return '<p class="gemz-portal-notice">This cash-back reward has already been paid out. Thanks for booking through Gemz!</p>';
		}
		if ( 'claimed' === $payout->status ) {
			return '<p class="gemz-portal-notice">You\'ve already submitted your payout details for this reward - we\'re processing it now.</p>';
		}

		ob_start();
		?>
		<div class="gemz-portal">
			<form id="gemz-claim-cashback-form" class="gemz-payment-form">
				<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

				<p style="margin-top:0;">Your cash back: <strong>$<?php echo esc_html( number_format( (float) $payout->amount, 2 ) ); ?></strong></p>

				<label for="gemz_claim_method">How should we send it?</label>
				<select id="gemz_claim_method" name="payout_method">
					<option value="">— Select —</option>
					<option value="paypal">PayPal</option>
					<option value="venmo">Venmo</option>
					<option value="bank">Bank transfer</option>
					<option value="check">Check by mail</option>
				</select>

				<div class="gemz-payment-fields" data-method="paypal" style="display:none;">
					<label for="gemz_paypal_email">PayPal email</label>
					<input type="email" id="gemz_paypal_email" name="paypal_email">
				</div>

				<div class="gemz-payment-fields" data-method="venmo" style="display:none;">
					<label for="gemz_venmo_handle">Venmo username</label>
					<input type="text" id="gemz_venmo_handle" name="venmo_handle">
				</div>

				<div class="gemz-payment-fields" data-method="bank" style="display:none;">
					<label for="gemz_bank_account_holder">Account holder name</label>
					<input type="text" id="gemz_bank_account_holder" name="bank_account_holder">

					<label for="gemz_bank_account_number">Account number / IBAN</label>
					<input type="text" id="gemz_bank_account_number" name="bank_account_number">

					<label for="gemz_bank_routing">Routing number / SWIFT / BIC</label>
					<input type="text" id="gemz_bank_routing" name="bank_routing">

					<label for="gemz_bank_name">Bank name</label>
					<input type="text" id="gemz_bank_name" name="bank_name">
				</div>

				<div class="gemz-payment-fields" data-method="check" style="display:none;">
					<label for="gemz_mailing_address">Mailing address</label>
					<textarea id="gemz_mailing_address" name="mailing_address" rows="3"></textarea>
				</div>

				<button type="submit" class="gemz-btn">Claim My Cash Back</button>
				<p class="gemz-form-message" role="status" aria-live="polite"></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}

GRC_Claim_Cashback::init();
