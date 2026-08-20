@props([
    'name',
    'value',
    'id' => null,
    'label',
    'description' => null,
    'checked' => false,
    'disabled' => false,
])
@php($controlId = $id ?: $name.'-'.substr(md5((string) $value), 0, 8))
<label class="recovery-choice nx-ui-radio" for="{{ $controlId }}">
    <input
        id="{{ $controlId }}"
        name="{{ $name }}"
        type="radio"
        value="{{ $value }}"
        @checked($checked)
        @disabled($disabled)
        {{ $attributes->except(['class']) }}
    >
    <span>
        <strong>{{ $label }}</strong>
        @if($description)<span class="hint">{{ $description }}</span>@endif
        {{ $slot }}
    </span>
</label>
