@props(['name', 'size' => 18, 'strokeWidth' => 1.8])
@php
$paths = [
    'check-circle' => ['<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>','<path d="m9 11 3 3L22 4"/>'],
    'x-circle' => ['<circle cx="12" cy="12" r="10"/>','<path d="m15 9-6 6"/>','<path d="m9 9 6 6"/>'],
    'alert-triangle' => ['<path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>','<path d="M12 9v4"/>','<path d="M12 17h.01"/>'],
    'server' => ['<rect width="20" height="8" x="2" y="2" rx="2" ry="2"/>','<rect width="20" height="8" x="2" y="14" rx="2" ry="2"/>','<path d="M6 6h.01"/>','<path d="M6 18h.01"/>'],
    'database' => ['<ellipse cx="12" cy="5" rx="9" ry="3"/>','<path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>','<path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>'],
    'user-cog' => ['<circle cx="9" cy="7" r="4"/>','<path d="M3 21v-2a6 6 0 0 1 6-6h2"/>','<path d="M16 19h6"/>','<path d="M19 16v6"/>'],
    'clipboard-check' => ['<rect width="16" height="18" x="4" y="3" rx="2"/>','<path d="M9 3V1h6v2"/>','<path d="m9 13 2 2 4-4"/>'],
    'arrow-left' => ['<path d="m12 19-7-7 7-7"/>','<path d="M19 12H5"/>'],
    'arrow-right' => ['<path d="m12 5 7 7-7 7"/>','<path d="M5 12h14"/>'],
    'play' => ['<polygon points="6 3 20 12 6 21 6 3"/>'],
    'loader' => ['<path d="M21 12a9 9 0 1 1-6.22-8.56"/>'],
    'shield-check' => ['<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/>','<path d="m9 12 2 2 4-4"/>'],
    'terminal' => ['<path d="m4 17 6-6-6-6"/>','<path d="M12 19h8"/>'],
    'package' => ['<path d="m7.5 4.27 9 5.15"/>','<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>','<path d="m3.3 7 8.7 5 8.7-5"/>','<path d="M12 22V12"/>'],
    'download' => ['<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>','<path d="m7 10 5 5 5-5"/>','<path d="M12 15V3"/>'],
    'refresh' => ['<path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5"/>','<path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/>'],
    'info' => ['<circle cx="12" cy="12" r="10"/>','<path d="M12 16v-4"/>','<path d="M12 8h.01"/>'],
    'cloud' => ['<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>'],
    'zap' => ['<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/>'],
    'globe' => ['<circle cx="12" cy="12" r="10"/>','<path d="M2 12h20"/>','<path d="M12 2a15.3 15.3 0 0 1 0 20"/>','<path d="M12 2a15.3 15.3 0 0 0 0 20"/>'],
    'eye' => ['<path d="M2.06 12.35a1 1 0 0 1 0-.7C3.73 7.6 7.6 5 12 5c4.4 0 8.27 2.6 9.94 6.65a1 1 0 0 1 0 .7C20.27 16.4 16.4 19 12 19c-4.4 0-8.27-2.6-9.94-6.65Z"/>','<circle cx="12" cy="12" r="3"/>'],
    'eye-off' => ['<path d="m2 2 20 20"/>','<path d="M6.71 6.71C4.8 7.9 3.33 9.65 2.06 11.65a1 1 0 0 0 0 .7C3.73 16.4 7.6 19 12 19c1.48 0 2.89-.3 4.16-.84"/>','<path d="M10.73 5.08C11.14 5.03 11.57 5 12 5c4.4 0 8.27 2.6 9.94 6.65a1 1 0 0 1 0 .7 16.8 16.8 0 0 1-2.2 3.47"/>','<path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"/>'],
    'lock' => ['<rect width="18" height="11" x="3" y="11" rx="2"/>','<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
    'backup' => ['<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>','<path d="M3 3v5h5"/>','<path d="M12 7v5l3 2"/>'],
    'sun' => ['<circle cx="12" cy="12" r="4"/>','<path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/>'],
    'moon' => ['<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>'],
    'monitor' => ['<rect x="2" y="3" width="20" height="14" rx="2"/>','<path d="M8 21h8M12 17v4"/>'],
    'trash' => ['<path d="M3 6h18"/>','<path d="M8 6V4h8v2"/>','<path d="M19 6l-1 14H6L5 6"/>','<path d="M10 11v5M14 11v5"/>'],
];
$svg = $paths[$name] ?? $paths['info'];
@endphp
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => $strokeWidth, 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>{!! implode('', $svg) !!}</svg>
