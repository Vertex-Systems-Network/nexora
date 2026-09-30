# PR #71 clean replacement preparation — 2026-10-01

Base: main `55c249c06621fe7dc14868b3a5740b657bcb6b7a`. Original quarantined head `004a63f501748d8e72892980d664d56c4f229a08` is not imported. Unit SYS-CORE-ADMIN-QA: source preparation authorized by owner; global runtime stage remains BLOCKED and formal Core QA acceptance is not claimed.

Original three suites: 76 methods, five placeholder assertions, six incomplete markers. Actual unchanged-suite diagnostic with ephemeral APP_KEY and SQLite: exit 2, 61 nonexistent users.role errors, five assertion failures, six incomplete cases. Failure evidence is retained on #71. The diagnostic host 708e792cca56df0f3230ea5619e6a52269f70dcf has an identical tracked tree to the base main.

New independent file: tests/Feature/Certification/QuarantinedPr71ReplacementTest.php. Eight executed regressions / 59 assertions PASS: guest inventory/dashboard exclusion; unverified administrator exclusion; suspended administrator exclusion; wrong-password guest state plus audit; correct-password suspended-login denial plus audit; logout/auth revocation plus audit; actual dashboard user/registered-module counts; module manifest hash mismatch reported against actual registered source. Existing oracles remain unchanged. PHP backend checks do not prove browser responsiveness, accessibility, load behavior, realtime behavior or production target acceptance.

Remaining original requirements are inventoried below. No automatic one-for-one acceptance is inferred from this new suite. Some cases duplicate existing tested product coverage, some assume nonexistent routes/features, and all require contract mapping before formal acceptance. No placeholder assertions or incomplete tests were copied into the clean suite.

## Original-case inventory (all formal acceptance pending)


### Auth

- `test_successful_login_with_valid_credentials` — pending contract/coverage mapping.
- `test_failed_login_with_invalid_credentials` — pending contract/coverage mapping.
- `test_logout_clears_session` — pending contract/coverage mapping.
- `test_session_persists_across_multiple_requests` — pending contract/coverage mapping.
- `test_token_refresh_on_expiry` — pending contract/coverage mapping.
- `test_rate_limiting_on_failed_login_attempts` — pending contract/coverage mapping.
- `test_concurrent_sessions_from_multiple_devices` — pending contract/coverage mapping.
- `test_password_reset_flow_initiation` — pending contract/coverage mapping.
- `test_account_lockout_after_repeated_failures` — pending contract/coverage mapping.
- `test_remember_me_persistence` — pending contract/coverage mapping.
- `test_session_invalidation_on_password_change` — pending contract/coverage mapping.
- `test_csrf_protection_on_login` — pending contract/coverage mapping.
- `test_login_email_case_insensitivity` — pending contract/coverage mapping.
- `test_two_factor_authentication_setup` — pending contract/coverage mapping.
- `test_oauth_integration` — pending contract/coverage mapping.
- `test_api_token_authentication` — pending contract/coverage mapping.
- `test_session_timeout_enforcement` — pending contract/coverage mapping.
- `test_login_audit_logging` — pending contract/coverage mapping.
- `test_concurrent_session_limit` — pending contract/coverage mapping.
- `test_secure_cookie_flags` — pending contract/coverage mapping.

### Admin

- `test_super_admin_can_access_dashboard` — pending contract/coverage mapping.
- `test_non_admin_users_cannot_access_dashboard` — pending contract/coverage mapping.
- `test_guest_users_redirected_to_login` — pending contract/coverage mapping.
- `test_dashboard_displays_system_statistics` — pending contract/coverage mapping.
- `test_dashboard_shows_recent_activity` — pending contract/coverage mapping.
- `test_navigation_menu_renders_correctly` — pending contract/coverage mapping.
- `test_quick_actions_are_available` — pending contract/coverage mapping.
- `test_system_health_indicators_display` — pending contract/coverage mapping.
- `test_dashboard_loads_within_acceptable_time` — pending contract/coverage mapping.
- `test_responsive_design_on_mobile` — pending contract/coverage mapping.
- `test_dark_mode_toggle` — pending contract/coverage mapping.
- `test_notification_badge_displays_unread_count` — pending contract/coverage mapping.
- `test_search_functionality` — pending contract/coverage mapping.
- `test_breadcrumb_navigation` — pending contract/coverage mapping.
- `test_user_profile_dropdown` — pending contract/coverage mapping.
- `test_help_documentation_links` — pending contract/coverage mapping.
- `test_keyboard_shortcuts` — pending contract/coverage mapping.
- `test_realtime_updates` — pending contract/coverage mapping.
- `test_export_dashboard_data` — pending contract/coverage mapping.
- `test_customizable_widgets` — pending contract/coverage mapping.
- `test_multilingual_support` — pending contract/coverage mapping.
- `test_accessibility_compliance` — pending contract/coverage mapping.
- `test_performance_under_load` — pending contract/coverage mapping.
- `test_session_timeout_warning` — pending contract/coverage mapping.
- `test_audit_logging_of_access` — pending contract/coverage mapping.

### ModuleManagement

- `test_super_admin_can_access_module_management` — pending contract/coverage mapping.
- `test_non_admin_cannot_access_module_management` — pending contract/coverage mapping.
- `test_module_list_displays_installed_modules` — pending contract/coverage mapping.
- `test_module_installation_from_marketplace` — pending contract/coverage mapping.
- `test_module_activation` — pending contract/coverage mapping.
- `test_module_deactivation` — pending contract/coverage mapping.
- `test_module_uninstallation` — pending contract/coverage mapping.
- `test_module_dependency_resolution` — pending contract/coverage mapping.
- `test_module_installation_fails_without_dependencies` — pending contract/coverage mapping.
- `test_module_version_upgrade` — pending contract/coverage mapping.
- `test_module_version_downgrade` — pending contract/coverage mapping.
- `test_module_configuration_update` — pending contract/coverage mapping.
- `test_module_search` — pending contract/coverage mapping.
- `test_module_filter_by_status` — pending contract/coverage mapping.
- `test_module_compatibility_check` — pending contract/coverage mapping.
- `test_module_rollback_on_failure` — pending contract/coverage mapping.
- `test_module_permissions_assignment` — pending contract/coverage mapping.
- `test_module_update_notifications` — pending contract/coverage mapping.
- `test_bulk_module_activation` — pending contract/coverage mapping.
- `test_marketplace_connectivity` — pending contract/coverage mapping.
- `test_module_license_validation` — pending contract/coverage mapping.
- `test_auto_update_settings` — pending contract/coverage mapping.
- `test_conflict_detection` — pending contract/coverage mapping.
- `test_backup_before_update` — pending contract/coverage mapping.
- `test_module_audit_logging` — pending contract/coverage mapping.
- `test_performance_impact_assessment` — pending contract/coverage mapping.
- `test_module_api_endpoints` — pending contract/coverage mapping.
- `test_database_migrations_execution` — pending contract/coverage mapping.
- `test_asset_publishing` — pending contract/coverage mapping.
- `test_translation_loading` — pending contract/coverage mapping.
- `test_health_check` — pending contract/coverage mapping.

## Combined backend verification

Actual corrected combined run: 488 tests / 4663 assertions, exit 0, PASS; no failures. First combined run failed an existing bootstrap process-environment assertion because inherited NPM_CONFIG_CACHE pointed to a missing directory. Configured an actual writable disposable npm cache and reran unchanged tests. No production code, existing assertion or tolerance was modified. Remote exact-head CI and source-only certification remain distinct gates.
