@props([
    'name','id'=>null,'label'=>null,'placeholder'=>null,'required'=>false,'autocomplete'=>'off','hint'=>null,
    'wrapperClass'=>'','meta'=>null,'metaId'=>null,'metaClass'=>'field-meta','minlength'=>null,
])
@php($controlId = $id ?: $name)
<label class="field nx-ui-field {{ $wrapperClass }}" for="{{ $controlId }}">
    @if($label || $meta)<span class="field-label">@if($label)<span class="nx-ui-field-label">{{ $label }}</span>@endif @if($meta)<span @if($metaId)id="{{ $metaId }}"@endif class="{{ $metaClass }}">{{ $meta }}</span>@endif</span>@endif
    <span class="input-shell nx-ui-password">
        <input id="{{ $controlId }}" name="{{ $name }}" type="password" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" @if($minlength)minlength="{{ $minlength }}"@endif @required($required) {{ $attributes->class(['nx-ui-input']) }}>
        <x-ui.button type="button" variant="ghost" class="icon-button" data-password-toggle="{{ $controlId }}" aria-label="Show password"><x-lucide name="eye"/></x-ui.button>
    </span>
    @if($hint)<span class="hint">{{ $hint }}</span>@endif
</label>
