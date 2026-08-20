@props([
    'name', 'id' => null, 'label' => null, 'labelId' => null, 'type' => 'text', 'value' => null,
    'placeholder' => null, 'hint' => null, 'required' => false, 'disabled' => false,
    'wrapperClass' => '', 'min' => null, 'max' => null, 'autocomplete' => null,
])
@php($controlId = $id ?: $name)
<label class="field nx-ui-field {{ $wrapperClass }}" for="{{ $controlId }}">
    @if($label)<span class="nx-ui-field-label" @if($labelId)id="{{ $labelId }}"@endif>{{ $label }}</span>@endif
    <input id="{{ $controlId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" placeholder="{{ $placeholder }}" @if($min !== null)min="{{ $min }}"@endif @if($max !== null)max="{{ $max }}"@endif @if($autocomplete)autocomplete="{{ $autocomplete }}"@endif @required($required) @disabled($disabled) {{ $attributes->class(['nx-ui-input']) }}>
    @if($hint)<span class="hint">{{ $hint }}</span>@endif
</label>
