<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('localization.supported.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Nexora runtime handoff</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f5f7fb}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px}.card{width:min(760px,100%);background:#fff;border:1px solid #e2e7f0;border-radius:20px;padding:28px;box-shadow:0 20px 55px rgba(22,34,55,.08)}
        h1{margin:0;font-size:26px}p{line-height:1.65;color:#5a6577}.badge{display:inline-flex;margin-top:12px;padding:7px 10px;border-radius:999px;background:#fff4e5;color:#8a4b08;font-size:13px;font-weight:700}.errors{margin:20px 0;padding:16px 18px;border-radius:14px;background:#fff7f7;border:1px solid #f0cccc;color:#7e2727}.errors li+li{margin-top:8px}code{display:block;overflow:auto;margin:10px 0;padding:12px 14px;border-radius:10px;background:#111827;color:#f8fafc;white-space:pre-wrap}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.button{appearance:none;border:0;border-radius:10px;padding:11px 15px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;background:#111827;color:#fff}.secondary{background:#eef1f6;color:#263246}.meta{margin-top:20px;font-size:13px;color:#788396}
    </style>
</head>
<body>
<main class="card">
    <h1>Installation committed · runtime handoff pending</h1>
    <span class="badge">Do not run the installer again</span>
    <p>The permanent installation lock is valid, but Nexora has not yet recorded a current runtime-handoff receipt for the PHP process that will serve login and admin requests.</p>

    @if (! empty($handoff['errors']))
        <div class="errors">
            <strong>Current blocker</strong>
            <ul>
                @foreach ((array) $handoff['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p>Restart or reload Laragon/PHP first if the source or activation generation changed, then run:</p>
    <code>php artisan nexora:source:status --require-web-ack</code>
    <code>php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE</code>
    <code>php artisan nexora:runtime:post-install-status --assert-ready</code>

    <div class="actions">
        <a class="button" href="{{ route('install.runtime.handoff') }}">Recheck handoff</a>
        <a class="button secondary" href="{{ route('install.source.status') }}">Source status</a>
    </div>

    <div class="meta">
        {{ $sourceIdentity['platform_version'] ?? 'unknown' }} · {{ $sourceIdentity['running_protocol'] ?? 'unknown' }} · {{ $sourceIdentity['running_generation'] ?? 'unknown' }} · source {{ $sourceIdentity['critical_source_files_matched'] ?? 0 }}/{{ $sourceIdentity['critical_source_files'] ?? 0 }} · runtime {{ $sourceIdentity['runtime_classes_matched'] ?? 0 }}/{{ $sourceIdentity['runtime_classes_total'] ?? 0 }}
    </div>
</main>
</body>
</html>
