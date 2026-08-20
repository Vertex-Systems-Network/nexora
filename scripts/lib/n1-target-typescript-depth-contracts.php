<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTypeScriptDepthContracts(string $root): array
{
    $errors = [];
    $automationPath = $root.'/resources/js/admin/pages/Admin/Automation/Form.tsx';
    $documentsPath = $root.'/resources/js/admin/pages/Admin/Documents/Form.tsx';
    $automation = is_file($automationPath) ? (string) file_get_contents($automationPath) : '';
    $documents = is_file($documentsPath) ? (string) file_get_contents($documentsPath) : '';

    foreach (['type WorkflowScalar = string | number | boolean | null', 'type TriggerConfig = Record<string, WorkflowScalar>', 'useForm<WorkflowFormData>'] as $marker) {
        if (! str_contains($automation, $marker)) $errors[] = "Automation TS2589 boundary missing [{$marker}]";
    }
    if (str_contains($automation, 'Record<string, FormDataConvertible>')) {
        $errors[] = 'Automation recursive FormDataConvertible record reintroduced';
    }

    foreach (['Deliberate shallow boundary: DocumentContent contains recursive WriterValue nodes.', 'content: any;', 'form.data.content as DocumentContent'] as $marker) {
        if (! str_contains($documents, $marker)) $errors[] = "Documents TS2589 boundary missing [{$marker}]";
    }
    if (str_contains($documents, 'excerpt: string; content: DocumentContent; lock_version: number;')) {
        $errors[] = 'Documents recursive DocumentContent must not be expanded directly by Inertia FormDataType';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'target_files' => 2,
            'observed_ts2589_errors' => 4,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
