@props(['icon' => 'bi-inbox', 'title' => 'Tidak ada data', 'description' => null, 'action' => null, 'actionUrl' => null])

<div {{ $attributes->merge(['class' => 'text-center py-5']) }}>
    <i class="bi {{ $icon }} display-1 text-muted"></i>
    <h5 class="mt-3 text-muted">{{ $title }}</h5>
    @if($description)
        <p class="text-muted">{{ $description }}</p>
    @endif
    @if($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-2">
            {{ $action }}
        </a>
    @endif
</div>
