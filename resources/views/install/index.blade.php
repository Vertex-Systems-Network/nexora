<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $localization['direction'] ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>Install Nexora</title>
    <script>
        (() => {
            const saved = localStorage.getItem('nexora-installer-theme') || 'system';
            const resolved = saved === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : saved;
            document.documentElement.dataset.theme = resolved;
            document.documentElement.dataset.themeMode = saved;
        })();
    </script>
    <style>
        :root{color-scheme:light;--bg:#f5f6fa;--panel:#fff;--panel-subtle:#fafafa;--text:#18181b;--text-strong:#344054;--muted:#6b7280;--line:#e5e7eb;--control:#fff;--control-line:#d0d5dd;--brand:#7f56d9;--brand2:#6941c6;--brand-soft:#f4f0ff;--brand-text:#53389e;--good:#067647;--goodbg:#ecfdf3;--bad:#b42318;--badbg:#fef3f2;--warn:#b54708;--warnbg:#fffaeb;--shadow:0 14px 45px rgba(16,24,40,.06);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}html[data-theme="dark"]{color-scheme:dark;--bg:#0b0d12;--panel:#12151c;--panel-subtle:#181c25;--text:#f5f7fa;--text-strong:#e4e7ec;--muted:#98a2b3;--line:#29303d;--control:#171b24;--control-line:#394150;--brand:#9e77ed;--brand2:#b692f6;--brand-soft:#251d36;--brand-text:#d6bbfb;--good:#75e0a7;--goodbg:#0c2f24;--bad:#fda29b;--badbg:#3b1715;--warn:#fec84b;--warnbg:#382b0d;--shadow:0 18px 55px rgba(0,0,0,.32)}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text)}button,input,select{font:inherit}.top{height:68px;background:var(--panel);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 max(20px,calc((100vw - 1080px)/2))}.brand{display:flex;gap:10px;align-items:center}.brand img{width:36px;height:36px}.brand strong{display:block;font-size:15px}.brand small{color:var(--muted)}.status-pill{font-size:12px;padding:7px 10px;border-radius:999px;background:var(--goodbg);color:var(--good);font-weight:700}.status-pill.bad{background:var(--badbg);color:var(--bad)}
        .wrap{width:min(1080px,calc(100% - 32px));margin:34px auto 70px;display:grid;grid-template-columns:230px minmax(0,1fr);gap:26px}.side{align-self:start;position:sticky;top:24px}.side-card,.card{background:var(--panel);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow)}.side-card{padding:14px}.step-nav{display:grid;gap:5px}.step-nav button{border:0;background:transparent;text-align:left;padding:11px 12px;border-radius:11px;color:var(--muted);font-weight:650;cursor:pointer}.step-nav button.active{background:var(--brand-soft);color:var(--brand-text)}.step-nav button.done{color:var(--text-strong)}.side-note{padding:14px 12px 4px;color:var(--muted);font-size:12px;line-height:1.55}.main h1{font-size:30px;letter-spacing:-.04em;margin:0 0 6px}.lead{margin:0 0 20px;color:var(--muted);line-height:1.6}.card{overflow:hidden}.card-head{padding:21px 24px;border-bottom:1px solid var(--line)}.card-head h2{margin:0;font-size:17px}.card-head p{margin:5px 0 0;color:var(--muted);font-size:13px}.card-body{padding:24px}.wizard-step{display:none}.wizard-step.active{display:block}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:12px;font-weight:700;color:var(--text-strong)}input,select{width:100%;height:44px;border:1px solid var(--control-line);border-radius:10px;padding:0 12px;background:var(--control);color:var(--text);outline:none}input:focus,select:focus{border-color:#9e77ed;box-shadow:0 0 0 3px rgba(127,86,217,.1)}.checks{display:grid;grid-template-columns:1fr 1fr;gap:10px}.check{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 13px;border:1px solid var(--line);border-radius:11px;background:var(--panel-subtle)}.check strong{font-size:13px}.good{color:var(--good)}.bad{color:var(--bad)}.muted{color:var(--muted)}.callout{padding:13px 14px;border:1px solid #e9d7fe;background:#f9f5ff;color:#53389e;border-radius:11px;font-size:13px;line-height:1.55}.callout.warn{border-color:#fedf89;background:var(--warnbg);color:var(--warn)}.callout.bad{border-color:#fecdca;background:var(--badbg);color:var(--bad)}.callout.good{border-color:#abefc6;background:var(--goodbg);color:var(--good)}.stack{display:grid;gap:12px}.btn{height:42px;border:1px solid var(--control-line);border-radius:10px;background:#fff;padding:0 15px;font-weight:700;cursor:pointer}.btn.primary{background:var(--brand);border-color:var(--brand);color:#fff}.btn.primary:hover{background:var(--brand2)}.btn:disabled{opacity:.45;cursor:not-allowed}.actions{display:flex;justify-content:space-between;gap:10px;padding:17px 24px;border-top:1px solid var(--line);background:var(--panel-subtle)}.right{display:flex;gap:9px}.hidden,[hidden]{display:none!important}.db-result{display:none}.db-result.visible{display:block}.safety{margin-top:15px;padding:15px;border:1px solid #fedf89;border-radius:12px;background:var(--warnbg)}.safety h3{margin:0 0 8px;font-size:14px}.radio-row,.check-row,.toggle,.recovery-choice{display:flex;align-items:flex-start;gap:9px;margin-top:9px}.radio-row input,.check-row input,.toggle input,.recovery-choice input{width:17px;height:17px;margin-top:2px}.input-shell{position:relative;display:block}.input-shell input{padding-right:48px}.icon-button{position:absolute;right:6px;top:6px;width:32px;height:32px!important;padding:0!important;display:grid;place-items:center}.icon-button svg{width:16px;height:16px}.password-strength{margin-top:7px}.password-strength-track{height:6px;background:var(--line);border-radius:999px;overflow:hidden}.password-strength-bar{height:100%;width:0;background:#d92d20;transition:.2s}.password-meta{display:flex;justify-content:space-between;gap:10px;margin-top:5px;font-size:11px;color:var(--muted)}.summary{display:grid;grid-template-columns:1fr 1fr;gap:10px}.summary div{padding:12px;border:1px solid var(--line);border-radius:10px;background:var(--panel-subtle)}.summary span{display:block;font-size:11px;color:var(--muted);margin-bottom:4px}.services{display:grid;grid-template-columns:1fr 1fr;gap:9px}.service{display:flex;gap:9px;align-items:flex-start;border:1px solid var(--line);border-radius:10px;padding:11px}.service input{width:16px;height:16px;margin-top:2px}.progress{margin-top:16px;border:1px solid var(--line);border-radius:12px;overflow:hidden}.progress-head{display:flex;justify-content:space-between;align-items:center;padding:13px 15px}.progress-track{height:7px;background:var(--line)}.progress-bar{height:100%;width:0;background:var(--brand);transition:.2s}.stages{padding:6px 15px 12px}.install-stage{display:grid;grid-template-columns:28px 1fr 70px;gap:9px;align-items:center;padding:8px 0;border-bottom:1px solid var(--line);font-size:12px}.install-stage:last-child{border-bottom:0}.install-stage-icon{width:26px;height:26px;border-radius:8px;background:var(--panel-subtle);display:grid;place-items:center}.install-stage-state{text-align:right;color:var(--muted)}.log{max-height:180px;overflow:auto;background:#101828;color:#d0d5dd;padding:12px;border-radius:10px;font:11px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre-wrap}.advanced{margin-top:14px}.advanced summary{cursor:pointer;font-size:12px;font-weight:700;color:#475467}.source-details{margin-top:10px;font-size:12px;color:var(--muted);line-height:1.55}.driver-health-icon svg{width:16px;height:16px}.footer-note{margin-top:14px;color:var(--muted);font-size:12px}.error-list{margin:0;padding-left:18px}.error-list li+li{margin-top:5px}
        .header-actions{display:flex;align-items:center;gap:10px}.theme-switch{display:flex;align-items:center;gap:4px;padding:3px;border:1px solid var(--line);border-radius:11px;background:var(--panel-subtle)}.theme-switch .btn{width:34px;height:34px;padding:0;display:grid;place-items:center;border:0;background:transparent}.theme-switch .btn span:empty{display:none}.theme-switch .btn.active{background:var(--panel);color:var(--brand);box-shadow:0 1px 3px rgba(16,24,40,.12)}.theme-switch svg{width:16px;height:16px}
        .nx-select-ready{position:relative}.nx-select-ready>select{position:absolute!important;inline-size:1px!important;block-size:1px!important;opacity:0!important;pointer-events:none!important}.nx-select-trigger{width:100%;min-height:48px;border:1px solid var(--control-line);border-radius:10px;padding:7px 10px;background:var(--control);color:var(--text);display:flex;align-items:center;justify-content:space-between;gap:10px;text-align:left;cursor:pointer}.nx-select-trigger:focus{outline:0;border-color:#9e77ed;box-shadow:0 0 0 3px rgba(127,86,217,.14)}.nx-select-trigger-main{display:flex;align-items:center;gap:9px;min-width:0}.nx-select-value{display:grid;min-width:0}.nx-select-value strong,.nx-select-option-copy strong{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nx-select-value small,.nx-select-option-copy small{font-size:11px;color:var(--muted);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nx-select-leading-icon,.nx-select-flag-image{width:18px;height:18px;flex:0 0 auto}.nx-select-flag-image{border-radius:50%;object-fit:cover}.nx-select-flag{font-size:17px;line-height:1}.nx-select-chevron{width:16px;height:16px;flex:0 0 auto;transition:transform .18s}.nx-select-ready.open .nx-select-chevron{transform:rotate(180deg)}.nx-select-menu{position:absolute;z-index:50;left:0;right:0;top:calc(100% + 6px);display:none;max-height:300px;overflow:auto;padding:6px;background:var(--panel);border:1px solid var(--line);border-radius:12px;box-shadow:0 18px 45px rgba(16,24,40,.18)}.nx-select-ready.open .nx-select-menu{display:block}.nx-select-group{padding:8px 9px 5px;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}.nx-select-option{width:100%;border:0;background:transparent;color:var(--text);border-radius:9px;padding:9px;display:grid;grid-template-columns:22px minmax(0,1fr) 18px;gap:8px;align-items:center;text-align:left;cursor:pointer}.nx-select-option:hover,.nx-select-option:focus{outline:0;background:var(--panel-subtle)}.nx-select-option.selected{background:var(--brand-soft);color:var(--brand-text)}.nx-select-option[aria-disabled="true"]{opacity:.45;cursor:not-allowed}.nx-select-option-copy{display:grid;min-width:0}.nx-select-option-check{opacity:0}.nx-select-option.selected .nx-select-option-check{opacity:1}.nx-select-check-icon{width:16px;height:16px}.nx-select-option-leading{display:grid;place-items:center}
        .service-card{border:1px solid var(--line);border-radius:12px;background:var(--panel-subtle);padding:12px}.service-card .toggle{margin-top:0}.service-config{margin-top:12px;padding-top:12px;border-top:1px solid var(--line)}.service-config-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.service-config-grid .full{grid-column:1/-1}.service-test-row{display:flex;align-items:center;gap:10px;margin-top:12px}.service-test-status{font-size:12px;color:var(--muted)}.service-test-status.good{color:var(--good)}.service-test-status.bad{color:var(--bad)}
        html[data-theme="dark"] .log{background:#080a0f;color:#d0d5dd}html[data-theme="dark"] .callout{border-color:#4a376a;background:#221931;color:#d6bbfb}html[data-theme="dark"] .callout.good{border-color:#175c45;background:var(--goodbg);color:var(--good)}html[data-theme="dark"] .callout.bad{border-color:#6b2420;background:var(--badbg);color:var(--bad)}html[data-theme="dark"] .callout.warn,html[data-theme="dark"] .safety{border-color:#6b5318;background:var(--warnbg);color:var(--warn)}
        @media(max-width:800px){.wrap{grid-template-columns:1fr}.side{position:static}.step-nav{grid-template-columns:repeat(4,1fr)}.step-nav button{text-align:center;font-size:11px;padding:9px 4px}.side-note{display:none}.grid,.checks,.summary,.services{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="top">
    <div class="brand"><img src="/brand/nexora-mark.svg" alt="Nexora"><div><strong>Nexora</strong><small>Installation Wizard</small></div></div>
    @php($sourceOk = ($sourceIdentity['status'] ?? 'fail') === 'pass')
    @php($requirementsReady = (bool) ($requirements['ready'] ?? false))
    <div class="header-actions">
        <div id="source-pill" class="status-pill {{ $sourceOk ? '' : 'bad' }}">{{ $sourceOk ? 'Source ready' : 'Source check required' }}</div>
        <div class="theme-switch" role="group" aria-label="Installer appearance">
            <x-ui.button type="button" variant="ghost" size="sm" icon="sun" data-theme-choice="light" title="Light appearance" aria-label="Light appearance"></x-ui.button>
            <x-ui.button type="button" variant="ghost" size="sm" icon="moon" data-theme-choice="dark" title="Dark appearance" aria-label="Dark appearance"></x-ui.button>
            <x-ui.button type="button" variant="ghost" size="sm" icon="monitor" data-theme-choice="system" title="System appearance" aria-label="System appearance"></x-ui.button>
        </div>
    </div>
</header>

<div class="wrap">
    <aside class="side">
        <div class="side-card">
            <nav class="step-nav" aria-label="Installation steps">
                <x-ui.button type="button" variant="ghost" class="active" data-step-nav="0">1. Requirements</x-ui.button>
                <x-ui.button type="button" variant="ghost" data-step-nav="1">2. Database</x-ui.button>
                <x-ui.button type="button" variant="ghost" data-step-nav="2">3. Account</x-ui.button>
                <x-ui.button type="button" variant="ghost" data-step-nav="3">4. Install</x-ui.button>
            </nav>
            <div class="side-note">Simple installation first. Recovery, backup and source diagnostics appear only when they are needed.</div>
        </div>
    </aside>

    <main class="main">
        <h1>Install Nexora</h1>
        <p class="lead">Configure the application, connect a database, create the first Super Admin, then install.</p>

        @if ($errors->any())
            <div class="callout bad"><strong>Please fix these fields:</strong><ul class="error-list">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form id="installer-form" method="post" action="{{ route('install.store') }}" novalidate>
            @csrf
            <x-ui.hidden name="db_reset_existing" id="db-reset-existing" value="0" />
            <x-ui.hidden name="db_backup_token" id="db-backup-token" value="" />
            <x-ui.hidden name="db_backup_confirmed" id="db-backup-confirmed" value="0" />

            <section class="card">
                <div class="card-head"><h2 id="step-title">Server requirements</h2><p id="step-copy">Check the PHP runtime and source before any database change.</p></div>
                <div class="card-body">
                    <div class="wizard-step active" data-step="0">
                        <div class="checks">
                            @foreach ((array) ($requirements['checks'] ?? []) as $requirement)
                                @php($ok = (bool) ($requirement['ok'] ?? false))
                                <div class="check"><div><strong>{{ $requirement['label'] ?? $requirement['key'] ?? 'Requirement' }}</strong><div class="muted" style="font-size:11px">{{ $requirement['detail'] ?? '' }}</div></div><span class="{{ $ok ? 'good' : 'bad' }}">{{ $ok ? 'Ready' : 'Fix' }}</span></div>
                            @endforeach
                        </div>
                        <div class="callout {{ $sourceOk ? 'good' : 'bad' }}" id="source-status-box" style="margin-top:15px">
                            <div>
                                <strong>{{ $sourceOk ? 'Executing source verified' : 'Source activation mismatch — installation is blocked before database mutation' }}</strong>
                                <div id="source-status-copy" style="margin-top:4px">critical source {{ $sourceIdentity['critical_source_files_matched'] ?? 0 }}/{{ $sourceIdentity['critical_source_files'] ?? 0 }} · runtime {{ $sourceIdentity['runtime_classes_matched'] ?? 0 }}/{{ $sourceIdentity['runtime_classes_total'] ?? 0 }} · source_generation {{ $sourceIdentity['running_generation'] ?? 'unknown' }}</div>
                            </div>
                        </div>
                        <details class="advanced"><summary>Advanced source diagnostics</summary><div class="source-details">Platform {{ $sourceIdentity['platform_version'] ?? 'unknown' }} · protocol {{ $sourceIdentity['running_protocol'] ?? 'unknown' }} · generation {{ $sourceIdentity['running_generation'] ?? 'unknown' }}<br><x-ui.button type="button" id="recheck-source" style="margin-top:9px">Recheck source</x-ui.button></div></details>
                    </div>

                    <div class="wizard-step" data-step="1">
                        <div class="grid">
                            <div class="field full"><x-ui.select name="db_driver" id="db_driver" label="Database driver" :options="$databaseDriverOptions" :selected="old('db_driver', $defaults['db_driver'])" kind="database" /></div>
                            <x-ui.input name="db_host" id="db_host" label="Host" :value="old('db_host', $defaults['db_host'])" autocomplete="off" />
                            <x-ui.input name="db_port" id="db_port" label="Port" type="number" min="1" max="65535" :value="old('db_port', $defaults['db_port'])" />
                            <x-ui.input name="db_database" id="db_database" label="Database" :value="old('db_database', $defaults['db_database'])" required />
                            <x-ui.input name="db_username" id="db_username" label="Username" :value="old('db_username', $defaults['db_username'])" autocomplete="username" />
                            <x-ui.password name="db_password" id="db_password" label="Password" wrapperClass="full" autocomplete="new-password" />
                            <div class="field full"><x-ui.checkbox name="db_create" value="1" label="Create the database when the selected driver supports it." /></div>
                        </div>
                        <div style="margin-top:15px"><x-ui.button type="button" id="test-database">Test database</x-ui.button></div>
                        <x-ui.status id="db-driver-health" class="hidden" tone="neutral" icon="database" title="Database driver health" description="Waiting for a database test." />
                        <div id="db-result" class="callout db-result"></div>

                        <div id="existing-db" class="safety hidden">
                            <div id="db-recovery-state" class="footer-note hidden">Recoverable installation history detected — resume migrations/seeding without destructive reset when exact-source recovery is valid.</div>
                            <h3>Existing database detected</h3>
                            <div id="existing-db-copy" class="muted">Choose how Nexora should continue.</div>
                            <div id="recovery-actions" class="hidden">
                                <x-ui.radio name="db_existing_action" value="resume" id="recovery-resume" label="Resume interrupted installation" description="Continue the exact-source Nexora-owned partial installation." />
                                <x-ui.radio name="db_existing_action" value="reset" id="recovery-reset" label="Discard partial schema and start clean" description="Use backup or explicit overwrite consent below." />
                                <div id="recovery-copy" class="footer-note"></div>
                            </div>
                            <div id="db-protection-choice">
                                <div style="margin-top:12px"><x-ui.button type="button" id="backup-database">Create protected backup</x-ui.button> <a id="backup-download" class="btn hidden" href="#">Download backup</a></div>
                                <div id="backup-progress" class="footer-note"></div>
                                <x-ui.checkbox id="backup-confirm-check" label="I downloaded the backup and authorize Nexora to empty this database." disabled />
                                <x-ui.checkbox id="skip-backup" name="db_skip_backup_consent" value="1" label="Continue without a Nexora backup. I understand this database may be erased." />
                                <div style="margin-top:8px"><x-ui.input id="db-skip-backup-database" name="db_skip_backup_database" label="Type the database name to confirm no-backup reset" placeholder="Database name" /></div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="2">
                        <div class="grid">
                            <x-ui.input name="app_name" id="app_name" label="Application name" :value="old('app_name', $defaults['app_name'])" required />
                            <x-ui.input name="app_url" id="app_url" label="Application URL" :value="old('app_url', $defaults['app_url'])" required />
                            <div class="field full"><x-ui.select name="language" id="language" label="Language" :options="$languageOptions" :selected="old('language', $defaults['language'])" kind="language" /></div>
                            <x-ui.input name="admin_name" id="admin_name" label="Super Admin name" :value="old('admin_name')" required autocomplete="name" />
                            <x-ui.input name="admin_email" id="admin_email" label="Super Admin email" type="email" :value="old('admin_email')" required autocomplete="email" />
                            <div><x-ui.password name="admin_password" id="admin_password" label="Password" minlength="10" required autocomplete="new-password" /><div class="password-strength"><div class="password-strength-track"><div class="password-strength-bar" id="password-strength-bar"></div></div><div class="password-meta"><span id="password-level">Blocked</span><span>10+ chars, 3+ character types minimum</span></div></div></div>
                            <div><x-ui.password name="admin_password_confirmation" id="admin_password_confirmation" label="Confirm password" minlength="10" required autocomplete="new-password" /><div id="password-match" class="footer-note"></div></div>
                            <div class="field full hidden" id="password-consent"><x-ui.checkbox id="password-consent-checkbox" name="password_strength_consent" value="1" label="Weak / Low / Medium require explicit risk consent. I accept this password risk." /></div>
                        </div>
                        <div style="margin-top:20px">
                            <strong>Additional data services</strong>
                            <p class="muted" style="font-size:12px">Optional document, cache and AWS services. Select one to configure and test it now; unavailable adapters can still be saved for later activation.</p>
                            <div class="services">
                                @foreach ($dataServices as $key => $service)
                                    @php($serviceId = 'data-service-'.str_replace('_', '-', $key))
                                    <div class="service-card">
                                        <x-ui.checkbox id="{{ $serviceId }}" name="requested_data_services[]" :value="$key" :label="$service['label'] ?? $key" :description="$service['description'] ?? $service['kind'] ?? ''" data-service-toggle="{{ $key }}" />
                                        <div class="service-config hidden" data-service-config="{{ $key }}">
                                            <div class="callout {{ ($service['available'] ?? false) ? 'good' : 'warn' }}" style="margin-bottom:12px">
                                                {{ $service['availability_message'] ?? '' }}
                                            </div>
                                            <div class="service-config-grid">
                                                @if($key !== 'aws_dynamodb')
                                                    <x-ui.input name="data_services[{{ $key }}][endpoint]" id="{{ $serviceId }}-endpoint" label="Endpoint / connection string" :value="old('data_services.'.$key.'.endpoint', $service['example'] ?? '')" wrapperClass="full" autocomplete="off" />
                                                    <x-ui.input name="data_services[{{ $key }}][database]" id="{{ $serviceId }}-database" label="Database / namespace" :value="old('data_services.'.$key.'.database')" />
                                                    <x-ui.input name="data_services[{{ $key }}][username]" id="{{ $serviceId }}-username" label="Username" :value="old('data_services.'.$key.'.username')" autocomplete="off" />
                                                    <x-ui.password name="data_services[{{ $key }}][password]" id="{{ $serviceId }}-password" label="Password / token" wrapperClass="full" autocomplete="new-password" />
                                                @else
                                                    <x-ui.input name="data_services[{{ $key }}][region]" id="{{ $serviceId }}-region" label="AWS region" value="{{ old('data_services.'.$key.'.region', 'us-east-1') }}" wrapperClass="full" autocomplete="off" />
                                                    <x-ui.input name="data_services[{{ $key }}][access_key]" id="{{ $serviceId }}-access-key" label="Access key (optional with IAM role)" :value="old('data_services.'.$key.'.access_key')" autocomplete="off" />
                                                    <x-ui.password name="data_services[{{ $key }}][secret_key]" id="{{ $serviceId }}-secret-key" label="Secret key" autocomplete="new-password" />
                                                @endif
                                            </div>
                                            <div class="service-test-row">
                                                <x-ui.button type="button" size="sm" data-service-test="{{ $key }}">Test connection</x-ui.button>
                                                <span class="service-test-status" data-service-status="{{ $key }}">Not tested.</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="3">
                        <div class="summary" id="summary"></div>
                        <div style="margin-top:14px"><x-ui.checkbox id="terms" name="terms" value="1" label="I confirm these settings and authorize Nexora to complete the installation." required /></div>
                        <div id="install-error" class="callout bad hidden" style="margin-top:14px"></div>
                        <div id="install-progress" class="progress hidden">
                            <div class="progress-head"><div><strong id="progress-title">Installing Nexora</strong><div class="muted" id="progress-meta" style="font-size:11px"></div></div><strong id="progress-percent">0%</strong></div>
                            <div class="progress-track"><div id="progress-bar" class="progress-bar"></div></div>
                            <div class="stages" id="stage-list"></div>
                            <div style="padding:0 15px 15px"><div class="log" id="install-log"></div></div>
                        </div>
                        <div class="footer-note">If installation is durably committed but runtime handoff is pending, Do not retry installation. Nexora will show the recovery action instead.</div>
                    </div>
                </div>
                <div class="actions"><x-ui.button type="button" id="back">Back</x-ui.button><div class="right"><x-ui.button type="button" variant="danger" id="cancel-install" hidden>Cancel installation</x-ui.button><x-ui.button type="button" variant="primary" id="next">Continue</x-ui.button><x-ui.button type="submit" variant="primary" id="install" hidden>Install Nexora</x-ui.button></div></div>
            </section>
        </form>
    </main>
</div>

<script src="{{ asset('installer/nexora-ui.js') }}"></script>
<script>
(() => {
    window.NexoraInstallerUI?.enhanceSelects();
    const form = document.getElementById('installer-form');
    const steps = [...document.querySelectorAll('.wizard-step')];
    const nav = [...document.querySelectorAll('[data-step-nav]')];
    const next = document.getElementById('next');
    const back = document.getElementById('back');
    const install = document.getElementById('install');
    const cancelInstall = document.getElementById('cancel-install');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const themeButtons = [...document.querySelectorAll('[data-theme-choice]')];
    const applyTheme = (mode) => {
        const resolved = mode === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : mode;
        document.documentElement.dataset.theme = resolved;
        document.documentElement.dataset.themeMode = mode;
        localStorage.setItem('nexora-installer-theme', mode);
        themeButtons.forEach((button) => button.classList.toggle('active', button.dataset.themeChoice === mode));
    };
    applyTheme(document.documentElement.dataset.themeMode || 'system');
    themeButtons.forEach((button) => button.addEventListener('click', () => applyTheme(button.dataset.themeChoice || 'system')));
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', () => { if ((document.documentElement.dataset.themeMode || 'system') === 'system') applyTheme('system'); });
    let index = 0;
    let dbVerified = false;
    let dbTableCount = 0;
    let dbInterrupted = false;
    let dbRecoverable = false;
    let runId = null;

    const titles = [
        ['Server requirements','Check PHP and source readiness before any database change.'],
        ['Database','Connect Nexora to any supported database driver.'],
        ['Application & Super Admin','Set the application identity and create the first administrator.'],
        ['Review & install','Review the settings, then install Nexora.'],
    ];

    function showStep(nextIndex) {
        index = Math.max(0, Math.min(3, nextIndex));
        steps.forEach((step, i) => step.classList.toggle('active', i === index));
        nav.forEach((button, i) => { button.classList.toggle('active', i === index); button.classList.toggle('done', i < index); });
        document.getElementById('step-title').textContent = titles[index][0];
        document.getElementById('step-copy').textContent = titles[index][1];
        back.disabled = index === 0;
        install.hidden = index !== 3;
        next.hidden = index === 3;
        if (index === 3) refreshSummary();
    }

    function pageValid(stepIndex) {
        const fields = [...steps[stepIndex].querySelectorAll('input[required],select[required]')];
        for (const field of fields) if (!field.reportValidity()) return false;
        if (stepIndex === 0 && @json(!$requirementsReady)) { alert('Resolve the blocking server requirements before continuing.'); return false; }
        if (stepIndex === 0 && @json(!$sourceOk)) { alert('Source activation mismatch must be resolved before installation.'); return false; }
        if (stepIndex === 1 && !dbVerified) { alert('Test the database connection before continuing.'); return false; }
        if (stepIndex === 1 && dbTableCount > 0 && !databaseSafetyReady()) { alert('Choose Resume or complete the existing database safety confirmation.'); return false; }
        if (stepIndex === 2 && !passwordReady()) { alert('Complete the password requirements and confirmation.'); return false; }
        return true;
    }

    next.addEventListener('click', () => { if (pageValid(index)) showStep(index + 1); });
    back.addEventListener('click', () => showStep(index - 1));
    nav.forEach((button, target) => button.addEventListener('click', () => { if (target <= index || pageValid(index)) showStep(target); }));

    document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        input.type = input.type === 'password' ? 'text' : 'password';
        button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
    }));

    document.getElementById('recheck-source').addEventListener('click', async () => {
        const box = document.getElementById('source-status-box');
        try {
            const response = await fetch(@json(route('install.source.status')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
            const source = await response.json();
            const ok = source.status === 'pass' && source.current === true;
            box.className = 'callout ' + (ok ? 'good' : 'bad');
            box.querySelector('strong').textContent = ok ? 'Executing source verified' : 'Source activation mismatch — installation is blocked before database mutation';
            const handshake=source.activation_handshake ?? {status:'pending'}; document.getElementById('source-status-copy').textContent = `source set ${source.source_set_status ?? 'unknown'} · critical source ${source.critical_source_files_matched ?? 0}/${source.critical_source_files ?? 0} · runtime ${source.runtime_classes_matched ?? 0}/${source.runtime_classes_total ?? 0} · source_generation ${source.running_generation ?? 'unknown'} · activation ${handshake.status ?? 'pending'}`;
        } catch (error) { box.className='callout bad'; box.querySelector('strong').textContent='Source status could not be checked.'; }
    });

    const dbResult = document.getElementById('db-result');
    const dbDriverHealth = document.getElementById('db-driver-health');
    const existingDb = document.getElementById('existing-db');
    const protectionChoice = document.getElementById('db-protection-choice');
    const recoveryActions = document.getElementById('recovery-actions');
    const recoveryResume = document.getElementById('recovery-resume');
    const recoveryReset = document.getElementById('recovery-reset');
    const backupButton = document.getElementById('backup-database');
    const backupDownload = document.getElementById('backup-download');
    const backupToken = document.getElementById('db-backup-token');
    const backupConfirmed = document.getElementById('db-backup-confirmed');
    const backupConfirmCheck = document.getElementById('backup-confirm-check');
    const skipBackup = document.getElementById('skip-backup');
    const skipBackupDatabase = document.getElementById('db-skip-backup-database');

    function databasePayload() {
        const data = new FormData();
        ['db_driver','db_host','db_port','db_database','db_username','db_password'].forEach((key) => data.append(key, form.elements[key]?.value ?? ''));
        if (form.elements.db_create?.checked) data.append('db_create','1');
        data.append('_token', csrf);
        return data;
    }

    document.getElementById('test-database').addEventListener('click', async () => {
        dbVerified=false; dbResult.className='callout db-result visible'; dbResult.textContent='Testing database...'; dbDriverHealth.classList.remove('hidden'); dbDriverHealth.classList.remove('good','bad');
        try {
            const response = await fetch(@json(route('install.database.test')), {method:'POST',body:databasePayload(),headers:{Accept:'application/json'}});
            const body = await response.json();
            if (!response.ok || !body.ok) throw new Error(body.message || 'Database connection failed.');
            dbVerified=true; dbTableCount=Number(body.object_count ?? body.table_count ?? 0); dbInterrupted=!!body.interrupted_installation; dbRecoverable=!!body.recoverable_installation;
            dbResult.className='callout db-result visible good'; dbResult.textContent=`Database connection ready. ${dbTableCount} existing object(s) detected.`; dbDriverHealth.classList.add('good'); dbDriverHealth.classList.remove('bad'); dbDriverHealth.querySelector('.driver-health-copy').textContent=`Driver ${body.driver ?? form.elements.db_driver.value} is available and the connection succeeded.`;
            existingDb.classList.toggle('hidden', dbTableCount < 1);
            if (dbTableCount > 0) {
                recoveryActions.classList.toggle('hidden', !dbInterrupted);
                if (dbInterrupted && dbRecoverable) {
                    recoveryResume.checked=true; protectionChoice.classList.add('hidden');
                    document.getElementById('existing-db-copy').textContent=`Nexora found an interrupted installation that matches this exact source. Resume is available.`;
                    document.getElementById('recovery-copy').textContent=`Previous run: ${body.recoverable_platform_version ?? 'unknown'} / ${body.recoverable_installer_protocol ?? 'unknown'}.`;
                } else if (dbInterrupted) {
                    recoveryReset.checked=true; protectionChoice.classList.remove('hidden');
                    document.getElementById('existing-db-copy').textContent='Nexora found an interrupted installation, but exact-source Resume is disabled. Start clean using backup or explicit overwrite consent.';
                    document.getElementById('recovery-copy').textContent=`${body.recovery_reason ?? ''} Previous run: ${body.recoverable_platform_version ?? 'unknown'} / ${body.recoverable_installer_protocol ?? 'unknown'}.`;
                } else {
                    protectionChoice.classList.remove('hidden');
                    document.getElementById('existing-db-copy').textContent='This database already contains data. Create a protected backup or explicitly confirm a no-backup reset.';
                }
                backupButton.disabled = body.backup_available === false;
                if (body.backup_message) document.getElementById('backup-progress').textContent=body.backup_message;
            }
        } catch(error) { dbResult.className='callout db-result visible bad'; dbResult.textContent=error.message || String(error); dbDriverHealth.classList.add('bad'); dbDriverHealth.classList.remove('good'); dbDriverHealth.querySelector('.driver-health-copy').textContent=error.message || String(error); }
    });

    [recoveryResume,recoveryReset].forEach((input) => input.addEventListener('change', () => {
        if (!dbInterrupted) return;
        protectionChoice.classList.toggle('hidden', recoveryResume.checked && dbRecoverable);
    }));

    async function readNdjson(response, onEvent) {
        if (!response.ok || !response.body) {
            const retryAfter = Number(response.headers.get('Retry-After') || 0);
            let detail = '';
            try { detail = (await response.clone().text()).trim(); } catch (_) {}
            if (response.status === 429) {
                const wait = retryAfter > 0 ? ` Try again in ${retryAfter} second(s).` : ' Retry after a short cooldown.';
                throw new Error(`Too many installer requests (HTTP 429).${wait}`);
            }
            throw new Error(detail || `Request failed (HTTP ${response.status}).`);
        }
        const reader=response.body.getReader(); const decoder=new TextDecoder(); let buffer='';
        while(true){const {value,done}=await reader.read();buffer+=decoder.decode(value||new Uint8Array(),{stream:!done});let pos;while((pos=buffer.indexOf('\n'))>=0){const line=buffer.slice(0,pos).trim();buffer=buffer.slice(pos+1);if(line)onEvent(JSON.parse(line));}if(done)break;}
        if(buffer.trim())onEvent(JSON.parse(buffer.trim()));
    }

    backupButton.addEventListener('click', async () => {
        backupButton.disabled=true; document.getElementById('backup-progress').textContent='Creating backup…';
        try {
            await readNdjson(await fetch(@json(route('install.database.backup.stream')), {method:'POST',body:databasePayload(),headers:{Accept:'application/x-ndjson'}}), (event) => {
                if(event.message)document.getElementById('backup-progress').textContent=event.message;
                if(event.type==='complete' && event.ok){backupToken.value=event.token || '';backupDownload.href=event.download_url || '#';backupDownload.classList.remove('hidden');backupDownload.setAttribute('download',event.file_name || 'nexora-database-backup');}
                if(event.type==='complete' && !event.ok)throw new Error(event.message || 'Backup failed.');
            });
        } catch(error) { document.getElementById('backup-progress').textContent=error.message || String(error); }
        finally { backupButton.disabled=false; }
    });
    backupDownload.addEventListener('click', () => { if(backupToken.value) backupConfirmCheck.disabled=false; });
    backupConfirmCheck.addEventListener('change', () => { backupConfirmed.value=backupConfirmCheck.checked?'1':'0'; if(backupConfirmCheck.checked)skipBackup.checked=false; });
    skipBackup.addEventListener('change', () => { if(skipBackup.checked){backupConfirmCheck.checked=false;backupConfirmed.value='0';} });

    function databaseSafetyReady() {
        if (dbTableCount < 1) return true;
        if (dbInterrupted && dbRecoverable && recoveryResume.checked) { document.getElementById('db-reset-existing').value='0'; return true; }
        if (dbInterrupted && !recoveryReset.checked) return false;
        const backupReady = backupToken.value && backupConfirmed.value === '1';
        const noBackupReady = skipBackup.checked && skipBackupDatabase.value === form.elements.db_database.value;
        const ready = !!backupReady || !!noBackupReady;
        document.getElementById('db-reset-existing').value = ready ? '1' : '0';
        return ready;
    }

    const serviceTests = new Map();
    document.querySelectorAll('[data-service-toggle]').forEach((toggle) => {
        const key = toggle.dataset.serviceToggle;
        const panel = document.querySelector(`[data-service-config="${key}"]`);
        const sync = () => panel?.classList.toggle('hidden', !toggle.checked);
        toggle.addEventListener('change', sync);
        sync();
    });
    const servicePayload = (key) => {
        const data = new FormData();
        data.append('_token', csrf);
        data.append('driver', key);
        const names = ['endpoint','database','username','password','region','access_key','secret_key'];
        names.forEach((name) => {
            const field = form.elements[`data_services[${key}][${name}]`];
            if (field) data.append(name, field.value || '');
        });
        return data;
    };
    document.querySelectorAll('[data-service-test]').forEach((button) => button.addEventListener('click', async () => {
        const key = button.dataset.serviceTest;
        const status = document.querySelector(`[data-service-status="${key}"]`);
        button.disabled = true; status.className='service-test-status'; status.textContent='Testing…';
        try {
            const response = await fetch(@json(route('install.data-service.test')), {method:'POST',body:servicePayload(key),headers:{Accept:'application/json'}});
            const body = await response.json().catch(() => ({}));
            if (!response.ok || !body.ok) throw new Error(body.message || `Connection test failed (HTTP ${response.status}).`);
            serviceTests.set(key, true); status.className='service-test-status good'; status.textContent=body.message || 'Connection ready.';
        } catch (error) {
            serviceTests.set(key, false); status.className='service-test-status bad'; status.textContent=error.message || String(error);
        } finally { button.disabled=false; }
    }));

    const password = document.getElementById('admin_password');
    const confirmation = document.getElementById('admin_password_confirmation');
    const consentWrap = document.getElementById('password-consent');
    const consent = document.getElementById('password-consent-checkbox');
    let passwordState={level:'blocked',minimumAccepted:false,consentRequired:false};
    function evaluatePassword(value){
        const classes=[/[a-z]/.test(value),/[A-Z]/.test(value),/\d/.test(value),/[^A-Za-z0-9]/.test(value)].filter(Boolean).length;
        const minimumAccepted=value.length>=10&&classes>=3; let score=0;
        if(value.length>=12)score++; if(value.length>=16)score++; if(value.length>=20)score++; if(new Set(value).size>=10)score++;
        const predictable=/(password|admin|nexora|qwerty|123456|letmein|welcome|(.)\1{3,}|0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef)/i.test(value); if(!predictable)score++;
        const recommended=value.length>=12&&classes===4;
        let level='blocked'; if(minimumAccepted&&!recommended)level='weak'; else if(minimumAccepted&&recommended&&!predictable&&score>=4)level='strong'; else if(minimumAccepted&&recommended&&score>=2)level='medium'; else if(minimumAccepted)level='low';
        return {level,minimumAccepted,consentRequired:minimumAccepted&&level!=='strong'};
    }
    function syncPassword(){
        passwordState=evaluatePassword(password.value); const width={blocked:0,weak:25,low:40,medium:65,strong:100}[passwordState.level]||0; const color={blocked:'#d92d20',weak:'#d92d20',low:'#f79009',medium:'#fdb022',strong:'#12b76a'}[passwordState.level];
        document.getElementById('password-strength-bar').style.width=width+'%'; document.getElementById('password-strength-bar').style.background=color; document.getElementById('password-level').textContent=passwordState.level[0].toUpperCase()+passwordState.level.slice(1);
        consentWrap.classList.toggle('hidden',!passwordState.consentRequired); if(!passwordState.consentRequired)consent.checked=false;
        document.getElementById('password-match').textContent=confirmation.value ? (confirmation.value===password.value?'Passwords match.':'Passwords do not match.') : '';
    }
    function passwordReady(){return passwordState.minimumAccepted && confirmation.value===password.value && (!passwordState.consentRequired || consent.checked);}
    password.addEventListener('input',syncPassword); confirmation.addEventListener('input',syncPassword); consent.addEventListener('change',syncPassword); syncPassword();

    function refreshSummary(){
        const requested=[...form.querySelectorAll('[data-service-toggle]:checked')]; const configured=requested.filter((item)=>serviceTests.get(item.dataset.serviceToggle)===true).length; const values=[['Application',form.elements.app_name.value],['URL',form.elements.app_url.value],['Database',`${form.elements.db_driver.value}: ${form.elements.db_database.value}`],['Super Admin',form.elements.admin_email.value],['Language',form.elements.language.value],['Password',passwordState.level],['Additional services',requested.length?`${requested.length} selected · ${configured} tested successfully`:'None']];
        document.getElementById('summary').innerHTML=values.map(([label,value])=>`<div><span>${label}</span><strong>${String(value).replace(/[<>&]/g,'')}</strong></div>`).join('');
    }

    const stageList=document.getElementById('stage-list'); const stageMap=new Map(); const installLog=document.getElementById('install-log');
    function renderStages(items){stageList.innerHTML='';items.forEach(item=>{const row=document.createElement('div');row.className='install-stage';row.dataset.stage=item.id;row.innerHTML=`<div class="install-stage-icon"><x-lucide name="loader" size="16" /></div><div>${item.label}</div><div class="install-stage-state">Waiting</div>`;stageList.appendChild(row);stageMap.set(item.id,row);});}
    // Stream schema: protocol ${e.installer_protocol}; runtime ${e.runtime_classes_matched}/${e.runtime_classes_total}.
    function updateProgress(event){
        const pct=Math.max(0,Math.min(100,Number(event.progress??0)));document.getElementById('progress-percent').textContent=pct+'%';document.getElementById('progress-bar').style.width=pct+'%';
        if(event.platform_version)document.getElementById('progress-meta').textContent=`${event.platform_version} · protocol ${event.installer_protocol ?? ''} · ${event.source_generation ?? ''} · critical source ${event.critical_source_files_matched ?? 0}/${event.critical_source_files ?? 0} · runtime ${event.runtime_classes_matched ?? 0}/${event.runtime_classes_total ?? 0}`;
        if(event.steps)renderStages(event.steps);
        if(event.stage&&stageMap.has(event.stage)){const row=stageMap.get(event.stage);row.querySelector('.install-stage-state').textContent=event.status||'Running';}
        if(event.message)installLog.textContent+=event.message+'\n'; if(event.type==='log'&&event.message)installLog.textContent+=event.message+'\n'; installLog.scrollTop=installLog.scrollHeight;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault(); if(index!==3||!pageValid(2)||!databaseSafetyReady()||!document.getElementById('terms').checked)return;
        install.disabled=true; next.disabled=true; back.disabled=true; document.getElementById('install-error').classList.add('hidden');document.getElementById('install-progress').classList.remove('hidden');installLog.textContent='Opening secure installation stream…\n';
        const data=new FormData(form);
        if(dbInterrupted)data.set('db_existing_action',recoveryResume.checked?'resume':'reset');
        try{
            let complete=null;
            await readNdjson(await fetch(@json(route('install.stream')),{method:'POST',body:data,headers:{Accept:'application/x-ndjson'},cache:'no-store'}),(e)=>{updateProgress(e);if(e.run_id){runId=e.run_id;cancelInstall.hidden=e.cancellable===false;}if(e.cancellable===false)cancelInstall.hidden=true;if(e.type==='complete'){complete=e;cancelInstall.hidden=true;}});
            if(!complete)throw new Error('Installation stream ended before completion.');
            if(complete.ok&&complete.redirect){window.location.href=complete.redirect;return;}
            if(complete.committed&&!complete.runtime_handoff_ready){document.getElementById('install-error').classList.remove('hidden');document.getElementById('install-error').textContent='Installation committed · runtime handoff pending. Do not retry installation. '+(complete.message||''); if(complete.recovery_url){setTimeout(()=>window.location.href=complete.recovery_url,1200);}return;}
            throw new Error(complete.message||'Installation failed.');
        }catch(error){document.getElementById('install-error').classList.remove('hidden');document.getElementById('install-error').textContent=error.message||String(error);}
        finally{install.disabled=false;next.disabled=false;back.disabled=false;if(!runId)cancelInstall.hidden=true;}
    });

    cancelInstall.addEventListener('click', async () => {
        if(!runId)return; cancelInstall.disabled=true; const data=new FormData();data.append('_token',csrf);data.append('run_id',runId);
        try{
            const response=await fetch(@json(route('install.cancel')),{method:'POST',body:data,headers:{Accept:'application/json'}});
            const body=await response.json().catch(()=>({}));
            if(!response.ok||!body.ok)throw new Error(body.message||`Cancellation failed (HTTP ${response.status}).`);
            cancelInstall.textContent='Cancellation requested';
            installLog.textContent+=(body.message||'Cancellation requested. Nexora will stop at the next safe checkpoint.')+'\n';
        }catch(error){
            document.getElementById('install-error').classList.remove('hidden');
            document.getElementById('install-error').textContent=error.message||String(error);
        }finally{cancelInstall.disabled=false;}
    });

    showStep(0);
})();
</script>
</body>
</html>
