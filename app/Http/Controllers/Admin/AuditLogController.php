<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $query = AuditLog::query()->with('user:id,name,email')->latest('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('event', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Audit/Index', [
            'filters' => ['search' => $search],
            'logs' => $query->paginate(25)->withQueryString()->through(static fn (AuditLog $log): array => [
                'id' => $log->id,
                'event' => $log->event,
                'user' => $log->user?->only(['id', 'name', 'email']),
                'subjectType' => $log->subject_type,
                'subjectId' => $log->subject_id,
                'ipAddress' => $log->ip_address,
                'metadata' => $log->metadata,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
