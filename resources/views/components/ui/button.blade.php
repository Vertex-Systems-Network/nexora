@props([
    'type' => 'button',
    'variant' => 'secondary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'loading' => false,
])
@php
$classes = 'btn nx-ui-button';
if ($variant === 'primary') $classes .= ' primary';
if ($variant === 'danger') $classes .= ' danger';
if ($variant === 'ghost') $classes .= ' ghost';
if ($size === 'sm') $classes .= ' sm';
@endphp
@if($href)
<a href="{{ $href }}" {{ $attributes->class($classes) }}>@if($icon)<x-lucide :name="$icon"/>@endif<span>{{ $slot }}</span></a>
@else
<button type="{{ $type }}" {{ $attributes->class($classes)->merge(['aria-busy' => $loading ? 'true' : null]) }}>@if($icon)<x-lucide :name="$icon"/>@endif<span>{{ $slot }}</span></button>
@endif
