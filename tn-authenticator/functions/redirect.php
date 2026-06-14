<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Add a "Login with PIN?" link to the native wp-login.php screen.
 */
function tn_auth_add_login_pin_link() {
	$url = home_url('/login-pin');
	echo '<p style="text-align:center;margin-top:12px">'
	   . '<a href="'.esc_url($url).'">Login with PIN?</a>'
	   . '</p>';
}
add_action('login_footer', 'tn_auth_add_login_pin_link');
