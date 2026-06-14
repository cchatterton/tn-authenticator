<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Shortcode: [tn_auth]
 * Step 1: Email entry → send 4-digit PIN (only if user exists; message is generic)
 * Step 2: PIN entry → posts to /wp-admin/admin-post.php?action=tn_auth_verify
 */
add_shortcode('tn_auth', 'tn_auth_shortcode');

function tn_auth_shortcode() {
	return tn_auth_render_login_pin();
}

function tn_auth_render_login_pin(): string {
	if ( is_user_logged_in() ) {
		$u = wp_get_current_user();
		return '<p>You’re signed in as <strong>' . esc_html( $u->user_email ?: $u->user_login ) . '</strong>.</p>';
	}

	$msg_html = '';
	$email_value = '';

	// Show any error returned from the verify handler (generic)
	if ( isset($_GET['tn_auth_err']) ) {
		$msg_html .= tn_auth_notice('That code is invalid or expired. Please try again or request a new one.', false);
	}

	// Handle email submit
	$request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';

	if ( 'POST' === $request_method && isset($_POST['tn_auth_email']) ) {
		check_admin_referer('tn_auth_request');

		$email_value = sanitize_email( trim( wp_unslash( $_POST['tn_auth_email'] ) ) );
		if ( empty($email_value) || ! is_email($email_value) ) {
			$msg_html .= tn_auth_notice('Please enter a valid email address.', false);
			return $msg_html . tn_auth_render_email_form();
		}

		// --- Honeypot: if filled, pretend success but do nothing ---
		$honeypot = isset($_POST['tn_auth_hp']) ? sanitize_text_field(wp_unslash($_POST['tn_auth_hp'])) : '';
		if ( '' !== $honeypot ) {
			$msg_html .= tn_auth_notice('If an account exists, we’ve emailed a 4-digit sign-in code.', true);
			// Still show PIN form (no token stored) — remains generic
			return $msg_html . tn_auth_render_pin_form($email_value);
		}

		// --- Throttle: 5 sends / 10 min per email, 20 / 10 min per IP ---
		$ip       = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
		$rl_email = 'tn_auth_rl_e_'  . md5(strtolower($email_value));
		$rl_ip    = 'tn_auth_rl_ip_' . md5($ip);
		$e_cnt    = (int) get_transient($rl_email);
		$ip_cnt   = (int) get_transient($rl_ip);

		if ( $e_cnt >= 5 || $ip_cnt >= 20 ) {
			$msg_html .= tn_auth_notice('Too many requests. Please try again in a few minutes.', false);
			return $msg_html . tn_auth_render_email_form($email_value);
		}
		set_transient($rl_email, $e_cnt + 1, 10 * MINUTE_IN_SECONDS);
		set_transient($rl_ip,    $ip_cnt + 1, 10 * MINUTE_IN_SECONDS);

		// Generate a 4-digit PIN (0000–9999)
		$pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

		// Ask email.php to send (it returns generic message + exists flag)
		if ( ! function_exists('tn_auth_send_pin_email') ) {
			$msg_html .= tn_auth_notice('If an account exists, we’ve emailed a 4-digit sign-in code.', true);
			return $msg_html . tn_auth_render_pin_form($email_value);
		}
		$res = tn_auth_send_pin_email($email_value, $pin);
		$msg_html .= $res['message_html'] ?? tn_auth_notice('If an account exists, we’ve emailed a 4-digit sign-in code.', true);

		// Store hashed PIN only if a user exists for this email
		$exists = isset($res['exists']) ? (bool) $res['exists'] : false;
		if ( $exists ) {
			$settings = tn_auth_settings();
			$ttl      = max(60, (int) $settings['ttl']); // >= 1 minute
			$key      = tn_auth_pin_key($email_value);
			$pin_hash = wp_hash_password($pin);

			set_transient($key, [
				'pin_hash' => $pin_hash,
				'ts'       => time(),
				'email'    => $email_value,
				'ip'       => $ip,
				'ua'       => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
			], $ttl);
		}

		// Always show the PIN entry form to avoid enumeration
		return $msg_html . tn_auth_render_pin_form($email_value);
	}

	// Initial render
	return $msg_html . tn_auth_render_email_form();
}

/** ---------- form renderers & helpers ---------- */

function tn_auth_render_email_form( string $prefill = '' ) : string {
	ob_start();
	$label = apply_filters('tn_auth_field_label', 'Email');
	$btn   = apply_filters('tn_auth_button_text', 'Email me a sign-in code');
	?>
	<form method="post" class="tn-auth-form">
		<p>
			<label for="tn_auth_email"><strong><?php echo esc_html($label); ?></strong></label><br>
			<input type="email" name="tn_auth_email" id="tn_auth_email" class="regular-text"
			       required placeholder="you@example.com"
			       value="<?php echo esc_attr($prefill); ?>"
			>
		</p>
		<!-- Honeypot (hidden to humans) -->
		<input type="text" name="tn_auth_hp" value="" class="tn-auth-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
		<?php wp_nonce_field('tn_auth_request'); ?>
		<p><button type="submit" class="button button-primary"><?php echo esc_html($btn); ?></button></p>
	</form>
	<?php
	return ob_get_clean();
}

function tn_auth_render_pin_form( string $email ) : string {
	$action = admin_url('admin-post.php?action=tn_auth_verify');
	ob_start(); ?>
	<form method="post" action="<?php echo esc_url($action); ?>" class="tn-auth-pin-form">
		<p><strong>Enter the 4-digit code we emailed to <?php echo esc_html($email); ?>:</strong></p>
		<input type="hidden" name="action" value="tn_auth_verify">
		<input type="hidden" name="tn_auth_email" value="<?php echo esc_attr($email); ?>">
		<?php wp_nonce_field('tn_auth_verify'); ?>
		<p>
			<label for="tn_auth_pin">Code</label><br>
			<input type="text" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="4"
			       name="tn_auth_pin" id="tn_auth_pin" class="input"
			       autofocus required>
		</p>
		<p><button type="submit" class="button button-primary">Verify &amp; sign in</button></p>
	</form>
	<?php return ob_get_clean();
}

function tn_auth_notice( string $text, bool $positive ) : string {
	$cls = $positive ? 'notice-success' : 'notice-error';
	return '<div class="notice '.$cls.'"><p>'.esc_html($text).'</p></div>';
}

/** Settings (defaults; move to settings.php later) */
if ( ! defined('TN_AUTH_OPTION_SETTINGS') ) {
	define('TN_AUTH_OPTION_SETTINGS', 'tn_auth_settings');
}
function tn_auth_settings() : array {
	$defaults = [
		'ttl'       => 5 * MINUTE_IN_SECONDS, // PIN lifetime (shorter by default)
		'from_name' => get_bloginfo('name'),
		'from_email'=> get_option('admin_email'),
		'subject'   => 'Your sign-in code for ' . get_bloginfo('name'),
	];
	$opt = get_option( TN_AUTH_OPTION_SETTINGS, [] );
	return wp_parse_args( $opt, $defaults );
}

/** Transient key for an email address */
function tn_auth_pin_key( string $email ) : string {
	$norm = strtolower( trim( $email ) );
	return 'tn_auth_pin_' . md5( $norm );
}
