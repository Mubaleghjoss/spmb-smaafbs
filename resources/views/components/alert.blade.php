@props(['type' => 'info', 'dismissible' => true])

@php
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];
    $icons = [
        'success' => 'bi-check-circle-fill',
        'error' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'alert ' . ($classes[$type] ?? 'alert-info') . ($dismissible ? ' alert-dismissible fade show' : '')]) }} role="alert">
    <i class="bi {{ $icons[$type] ?? 'bi-info-circle-fill' }} me-2"></i>
    {{ $slot }}
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    @endif
</div>
