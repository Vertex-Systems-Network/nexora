<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Nexora\Forms\Services\FormSubmissionManager;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FormController extends Controller
{
    public function __construct(
        private ThemeRendererContract $themes,
        private SettingsContract $settings,
    ) {}

    public function show(FormDefinition $form): Response
    {
        abort_unless($form->isPubliclySubmittable(), 404);
        $siteName = (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora'));
        $settings = (array) ($form->settings ?? []);
        $canonical = url('/forms/'.rawurlencode((string) $form->slug));
        $robots = (bool) ($settings['indexable'] ?? false) ? 'index,follow' : 'noindex,follow';
        $head = '<title>'.e((string) $form->name).' · '.e($siteName).'</title>';
        $head .= '<link rel="canonical" href="'.e($canonical).'">';
        $head .= '<meta name="robots" content="'.$robots.'">';
        if ($form->description) {
            $head .= '<meta name="description" content="'.e(mb_substr((string) $form->description, 0, 320)).'">';
        }

        $html = $this->themes->render('home', [
            'site_name' => $siteName,
            'page_title' => $form->name,
            'tagline' => (string) ($form->description ?? ''),
            'nx_head' => $head,
            'nx_schema' => '',
            'nx_content' => $this->formHtml($form),
        ]);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function submit(
        Request $request,
        FormDefinition $form,
        FormSubmissionManager $submissions,
    ): RedirectResponse {
        abort_unless($form->isPubliclySubmittable(), 404);
        $settings = (array) ($form->settings ?? []);

        if (trim((string) $request->input('_nx_website', '')) === '') {
            $submissions->submit(
                $form,
                $request->except(['_token', '_nx_website']),
                $request->user(),
                ['locale' => app()->getLocale()],
            );
        }

        return redirect()
            ->route('forms.public.show', $form)
            ->with('success', (string) ($settings['success_message'] ?? 'Thanks. Your response has been received.'));
    }

    private function formHtml(FormDefinition $form): string
    {
        $settings = (array) ($form->settings ?? []);
        $content = '<section class="nx-public-form"><header><h1>'.e((string) $form->name).'</h1>';
        if ($form->description) $content .= '<p>'.e((string) $form->description).'</p>';
        $content .= '</header>';

        if (session('success')) {
            $content .= '<div class="nx-form-success" role="status">'.e((string) session('success')).'</div>';
        }
        $errors = session('errors');
        if ($errors && $errors->has('form')) {
            $content .= '<div class="nx-form-error" role="alert">'.e((string) $errors->first('form')).'</div>';
        }

        $content .= '<form method="post" action="'.e(route('forms.public.submit', $form, false)).'" class="nx-form">';
        $content .= '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';
        $content .= '<div aria-hidden="true" style="position:absolute;inline-size:1px;block-size:1px;overflow:hidden;clip:rect(0 0 0 0)">';
        $content .= '<label>Website<input type="text" name="_nx_website" value="" tabindex="-1" autocomplete="off"></label></div>';

        foreach ((array) $form->fields as $field) {
            if (! is_array($field)) continue;
            $content .= $this->fieldHtml($field, $errors);
        }

        $label = trim((string) ($settings['submit_button'] ?? 'Submit')) ?: 'Submit';
        $content .= '<button type="submit">'.e($label).'</button></form></section>';

        return $content;
    }

    /** @param array<string,mixed> $field */
    private function fieldHtml(array $field, mixed $errors): string
    {
        $key = (string) ($field['key'] ?? '');
        $label = (string) ($field['label'] ?? $key);
        $type = (string) ($field['type'] ?? 'text');
        $required = (bool) ($field['required'] ?? false);
        $help = trim((string) ($field['help'] ?? ''));
        $placeholder = (string) ($field['placeholder'] ?? '');
        $error = $errors?->first($key);
        $id = 'nx-form-'.preg_replace('/[^a-z0-9_-]/i', '-', $key);
        $helpId = $id.'-help';
        $errorId = $id.'-error';
        $describedBy = trim(($help !== '' ? $helpId.' ' : '').($error ? $errorId : ''));
        $common = ' id="'.e($id).'" name="'.e($key).'"'.($required ? ' required' : '');
        if ($describedBy !== '') $common .= ' aria-describedby="'.e($describedBy).'"';
        if ($error) $common .= ' aria-invalid="true"';

        $html = '<div class="nx-form-field"><label for="'.e($id).'">'.e($label).($required ? ' <span aria-hidden="true">*</span>' : '').'</label>';
        $old = old($key);

        if ($type === 'textarea') {
            $html .= '<textarea'.$common.' maxlength="'.(int) ($field['max_length'] ?? 10000).'" placeholder="'.e($placeholder).'">'.e((string) $old).'</textarea>';
        } elseif ($type === 'select') {
            $html .= '<select'.$common.'><option value="">Choose an option</option>';
            foreach ((array) ($field['options'] ?? []) as $option) {
                if (! is_array($option)) continue;
                $value = (string) ($option['value'] ?? '');
                $selected = (string) $old === $value ? ' selected' : '';
                $html .= '<option value="'.e($value).'"'.$selected.'>'.e((string) ($option['label'] ?? $value)).'</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'checkbox') {
            $html .= '<input type="checkbox"'.$common.' value="1"'.($old ? ' checked' : '').'>';
        } else {
            $inputType = in_array($type, ['email', 'number', 'date'], true) ? $type : 'text';
            $html .= '<input type="'.$inputType.'"'.$common.' value="'.e((string) $old).'"';
            if (in_array($inputType, ['text', 'email'], true)) {
                $html .= ' maxlength="'.(int) ($field['max_length'] ?? 255).'" placeholder="'.e($placeholder).'"';
            }
            $html .= '>';
        }

        if ($help !== '') $html .= '<p id="'.e($helpId).'" class="nx-form-help">'.e($help).'</p>';
        if ($error) $html .= '<p id="'.e($errorId).'" class="nx-form-error" role="alert">'.e((string) $error).'</p>';
        $html .= '</div>';

        return $html;
    }
}
