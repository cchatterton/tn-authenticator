<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Password changes are disabled only for the session created by PIN login.
 * Other sessions for the same user, including username/password sessions,
 * retain WordPress's native password controls.
 */

add_action( 'set_auth_cookie', 'tn_auth_record_pin_session', 10, 6 );
add_filter( 'show_password_fields', 'tn_auth_filter_password_fields', 10, 2 );
add_filter( 'allow_password_reset', 'tn_auth_filter_password_reset', 10, 2 );
add_action( 'user_profile_update_errors', 'tn_auth_block_profile_password_change', 10, 3 );

/** Record the token for an authentication cookie created by the PIN flow. */
function tn_auth_record_pin_session( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ): void {
	if ( empty( $GLOBALS['tn_auth_pin_login_in_progress'] ) || empty( $token ) ) {
		return;
	}

	$sessions = tn_auth_get_pin_sessions( (int) $user_id );
	$sessions[ tn_auth_hash_session_token( (string) $token ) ] = (int) $expiration;

	update_user_meta( (int) $user_id, '_tn_auth_pin_sessions', $sessions );
}

/** Return non-expired PIN-session markers for a user. */
function tn_auth_get_pin_sessions( int $user_id ): array {
	$stored   = get_user_meta( $user_id, '_tn_auth_pin_sessions', true );
	$sessions = is_array( $stored ) ? $stored : [];
	$now      = time();

	foreach ( $sessions as $token_hash => $expiration ) {
		if ( ! is_string( $token_hash ) || (int) $expiration < $now ) {
			unset( $sessions[ $token_hash ] );
		}
	}

	if ( $sessions !== $stored ) {
		if ( empty( $sessions ) ) {
			delete_user_meta( $user_id, '_tn_auth_pin_sessions' );
		} else {
			update_user_meta( $user_id, '_tn_auth_pin_sessions', $sessions );
		}
	}

	return $sessions;
}

/** Hash session tokens before storing them as user metadata. */
function tn_auth_hash_session_token( string $token ): string {
	return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
}

/** Whether the current authenticated request belongs to a PIN session. */
function tn_auth_is_pin_session(): bool {
	$user_id = get_current_user_id();
	$token   = wp_get_session_token();

	if ( ! $user_id || empty( $token ) ) {
		return false;
	}

	$sessions = tn_auth_get_pin_sessions( $user_id );
	$token_key = tn_auth_hash_session_token( $token );

	return isset( $sessions[ $token_key ] );
}

/** Hide the native password fields when a PIN user edits their own profile. */
function tn_auth_filter_password_fields( bool $show, WP_User $profile_user ): bool {
	if ( get_current_user_id() === $profile_user->ID && tn_auth_is_pin_session() ) {
		return false;
	}

	return $show;
}

/** Deny native password-reset requests made from the active PIN session. */
function tn_auth_filter_password_reset( $allow, int $user_id ) {
	if ( get_current_user_id() === $user_id && tn_auth_is_pin_session() ) {
		return false;
	}

	return $allow;
}

/** Reject a forged/direct profile submission that attempts a password change. */
function tn_auth_block_profile_password_change( WP_Error $errors, bool $update, stdClass $user ): void {
	if ( ! $update || get_current_user_id() !== (int) $user->ID || ! tn_auth_is_pin_session() ) {
		return;
	}

	$password_submitted =
		( isset( $_POST['pass1'] ) && '' !== (string) wp_unslash( $_POST['pass1'] ) ) ||
		( isset( $_POST['pass2'] ) && '' !== (string) wp_unslash( $_POST['pass2'] ) );

	if ( $password_submitted ) {
		$errors->add(
			'tn_auth_password_change_disabled',
			__( 'Password changes are unavailable when you sign in with an emailed code. Sign in with your username and password to change it.', 'tn-authenticator' )
		);
	}
}
