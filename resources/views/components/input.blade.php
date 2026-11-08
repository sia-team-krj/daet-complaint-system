@props([
    'type' => 'text',
    'name' => '',
    'id' => '',
    'label' => '',
    'placeholder' => '',
    'value' => '',
    'required' => false,
    'error' => '',
    'helper' => '',
])

@php
    $requiredMark = $required ? '<span class="text-red-500 ml-1" aria-hidden="true">*</span>' : '';
    $errorClass = $error ? 'border-red-500 focus-visible:ring-red-500/20' : 'border-navy-200 focus-visible:ring-navy-900/10';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <label for="{{ $id ?? $name }}" class="block text-[10px] font-semibold text-navy-700 uppercase tracking-wider leading-none">
            {{ $label }} {{ $requiredMark }}
        </label>
    @endif

    <input
        {{ $attributes->merge([
            'type' => $type,
            'name' => $name,
            'id' => $id ?? $name,
            'placeholder' => $placeholder,
            'value' => $value,
            'required' => $required ? true : false,
            'class' => 'block w-full rounded-10 border px-3 py-2 text-sm text-navy-900 placeholder-navy-400 ' . $errorClass . ' focus-visible:outline-none disabled:opacity-50 disabled:pointer-events-none',
        ]) }}
    >

    @if($error)
        <p class="text-[10px] text-red-500">{{ $error }}</p>
    @elseif($helper)
        <p class="text-[10px] text-navy-400">{{ $helper }}</p>
    @endif
</div>
