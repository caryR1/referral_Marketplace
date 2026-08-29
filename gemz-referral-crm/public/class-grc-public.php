<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Everything customer/agent-facing that isn't wp-admin.
 *
 * Flow:
 *   1. Agent shares https://refer.gemzonline.com/go/{tracking_slug}?ref=AG-XXXXX
 *   2. GRC_Public::maybe_redirect_tracking_link() catches /go/{slug}, looks up
 *      the campaign, and 302s to that campaign's actual landing page (a normal
 *      Gutenberg page), carrying the ?ref= code along in the query string.
 *   3. That landing page contains the [gemz_appointment_form] shortcode
 *      somewhere in its Gutenberg content (dropped in like any other block-editor
 *      content - this is the "reusable standard component" piece).
 *   4. The shortcode's JS reads campaign + ref from the URL and POSTs to
 *      /wp-json/gemz-crm/v1/leads on submit.
 */
class GRC_Public {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_tracking_link' ) );
		add_shortcode( 'gemz_appointment_form', array( __CLASS__, 'render_appointment_form' ) );
		add_shortcode( 'gemz_industry_browser', array( __CLASS__, 'render_industry_browser' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^go/([^/]+)/?$', 'index.php?grc_tracking_slug=$matches[1]', 'top' );
	}

	public static function add_query_var( $vars ) {
		$vars[] = 'grc_tracking_slug';
		return $vars;
	}

	public static function maybe_redirect_tracking_link() {
		$slug = get_query_var( 'grc_tracking_slug' );
		if ( empty( $slug ) ) {
			return;
		}

		global $wpdb;
		$campaigns_table = GRC_DB::table( 'campaigns' );
		$campaign = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$campaigns_table} WHERE tracking_slug = %s AND status = 'active'", $slug
		) );

		if ( ! $campaign || empty( $campaign->landing_page_id ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$target = get_permalink( $campaign->landing_page_id );
		if ( ! empty( $_GET['ref'] ) ) {
			$target = add_query_arg( array(
				'ref'         => sanitize_text_field( wp_unslash( $_GET['ref'] ) ),
				'campaign_id' => $campaign->id,
			), $target );
		} else {
			$target = add_query_arg( 'campaign_id', $campaign->id, $target );
		}

		wp_safe_redirect( $target );
		exit;
	}

	public static function enqueue_assets() {
		// Site-wide: fonts + the frontend theme (header/footer/hero/card system)
		// load on every page, not just ones with a shortcode, since the header
		// and footer appear everywhere.
		wp_enqueue_style( 'grc-brand-fonts', 'https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap', array(), null );
		wp_enqueue_style( 'grc-frontend-theme', GRC_PLUGIN_URL . 'assets/css/gemz-frontend-theme.css', array(), GRC_VERSION );

		$content = is_singular() ? ( get_post()->post_content ?? '' ) : '';
		$has_form    = has_shortcode( $content, 'gemz_appointment_form' );
		$has_browser = has_shortcode( $content, 'gemz_industry_browser' );
		$has_portal  = has_shortcode( $content, 'gemz_agent_dashboard' );
		$has_signup  = has_shortcode( $content, 'gemz_agent_signup' );
		$has_login   = has_shortcode( $content, 'gemz_agent_login' );
		$has_claim   = has_shortcode( $content, 'gemz_claim_cashback' );
		$has_partner_dashboard = has_shortcode( $content, 'gemz_partner_dashboard' );

		if ( ! $has_form && ! $has_browser && ! $has_portal && ! $has_signup && ! $has_login && ! $has_claim && ! $has_partner_dashboard ) {
			return;
		}

		// Legacy brand tokens - kept for the older inline-styled pages (Home,
		// Roofing, Solar, Windows & Doors, Tiny & Modular Homes) built before
		// the gemz-frontend-theme.css system; harmless to load alongside it.
		wp_enqueue_style( 'grc-brand-tokens', GRC_PLUGIN_URL . 'assets/css/gemz-brand.css', array(), GRC_VERSION );

		if ( $has_form ) {
			wp_enqueue_script( 'grc-appointment-form', GRC_PLUGIN_URL . 'assets/js/appointment-form.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-appointment-form', GRC_PLUGIN_URL . 'assets/css/appointment-form.css', array( 'grc-brand-tokens' ), GRC_VERSION );
			wp_localize_script( 'grc-appointment-form', 'grcFunnel', array(
				'restUrl' => esc_url_raw( rest_url( 'gemz-crm/v1/leads' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			) );
		}

		if ( $has_browser ) {
			wp_enqueue_script( 'grc-industry-browser', GRC_PLUGIN_URL . 'assets/js/industry-browser.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-industry-browser', GRC_PLUGIN_URL . 'assets/css/industry-browser.css', array( 'grc-brand-tokens' ), GRC_VERSION );
			wp_localize_script( 'grc-industry-browser', 'gemzBrowser', array(
				'restUrl' => esc_url_raw( rest_url( 'gemz-crm/v1/coverage-search' ) ),
			) );
		}

		if ( $has_portal ) {
			wp_enqueue_script( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/js/agent-portal.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/css/agent-portal.css', array( 'grc-brand-tokens' ), GRC_VERSION );
		}

		if ( $has_partner_dashboard ) {
			wp_enqueue_style( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/css/agent-portal.css', array( 'grc-brand-tokens' ), GRC_VERSION );
		}

		if ( $has_signup ) {
			wp_enqueue_script( 'grc-agent-signup', GRC_PLUGIN_URL . 'assets/js/agent-signup.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/css/agent-portal.css', array( 'grc-brand-tokens' ), GRC_VERSION );
			wp_localize_script( 'grc-agent-signup', 'gemzAgentSignup', array(
				'restUrl'  => esc_url_raw( rest_url( 'gemz-crm/v1/agents/register' ) ),
				'loginUrl' => esc_url_raw( home_url( '/agent-login/' ) ),
			) );
		}

		if ( $has_login ) {
			wp_enqueue_script( 'grc-agent-login', GRC_PLUGIN_URL . 'assets/js/agent-login.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/css/agent-portal.css', array( 'grc-brand-tokens' ), GRC_VERSION );
			wp_localize_script( 'grc-agent-login', 'gemzAgentLogin', array(
				'restUrl'      => esc_url_raw( rest_url( 'gemz-crm/v1/agents/login' ) ),
				'dashboardUrl' => esc_url_raw( home_url( '/agent-portal/' ) ),
			) );
		}

		if ( $has_claim ) {
			wp_enqueue_script( 'grc-claim-cashback', GRC_PLUGIN_URL . 'assets/js/claim-cashback.js', array(), GRC_VERSION, true );
			wp_enqueue_style( 'grc-agent-portal', GRC_PLUGIN_URL . 'assets/css/agent-portal.css', array( 'grc-brand-tokens' ), GRC_VERSION );
			wp_localize_script( 'grc-claim-cashback', 'gemzClaimCashback', array(
				'restUrl' => esc_url_raw( rest_url( 'gemz-crm/v1/cashback/claim' ) ),
			) );
		}
	}

	/**
	 * Shortcode: [gemz_appointment_form partner_id="3"]
	 * partner_id is required so the form knows which fulfillment partner
	 * this lead is destined for (front-end never shows partner name -
	 * this attribute is set by you in the block editor when building the
	 * landing page, the visitor never sees it).
	 */
	public static function render_appointment_form( $atts ) {
		$atts = shortcode_atts( array(
			'partner_id' => 0,
		), $atts, 'gemz_appointment_form' );

		if ( empty( $atts['partner_id'] ) ) {
			return is_user_logged_in() && current_user_can( 'grc_manage_partners' )
				? '<p style="color:#a00;">[gemz_appointment_form] is missing a partner_id attribute.</p>'
				: '';
		}

		ob_start();
		?>
		<div class="grc-appointment-form-wrap" data-partner-id="<?php echo esc_attr( absint( $atts['partner_id'] ) ); ?>">
			<form id="grc-appointment-form" class="grc-appointment-form">
				<div class="grc-field">
					<label for="grc_name">Full Name</label>
					<input type="text" id="grc_name" name="customer_name" required>
				</div>
				<div class="grc-field">
					<label for="grc_phone">Phone</label>
					<input type="tel" id="grc_phone" name="customer_phone">
				</div>
				<div class="grc-field">
					<label for="grc_email">Email</label>
					<input type="email" id="grc_email" name="customer_email">
				</div>
				<div class="grc-field">
					<label for="grc_zip">Zip Code</label>
					<input type="text" id="grc_zip" name="customer_zip" required>
				</div>
				<div class="grc-field">
					<label for="grc_preferred_contact">Preferred Contact Method</label>
					<select id="grc_preferred_contact" name="preferred_contact">
						<option value="phone">Phone</option>
						<option value="text">Text</option>
						<option value="email">Email</option>
					</select>
				</div>
				<div class="grc-field">
					<label for="grc_appt_primary">Preferred Appointment</label>
					<input type="datetime-local" id="grc_appt_primary" name="appointment_primary" required>
				</div>
				<div class="grc-field">
					<label for="grc_appt_backup">Backup Appointment</label>
					<input type="datetime-local" id="grc_appt_backup" name="appointment_backup">
				</div>
				<button type="submit" class="grc-submit-btn">Book My Appointment</button>
				<p class="grc-form-message" role="status" aria-live="polite"></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
	/**
	 * Icon paths for the glossy-badge treatment - simple, original line-art
	 * (not any borrowed studio's actual imagery), just clean enough shapes
	 * to read instantly at a glance for each trade.
	 */
	private static function industry_icon_svg( $industry ) {
		$paths = array(
			'roofing'       => '<path d="M3 12L12 5l9 7"/><path d="M5 10v9h14v-9"/><path d="M9 19v-6h6v6"/>',
			'hvac'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/>',
			'solar'         => '<rect x="3" y="7" width="8" height="6" rx="1"/><rect x="13" y="7" width="8" height="6" rx="1"/><path d="M7 13v3M17 13v3M4 21h16"/>',
			'plumbing'      => '<path d="M7 4v6a3 3 0 003 3h0a3 3 0 003-3V4"/><path d="M13 13v3a4 4 0 01-4 4H7"/><circle cx="17" cy="17" r="3"/>',
			'remodeling'    => '<path d="M14.5 3.5l6 6-9 9H5.5v-6l9-9z"/><path d="M12 6l6 6"/>',
			'windows_doors' => '<rect x="3" y="3" width="8" height="18" rx="1"/><path d="M7 3v18"/><rect x="14" y="3" width="7" height="18" rx="1"/><path d="M14 12h7"/>',
			'tiny_modular_homes' => '<path d="M3 14L8 9h8l5 5"/><path d="M5 12v7h14v-7"/><circle cx="8" cy="20" r="1.5"/><circle cx="16" cy="20" r="1.5"/>',
		);
		return $paths[ $industry ] ?? $paths['roofing'];
	}

	/**
	 * Shortcode: [gemz_industry_browser]
	 * The public browsing UX: pick an industry, enter a zip/city/state,
	 * see matching offers without any fulfillment-partner name attached.
	 * A "Get My Cashback" click sends them into the same /go/{slug}
	 * tracking-link redirect agents use, just without a ?ref= code -
	 * so it lands as an organic/direct lead rather than agent-credited.
	 */
	public static function render_industry_browser( $atts ) {
		$industries = GRC_Industries::all();

		ob_start();
		?>
		<div class="gemz-browser">
			<div class="gemz-browser-step is-active" data-step="industry">
				<h2>What kind of project are you working on?</h2>
				<div class="gemz-industry-grid">
					<?php foreach ( $industries as $key => $label ) : ?>
						<div class="gemz-industry-card" data-industry="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $label ); ?>">
							<div class="gemz-badge">
								<svg viewBox="0 0 24 24"><?php echo self::industry_icon_svg( $key ); /* phpcs:ignore -- fixed internal SVG path constants, no user input */ ?></svg>
							</div>
							<span><?php echo esc_html( $label ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="gemz-browser-step" data-step="location">
				<div class="gemz-breadcrumb"><button type="button" data-back="industry">&larr; Change project type</button></div>
				<h2>Where's the <span class="gemz-selected-industry-label"></span> project?</h2>
				<form class="gemz-location-form">
					<label for="gemz_zip">Zip Code</label>
					<input type="text" id="gemz_zip" name="zip" placeholder="e.g. 34950">
					<label for="gemz_city">City</label>
					<input type="text" id="gemz_city" name="city" placeholder="e.g. Fort Pierce">
					<label for="gemz_state">State</label>
					<input type="text" id="gemz_state" name="state" placeholder="e.g. FL">
					<button type="submit" class="gemz-btn">Find Cashback Offers</button>
				</form>
			</div>

			<div class="gemz-browser-step" data-step="offers">
				<div class="gemz-breadcrumb"><button type="button" data-back="location">&larr; Change location</button></div>
				<h2>Offers near you</h2>
				<div class="gemz-offers-list"></div>
				<div class="gemz-empty-state" style="display:none;">
					<p>No cashback offers in your area yet for this project type - check back soon, we're adding coverage areas regularly.</p>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

GRC_Public::init();
