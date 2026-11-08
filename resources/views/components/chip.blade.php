@props([
    'value' => '',
    'label' => '',
    'selected' => false,
    'class' => '',
])

@php
    $base = 'rounded-full border text-[11px] font-medium whitespace-nowrap px-3 py-1.5 transition-all focus-visible:outline-none focus-visible:ring-2';
    $selectedClass = $selected
        ? 'bg-navy-900 text-white border-navy-900 focus-visible:ring-navy-900/30'
        : 'bg-white text-navy-600 border-navy-200 hover:bg-navy-50 focus-visible:ring-navy-900/10';
@endphp

<button {{ $attributes->merge(['type' => 'button', 'value' => $value, 'class' => $base . ' ' . $selectedClass . ' ' . $class]) }}>
    {{ $label }}
</button>
