@props(['type' => 'secondary'])

@php
    $classes = [
        'success' => 'bg-success',
        'error' => 'bg-danger',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info',
        'primary' => 'bg-primary',
        'secondary' => 'bg-secondary',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . ($classes[$type] ?? 'bg-secondary')]) }}>
    {{ $slot }}
</span>
