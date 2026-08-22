<?php

declare(strict_types=1);

namespace App\Nexora\Forms\Services;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\User;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FormSubmissionManager
{
    public function __construct(private AutomationEventBusContract $events) {}

    /** @param array<string,mixed> $input
     *  @param array<string,mixed> $metadata */
    public function submit(FormDefinition $form, array $input, ?User $user = null, array $metadata = []): FormSubmission
    {
        if (! $form->isPubliclySubmittable()) {
            throw ValidationException::withMessages(['form' => 'This form is not accepting submissions.']);
        }

        $settings = (array) ($form->settings ?? []);
        if ((bool) ($settings['require_auth'] ?? false) && $user === null) {
            throw ValidationException::withMessages(['form' => 'Sign in before submitting this form.']);
        }

        [$rules, $attributes] = $this->validationRules($form);
        $values = Validator::make($input, $rules, [], $attributes)->validate();

        $submission = DB::transaction(function () use ($form, $user, $values, $metadata): FormSubmission {
            $submission = FormSubmission::query()->create([
                'tenant_id' => $form->tenant_id,
                'uuid' => (string) Str::uuid(),
                'form_id' => $form->id,
                'user_id' => $user?->id,
                'status' => 'received',
                'values' => $values,
                'metadata' => array_filter([
                    'locale' => isset($metadata['locale']) ? mb_substr((string) $metadata['locale'], 0, 16) : null,
                    'authenticated' => $user !== null,
                ], static fn ($value): bool => $value !== null),
                'submitted_at' => now(),
            ]);

            FormDefinition::query()->whereKey($form->id)->increment('submission_count');

            return $submission;
        });

        try {
            $this->events->emit(
                'form.submitted',
                [
                    'form' => [
                        'id' => $form->id,
                        'uuid' => $form->uuid,
                        'slug' => $form->slug,
                        'name' => $form->name,
                    ],
                    'submission' => [
                        'id' => $submission->id,
                        'uuid' => $submission->uuid,
                        'values' => $submission->values,
                        'user_id' => $submission->user_id,
                    ],
                ],
                'form',
                $form->id,
                'form-submission:'.$submission->uuid,
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $submission->refresh();
    }

    /** @return array{0:array<string,array<int,mixed>>,1:array<string,string>} */
    private function validationRules(FormDefinition $form): array
    {
        $rules = [];
        $attributes = [];
        foreach ((array) $form->fields as $field) {
            if (! is_array($field)) continue;
            $key = (string) ($field['key'] ?? '');
            if ($key === '') continue;

            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $fieldRules = [$required ? 'required' : 'nullable'];

            if ($type === 'email') {
                // RFC-only validation accepts local-only mailbox forms such as
                // "not-an-email". Public form email fields represent practical
                // Internet addresses, so require both RFC and filter_var shape
                // without introducing DNS/network dependency into submission.
                $fieldRules[] = 'email:rfc,filter';
                $fieldRules[] = 'max:'.(int) ($field['max_length'] ?? 255);
            } elseif ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'date') {
                $fieldRules[] = 'date';
            } elseif ($type === 'checkbox') {
                $fieldRules = $required ? ['required', 'accepted'] : ['nullable', 'boolean'];
            } elseif ($type === 'select') {
                $allowed = collect((array) ($field['options'] ?? []))
                    // Collection::filter passes both value and key to callbacks.
                    // Avoid a direct `is_array` callback because PHP's built-in
                    // accepts one argument and Laravel 13 therefore raises an
                    // ArgumentCountError before form validation can run.
                    ->filter(static fn (mixed $option): bool => is_array($option))
                    ->map(static fn (array $option): string => (string) ($option['value'] ?? ''))
                    ->filter(static fn (string $value): bool => $value !== '')
                    ->values()
                    ->all();
                $fieldRules[] = Rule::in($allowed);
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:'.(int) ($field['max_length'] ?? ($type === 'textarea' ? 10000 : 255));
            }

            $rules[$key] = $fieldRules;
            $attributes[$key] = (string) ($field['label'] ?? $key);
        }

        return [$rules, $attributes];
    }
}
