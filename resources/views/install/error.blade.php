<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Installer error · Nexora</title>
    <style>
        :root{color-scheme:light dark;font-family:"Plus Jakarta Sans Variable","Plus Jakarta Sans",Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}body{margin:0;background:#000000;color:#f5f7fa;min-height:100vh;display:grid;place-items:center;padding:24px}.card{width:min(720px,100%);background:#0f1118;border:1px solid #29303d;border-radius:18px;padding:28px;box-shadow:0 18px 55px rgba(0,0,0,.32)}.eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#a9a4e0;font-weight:800}.code{display:inline-flex;margin:14px 0 4px;padding:5px 9px;border-radius:999px;background:#1d1b33;color:#c9c6ef;font-weight:800;font-size:12px}h1{font-size:28px;letter-spacing:-.035em;margin:12px 0 8px}p{color:#c4c8d0;line-height:1.6}.detail{margin-top:16px;padding:14px;border-radius:12px;background:#181c25;border:1px solid #29303d;font:12px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace;overflow-wrap:anywhere}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}a{display:inline-flex;text-decoration:none;padding:10px 16px;border-radius:999px;background:#625ba8;background-image:linear-gradient(90deg,#625ba8 0%,#6188c6 55%,#5ac8d6 100%);color:white;font-weight:750}.secondary{background:#131620;background-image:none;border:1px solid #394150}.request{margin-top:18px;font-size:12px;color:#98a2b3}
    </style>
</head>
<body>
<div class="card">
    <div class="eyebrow">Nexora installation</div>
    <span class="code">{{ $error['code'] ?? 'HTTP_500' }}</span>
    <h1>{{ $error['title'] ?? 'Installer could not continue' }}</h1>
    <p>{{ $error['message'] ?? 'Nexora could not render the installation wizard.' }}</p>
    @if(!empty($detail))
        <div class="detail">{{ $detail }}</div>
    @endif
    <div class="actions">
        <a href="/install">Retry installer</a>
        <a class="secondary" href="/nexora-bootstrap.php">Deployment bootstrap</a>
    </div>
    <div class="request">Request ID: {{ $error['request_id'] ?? 'unavailable' }} · The same ID is returned in the X-Request-Id response header.</div>
</div>
</body>
</html>
