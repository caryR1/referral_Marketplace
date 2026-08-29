<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * REST endpoints under /wp-json/gemz-crm/v1/...
 *
 * Note: this is for OUR plugin's data (partners, leads, commissions).
 * Editing ordinary WordPress pages/content is already possible via
 * WordPress core's own REST API (/wp-json/wp/v2/pages) plus an
 * Application Password - no custom code needed for that part, it's
 * a WP-admin setup step (Users -> Profile -> Application Passwords).
 */
class GRC_REST_API {

	const NAMESPACE_ = 'gemz-crm/v1';

	public static function register_routes() {
		register_rest_route( self::NAMESPACE_, '/leads', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_leads' ),
			'permission_callback' => function () {
				return current_user_can( 'grc_manage_leads' ) || current_user_can( 'grc_view_own_leads' );
			},
		) );

		register_rest_route( self::NAMESPACE_, '/leads', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_lead' ),
			'permission_callback' => '__return_true', // public: this is how the appointment funnel submits a new lead
		) );

		register_rest_route( self::NAMESPACE_, '/partners', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_partners' ),
			'permission_callback' => function () {
				return current_user_can( 'grc_manage_partners' );
			},
		) );

		register_rest_route( self::NAMESPACE_, '/coverage-search', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'coverage_search' ),
			'permission_callback' => '__return_true', // public: powers the front-end industry/area browser
		) );

		register_rest_route( self::NAMESPACE_, '/agents/register', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'register_agent' ),
			'permission_callback' => '__return_true', // public: this is how a visitor becomes an agent
		) );

		register_rest_route( self::NAMESPACE_, '/agents/login', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'login_agent' ),
			'permission_callback' => '__return_true', // public: this is the front-end login form
		) );

		register_rest_route( self::NAMESPACE_, '/cashback/claim', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'claim_cashback' ),
			'permission_callback' => '__return_true', // public: the claim_token itself is the auth, no login exists for customers
		) );

		register_rest_route( self::NAMESPACE_, '/partners/research-batch', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'ingest_partner_research_batch' ),
			'permission_callback' => function () {
				return current_user_can( 'grc_manage_partners' );
			},
		) );
	}

	/**
	 * Ingests a batch of candidate fulfillment partners found by a
	 * research pass (industry/state/min-commission/etc are whatever
	 * criteria that pass used - not re-validated here beyond min
	 * commission, since the actual searching/verification happens
	 * outside PHP). Every accepted candidate lands with outreach_status
	 * 'new' and live status 'paused' - a researched partner must never
	 * silently start receiving real leads before someone reviews it.
	 *
	 * Dedup is by normalized website first, company name second, against
	 * every existing partner regardless of industry/status - and also
	 * within the same batch, so submitting overlapping results twice
	 * (or the same batch retried) can't create duplicate rows.
	 */
	public static function ingest_partner_research_batch( WP_REST_Request $request ) {
		global $wpdb;
		$params = $request->get_json_params();

		$candidates = $params['candidates'] ?? array();
		if ( empty( $candidates ) || ! is_array( $candidates ) ) {
			return new WP_Error( 'grc_no_candidates', 'No candidates provided.', array( 'status' => 400 ) );
		}
		$min_commission = isset( $params['min_commission'] ) ? (float) $params['min_commission'] : 0;

		$partners_table = GRC_DB::table( 'partners' );
		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 6, false, false );
		$now = current_time( 'mysql' );

		$known = array();
		foreach ( $wpdb->get_results( "SELECT website, name FROM {$partners_table}" ) as $row ) {
			$known[] = array(
				'website' => self::normalize_website_for_dedup( $row->website ),
				'name'    => strtolower( trim( $row->name ) ),
			);
		}

		$valid_industries = array_keys( GRC_Industries::all() );
		$added = array();
		$skipped = array();

		foreach ( $candidates as $c ) {
			$name        = sanitize_text_field( $c['name'] ?? '' );
			$industry    = sanitize_text_field( $c['industry'] ?? '' );
			$website     = esc_url_raw( $c['website'] ?? '' );
			$commission  = isset( $c['commission_amount'] ) ? (float) $c['commission_amount'] : 0;

			if ( empty( $name ) || ! in_array( $industry, $valid_industries, true ) ) {
				$skipped[] = array( 'name' => $name ?: '(unnamed)', 'reason' => 'missing name or invalid industry' );
				continue;
			}
			if ( $commission < $min_commission ) {
				$skipped[] = array( 'name' => $name, 'reason' => "commission \${$commission} below \${$min_commission} minimum" );
				continue;
			}

			$norm_website = self::normalize_website_for_dedup( $website );
			$norm_name    = strtolower( trim( $name ) );
			$is_duplicate = false;
			foreach ( $known as $k ) {
				if ( $norm_website && $k['website'] && $norm_website === $k['website'] ) {
					$is_duplicate = true;
					break;
				}
				if ( ! $norm_website && $norm_name === $k['name'] ) {
					$is_duplicate = true;
					break;
				}
			}
			if ( $is_duplicate ) {
				$skipped[] = array( 'name' => $name, 'reason' => 'duplicate (matches an existing partner)' );
				continue;
			}

			$result = $wpdb->insert( $partners_table, array(
				'name'              => $name,
				'industry'          => $industry,
				'contact_name'      => sanitize_text_field( $c['contact_name'] ?? '' ),
				'phone'             => sanitize_text_field( $c['phone'] ?? '' ),
				'email'             => sanitize_email( $c['email'] ?? '' ),
				'website'           => $website,
				'service_areas'     => wp_json_encode( array() ),
				'payout_amount'     => $commission,
				'payout_type'       => 'flat',
				'status'            => 'paused', // not live until approved + service areas are set
				'outreach_status'   => 'new',
				'source_url'        => esc_url_raw( $c['source_url'] ?? '' ),
				'discovered_via'    => 'research',
				'research_batch_id' => $batch_id,
				'created_at'        => $now,
				'updated_at'        => $now,
			) );
			if ( false === $result ) {
				$skipped[] = array( 'name' => $name, 'reason' => 'db error: ' . $wpdb->last_error );
				continue;
			}
			$new_id = $wpdb->insert_id;
			GRC_Roles::provision_partner_account( $new_id );

			$added[] = array( 'id' => $new_id, 'name' => $name, 'industry' => $industry, 'commission_amount' => $commission );
			$known[] = array( 'website' => $norm_website, 'name' => $norm_name ); // prevents dupes within this same batch

			if ( class_exists( 'GRC_Admin' ) ) {
				GRC_Admin::audit_log( 'partner', $new_id, 'research_added', array( 'batch_id' => $batch_id, 'source_url' => $c['source_url'] ?? '' ) );
			}
		}

		return rest_ensure_response( array( 'batch_id' => $batch_id, 'added' => $added, 'skipped' => $skipped ) );
	}

	private static function normalize_website_for_dedup( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		$url = strtolower( trim( $url ) );
		$url = preg_replace( '#^https?://#', '', $url );
		$url = preg_replace( '#^www\.#', '', $url );
		return rtrim( $url, '/' );
	}

	/**
	 * Homeowner submits their payout method via the emailed claim link.
	 * The claim_token is the sole credential - long/random enough (48 hex
	 * chars) that guessing one is infeasible, same trust model as a
	 * password-reset link. Only a row still in 'ready' status can be
	 * claimed; already-claimed/paid rows return a clear, non-destructive
	 * error rather than silently overwriting a prior submission.
	 */
	public static function claim_cashback( WP_REST_Request $request ) {
		global $wpdb;
		$params = $request->get_json_params();

		$token = sanitize_text_field( $params['token'] ?? '' );
		$method = sanitize_text_field( $params['payout_method'] ?? '' );
		$allowed_methods = array( 'paypal', 'venmo', 'bank', 'check' );

		if ( empty( $token ) || ! in_array( $method, $allowed_methods, true ) ) {
			return new WP_Error( 'grc_invalid_claim', 'Please choose a payout method and try again.', array( 'status' => 400 ) );
		}

		$payout = GRC_Customer_Payouts::get_by_token( $token );
		if ( ! $payout ) {
			return new WP_Error( 'grc_claim_not_found', 'That claim link is invalid.', array( 'status' => 404 ) );
		}
		if ( 'ready' !== $payout->status ) {
			return new WP_Error( 'grc_already_claimed', 'This cash-back reward has already been claimed.', array( 'status' => 409 ) );
		}

		$details = array();
		if ( 'paypal' === $method ) {
			$details['paypal_email'] = sanitize_email( $params['paypal_email'] ?? '' );
		} elseif ( 'venmo' === $method ) {
			$details['venmo_handle'] = sanitize_text_field( $params['venmo_handle'] ?? '' );
		} elseif ( 'bank' === $method ) {
			$details['bank_account_holder'] = sanitize_text_field( $params['bank_account_holder'] ?? '' );
			$details['bank_account_number'] = sanitize_text_field( $params['bank_account_number'] ?? '' );
			$details['bank_routing']        = sanitize_text_field( $params['bank_routing'] ?? '' );
			$details['bank_name']           = sanitize_text_field( $params['bank_name'] ?? '' );
		} elseif ( 'check' === $method ) {
			$details['mailing_address'] = sanitize_textarea_field( $params['mailing_address'] ?? '' );
		}

		$table = GRC_DB::table( 'customer_payouts' );
		$wpdb->update( $table, array(
			'status'         => 'claimed',
			'payout_method'  => $method,
			'payout_details' => wp_json_encode( $details ),
			'claimed_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		), array( 'id' => $payout->id ) );

		if ( class_exists( 'GRC_Admin' ) ) {
			GRC_Admin::audit_log( 'customer_payout', $payout->id, 'claimed', array( 'method' => $method ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Front-end agent login. Deliberately returns the SAME generic error
	 * for "no account with that email" and "wrong password" - never let
	 * the UI tell a visitor whether a given email is registered.
	 */
	public static function login_agent( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$email    = sanitize_email( $params['email'] ?? '' );
		$password = (string) ( $params['password'] ?? '' );

		$generic_error = new WP_Error( 'grc_login_failed', 'Invalid email or password.', array( 'status' => 401 ) );

		if ( empty( $email ) || empty( $password ) ) {
			return $generic_error;
		}

		$user = wp_signon( array(
			'user_login'    => $email,
			'user_password' => $password,
			'remember'      => true,
		), is_ssl() );

		if ( is_wp_error( $user ) ) {
			return $generic_error;
		}

		wp_set_current_user( $user->ID );

		// A person can hold both roles at once (e.g. an agent who is also
		// a fulfillment partner) - default to the agent portal when both
		// apply; each portal shows a link to the other when the logged-in
		// user holds that role too, so nothing is ever unreachable.
		if ( in_array( GRC_Roles::AGENT_ROLE, (array) $user->roles, true ) ) {
			$redirect = home_url( '/agent-portal/' );
		} elseif ( in_array( GRC_Roles::PARTNER_ROLE, (array) $user->roles, true ) ) {
			$redirect = home_url( '/partner-portal/' );
		} else {
			$redirect = home_url( '/' );
		}

		return rest_ensure_response( array( 'success' => true, 'redirect' => $redirect ) );
	}

	/**
	 * Public self-registration: creates a WP user with the grc_agent
	 * role, an agents table row, a unique referral code, and resolves
	 * sponsor_agent_id from a ?ref= code if the visitor arrived via
	 * another agent's link. Signup is instant - no admin approval step,
	 * matching the "joining is instant" decision made for campaigns.
	 */
	public static function register_agent( WP_REST_Request $request ) {
		global $wpdb;
		$params = $request->get_json_params();

		$full_name = sanitize_text_field( $params['full_name'] ?? '' );
		$email     = sanitize_email( $params['email'] ?? '' );
		$password  = (string) ( $params['password'] ?? '' );

		if ( empty( $full_name ) || empty( $email ) || strlen( $password ) < 8 ) {
			return new WP_Error( 'grc_invalid_signup', 'Please fill in your name, a valid email, and a password of at least 8 characters.', array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'grc_invalid_email', 'That email address doesn\'t look valid.', array( 'status' => 400 ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'grc_email_taken', 'An account with that email already exists. Try logging in instead.', array( 'status' => 409 ) );
		}

		// Build a unique username from the email's local part.
		$base_username = sanitize_user( current( explode( '@', $email ) ), true );
		$username      = $base_username;
		$suffix        = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . $suffix;
			$suffix++;
		}

		$user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $password,
			'display_name' => $full_name,
			'first_name'   => $full_name,
			'role'         => GRC_Roles::AGENT_ROLE,
		) );

		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'grc_user_create_failed', $user_id->get_error_message(), array( 'status' => 500 ) );
		}

		// Resolve sponsor from the ref code, if one was passed in the signup link.
		$sponsor_agent_id = null;
		if ( ! empty( $params['ref'] ) ) {
			$sponsor = GRC_Referral_Codes::get_agent_by_code( sanitize_text_field( $params['ref'] ) );
			if ( $sponsor ) {
				$sponsor_agent_id = $sponsor->id;
			}
		}

		$referral_code = GRC_Referral_Codes::generate_unique_code();
		$now = current_time( 'mysql' );
		$wpdb->insert( GRC_DB::table( 'agents' ), array(
			'user_id'          => $user_id,
			'referral_code'    => $referral_code,
			'sponsor_agent_id' => $sponsor_agent_id,
			'status'           => 'active',
			'created_at'       => $now,
			'updated_at'       => $now,
		) );
		$agent_id = $wpdb->insert_id;

		GRC_Notifications::send( 'agent_signup', 'agent', $email, 'email', array(
			'agent_name'    => $full_name,
			'referral_code' => $referral_code,
		) );

		if ( class_exists( 'GRC_Admin' ) ) {
			GRC_Admin::audit_log( 'agent', $agent_id, 'self_registered', array( 'email' => $email, 'sponsor_agent_id' => $sponsor_agent_id ) );
		}

		return rest_ensure_response( array( 'success' => true, 'referral_code' => $referral_code ) );
	}

	public static function coverage_search( WP_REST_Request $request ) {
		$industry = sanitize_text_field( $request->get_param( 'industry' ) ?? '' );
		if ( empty( $industry ) ) {
			return new WP_Error( 'grc_missing_industry', 'Industry is required.', array( 'status' => 400 ) );
		}
		$zip   = sanitize_text_field( $request->get_param( 'zip' ) ?? '' );
		$city  = sanitize_text_field( $request->get_param( 'city' ) ?? '' );
		$state = sanitize_text_field( $request->get_param( 'state' ) ?? '' );

		$offers = GRC_Coverage::search( $industry, $zip, $city, $state );
		return rest_ensure_response( array( 'offers' => $offers ) );
	}

	public static function get_leads( WP_REST_Request $request ) {
		global $wpdb;
		$table = GRC_DB::table( 'leads' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
		return rest_ensure_response( $rows );
	}

	public static function get_partners( WP_REST_Request $request ) {
		global $wpdb;
		$table = GRC_DB::table( 'partners' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );
		return rest_ensure_response( $rows );
	}

	/**
	 * Public-facing lead creation: called from the appointment-setting
	 * step of the funnel. Validates + inserts, then fires notifications.
	 */
	public static function create_lead( WP_REST_Request $request ) {
		global $wpdb;

		$params = $request->get_json_params();

		$required = array( 'partner_id', 'customer_name' );
		foreach ( $required as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error( 'grc_missing_field', "Missing required field: {$field}", array( 'status' => 400 ) );
			}
		}

		$agent_id = null;
		if ( ! empty( $params['ref'] ) ) {
			$agent = GRC_Referral_Codes::get_agent_by_code( sanitize_text_field( $params['ref'] ) );
			if ( $agent ) {
				$agent_id = $agent->id;
			}
		}

		$now = current_time( 'mysql' );
		$wpdb->insert(
			GRC_DB::table( 'leads' ),
			array(
				'partner_id'          => absint( $params['partner_id'] ),
				'agent_id'            => $agent_id,
				'campaign_id'         => isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : null,
				'customer_name'       => sanitize_text_field( $params['customer_name'] ),
				'customer_phone'      => sanitize_text_field( $params['customer_phone'] ?? '' ),
				'customer_email'      => sanitize_email( $params['customer_email'] ?? '' ),
				'customer_zip'        => sanitize_text_field( $params['customer_zip'] ?? '' ),
				'preferred_contact'   => sanitize_text_field( $params['preferred_contact'] ?? 'phone' ),
				'appointment_primary' => ! empty( $params['appointment_primary'] ) ? sanitize_text_field( $params['appointment_primary'] ) : null,
				'appointment_backup'  => ! empty( $params['appointment_backup'] ) ? sanitize_text_field( $params['appointment_backup'] ) : null,
				'status'              => 'new',
				'created_at'          => $now,
				'updated_at'          => $now,
			)
		);
		$lead_id = $wpdb->insert_id;

		if ( ! empty( $params['customer_email'] ) ) {
			GRC_Notifications::send( 'appointment_booked', 'customer', $params['customer_email'], 'email', array(
				'customer_name'    => sanitize_text_field( $params['customer_name'] ),
				'appointment_date' => ! empty( $params['appointment_primary'] ) ? sanitize_text_field( $params['appointment_primary'] ) : 'a time we\'ll confirm with you',
			), $lead_id );
		}
		if ( $agent_id ) {
			$agent_user = get_userdata( ( GRC_Referral_Codes::get_agent_by_code( $params['ref'] ) )->user_id ?? 0 );
			if ( $agent_user ) {
				GRC_Notifications::send( 'appointment_booked', 'agent', $agent_user->user_email, 'email', array(
					'customer_name'    => sanitize_text_field( $params['customer_name'] ),
					'agent_name'       => $agent_user->display_name,
					'appointment_date' => ! empty( $params['appointment_primary'] ) ? sanitize_text_field( $params['appointment_primary'] ) : 'not set',
				), $lead_id );
			}
		}

		$partners_table = GRC_DB::table( 'partners' );
		$partner = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$partners_table} WHERE id = %d", absint( $params['partner_id'] ) ) );
		if ( $partner && ! empty( $partner->email ) ) {
			GRC_Notifications::send( 'new_lead_for_partner', 'partner', $partner->email, 'email', array(
				'customer_name'      => sanitize_text_field( $params['customer_name'] ),
				'preferred_contact'  => sanitize_text_field( $params['preferred_contact'] ?? 'phone' ),
				'appointment_date'   => ! empty( $params['appointment_primary'] ) ? sanitize_text_field( $params['appointment_primary'] ) : 'not set',
			), $lead_id );
		}

		return rest_ensure_response( array( 'success' => true, 'lead_id' => $lead_id ) );
	}
}
