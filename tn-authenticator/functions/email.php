<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Send a 4-digit PIN email only if a WP user exists.
 * Always returns a generic user-facing message to prevent enumeration.
 *
 * @return array { sent(bool), message(string), message_html(string), exists(bool), mailed(bool) }
 */
function tn_auth_send_pin_email( string $email, string $pin ) : array {
	$generic_msg = "If an account exists, we’ve emailed a 4-digit sign-in code.";

	// Quick validation; treat invalid as "no user" but keep generic message
	if ( empty($email) || ! is_email($email) ) {
		return tn_auth_build_result(true, $generic_msg, ['exists' => false, 'mailed' => false]);
	}

	$user_id = email_exists($email); // user ID or false
	if ( ! $user_id ) {
		// No email sent; same public message
		return tn_auth_build_result(true, $generic_msg, ['exists' => false, 'mailed' => false]);
	}

	$settings = function_exists('tn_auth_settings') ? tn_auth_settings() : [
		'ttl'       => 5 * MINUTE_IN_SECONDS, // shorter TTL by default
		'from_name' => get_bloginfo('name'),
		'from_email'=> get_option('admin_email'),
		'subject'   => 'Your sign-in code for ' . get_bloginfo('name'),
	];

	$ttl_mins = max(1, (int) floor(($settings['ttl'] ?? 300) / 60));

	$subject = apply_filters('tn_auth_email_subject', $settings['subject'], $email);
	$body    = apply_filters('tn_auth_email_body', tn_auth_default_pin_email_body($pin, $ttl_mins), $email, $pin);
	$headers = [];

	if ( ! empty($settings['from_email']) ) {
		$from_name  = $settings['from_name'] ?: get_bloginfo('name');
		$from_email = $settings['from_email'];
		$headers[]  = 'From: ' . $from_name . ' <' . $from_email . '>';
	}

	add_filter('wp_mail_content_type', 'tn_auth_mail_content_type');
	$mailed = wp_mail($email, $subject, $body, $headers);
	remove_filter('wp_mail_content_type', 'tn_auth_mail_content_type');

	// Always show the same message; include exists/mailed for logs
	return tn_auth_build_result(true, $generic_msg, [
		'exists' => true,
		'mailed' => (bool) $mailed,
	]);
}

function tn_auth_build_result(bool $positive, string $message, array $extras = []) : array {
	$cls  = $positive ? 'notice-success' : 'notice-error';
	$html = '<div class="notice '.$cls.'" style="margin:1em 0;padding:10px"><p>'.esc_html($message).'</p></div>';
	return array_merge([
		'sent'         => $positive,
		'message'      => $message,
		'message_html' => $html,
	], $extras);
}

function tn_auth_default_pin_email_body(string $pin, int $ttl_mins) : string {
	$site = esc_html(get_bloginfo('name'));
	$pin4 = esc_html($pin);
	return '
		<p>Your one-time sign-in code for <strong>'.$site.'</strong> is:</p>
		<p style="font-size:22px;letter-spacing:3px;margin:16px 0"><strong>'.$pin4.'</strong></p>
		<p>This code expires in '.$ttl_mins.' minute'.($ttl_mins === 1 ? '' : 's').'.</p>
	';
}

function tn_auth_mail_content_type() { return 'text/html; charset=UTF-8'; }
