<?php
/**
 * Plugin Name: TN Authenticator
 * Plugin URI: https://github.com/cchatterton/tn-authenticator
 * Description: Provides a plug-and-play PIN login page at /login-pin.
 * Version: 1.5
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Techn
 * Author URI: https://techn.com.au
 * Text Domain: tn-authenticator
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'TN_AUTH_VERSION', '1.5' );
define( 'TN_AUTH_PLUGIN_FILE', __FILE__ );
define( 'TN_AUTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TN_AUTH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$dir = TN_AUTH_PLUGIN_DIR . 'functions/';

foreach ( [
	'email.php',
	'shortcode.php',
	'login.php',
	'redirect.php',
	'route.php',
	'github-updater.php',
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
