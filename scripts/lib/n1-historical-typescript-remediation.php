<?php

declare(strict_types=1);

/** @return array<string,mixed> */
function nexoraAnalyzeHistoricalTypeScriptRemediation(string $root): array
{
    $files = [
        'resources/js/admin/pages/Admin/Automation/Form.tsx' => 50,
        'resources/js/admin/pages/Admin/Cloud/Index.tsx' => 1,
        'resources/js/admin/pages/Admin/Discovery/Index.tsx' => 1,
        'resources/js/admin/pages/Admin/Distribution/Index.tsx' => 1,
        'resources/js/admin/pages/Admin/Documents/Form.tsx' => 3,
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx' => 14,
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx' => 1,
        'resources/js/admin/pages/Admin/Media/Index.tsx' => 1,
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx' => 1,
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx' => 1,
        'resources/js/admin/pages/Admin/Studio/Editor.tsx' => 2,
    ];

    $errors = [];
    $rows = [];

    $sourceChecks = [
        'resources/js/admin/pages/Admin/Automation/Form.tsx' => static function (string $source): array {
            $issues = [];
            if (! str_contains($source, 'useForm<WorkflowFormData>')) {
                $issues[] = 'explicit WorkflowFormData useForm generic missing';
            }
            if (! str_contains($source, 'type WorkflowScalar = string | number | boolean | null')) {
                $issues[] = 'finite WorkflowScalar form boundary missing';
            }
            if (! str_contains($source, 'type TriggerConfig = Record<string, WorkflowScalar>')) {
                $issues[] = 'finite TriggerConfig form boundary missing';
            }
            if (str_contains($source, 'Record<string, FormDataConvertible>')) {
                $issues[] = 'recursive FormDataConvertible workflow record remains';
            }
            if (str_contains($source, 'Record<string, unknown>')) {
                $issues[] = 'historical unknown record remains';
            }
            return $issues;
        },
        'resources/js/admin/pages/Admin/Cloud/Index.tsx' => static function (string $source): array {
            return str_contains($source, 'RequestPayload') ? [] : ['router payload is not RequestPayload'];
        },
        'resources/js/admin/pages/Admin/Discovery/Index.tsx' => static function (string $source): array {
            return str_contains($source, 'RequestPayload') ? [] : ['router payload is not RequestPayload'];
        },
        'resources/js/admin/pages/Admin/Distribution/Index.tsx' => static function (string $source): array {
            return preg_match('/transform\([^;]+\)\s*\.post\(/s', $source) === 1
                ? ['transform().post() chain remains']
                : [];
        },
        'resources/js/admin/pages/Admin/Documents/Form.tsx' => static function (string $source) use ($root): array {
            $issues = [];
            $editor = (string) @file_get_contents($root.'/resources/js/admin/components/writer/BlockEditor.tsx');
            if (! str_contains($source, 'useForm<FormData>')) {
                $issues[] = 'typed document useForm missing';
            }
            if (str_contains($source, 'form.errors.document')) {
                $issues[] = 'non-form document error key remains';
            }
            if (! str_contains($source, 'Deliberate shallow boundary: DocumentContent contains recursive WriterValue nodes.')) {
                $issues[] = 'recursive document form shallow boundary missing';
            }
            if (! str_contains($source, 'content: any;')) {
                $issues[] = 'recursive document content is still expanded through Inertia FormDataType';
            }
            if (! str_contains($source, 'form.data.content as DocumentContent')) {
                $issues[] = 'typed DocumentContent consumer boundary missing';
            }
            if (! str_contains($editor, 'export type WriterValue = WriterScalar | WriterValue[] | { [key: string]: WriterValue }')) {
                $issues[] = 'recursive WriterValue form-safe type missing';
            }
            if (str_contains($editor, 'data: Record<string, unknown>')) {
                $issues[] = 'writer block data still uses unknown';
            }
            return $issues;
        },
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx' => static function (string $source): array {
            $issues = [];
            if (! str_contains($source, 'Deliberate shallow boundary: SSO configuration and secret payload default server-side.')) {
                $issues[] = 'SSO shallow form boundary marker missing';
            }
            if (! str_contains($source, 'const ssoForm = useForm({')) {
                $issues[] = 'SSO form is not using the shallow inference boundary';
            }
            if (str_contains($source, 'useForm<SsoFormData>') || str_contains($source, 'Record<string, FormDataConvertible>')) {
                $issues[] = 'recursive SSO Inertia generic remains';
            }
            return $issues;
        },
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx' => static function (string $source): array {
            return str_contains($source, 'ButtonLink') && ! str_contains($source, '<NavLink')
                ? []
                : ['legacy NavLink children API remains'];
        },
        'resources/js/admin/pages/Admin/Media/Index.tsx' => static function (string $source): array {
            return preg_match('/transform\([^;]+\)\s*\.put\(/s', $source) === 1
                ? ['transform().put() chain remains']
                : [];
        },
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx' => static function (string $source): array {
            return str_contains($source, 'ButtonLink') && ! str_contains($source, '<NavLink')
                ? []
                : ['legacy NavLink children API remains'];
        },
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx' => static function (string $source): array {
            return preg_match('/transform\([^;]+\)\s*\.put\(/s', $source) === 1
                ? ['transform().put() chain remains']
                : [];
        },
        'resources/js/admin/pages/Admin/Studio/Editor.tsx' => static function (string $source): array {
            return preg_match('/transform\([^;]+\)\s*\.(put|post)\(/s', $source) === 1
                ? ['transform submit chain remains']
                : [];
        },
    ];

    $sourceRemediated = 0;
    foreach ($files as $file => $historicalErrors) {
        $path = $root.'/'.$file;
        $issues = [];
        if (! is_file($path)) {
            $issues[] = 'source file missing';
        } else {
            $source = (string) file_get_contents($path);
            $issues = $sourceChecks[$file]($source);
        }

        $fixed = $issues === [];
        if ($fixed) {
            $sourceRemediated += $historicalErrors;
        } else {
            foreach ($issues as $issue) {
                $errors[] = "{$file}: {$issue}";
            }
        }

        $rows[] = [
            'file' => $file,
            'historical_errors' => $historicalErrors,
            'source_remediated' => $fixed,
            'issues' => $issues,
        ];
    }

    $c1 = nexoraHistoricalTsReadJson($root.'/storage/app/nexora/n1-c1/latest.json');
    $targetVerified = is_array($c1)
        && ($c1['status'] ?? null) === 'pass'
        && nexoraHistoricalTsStepPassed($c1, 'typecheck')
        && nexoraHistoricalTsStepPassed($c1, 'vite-build');

    return [
        'errors' => $errors,
        'historical_error_total' => array_sum($files),
        'historical_file_total' => count($files),
        'source_remediated_errors' => $sourceRemediated,
        'source_remediated_files' => count(array_filter($rows, static fn (array $row): bool => $row['source_remediated'] === true)),
        'target_verified_errors' => $targetVerified ? array_sum($files) : 0,
        'target_verified_files' => $targetVerified ? count($files) : 0,
        'target_verified' => $targetVerified,
        'rows' => $rows,
        'meaning' => 'Source remediation proves historical error patterns are removed from current source. Only a dependency-backed C1 typecheck + Vite build can mark the historical 76 errors target-verified.',
    ];
}

/** @return array<string,mixed>|null */
function nexoraHistoricalTsReadJson(string $path): ?array
{
    if (! is_file($path)) {
        return null;
    }

    try {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($data) ? $data : null;
}

/** @param array<string,mixed> $report */
function nexoraHistoricalTsStepPassed(array $report, string $id): bool
{
    foreach ((array) ($report['steps'] ?? []) as $step) {
        if (! is_array($step) || ($step['id'] ?? null) !== $id) {
            continue;
        }

        return in_array(($step['status'] ?? null), ['pass', 'reused-pass'], true);
    }

    return false;
}
