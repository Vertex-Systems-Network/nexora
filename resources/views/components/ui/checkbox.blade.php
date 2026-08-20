@props(['name'=>null,'id'=>null,'value'=>'1','label'=>null,'labelId'=>null,'description'=>null,'checked'=>false,'disabled'=>false])
@php($controlId = $id ?: ($name ? $name.'-'.substr(md5((string)$value),0,6) : 'nx-check-'.uniqid()))
<label class="toggle nx-ui-checkbox" for="{{ $controlId }}">
    <input id="{{ $controlId }}" @if($name) name="{{ $name }}" @endif type="checkbox" value="{{ $value }}" @checked($checked) @disabled($disabled) {{ $attributes->except(['class']) }}>
    <span>@if($label)<strong @if($labelId)id="{{ $labelId }}"@endif>{{ $label }}</strong>@endif @if($description)<span class="hint">{{ $description }}</span>@endif {{ $slot }}</span>
</label>
