<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\SeoInternalLinkSuggestion;
use App\Nexora\Seo\Services\InternalLinkAnalyzer;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class InternalLinkController extends Controller
{
    public function refresh(Request $request, Document $document, InternalLinkAnalyzer $analyzer, AuditManager $audit): RedirectResponse
    {
        $count = $analyzer->refresh($document);
        $audit->record('seo.internal-links.refreshed', $document, ['suggestions' => $count]);
        return back()->with('success', "Internal-link analysis completed. {$count} suggestion(s) found.");
    }

    public function update(Request $request, SeoInternalLinkSuggestion $suggestion, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['suggested', 'added', 'dismissed'])]]);
        $suggestion->update(['status' => $data['status']]);
        $audit->record('seo.internal-link.updated', $suggestion, ['status' => $data['status']]);
        return back()->with('success', 'Internal-link suggestion updated.');
    }
}
