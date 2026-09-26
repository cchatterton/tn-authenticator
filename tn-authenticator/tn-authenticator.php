<?php
/**
 * Plugin Name: TN Authenticator
 * Description: Provides a plug-and-play PIN login page at /login-pin.
 * Version: 1.6.1
 * Requires at least: 7.0
 * Requires PHP: 8.5
 * Author: Techn
 * Author URI: https://techn.com.au
 * Update URI: https://github.com/cchatterton/tn-authenticator
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Techn Controller API: 1
 * Text Domain: tn-authenticator
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'TN_AUTH_VERSION', '1.6.1' );
define( 'TN_AUTH_PLUGIN_FILE', __FILE__ );
define( 'TN_AUTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TN_AUTH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$dir = TN_AUTH_PLUGIN_DIR . 'functions/';

foreach ( [
	'email.php',
	'shortcode.php',
	'login.php',
	'password-reset.php',
	'redirect.php',
	'route.php',
] as $file ) {
	require_once $dir . $file;
}

register_activation_hook( TN_AUTH_PLUGIN_FILE, 'tn_auth_activate' );
register_deactivation_hook( TN_AUTH_PLUGIN_FILE, 'tn_auth_deactivate' );

function tn_auth_activate(): void {
	if ( function_exists( 'tn_auth_add_rewrite_rule' ) ) {
		tn_auth_add_rewrite_rule();
	}

	flush_rewrite_rules();
}

function tn_auth_deactivate(): void {
	flush_rewrite_rules();
}

require_once __DIR__ . '/functions/controller-client.php';
tnuc_client_register(__FILE__, 'tn-authenticator');
