<?php

declare(strict_types=1);

/**
 * Dependency-free browser/UX/accessibility source contract analysis.
 * This does not replace manual screen-reader or real-browser evidence; it prevents
 * known structural regressions before dependency-backed certification begins.
 *
 * @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int|string>}
 */
function nexoraAnalyzeBrowserUxContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $relative): string => is_file($root.'/'.$relative) ? (string) file_get_contents($root.'/'.$relative) : '';

    $layout = $read('resources/js/admin/layout/AdminLayout.tsx');
    $authLayout = $read('resources/js/admin/layout/AuthLayout.tsx');
    $css = $read('resources/css/app.css');
    $tokens = $read('resources/css/admin/tokens.css');
    $modal = $read('resources/js/admin/ui/untitled/modal.tsx');
    $input = $read('resources/js/admin/ui/untitled/input.tsx');
    $textarea = $read('resources/js/admin/ui/untitled/textarea.tsx');
    $iconButton = $read('resources/js/admin/ui/untitled/icon-button.tsx');
    $tooltip = $read('resources/js/admin/ui/untitled/tooltip.tsx');
    $select = $read('resources/js/admin/ui/untitled/select.tsx');
    $dataTable = $read('resources/js/admin/components/data/DataTable.tsx');
    $palette = $read('resources/js/admin/components/CommandPalette.tsx');
    $toast = $read('resources/js/admin/providers/ToastProvider.tsx');
    $loading = $read('resources/js/admin/components/LoadingStates.tsx');
    $appView = $read('resources/views/app.blade.php');

    foreach ([
        'AdminLayout skip link' => str_contains($layout, 'href="#nexora-main-content"') && str_contains($layout, 'id="nexora-main-content"'),
        'AdminLayout language/direction sync' => str_contains($layout, 'document.documentElement.lang') && str_contains($layout, 'document.documentElement.dir'),
        'server HTML language/direction' => str_contains($appView, '<html lang=') && str_contains($appView, ' dir='),
        'reduced motion CSS' => str_contains($css, '@media (prefers-reduced-motion: reduce)'),
        'forced colors focus fallback' => str_contains($css, '@media (forced-colors: active)'),
        'visible skip-link CSS' => str_contains($css, '.nx-skip-link:focus-visible'),
        'RTL route progress' => str_contains($css, '[dir="rtl"] .nx-route-progress'),
        'focus-visible primitive' => str_contains($css, '.nx-focus:focus-visible'),
        'light tokens' => str_contains($tokens, ':root {'),
        'dark tokens' => str_contains($tokens, ':root.dark'),
        'modal dialog semantics' => str_contains($modal, 'role="dialog"') && str_contains($modal, 'aria-modal="true"') && str_contains($modal, 'aria-labelledby={titleId}'),
        'modal keyboard/focus restoration' => str_contains($modal, 'e.key==="Escape"') && str_contains($modal, 'previous?.focus()'),
        'input accessible descriptions' => str_contains($input, 'aria-describedby=') && str_contains($input, 'aria-errormessage=') && str_contains($input, 'role="alert"'),
        'textarea accessible descriptions' => str_contains($textarea, 'aria-describedby=') && str_contains($textarea, 'aria-errormessage=') && str_contains($textarea, 'role="alert"'),
        'icon button label + tooltip' => str_contains($iconButton, 'aria-label={label}') && str_contains($iconButton, '<Tooltip content={label}'),
        'tooltip keyboard support' => str_contains($tooltip, 'onFocusCapture=') && str_contains($tooltip, 'role="tooltip"'),
        'select accessible label fallback' => str_contains($select, 'aria-label={ariaLabel ??'),
        'DataTable scroll keyboard reachability' => str_contains($dataTable, 'tabIndex={0}') && str_contains($dataTable, 'aria-label="Scrollable data table"'),
        'DataTable sort semantics' => str_contains($dataTable, 'aria-sort={ariaSort}'),
        'DataTable loading semantics' => str_contains($dataTable, 'aria-busy={loading || undefined}') && str_contains($dataTable, 'Loading table data'),
        'DataTable pagination landmark' => str_contains($dataTable, 'aria-label="Table pagination"') && str_contains($dataTable, 'aria-current={link.active?"page":undefined}'),
        'command palette dialog semantics' => str_contains($palette, 'role="dialog"') && str_contains($palette, 'aria-modal="true"'),
        'command palette focus trap/restore' => str_contains($palette, 'previous?.focus()') && str_contains($palette, 'event.key !== "Tab"'),
        'command palette live results status' => str_contains($palette, 'aria-live="polite"') && str_contains($palette, 'aria-label="Search results"'),
        'toast live semantics' => str_contains($toast, 'aria-live=') && str_contains($toast, 'role={toast.kind === "error" ? "alert" : "status"}'),
        'empty/error state semantics' => str_contains($loading, 'role="status"') && str_contains($loading, 'role="alert"'),
        'auth RTL logical border' => str_contains($authLayout, 'border-s border-[var(--nx-border)]'),
    ] as $label => $ok) {
        if (! $ok) $errors[] = $label.' contract missing.';
    }

    $adminRoot = $root.'/resources/js/admin';
    $physicalText = [];
    $physicalPositioning = [];
    $rawInteractive = [];
    $nativeDates = [];
    $filesChecked = 0;
    if (is_dir($adminRoot)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['ts','tsx'], true)) continue;
            $filesChecked++;
            $relative = str_replace('\\','/',substr($file->getPathname(), strlen($root)+1));
            $source = (string) file_get_contents($file->getPathname());
            if (str_contains($source, 'text-left')) $physicalText[] = $relative;
            if (! str_contains($relative, '/ui/untitled/tooltip.tsx') && preg_match('/\b(?:left|right)-(?:0|[1-9][0-9]*|\[[^\]]+\])/', $source) === 1) $physicalPositioning[] = $relative;
            if (! str_contains($relative, '/ui/')) {
                if (preg_match('/<(button|input|select|textarea)\b/', $source) === 1) $rawInteractive[] = $relative;
                if (preg_match('/type=[\'\"](?:date|time|datetime-local|month|week)[\'\"]/i', $source) === 1) $nativeDates[] = $relative;
            }
        }
    }
    if ($physicalText !== []) $errors[] = 'Physical text alignment remains in Admin; use logical text-start/text-end: '.implode(', ', array_slice($physicalText,0,10));
    if ($physicalPositioning !== []) $errors[] = 'Physical left/right utility remains in shared Admin surface; use logical start/end: '.implode(', ', array_slice($physicalPositioning,0,10));
    if ($rawInteractive !== []) $errors[] = 'Raw feature interactive controls remain: '.implode(', ', array_slice($rawInteractive,0,10));
    if ($nativeDates !== []) $errors[] = 'Native date/time inputs remain: '.implode(', ', array_slice($nativeDates,0,10));

    $evidenceTemplate = $root.'/docs/browser-certification-evidence.example.json';
    if (! is_file($evidenceTemplate)) {
        $errors[] = 'Browser certification evidence template is missing.';
    } else {
        try {
            $json = json_decode((string) file_get_contents($evidenceTemplate), true, 512, JSON_THROW_ON_ERROR);
            if (($json['schema'] ?? null) !== 2) $errors[] = 'Browser evidence template schema must be 2.';
            if (count((array)($json['matrix'] ?? [])) !== 36) $errors[] = 'Browser evidence template must include 36 fail-closed browser matrix rows.';
            if (!isset($json['assistive_technology'])) $errors[] = 'Browser evidence template must include assistive technology observation fields.';
        } catch (Throwable $e) {
            $errors[] = 'Browser evidence template JSON is invalid: '.$e->getMessage();
        }
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'admin_files' => $filesChecked,
            'physical_text_alignment' => count($physicalText),
            'physical_positioning' => count($physicalPositioning),
            'raw_feature_controls' => count($rawInteractive),
            'native_date_time_inputs' => count($nativeDates),
        ],
    ];
}
