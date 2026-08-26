<?php
// This file runs when the plugin is deleted from wp-admin (not on
// deactivation). By design it does NOT drop tables automatically -
// losing partner/lead/commission history because someone clicked
// "Delete" on the plugin would be catastrophic. Data cleanup is a
// manual, deliberate action via Settings -> Danger Zone while the
// plugin is still active, not something tied to uninstall.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally left blank - see comment above.
