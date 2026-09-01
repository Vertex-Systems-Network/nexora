<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Collaboration source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Collaboration source file: {$relative}";
        return '';
    }
    return $contents;
};

$documentController = $read('app/Http/Controllers/Admin/Content/DocumentController.php');
$reviewController = $read('app/Http/Controllers/Admin/Content/DocumentReviewController.php');
$reviewModel = $read('app/Models/DocumentReviewComment.php');
$notificationModel = $read('app/Models/AdminNotification.php');
$notificationController = $read('app/Http/Controllers/Admin/NotificationController.php');
$migration = $read('database/migrations/2026_08_21_000600_scope_collaboration_identity_to_tenant.php');
$routes = $read('routes/web.php');
$test = $read('tests/Feature/Collaboration/CollaborationTenantIsolationTest.php');

foreach ([
    'private TenantMemberDirectory $tenantMembers' => 'shared tenant-member directory dependency',
    '$this->tenantMembers->activeUsers()' => 'tenant-scoped collaborator chooser',
    "'assigned_to' => ['nullable', 'integer', new TenantMemberExists()]" => 'tenant-member assignee validation',
    "'reviewer_id' => ['nullable', 'integer', new TenantMemberExists()]" => 'tenant-member reviewer validation',
] as $needle => $label) {
    if ($documentController !== '' && ! str_contains($documentController, $needle)) {
        $errors[] = "Document collaboration contract missing: {$label}.";
    }
}
if ($documentController !== '' && str_contains($documentController, 'User::query()')) {
    $errors[] = 'Document collaborator picker still contains platform-wide User::query().';
}
if ($documentController !== '' && str_contains($documentController, 'exists:users,id')) {
    $errors[] = 'Document collaboration validation still contains platform-wide exists:users,id.';
}

foreach ([
    "'tenant_id' => \$document->tenant_id" => 'review comment tenant inheritance from parent document',
    'abort_unless((int) $comment->document_id === (int) $document->id, 404)' => 'review comment parent-document defense in depth',
] as $needle => $label) {
    if ($reviewController !== '' && ! str_contains($reviewController, $needle)) {
        $errors[] = "Document review controller contract missing: {$label}.";
    }
}

foreach ([
    'use BelongsToTenant;' => 'tenant global scope',
    "protected \$table = 'nx_document_review_comments'" => 'review comment table binding',
] as $needle => $label) {
    if ($reviewModel !== '' && ! str_contains($reviewModel, $needle)) {
        $errors[] = "Review comment model contract missing: {$label}.";
    }
}
foreach ([
    'use BelongsToTenant;' => 'tenant global scope',
    "protected \$table = 'nx_admin_notifications'" => 'admin notification table binding',
    "'tenant_id'" => 'tenant identity fillable field',
] as $needle => $label) {
    if ($notificationModel !== '' && ! str_contains($notificationModel, $needle)) {
        $errors[] = "Admin notification model contract missing: {$label}.";
    }
}

foreach ([
    "->where('user_id', \$request->user()->id)" => 'current-user notification boundary',
    "whereNull('read_at')" => 'tenant-scoped unread update boundary',
] as $needle => $label) {
    if ($notificationController !== '' && ! str_contains($notificationController, $needle)) {
        $errors[] = "Admin notification controller contract missing: {$label}.";
    }
}

foreach ([
    "private const REVIEW_COMMENTS = 'nx_document_review_comments'" => 'review comment forward tenantization',
    "private const ADMIN_NOTIFICATIONS = 'nx_admin_notifications'" => 'notification forward tenantization',
    '$this->backfillReviewCommentTenants()' => 'parent-document review tenant backfill',
    '$this->backfillNotificationTenants()' => 'legacy notification tenant backfill',
    "->where('status', 'active')" => 'active membership-only historical inference',
    '$organizationIds->count() !== 1' => 'ambiguous legacy notification fail-closed branch',
    "->whereNull('tenant_id')" => 'nullable unresolved legacy identity boundary',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "Collaboration tenant migration contract missing: {$label}.";
    }
}

foreach ([
    "permission:documents.review" => 'document review permission boundary',
    'DocumentReviewController' => 'document review route wiring',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Collaboration route contract missing: {$label}.";
    }
}

foreach ([
    'test_document_collaborator_directory_and_validation_exclude_cross_tenant_users' => 'cross-tenant assignee/reviewer acceptance test',
    'assertNotContains($otherUser->id, $memberIds)' => 'collaborator chooser non-disclosure assertion',
    'test_review_comments_and_admin_notifications_are_isolated_by_tenant' => 'collaboration model isolation acceptance test',
    'test_ambiguous_legacy_notification_without_tenant_fails_closed' => 'legacy ambiguous notification acceptance test',
    "withoutGlobalScope('nexora_tenant')" => 'fail-closed legacy row control assertion',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Collaboration acceptance contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Collaboration Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Collaboration Product Contract] PASS — document collaborators are tenant-member scoped, review comments and admin notifications carry tenant identity, ambiguous legacy notifications fail closed, and review permissions remain enforced.'.PHP_EOL,
);
