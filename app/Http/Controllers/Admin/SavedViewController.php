<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SavedViewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $scope = $request->validate([
            'scope' => ['required', 'string', 'max:120'],
        ])['scope'];

        return response()->json([
            'views' => SavedView::query()
                ->where('user_id', $request->user()->id)
                ->where('scope', $scope)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'is_default', 'state']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['boolean'],
            'state' => ['required', 'array'],
        ]);

        if ($data['is_default'] ?? false) {
            SavedView::query()
                ->where('user_id', $request->user()->id)
                ->where('scope', $data['scope'])
                ->update(['is_default' => false]);
        }

        $view = SavedView::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'scope' => $data['scope'],
                'name' => $data['name'],
            ],
            [
                'is_default' => (bool) ($data['is_default'] ?? false),
                'state' => $data['state'],
            ],
        );

        return response()->json(['view' => $view], 201);
    }

    public function destroy(Request $request, SavedView $savedView): JsonResponse
    {
        abort_unless($savedView->user_id === $request->user()->id, 404);
        $savedView->delete();

        return response()->json(['deleted' => true]);
    }
}
