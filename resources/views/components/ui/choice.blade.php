@props(['name','value','label','description'=>null,'meta'=>null,'icon'=>'database','checked'=>false,'disabled'=>false])
<label class="data-service-card nx-ui-choice">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked) @disabled($disabled)>
    <span class="data-service-icon"><x-lucide :name="$icon"/></span>
    <span class="data-service-copy"><strong>{{ $label }}</strong>@if($description)<small>{{ $description }}</small>@endif @if($meta)<em>{{ $meta }}</em>@endif</span>
    <span class="data-service-check"><x-lucide name="check-circle"/></span>
</label>
