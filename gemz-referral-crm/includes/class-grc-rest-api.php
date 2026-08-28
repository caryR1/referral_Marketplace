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

		$redirect = home_url( '/agent-portal/' );

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

		return rest_ensure_response( array( 'success' => true, 'lead_id' => $lead_id ) );
	}
}
