<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Services;

use App\Models\StudioCanvas;
use App\Models\StudioComponent;
use App\Models\StudioRevision;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use App\Nexora\Studio\Contracts\StudioManagerContract;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class StudioManager implements StudioManagerContract
{
    public function __construct(
        private StudioCanvasValidator $validator,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function create(array $data, ?int $userId): StudioCanvas
    {
        return $this->concurrency->transaction(function () use ($data, $userId): StudioCanvas {
            $content = $this->validator->validate(is_array($data['content'] ?? null) ? $data['content'] : null);
            $canvas = StudioCanvas::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => (string) $data['name'],
                'scope' => (string) $data['scope'],
                'status' => 'draft',
                'document_id' => $data['document_id'] ?? null,
                'theme_id' => $data['theme_id'] ?? null,
                'template_key' => $data['template_key'] ?? null,
                'content' => $content,
                'metadata' => ['viewport' => 'desktop'],
                'schema_version' => 1,
                'lock_version' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->revision($canvas, $userId);
            return $canvas;
        });
    }

    public function update(StudioCanvas $canvas, array $data, ?int $userId): StudioCanvas
    {
        return $this->concurrency->transaction(function () use ($canvas, $data, $userId): StudioCanvas {
            $locked = StudioCanvas::query()->lockForUpdate()->findOrFail($canvas->id);
            if ((int) ($data['lock_version'] ?? 0) !== (int) $locked->lock_version) {
                throw ValidationException::withMessages(['canvas' => 'This Studio canvas changed in another session. Reload before saving.']);
            }

            $locked->fill([
                'name' => (string) ($data['name'] ?? $locked->name),
                'content' => $this->validator->validate(is_array($data['content'] ?? null) ? $data['content'] : []),
                'lock_version' => ((int) $locked->lock_version) + 1,
                'updated_by' => $userId,
            ])->save();
            $this->revision($locked, $userId);
            return $locked->refresh();
        });
    }

    public function publish(StudioCanvas $canvas, ?int $userId): StudioCanvas
    {
        return $this->concurrency->transaction(function () use ($canvas, $userId): StudioCanvas {
            $locked = StudioCanvas::query()->lockForUpdate()->findOrFail($canvas->id);
            $locked->forceFill([
                'status' => 'published',
                'published_at' => now(),
                'updated_by' => $userId,
                'lock_version' => ((int) $locked->lock_version) + 1,
            ])->save();
            return $locked->refresh();
        });
    }

    public function unpublish(StudioCanvas $canvas, ?int $userId): StudioCanvas
    {
        return $this->concurrency->transaction(function () use ($canvas, $userId): StudioCanvas {
            $locked = StudioCanvas::query()->lockForUpdate()->findOrFail($canvas->id);
            $locked->forceFill([
                'status' => 'draft',
                'published_at' => null,
                'updated_by' => $userId,
                'lock_version' => ((int) $locked->lock_version) + 1,
            ])->save();
            return $locked->refresh();
        });
    }

    public function saveComponent(string $name, array $node, ?int $userId): StudioComponent
    {
        $content = $this->validator->validate(['version' => 1, 'children' => [$node]]);
        return StudioComponent::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'category' => 'user',
            'content' => $content['children'][0],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function revision(StudioCanvas $canvas, ?int $userId): void
    {
        // Callers hold the canvas row lock while calculating the next revision.
        $revision = ((int) $canvas->revisions()->max('revision')) + 1;
        StudioRevision::query()->create([
            'canvas_id' => $canvas->id,
            'revision' => $revision,
            'name' => $canvas->name,
            'content' => $canvas->content,
            'metadata' => $canvas->metadata,
            'created_by' => $userId,
        ]);
    }
}
