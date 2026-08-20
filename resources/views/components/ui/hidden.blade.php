@props(['name','id'=>null,'value'=>''])
<input type="hidden" name="{{ $name }}" @if($id)id="{{ $id }}"@endif value="{{ $value }}" {{ $attributes }}>
