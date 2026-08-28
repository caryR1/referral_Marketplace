<?php
/**
 * Plugin Name: Gemz Referral CRM
 * Plugin URI: https://refer.gemzonline.com
 * Description: Custom CRM + funnel + payout system for the Gemz referral/cashback platform (roofing, HVAC, solar referrals). Manages fulfillment partners, leads, agents, multi-level commissions, campaigns, appointments, and notifications.
 * Version: 0.5.0
 * Author: Gemz
 * Text Domain: gemz-referral-crm
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
define( 'GRC_VERSION', '0.5.0' );
define( 'GRC_PLUGIN_FILE', __FILE__ );
define( 'GRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GRC_DB_VERSION', '0.3.0' ); // bump this whenever schema changes; activator checks it.

// ---------------------------------------------------------------------------
// Includes (order matters: db schema/helpers first, then things that use them)
// ---------------------------------------------------------------------------
require_once GRC_PLUGIN_DIR . 'includes/class-grc-db.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-industries.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-activator.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-deactivator.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-roles.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-referral-codes.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-coverage.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-rest-api.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-notifications.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-commissions.php';
require_once GRC_PLUGIN_DIR . 'includes/class-grc-customer-payouts.php';

require_once GRC_PLUGIN_DIR . 'public/class-grc-public.php';
require_once GRC_PLUGIN_DIR . 'public/class-grc-agent-portal.php';
require_once GRC_PLUGIN_DIR . 'public/class-grc-agent-signup.php';
require_once GRC_PLUGIN_DIR . 'public/class-grc-agent-login.php';
require_once GRC_PLUGIN_DIR . 'public/class-grc-claim-cashback.php';

if ( is_admin() ) {
	require_once GRC_PLUGIN_DIR . 'admin/class-grc-admin.php';
	require_once GRC_PLUGIN_DIR . 'admin/class-grc-email-templates.php';
}

// ---------------------------------------------------------------------------
// Activation / deactivation
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, array( 'GRC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GRC_Deactivator', 'deactivate' ) );

/**
 * Safety net: if the DB schema version stored in options doesn't match
 * GRC_DB_VERSION (e.g. after a plugin code update was deployed via Git
 * without re-running activation), re-run table creation. This is what
 * makes "push new plugin code" safe without a manual reactivate step.
 */
add_action( 'plugins_loaded', function () {
	$installed_version = get_option( 'grc_db_version', '' );
	if ( $installed_version !== GRC_DB_VERSION ) {
		GRC_Activator::create_tables();
		update_option( 'grc_db_version', GRC_DB_VERSION );
	}
} );

/**
 * Rewrite rules (the /go/{slug} tracking links) need a flush whenever
 * they change - same "safe after a Git push, no manual reactivate"
 * reasoning as the DB version check above.
 */
define( 'GRC_REWRITE_VERSION', '0.1.0' );
add_action( 'init', function () {
	if ( get_option( 'grc_rewrite_version', '' ) !== GRC_REWRITE_VERSION ) {
		flush_rewrite_rules();
		update_option( 'grc_rewrite_version', GRC_REWRITE_VERSION );
	}
}, 20 ); // after GRC_Public::add_rewrite_rule() has registered the rule

// ---------------------------------------------------------------------------
// Bootstrap runtime pieces
// ---------------------------------------------------------------------------
add_action( 'init', array( 'GRC_Roles', 'register_roles_and_caps' ) );
add_action( 'rest_api_init', array( 'GRC_REST_API', 'register_routes' ) );
