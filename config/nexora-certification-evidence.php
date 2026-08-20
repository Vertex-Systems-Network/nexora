<?php

declare(strict_types=1);

$hours = static function (string $name, int $default): int {
    $value = getenv($name);
    return max(1, is_string($value) && trim($value) !== '' ? (int) $value : $default);
};

return [
    'max_age_hours' => [
        'zero_install' => $hours('NEXORA_ZERO_INSTALL_EVIDENCE_MAX_AGE_HOURS', 168),
        'upgrade_rehearsal' => $hours('NEXORA_UPGRADE_EVIDENCE_MAX_AGE_HOURS', 168),
        'backup_restore' => $hours('NEXORA_BACKUP_RESTORE_EVIDENCE_MAX_AGE_HOURS', 168),
        'browser' => $hours('NEXORA_BROWSER_EVIDENCE_MAX_AGE_HOURS', 72),
        'web_vitals' => $hours('NEXORA_WEB_VITALS_EVIDENCE_MAX_AGE_HOURS', 24),
        'http_performance' => $hours('NEXORA_HTTP_EVIDENCE_MAX_AGE_HOURS', 24),
        'multi_node_ha' => $hours('NEXORA_HA_EVIDENCE_MAX_AGE_HOURS', 24),
    ],
    'certification_session_max_age_hours' => $hours('NEXORA_CERTIFICATION_SESSION_MAX_AGE_HOURS', 168),
    'max_future_clock_skew_seconds' => max(0, (int) (getenv('NEXORA_EVIDENCE_MAX_FUTURE_CLOCK_SKEW_SECONDS') ?: 300)),
    'final_target_https_required' => filter_var(getenv('NEXORA_FINAL_TARGET_HTTPS_REQUIRED') ?: 'true', FILTER_VALIDATE_BOOL),
];
