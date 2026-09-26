# Changelog

All notable changes to TN Authenticator are recorded here.

## 1.6.2 - 2026-09-26

- Lower the PHP requirement to 7.4, matching WordPress 7.0.
- Allow the PHP 7.4-compatible TN Update Controller bootstrap.

## 1.6.1 - 2026-09-26

- Require WordPress 7.0+ and PHP 8.5+ for this release.

- Replace the independent GitHub updater with the version 1 TN Update Controller integration.
- Add local Install/Activate/Check controller actions and standardise Techn author/repository metadata.
- Preserve plugin identity, feature code, settings and activation scope; no feature-plugin release discovery runs during page rendering.

## 1.6 - 2026-08-31

- Disabled native password reset controls for sessions authenticated with an emailed PIN.
- Kept password reset controls available to username/password sessions, including concurrent sessions for the same account.
- Added server-side validation to prevent direct profile password changes from a PIN-authenticated session.

## 1.5 - 2026-06-14

- Added a code-driven `/login-pin` route and plugin template.
- Kept the `[tn_auth]` shortcode as a backwards-compatible wrapper.
- Added compliant plugin metadata, version constant, and GitHub repository links.
- Added GitHub release update support for native WordPress plugin updates.
- Added stale update cleanup and forced update-check cache bypass handling.
- Added root `tn-authenticator.zip` direct upload package and release build script.
- Improved request, nonce, redirect, and server input handling.

## 1.4 - 2025-08-09

- Existing PIN authentication plugin build.
