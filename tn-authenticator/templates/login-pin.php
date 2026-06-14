<?php
/**
 * Login PIN page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		body.tn-auth-login-pin {
			min-height: 100vh;
			margin: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #f6f7f7;
			color: #1d2327;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}
		.tn-auth-page {
			width: min(100% - 32px, 520px);
			padding: 32px;
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 8px;
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
		}
		.tn-auth-page h1 {
			margin: 0 0 8px;
			font-size: 28px;
			line-height: 1.2;
		}
		.tn-auth-page p {
			font-size: 15px;
			line-height: 1.5;
		}
		.tn-auth-page .regular-text,
		.tn-auth-page .input {
			box-sizing: border-box;
			width: 100%;
			max-width: 100%;
			padding: 10px 12px;
			border: 1px solid #8c8f94;
			border-radius: 4px;
			font-size: 16px;
		}
		.tn-auth-page .tn-auth-form {
			max-width: 560px;
		}
		.tn-auth-page .tn-auth-pin-form {
			max-width: 560px;
			margin-top: 16px;
		}
		.tn-auth-page .tn-auth-pin-form .input {
			max-width: 160px;
		}
		.tn-auth-page .tn-auth-hp {
			position: absolute;
			left: -9999px;
		}
		.tn-auth-page .button {
			display: inline-block;
			min-height: 36px;
			padding: 8px 14px;
			border: 1px solid #2271b1;
			border-radius: 4px;
			background: #2271b1;
			color: #fff;
			text-decoration: none;
			cursor: pointer;
		}
		.tn-auth-page .notice {
			margin: 1em 0;
			padding: 10px;
			border-left: 4px solid #d63638;
			background: #fcf0f1;
		}
		.tn-auth-page .notice-success {
			border-left-color: #00a32a;
			background: #edfaef;
		}
	</style>
</head>
<body <?php body_class( 'tn-auth-login-pin' ); ?>>
	<main class="tn-auth-page">
		<h1><?php echo esc_html( $site_name ); ?></h1>
		<p>Sign in with a one-time email code.</p>
		<?php echo tn_auth_render_login_pin(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
