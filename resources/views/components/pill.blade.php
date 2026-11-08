@props([
    'variant' => 'default',
    'class' => '',
])

@php
    $variants = [
        'submitted' => 'bg-navy-100 text-navy-700 border border-navy-200',
        'verified' => 'bg-green-100 text-green-700 border border-green-200',
        'assigned' => 'bg-navy-50 text-navy-800 border border-navy-200',
        'in_progress' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'resolved' => 'bg-green-100 text-green-700 border border-green-200',
        'rejected' => 'bg-red-100 text-red-700 border border-red-200',
        'closed' => 'bg-slate-100 text-slate-600 border border-slate-200',
        'default' => 'bg-navy-50 text-navy-600 border border-navy-200',
    ];
    $classes = 'inline-flex items-center gap-1.5 rounded-full border text-[10px] font-medium leading-none px-2.5 py-0.5 whitespace-nowrap';
    $variantClass = $variants[$variant] ?? $variants['default'];
@endphp

<span {{ $attributes->merge(['class' => $classes . ' ' . $variantClass . ' ' . $class]) }}>
    {{ $slot }}
</span>
