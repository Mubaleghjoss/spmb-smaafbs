@props(['name', 'label', 'checked' => false, 'help' => null])

<div class="mb-3">
    <div class="form-check form-switch">
        <input 
            type="checkbox" 
            name="{{ $name }}" 
            id="{{ $name }}"
            {{ $attributes->merge(['class' => 'form-check-input']) }}
            {{ $checked ? 'checked' : '' }}
        >
        <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
    
    @if($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>
