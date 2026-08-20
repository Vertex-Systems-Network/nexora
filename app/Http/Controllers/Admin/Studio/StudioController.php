<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Studio;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\StudioCanvas;
use App\Models\StudioComponent;
use App\Models\Theme;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Studio\Contracts\StudioManagerContract;
use App\Nexora\Studio\Services\StudioBindingRegistry;
use App\Nexora\Studio\Services\StudioElementRegistry;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class StudioController extends Controller
{
    public function __construct(
        private StudioManagerContract $studio,
        private StudioElementRegistry $elements,
        private StudioBindingRegistry $bindings,
        private ThemeManagerContract $themes,
        private AuditManager $audit,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $scope = (string) $request->query('scope', '');
        $query = StudioCanvas::query()->with(['document:id,title', 'theme:id,name'])->withCount('revisions')->latest('updated_at');
        if ($search !== '') $query->where('name', 'like', '%'.$search.'%');
        if (in_array($scope, ['standalone', 'document', 'theme-template'], true)) $query->where('scope', $scope);

        return Inertia::render('Admin/Studio/Index', [
            'canvases' => $query->paginate(18)->withQueryString()->through(static fn (StudioCanvas $canvas): array => [
                'id' => $canvas->id,
                'name' => $canvas->name,
                'scope' => $canvas->scope,
                'status' => $canvas->status,
                'document' => $canvas->document?->title,
                'theme' => $canvas->theme?->name,
                'templateKey' => $canvas->template_key,
                'revisionsCount' => $canvas->revisions_count,
                'updatedAt' => $canvas->updated_at?->toIso8601String(),
            ]),
            'filters' => ['search' => $search, 'scope' => $scope],
            'documents' => $this->documents(),
            'themes' => $this->themeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCreate($request);
        $canvas = $this->studio->create($validated + ['content' => $this->emptyContent()], $request->user()?->id);
        $this->audit->record('studio.canvas.created', $canvas, ['scope' => $canvas->scope]);
        return redirect()->route('admin.studio.edit', $canvas)->with('success', 'Studio canvas created.');
    }

    public function edit(StudioCanvas $canvas): Response
    {
        $canvas->load(['document:id,title,excerpt,status', 'theme:id,name', 'revisions' => fn ($query) => $query->latest('revision')->limit(15)]);
        $activeTheme = $this->themes->active();
        $tokens = $activeTheme ? $this->themes->tokens($activeTheme) : [];

        return Inertia::render('Admin/Studio/Editor', [
            'canvas' => [
                'id' => $canvas->id,
                'uuid' => $canvas->uuid,
                'name' => $canvas->name,
                'scope' => $canvas->scope,
                'status' => $canvas->status,
                'documentId' => $canvas->document_id,
                'document' => $canvas->document ? ['id' => $canvas->document->id, 'title' => $canvas->document->title, 'excerpt' => $canvas->document->excerpt] : null,
                'themeId' => $canvas->theme_id,
                'theme' => $canvas->theme?->name,
                'templateKey' => $canvas->template_key,
                'content' => $canvas->content ?? $this->emptyContent(),
                'lockVersion' => (int) $canvas->lock_version,
                'updatedAt' => $canvas->updated_at?->toIso8601String(),
                'publishedAt' => $canvas->published_at?->toIso8601String(),
                'revisions' => $canvas->revisions->map(static fn ($revision): array => [
                    'revision' => $revision->revision,
                    'createdAt' => $revision->created_at?->toIso8601String(),
                ])->values(),
            ],
            'elements' => array_values(array_map(static fn ($definition) => $definition->toArray(), $this->elements->all())),
            'bindings' => $this->bindings->all(),
            'components' => StudioComponent::query()->latest()->limit(100)->get()->map(static fn (StudioComponent $component): array => [
                'id' => $component->id,
                'name' => $component->name,
                'category' => $component->category,
                'content' => $component->content,
            ])->values(),
            'themeTokens' => collect($tokens)->map(fn ($value, $key): array => ['key' => (string) $key, 'label' => $this->humanize((string) $key), 'value' => (string) $value])->values(),
        ]);
    }

    public function update(Request $request, StudioCanvas $canvas): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'lock_version' => ['required', 'integer', 'min:1'],
        ]);
        $updated = $this->studio->update($canvas, $validated, $request->user()?->id);
        $this->audit->record('studio.canvas.updated', $updated, ['lock_version' => $updated->lock_version]);
        return back()->with('success', 'Studio canvas saved as a new revision.');
    }

    public function publish(Request $request, StudioCanvas $canvas): RedirectResponse
    {
        $updated = $this->studio->publish($canvas, $request->user()?->id);
        $this->audit->record('studio.canvas.published', $updated, ['scope' => $updated->scope]);
        return back()->with('success', 'Studio canvas published.');
    }

    public function unpublish(Request $request, StudioCanvas $canvas): RedirectResponse
    {
        $updated = $this->studio->unpublish($canvas, $request->user()?->id);
        $this->audit->record('studio.canvas.unpublished', $updated, []);
        return back()->with('success', 'Studio canvas returned to draft.');
    }

    public function destroy(StudioCanvas $canvas): RedirectResponse
    {
        $this->audit->record('studio.canvas.deleted', $canvas, ['uuid' => $canvas->uuid, 'name' => $canvas->name]);
        $canvas->delete();
        return redirect()->route('admin.studio.index')->with('success', 'Studio canvas deleted.');
    }

    public function component(Request $request, StudioCanvas $canvas): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'node' => ['required', 'array'],
        ]);
        $component = $this->studio->saveComponent($validated['name'], $validated['node'], $request->user()?->id);
        $this->audit->record('studio.component.created', $component, ['canvas_id' => $canvas->id]);
        return back()->with('success', 'Reusable Studio component saved.');
    }

    /** @return array<string,mixed> */
    private function validateCreate(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['standalone', 'document', 'theme-template'])],
            'document_id' => ['nullable', 'integer', new TenantExists('nx_documents')],
            'theme_id' => ['nullable', 'integer', 'exists:nx_themes,id'],
            'template_key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
        ]);
        if ($data['scope'] === 'document' && empty($data['document_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['document_id' => 'Choose the document this canvas designs.']);
        }
        if ($data['scope'] === 'theme-template' && (empty($data['theme_id']) || empty($data['template_key']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['theme_id' => 'Theme templates require a theme and template key.']);
        }
        return $data;
    }

    /** @return list<array{id:int,title:string}> */
    private function documents(): array
    {
        return Document::query()->orderBy('title')->limit(250)->get(['id', 'title'])->map(static fn (Document $document): array => ['id' => $document->id, 'title' => $document->title])->values()->all();
    }

    /** @return list<array{id:int,name:string}> */
    private function themeOptions(): array
    {
        return Theme::query()->orderBy('name')->get(['id', 'name'])->map(static fn (Theme $theme): array => ['id' => $theme->id, 'name' => $theme->name])->values()->all();
    }

    /** @return array{version:int,children:array<int,mixed>} */
    private function emptyContent(): array
    {
        return ['version' => 1, 'children' => []];
    }

    private function humanize(string $key): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', $key));
    }
}
