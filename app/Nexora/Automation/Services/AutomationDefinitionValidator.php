<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use App\Models\WebhookDestination;
use App\Models\WebhookEndpoint;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use Illuminate\Validation\ValidationException;

final class AutomationDefinitionValidator
{
    public function __construct(
        private AutomationTriggerRegistry $triggers,
        private AutomationActionRegistry $actions,
        private TenantMemberDirectory $tenantMembers,
    ) {}

    /** @param array<string,mixed> $definition */
    public function validate(array $definition): array
    {
        $trigger = (string) ($definition['trigger_key'] ?? '');
        if (! $this->triggers->has($trigger)) throw ValidationException::withMessages(['trigger_key'=>'Choose a supported automation trigger.']);
        $conditions = array_values(array_slice((array) ($definition['conditions'] ?? []), 0, 20));
        foreach ($conditions as $index => $condition) {
            if (! is_array($condition)) throw ValidationException::withMessages(["conditions.$index"=>'Condition must be an object.']);
            $field = trim((string) ($condition['field'] ?? ''));
            $operator = (string) ($condition['operator'] ?? '');
            if ($field === '' || mb_strlen($field) > 120) throw ValidationException::withMessages(["conditions.$index.field"=>'Enter a valid payload field path.']);
            if (! in_array($operator, ['equals','not_equals','contains','not_contains','exists','not_exists','greater_than','less_than'], true)) throw ValidationException::withMessages(["conditions.$index.operator"=>'Choose a supported condition operator.']);
        }
        $actions = array_values(array_slice((array) ($definition['actions'] ?? []), 0, 20));
        if ($actions === []) throw ValidationException::withMessages(['actions'=>'Add at least one workflow action.']);
        foreach ($actions as $index => &$action) {
            if (! is_array($action)) throw ValidationException::withMessages(["actions.$index"=>'Action must be an object.']);
            $type = (string) ($action['type'] ?? '');
            if (! $this->actions->has($type)) throw ValidationException::withMessages(["actions.$index.type"=>'Choose a supported workflow action.']);
            $action['key'] = (string) ($action['key'] ?? 'step-'.($index + 1));
            $config = (array) ($action['config'] ?? []);
            if ($type === 'webhook.send') {
                $destination = (int) ($config['destination_id'] ?? 0);
                if ($destination < 1 || ! WebhookDestination::query()->whereKey($destination)->exists()) throw ValidationException::withMessages(["actions.$index.config.destination_id"=>'Choose an existing webhook destination.']);
            }
            if ($type === 'admin.notification') {
                $userId = (int) ($config['user_id'] ?? 0);
                if (! $this->tenantMembers->contains($userId)) throw ValidationException::withMessages(["actions.$index.config.user_id"=>'Choose an active member of the current organization.']);
                if (trim((string) ($config['title'] ?? '')) === '') throw ValidationException::withMessages(["actions.$index.config.title"=>'Notification title is required.']);
            }
            if ($type === 'audit.record' && trim((string) ($config['event'] ?? '')) === '') {
                $config['event'] = 'automation.workflow.action';
            }
            $action['config'] = $config;
        }
        unset($action);
        $triggerConfig = (array) ($definition['trigger_config'] ?? []);
        if ($trigger === 'webhook.inbound') {
            $endpoint = (int) ($triggerConfig['endpoint_id'] ?? 0);
            if ($endpoint < 1 || ! WebhookEndpoint::query()->whereKey($endpoint)->exists()) throw ValidationException::withMessages(['trigger_config.endpoint_id'=>'Choose the inbound webhook endpoint that should trigger this workflow.']);
        }
        return ['trigger_key'=>$trigger,'trigger_config'=>$triggerConfig,'conditions'=>$conditions,'actions'=>$actions];
    }
}
