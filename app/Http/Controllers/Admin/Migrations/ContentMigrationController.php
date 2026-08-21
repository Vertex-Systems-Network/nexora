<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Migrations;

use App\Http\Controllers\Controller;
use App\Models\ContentMigrationRun;
use App\Nexora\Migrations\Services\ContentExportService;
use App\Nexora\Migrations\Services\ContentMigrationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ContentMigrationController extends Controller
{
    public function index(): Response
    {
        $runs = ContentMigrationRun::query()
            ->with('creator:id,name,email')
            ->latest()
            ->limit(50)
            ->get()
            ->map(static fn (ContentMigrationRun $run): array => [
                'id' => $run->id,
                'sourceType' => $run->source_type,
                'sourceName' => $run->source_name,
                'sourceBytes' => (int) $run->source_bytes,
                'status' => $run->status,
                'processed' => (int) $run->processed_items,
                'imported' => (int) $run->imported_items,
                'skipped' => (int) $run->skipped_items,
                'failed' => (int) $run->failed_items,
                'errorCode' => $run->error_code,
                'createdBy' => $run->creator?->name,
                'createdAt' => $run->created_at?->toIso8601String(),
                'startedAt' => $run->started_at?->toIso8601String(),
                'completedAt' => $run->completed_at?->toIso8601String(),
                'canResume' => in_array($run->status, ['failed', 'queued', 'completed_with_errors'], true),
            ])
            ->values();

        return Inertia::render('Admin/Migrations/Index', [
            'runs' => $runs,
            'limits' => [
                'sourceBytes' => 52_428_800,
                'itemsPerRun' => 20_000,
                'remoteMediaFetch' => false,
            ],
        ]);
    }

    public function store(Request $request, ContentMigrationManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'file', 'max:51200'],
        ]);
        $user = $request->user();
        abort_unless($user !== null, 403);

        $run = $manager->stageWordPressWxr($validated['source'], $user);

        return redirect()->route('admin.migrations.index')
            ->with('success', 'WordPress migration queued. Run '.$run->id.' will process the staged export.');
    }

    public function resume(string $run, ContentMigrationManager $manager): RedirectResponse
    {
        $record = ContentMigrationRun::query()->whereKey($run)->firstOrFail();
        $manager->resume($record);

        return back()->with('success', 'Migration queued for a replay-safe resume.');
    }

    public function exportDocuments(ContentExportService $exports): StreamedResponse
    {
        return $exports->documents();
    }
}
