<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => AdminNotification::query()
                ->where('user_id', $userId)
                ->latest()
                ->paginate(25)
                ->through(static fn (AdminNotification $notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'actionUrl' => $notification->action_url,
                    'readAt' => $notification->read_at?->toIso8601String(),
                    'createdAt' => $notification->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function read(Request $request, AdminNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        AdminNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}
