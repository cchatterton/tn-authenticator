<?php
/**
 * Code-driven login-pin route and template loader.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'tn_auth_add_rewrite_rule' );
add_action( 'admin_init', 'tn_auth_maybe_flush_rewrite_rules' );
add_filter( 'query_vars', 'tn_auth_register_query_vars' );
add_action( 'template_redirect', 'tn_auth_render_login_pin_route' );
add_action( 'wp_head', 'tn_auth_noindex_login_pin_route', 1 );

function tn_auth_add_rewrite_rule(): void {
	add_rewrite_rule( '^login-pin/?$', 'index.php?tn_auth_login_pin=1', 'top' );
}

function tn_auth_maybe_flush_rewrite_rules(): void {
	if ( TN_AUTH_VERSION === get_option( 'tn_auth_rewrite_version' ) ) {
		return;
	}

	tn_auth_add_rewrite_rule();
	flush_rewrite_rules();
	update_option( 'tn_auth_rewrite_version', TN_AUTH_VERSION, false );
}

function tn_auth_register_query_vars( $vars ) {
	$vars[] = 'tn_auth_login_pin';
	return $vars;
}

function tn_auth_is_login_pin_route(): bool {
	return '1' === (string) get_query_var( 'tn_auth_login_pin' );
}

function tn_auth_render_login_pin_route(): void {
	if ( ! tn_auth_is_login_pin_route() ) {
		return;
	}

	status_header( 200 );
	nocache_headers();

	include TN_AUTH_PLUGIN_DIR . 'templates/login-pin.php';
	exit;
}

function tn_auth_noindex_login_pin_route(): void {
	if ( tn_auth_is_login_pin_route() ) {
		echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	}
}
