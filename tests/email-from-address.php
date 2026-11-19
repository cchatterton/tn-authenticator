<?php

define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );

$test_home_url = 'https://www.example.com';
$test_mail = array();

function wp_parse_url( $url, $component ) {
	return parse_url( $url, $component );
}

function home_url() {
	global $test_home_url;
	return $test_home_url;
}

function sanitize_email( $email ) {
	return filter_var( $email, FILTER_SANITIZE_EMAIL );
}

function is_email( $email ) {
	return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
}

function get_option( $name ) {
	return 'admin@fallback.example';
}

function get_bloginfo( $name ) {
	return 'Example Site';
}

function email_exists( $email ) {
	return 1;
}

function apply_filters( $hook, $value ) {
	return $value;
}

function add_filter() {}
function remove_filter() {}

function wp_mail( $to, $subject, $body, $headers ) {
	global $test_mail;
	$test_mail = compact( 'to', 'subject', 'body', 'headers' );
	return true;
}

function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function tn_auth_settings() {
	return array(
		'ttl'        => 300,
		'from_name'  => 'Example Site',
		'from_email' => 'old-setting@example.net',
		'subject'    => 'Your sign-in code',
	);
}

require_once dirname( __DIR__ ) . '/tn-authenticator/functions/email.php';

if ( 'no-reply@example.com' !== tn_auth_default_from_email() ) {
	throw new RuntimeException( 'Expected www. to be removed from the sender domain.' );
}

$test_home_url = 'https://members.example.com:8443/path';
if ( 'no-reply@members.example.com' !== tn_auth_default_from_email() ) {
	throw new RuntimeException( 'Expected the current site subdomain without port or path.' );
}

tn_auth_send_pin_email( 'member@example.net', '1234' );
if ( array( 'From: Example Site <no-reply@members.example.com>' ) !== $test_mail['headers'] ) {
	throw new RuntimeException( 'Expected the OTP send path to use the site-derived address.' );
}

$test_home_url = 'http://127.0.0.1:8080';
if ( 'admin@fallback.example' !== tn_auth_default_from_email() ) {
	throw new RuntimeException( 'Expected the administrator email fallback for an IP host.' );
}

$test_home_url = 'http://localhost:8080';
if ( 'admin@fallback.example' !== tn_auth_default_from_email() ) {
	throw new RuntimeException( 'Expected the administrator email fallback for a local hostname.' );
}

echo "PASS: OTP sender follows the current site domain\n";
