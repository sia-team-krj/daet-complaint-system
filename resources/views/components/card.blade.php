@props([
    'class' => '',
    'padding' => 'md',
])

@php
    $paddings = [
        'none' => 'p-0',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];
    $pad = $paddings[$padding] ?? $paddings['md'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-navy-200 rounded-12 shadow-sm ' . $pad . ' ' . $class]) }}>
    {{ $slot }}
</div>
