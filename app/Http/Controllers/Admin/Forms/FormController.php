<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Forms;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Forms\Services\FormDefinitionValidator;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class FormController extends Controller
{
    public function index(): Response
    {
        $forms = FormDefinition::query()
            ->withCount('submissions')
            ->latest('updated_at')
            ->paginate(24)
            ->through(fn (FormDefinition $form): array => $this->formPayload($form));

        return Inertia::render('Admin/Forms/Index', [
            'forms' => $forms,
            'summary' => [
                'total' => FormDefinition::query()->count(),
                'active' => FormDefinition::query()->where('status', 'active')->count(),
                'submissions' => FormSubmission::query()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return $this->editor(null);
    }

    public function store(
        Request $request,
        FormDefinitionValidator $definition,
        TenantContext $tenant,
        AuditManager $audit,
    ): RedirectResponse {
        $data = $this->validateDefinition($request, $tenant->id());
        $fields = $definition->normalize((array) $data['fields']);
        $form = FormDefinition::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
            'fields' => $fields,
            'settings' => $this->settings($data),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);
        $audit->record('forms.definition.created', $form, ['status' => $form->status], $request);

        return redirect()->route('forms.admin.edit', $form)->with('success', 'Form created.');
    }

    public function edit(FormDefinition $form): Response
    {
        return $this->editor($form);
    }

    public function update(
        Request $request,
        FormDefinition $form,
        FormDefinitionValidator $definition,
        TenantContext $tenant,
        AuditManager $audit,
    ): RedirectResponse {
        $data = $this->validateDefinition($request, $tenant->id(), $form);
        $form->forceFill([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
            'fields' => $definition->normalize((array) $data['fields']),
            'settings' => $this->settings($data),
            'updated_by' => $request->user()?->id,
        ])->save();
        $audit->record('forms.definition.updated', $form, ['status' => $form->status], $request);

        return back()->with('success', 'Form saved.');
    }

    public function status(Request $request, FormDefinition $form, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'active', 'paused', 'archived'])]]);
        $form->forceFill(['status' => $data['status'], 'updated_by' => $request->user()?->id])->save();
        $audit->record('forms.definition.status_changed', $form, ['status' => $form->status], $request);

        return back()->with('success', 'Form status updated.');
    }

    public function submissions(FormDefinition $form): Response
    {
        $submissions = $form->submissions()
            ->with('user:id,name,email')
            ->latest('submitted_at')
            ->paginate(30)
            ->through(static fn (FormSubmission $submission): array => [
                'id' => $submission->id,
                'uuid' => $submission->uuid,
                'status' => $submission->status,
                'values' => $submission->values ?? [],
                'metadata' => $submission->metadata ?? [],
                'submittedAt' => $submission->submitted_at?->toIso8601String(),
                'user' => $submission->user ? [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ] : null,
            ]);

        return Inertia::render('Admin/Forms/Submissions', [
            'form' => $this->formPayload($form),
            'submissions' => $submissions,
        ]);
    }

    private function editor(?FormDefinition $form): Response
    {
        return Inertia::render('Admin/Forms/Form', [
            'form' => $form ? $this->formPayload($form) : null,
            'fieldTypes' => [
                ['value' => 'text', 'label' => 'Text'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'textarea', 'label' => 'Long text'],
                ['value' => 'number', 'label' => 'Number'],
                ['value' => 'select', 'label' => 'Select'],
                ['value' => 'checkbox', 'label' => 'Checkbox'],
                ['value' => 'date', 'label' => 'Date'],
            ],
        ]);
    }

    /** @return array<string,mixed> */
    private function validateDefinition(Request $request, ?string $tenantId, ?FormDefinition $form = null): array
    {
        $unique = Rule::unique('nx_forms', 'slug')->where(function ($query) use ($tenantId): void {
            $tenantId === null ? $query->whereNull('tenant_id') : $query->where('tenant_id', $tenantId);
        });
        if ($form !== null) $unique->ignore($form->id);

        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => [$form ? 'required' : 'nullable', 'string', 'max:190', 'regex:/^[a-z0-9][a-z0-9-]*$/', $unique],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'archived'])],
            'fields' => ['required', 'array', 'min:1', 'max:50'],
            'settings' => ['nullable', 'array'],
            'settings.success_message' => ['nullable', 'string', 'max:1000'],
            'settings.submit_button' => ['nullable', 'string', 'max:80'],
            'settings.require_auth' => ['required', 'boolean'],
            'settings.indexable' => ['required', 'boolean'],
        ]);
    }

    /** @param array<string,mixed> $data
     *  @return array<string,mixed> */
    private function settings(array $data): array
    {
        $settings = (array) ($data['settings'] ?? []);
        return [
            'success_message' => trim((string) ($settings['success_message'] ?? 'Thanks. Your response has been received.')),
            'submit_button' => trim((string) ($settings['submit_button'] ?? 'Submit')) ?: 'Submit',
            'require_auth' => (bool) ($settings['require_auth'] ?? false),
            'indexable' => (bool) ($settings['indexable'] ?? false),
        ];
    }

    /** @return array<string,mixed> */
    private function formPayload(FormDefinition $form): array
    {
        return [
            'id' => $form->id,
            'uuid' => $form->uuid,
            'name' => $form->name,
            'slug' => $form->slug,
            'description' => $form->description,
            'status' => $form->status,
            'fields' => $form->fields ?? [],
            'settings' => $form->settings ?? [],
            'submissionCount' => (int) ($form->submissions_count ?? $form->submission_count ?? 0),
            'publicUrl' => route('forms.public.show', $form, false),
            'updatedAt' => $form->updated_at?->toIso8601String(),
        ];
    }
}
