@props(['tone'=>'neutral','icon'=>'info','title'=>null,'description'=>null,'id'=>null])
<div @if($id)id="{{ $id }}"@endif {{ $attributes->class(['nx-ui-status','driver-health',$tone]) }}>
    <span class="driver-health-icon"><x-lucide :name="$icon"/></span>
    <span class="driver-health-copy">@if($title)<strong>{{ $title }}</strong>@endif @if($description)<span>{{ $description }}</span>@endif {{ $slot }}</span>
</div>
