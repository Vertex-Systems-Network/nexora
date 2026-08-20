@props([
    'name',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'kind' => 'standard',
    'compact' => false,
])
@php
$controlId = $id ?: $name;
$grouped = collect($options)->groupBy(fn($option) => $option['group'] ?? '', preserveKeys: true);
@endphp
@if($label)<label class="nx-ui-field-label" for="{{ $controlId }}">{{ $label }}</label>@endif
<select id="{{ $controlId }}" name="{{ $name }}" data-nx-select="{{ $kind }}" @if($compact)data-nx-compact="1"@endif @required($required) {{ $attributes }}>
    @foreach($grouped as $group => $groupOptions)
        @if($group !== '')<optgroup label="{{ $group }}">@endif
        @foreach($groupOptions as $option)
            <option value="{{ $option['value'] }}"
                data-description="{{ $option['description'] ?? '' }}"
                data-provider="{{ $option['provider'] ?? '' }}"
                data-flag="{{ $option['flag'] ?? '' }}"
                data-flag-url="{{ $option['flag_url'] ?? '' }}"
                @selected((string)$selected === (string)$option['value'])
                @disabled($option['disabled'] ?? false)>{{ $option['label'] }}</option>
        @endforeach
        @if($group !== '')</optgroup>@endif
    @endforeach
</select>
