@props(['title', 'value', 'icon' => 'bi-graph-up', 'color' => 'primary', 'subtitle' => null])

<div {{ $attributes->merge(['class' => "card bg-{$color} text-white"]) }}>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="card-title opacity-75">{{ $title }}</h6>
                <h2 class="mb-0">{{ $value }}</h2>
                @if($subtitle)
                    <small class="opacity-75">{{ $subtitle }}</small>
                @endif
            </div>
            <i class="bi {{ $icon }} display-4 opacity-50"></i>
        </div>
    </div>
</div>
