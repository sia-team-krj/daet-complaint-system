@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'class' => '',
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-12 border border-transparent font-sans text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-navy-900 text-white hover:bg-navy-800 focus-visible:ring-navy-900/30',
        'secondary' => 'bg-white text-navy-900 border border-navy-200 hover:bg-navy-50 focus-visible:ring-navy-900/10',
        'ghost' => 'text-navy-600 hover:bg-navy-50 focus-visible:ring-navy-900/10',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-600/30',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
    $variantClasses = $variants[$variant] ?? $variants['primary'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

<button {{ $attributes->merge(['type' => $type, 'disabled' => $disabled ? true : false, 'class' => $base . ' ' . $variantClasses . ' ' . $sizeClasses . ' ' . $class]) }}>
    {{ $slot }}
</button>
