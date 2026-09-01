<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCustomFieldDefinition;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Nexora\Crm\Services\CrmActivityProviderRegistry;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class CrmSettingsController extends Controller
{
    public function index(CrmActivityProviderRegistry $providers): Response
    {
        return Inertia::render('Admin/Crm/Settings', [
            'pipelines' => CrmPipeline::query()->with('stages')->orderByDesc('is_default')->orderBy('name')->get()->map(fn ($pipeline): array => [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
                'slug' => $pipeline->slug,
                'is_default' => $pipeline->is_default,
                'active' => $pipeline->active,
                'stages' => $pipeline->stages->map(fn ($stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'slug' => $stage->slug,
                    'position' => $stage->position,
                    'probability' => $stage->probability,
                    'is_won' => $stage->is_won,
                    'is_lost' => $stage->is_lost,
                ])->values(),
            ]),
            'customFields' => CrmCustomFieldDefinition::query()->orderBy('entity_type')->orderBy('position')->get()->map(fn ($field): array => [
                'id' => $field->id,
                'entity_type' => $field->entity_type,
                'key' => $field->key,
                'label' => $field->label,
                'field_type' => $field->field_type,
                'options' => $field->options ?? [],
                'required' => $field->required,
                'active' => $field->active,
                'position' => $field->position,
            ]),
            'activityProviders' => collect($providers->all())->map(fn ($provider, $key): array => [
                'key' => $key,
                'label' => $provider->label(),
                'capabilities' => $provider->capabilities(),
            ])->values(),
        ]);
    }

    public function pipeline(Request $request, TenantContext $tenant): RedirectResponse
    {
        $tenantId = $this->requireTenantId($tenant);
        $slug = trim((string) $request->input('slug', ''));
        if ($slug === '') {
            $slug = Str::slug((string) $request->input('name', ''));
        }
        $request->merge(['slug' => $slug]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('nx_crm_pipelines', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($data['is_default'] ?? false) {
            CrmPipeline::query()->update(['is_default' => false]);
        }

        CrmPipeline::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_default' => (bool) ($data['is_default'] ?? false),
            'active' => true,
        ]);

        return back()->with('success', 'Pipeline created.');
    }

    public function stage(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180'],
            'position' => ['required', 'integer', 'min:0', 'max:10000'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'outcome' => ['required', 'in:open,won,lost'],
        ]);
        $slug = $data['slug'] ?: Str::slug($data['name']);
        if (CrmPipelineStage::query()->where('pipeline_id', $pipeline->id)->where('slug', $slug)->exists()) {
            return back()->withErrors(['slug' => 'That stage key already exists in this pipeline.']);
        }

        CrmPipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => $data['name'],
            'slug' => $slug,
            'position' => $data['position'],
            'probability' => $data['probability'],
            'is_won' => $data['outcome'] === 'won',
            'is_lost' => $data['outcome'] === 'lost',
        ]);

        return back()->with('success', 'Pipeline stage created.');
    }

    public function customField(Request $request, TenantContext $tenant): RedirectResponse
    {
        $tenantId = $this->requireTenantId($tenant);
        $entityType = (string) $request->input('entity_type', '');
        $data = $request->validate([
            'entity_type' => ['required', 'in:organization,contact,lead,opportunity'],
            'key' => [
                'required',
                'regex:/^[a-z][a-z0-9_]{1,119}$/',
                Rule::unique('nx_crm_custom_field_definitions', 'key')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('entity_type', $entityType)),
            ],
            'label' => ['required', 'string', 'max:180'],
            'field_type' => ['required', 'in:text,number,date,datetime,select,multi_select,checkbox'],
            'options' => ['nullable', 'string', 'max:5000'],
            'required' => ['nullable', 'boolean'],
            'position' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $options = [];
        if (in_array($data['field_type'], ['select', 'multi_select'], true)) {
            $options = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($data['options'] ?? '')) ?: [])));
        }

        CrmCustomFieldDefinition::query()->create([
            'entity_type' => $data['entity_type'],
            'key' => $data['key'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $options,
            'required' => (bool) ($data['required'] ?? false),
            'active' => true,
            'position' => $data['position'],
        ]);

        return back()->with('success', 'Custom field created.');
    }

    private function requireTenantId(TenantContext $tenant): string
    {
        $tenantId = $tenant->id();
        if ($tenantId === null) {
            throw ValidationException::withMessages(['tenant' => 'Select an organization before changing CRM settings.']);
        }

        return $tenantId;
    }
}
