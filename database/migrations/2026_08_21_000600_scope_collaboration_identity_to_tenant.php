<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const REVIEW_COMMENTS = 'nx_document_review_comments';
    private const REVIEW_TENANT_INDEX = 'nx_review_comments_tenant_document_idx';
    private const REVIEW_TENANT_FOREIGN = 'nx_review_comments_tenant_fk';

    private const ADMIN_NOTIFICATIONS = 'nx_admin_notifications';
    private const NOTIFICATION_TENANT_INDEX = 'nx_admin_notifications_tenant_user_idx';
    private const NOTIFICATION_TENANT_FOREIGN = 'nx_admin_notifications_tenant_fk';

    public function up(): void
    {
        Schema::table(self::REVIEW_COMMENTS, static function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index(['tenant_id', 'document_id'], self::REVIEW_TENANT_INDEX);
            $table->foreign('tenant_id', self::REVIEW_TENANT_FOREIGN)
                ->references('id')
                ->on('nx_enterprise_organizations')
                ->nullOnDelete();
        });

        Schema::table(self::ADMIN_NOTIFICATIONS, static function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index(['tenant_id', 'user_id'], self::NOTIFICATION_TENANT_INDEX);
            $table->foreign('tenant_id', self::NOTIFICATION_TENANT_FOREIGN)
                ->references('id')
                ->on('nx_enterprise_organizations')
                ->nullOnDelete();
        });

        $this->backfillReviewCommentTenants();
        $this->backfillNotificationTenants();
    }

    public function down(): void
    {
        Schema::table(self::ADMIN_NOTIFICATIONS, static function (Blueprint $table): void {
            $table->dropForeign(self::NOTIFICATION_TENANT_FOREIGN);
            $table->dropIndex(self::NOTIFICATION_TENANT_INDEX);
            $table->dropColumn('tenant_id');
        });

        Schema::table(self::REVIEW_COMMENTS, static function (Blueprint $table): void {
            $table->dropForeign(self::REVIEW_TENANT_FOREIGN);
            $table->dropIndex(self::REVIEW_TENANT_INDEX);
            $table->dropColumn('tenant_id');
        });
    }

    private function backfillReviewCommentTenants(): void
    {
        DB::table(self::REVIEW_COMMENTS)
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(250, static function ($comments): void {
                $documentIds = $comments->pluck('document_id')->filter()->unique()->values();
                if ($documentIds->isEmpty()) {
                    return;
                }

                $documentTenants = DB::table('nx_documents')
                    ->whereIn('id', $documentIds)
                    ->pluck('tenant_id', 'id');

                foreach ($comments as $comment) {
                    $tenantId = $documentTenants->get($comment->document_id);
                    if (! is_string($tenantId) || $tenantId === '') {
                        // An orphaned/legacy comment has no trustworthy tenant identity.
                        // Leave it NULL so BelongsToTenant excludes it from every tenant.
                        continue;
                    }

                    DB::table(self::REVIEW_COMMENTS)
                        ->where('id', $comment->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $tenantId]);
                }
            });
    }

    private function backfillNotificationTenants(): void
    {
        DB::table(self::ADMIN_NOTIFICATIONS)
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(250, static function ($notifications): void {
                $userIds = $notifications->pluck('user_id')->filter()->unique()->values();
                if ($userIds->isEmpty()) {
                    return;
                }

                $memberships = DB::table('nx_enterprise_organization_members')
                    ->whereIn('user_id', $userIds)
                    ->where('status', 'active')
                    ->get(['user_id', 'organization_id'])
                    ->groupBy('user_id');

                foreach ($notifications as $notification) {
                    $organizationIds = $memberships
                        ->get($notification->user_id, collect())
                        ->pluck('organization_id')
                        ->filter(static fn ($organizationId): bool => is_string($organizationId) && $organizationId !== '')
                        ->unique()
                        ->values();

                    if ($organizationIds->count() !== 1) {
                        // Historical user-only notifications cannot be attributed safely
                        // when the user belongs to zero or multiple active organizations.
                        // Keeping tenant_id NULL makes the new tenant scope fail closed.
                        continue;
                    }

                    DB::table(self::ADMIN_NOTIFICATIONS)
                        ->where('id', $notification->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $organizationIds->first()]);
                }
            });
    }
};
