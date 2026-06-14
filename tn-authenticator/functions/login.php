<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Verify PIN via admin-post (works when logged out).
 * PIN form should POST to: /wp-admin/admin-post.php?action=tn_auth_verify
 */
add_action('admin_post_nopriv_tn_auth_verify', 'tn_auth_handle_verify');
add_action('admin_post_tn_auth_verify',        'tn_auth_handle_verify');

function tn_auth_handle_verify() {
	// Only accept POSTs
	$request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
	if ( 'POST' !== $request_method ) return tn_auth_bounce_with_error('unknown');

	// Nonce
	$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
	if ( ! wp_verify_nonce( $nonce, 'tn_auth_verify' ) ) {
		return tn_auth_bounce_with_error('invalid'); // generic
	}

	$email = isset($_POST['tn_auth_email']) ? sanitize_email(trim(wp_unslash($_POST['tn_auth_email']))) : '';
	$pin   = isset($_POST['tn_auth_pin'])   ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['tn_auth_pin'])) : '';

	// Generic failure to avoid enumeration at verify step
	if ( empty($email) || ! is_email($email) || strlen($pin) !== 4 ) {
		return tn_auth_bounce_with_error('invalid');
	}

	$key  = function_exists('tn_auth_pin_key') ? tn_auth_pin_key($email) : ('tn_auth_pin_' . md5(strtolower(trim($email))));
	$data = get_transient($key);
	if ( ! is_array($data) || empty($data['pin_hash']) ) {
		return tn_auth_bounce_with_error('expired'); // generic "expired"
	}

	// Attempt cap (burn token after too many wrong tries)
	$max_attempts = (int) apply_filters('tn_auth_max_attempts', 5);
	$attempt_key  = 'tn_auth_attempts_' . md5(strtolower($email));
	$attempts     = (int) get_transient($attempt_key);
	if ( $attempts >= $max_attempts ) {
		delete_transient($key);
		delete_transient($attempt_key);
		return tn_auth_bounce_with_error('expired');
	}

	// Verify PIN
	if ( ! wp_check_password($pin, $data['pin_hash']) ) {
		set_transient($attempt_key, $attempts + 1, 10 * MINUTE_IN_SECONDS);
		return tn_auth_bounce_with_error('invalid');
	}

	// Success → consume & clear attempts
	delete_transient($key);
	delete_transient($attempt_key);

	// Find user & sign in (generic failure if somehow missing)
	$user = get_user_by('email', $email);
	if ( ! $user ) return tn_auth_bounce_with_error('invalid');

	// Optional role denylist (reduce risk of bypassing org 2FA)
	$deny_admins = (bool) apply_filters('tn_auth_block_admins', false);
	if ( $deny_admins && in_array('administrator', (array) $user->roles, true) ) {
		return tn_auth_bounce_with_error('invalid');
	}

	wp_set_current_user($user->ID);
	wp_set_auth_cookie($user->ID, false); // shorter session; set to true if you want remember-me

	// Fire core hook for compatibility (SSO, audit, etc.)
	do_action('wp_login', $user->user_login, $user);
	$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
	$user_agent  = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
	do_action('tn_auth_login', $user->ID, $email, $remote_addr, $user_agent);

	// Success → Dashboard
	wp_safe_redirect(admin_url());
	exit;
}

/** Bounce back with generic error code for the shortcode to show */
function tn_auth_bounce_with_error(string $code) {
	$back = wp_get_referer();
	if ( ! $back ) $back = home_url('/login-pin');
	wp_safe_redirect(add_query_arg('tn_auth_err', urlencode($code), $back));
	exit;
}
